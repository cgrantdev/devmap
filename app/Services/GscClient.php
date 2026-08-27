<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Google Search Console client. Talks straight to the JSON REST API
 * using a service account JWT — no google/apiclient dependency.
 *
 * Requires two env vars:
 *   GSC_SERVICE_ACCOUNT_JSON_PATH  Path on disk to the service account
 *                                  JSON key file
 *   GSC_SITE_URL                   Full URL of the verified property
 *                                  (e.g. "https://peptidemap.com/")
 *
 * The service account's email must be added as a user (Full or
 * Restricted) on the property in Search Console → Settings → Users.
 */
class GscClient
{
    private const OAUTH_URL   = 'https://oauth2.googleapis.com/token';
    private const SCOPE       = 'https://www.googleapis.com/auth/webmasters.readonly';
    private const API_BASE    = 'https://searchconsole.googleapis.com/webmasters/v3';
    private const TOKEN_CACHE_KEY = 'gsc_access_token_v1';

    public function isConfigured(): bool
    {
        // Two paths: OAuth refresh token stored via /admin/gsc/connect, OR
        // legacy service-account JSON on disk. First wins.
        if (Setting::where('key', 'gsc_oauth_refresh_token')->exists()
            && config('services.gsc.oauth_client_id')
            && config('services.gsc.oauth_client_secret')) {
            return (bool) config('services.gsc.site_url');
        }
        $path = config('services.gsc.service_account_json_path');
        $site = config('services.gsc.site_url');
        return $path && $site && is_file($path);
    }

    public function connectedEmail(): ?string
    {
        return Setting::where('key', 'gsc_oauth_connected_email')->value('value');
    }

    /**
     * Query searchAnalytics — the endpoint that returns per-query, per-page
     * click/impression/CTR/position rows for a date range.
     */
    public function query(array $body): ?array
    {
        if (!$this->isConfigured()) return null;
        $token = $this->accessToken();
        if (!$token) return null;

        $site = rawurlencode(rtrim(config('services.gsc.site_url'), '/') . '/');
        $url = self::API_BASE . "/sites/{$site}/searchAnalytics/query";

        try {
            $resp = Http::withToken($token)
                ->timeout(30)
                ->post($url, $body);
        } catch (\Throwable $e) {
            Log::warning('GSC query failed', ['err' => $e->getMessage()]);
            return null;
        }

        if (!$resp->successful()) {
            Log::warning('GSC HTTP ' . $resp->status(), ['body' => $resp->body(), 'sent' => $body]);
            return null;
        }

        return $resp->json();
    }

    /**
     * Fetch + cache an access token. Prefers the OAuth refresh-token flow
     * (Colin ran /admin/gsc/connect once) and falls back to the legacy
     * service-account JWT flow if only that's configured.
     */
    private function accessToken(): ?string
    {
        return Cache::remember(self::TOKEN_CACHE_KEY, 3000, function () {
            $oauth = $this->accessTokenFromRefresh();
            if ($oauth) return $oauth;
            return $this->accessTokenFromServiceAccount();
        });
    }

    /**
     * Exchange the stored OAuth refresh token for a fresh access token.
     * Returns null when no refresh token is present or the exchange fails.
     */
    private function accessTokenFromRefresh(): ?string
    {
        $refreshEnc = Setting::where('key', 'gsc_oauth_refresh_token')->value('value');
        if (!$refreshEnc) return null;
        $clientId = config('services.gsc.oauth_client_id');
        $clientSecret = config('services.gsc.oauth_client_secret');
        if (!$clientId || !$clientSecret) return null;

        try {
            $refresh = Crypt::decryptString($refreshEnc);
        } catch (\Throwable $e) {
            Log::warning('GSC refresh token decrypt failed', ['err' => $e->getMessage()]);
            return null;
        }

        try {
            $resp = Http::asForm()->timeout(15)->post(self::OAUTH_URL, [
                'refresh_token' => $refresh,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'refresh_token',
            ]);
        } catch (\Throwable $e) {
            Log::warning('GSC OAuth refresh exchange failed', ['err' => $e->getMessage()]);
            return null;
        }

        if (!$resp->successful()) {
            Log::warning('GSC OAuth refresh HTTP ' . $resp->status(), ['body' => $resp->body()]);
            return null;
        }
        return $resp->json('access_token');
    }

    /** Legacy path — service account JWT. Kept for backward compat. */
    private function accessTokenFromServiceAccount(): ?string
    {
        $sa = $this->loadServiceAccount();
        if (!$sa) return null;
        $jwt = $this->buildAndSignJwt($sa);
        if (!$jwt) return null;
        try {
            $resp = Http::asForm()->timeout(15)->post(self::OAUTH_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
        } catch (\Throwable $e) {
            Log::warning('GSC service-account exchange failed', ['err' => $e->getMessage()]);
            return null;
        }
        if (!$resp->successful()) {
            Log::warning('GSC service-account HTTP ' . $resp->status(), ['body' => $resp->body()]);
            return null;
        }
        return $resp->json('access_token');
    }

    private function loadServiceAccount(): ?array
    {
        $path = config('services.gsc.service_account_json_path');
        if (!$path || !is_file($path)) return null;
        try {
            $raw = file_get_contents($path);
            $decoded = json_decode($raw, true);
            if (!is_array($decoded) || empty($decoded['client_email']) || empty($decoded['private_key'])) {
                Log::warning('GSC service account JSON malformed', ['path' => $path]);
                return null;
            }
            return $decoded;
        } catch (\Throwable $e) {
            Log::warning('GSC service account read failed', ['err' => $e->getMessage()]);
            return null;
        }
    }

    private function buildAndSignJwt(array $sa): ?string
    {
        $now = time();
        $header = ['alg' => 'RS256', 'typ' => 'JWT'];
        $claims = [
            'iss'   => $sa['client_email'],
            'scope' => self::SCOPE,
            'aud'   => self::OAUTH_URL,
            'iat'   => $now,
            'exp'   => $now + 3600,
        ];

        $b64 = fn (string $s) => rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
        $signingInput = $b64(json_encode($header)) . '.' . $b64(json_encode($claims));

        $signature = '';
        $key = openssl_pkey_get_private($sa['private_key']);
        if (!$key) {
            Log::warning('GSC private key parse failed');
            return null;
        }
        if (!openssl_sign($signingInput, $signature, $key, 'sha256WithRSAEncryption')) {
            Log::warning('GSC JWT signing failed');
            return null;
        }
        return $signingInput . '.' . $b64($signature);
    }
}
