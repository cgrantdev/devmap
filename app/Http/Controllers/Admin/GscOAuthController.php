<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Wires the CEO admin to Google Search Console via OAuth 2.0 (user
 * consent flow, not service-account). Colin clicks "Connect GSC" on the
 * dashboard, gets bounced to Google, signs in, comes back with a code we
 * exchange for a refresh token. Refresh token is stored encrypted in the
 * settings table and used by GscClient to mint access tokens on demand.
 *
 * Why not service account? Colin's Google org enforces
 * iam.disableServiceAccountKeyCreation which blocks the JSON-key flow.
 * OAuth avoids service accounts entirely.
 */
class GscOAuthController extends Controller
{
    private const AUTHORIZE_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL     = 'https://oauth2.googleapis.com/token';
    private const SCOPES        = 'https://www.googleapis.com/auth/webmasters.readonly';

    /** Redirect Colin to Google's consent screen. */
    public function connect(Request $request): RedirectResponse
    {
        $clientId = config('services.gsc.oauth_client_id');
        if (!$clientId) {
            abort(500, 'GSC_OAUTH_CLIENT_ID env var is missing.');
        }

        $state = Str::random(40);
        session(['gsc_oauth_state' => $state]);

        $params = http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => route('admin.gsc.callback'),
            'response_type' => 'code',
            'scope' => self::SCOPES,
            'access_type' => 'offline',   // request a refresh token
            'prompt' => 'consent',        // force refresh_token even on repeat auth
            'state' => $state,
        ]);
        return redirect(self::AUTHORIZE_URL . '?' . $params);
    }

    /** Google bounces the user back here with ?code=... */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->get('state') !== session('gsc_oauth_state')) {
            return redirect('/admin/ceo')->with('error', 'GSC OAuth state mismatch — try Connect again.');
        }
        session()->forget('gsc_oauth_state');

        $code = $request->get('code');
        if (!$code) {
            $err = $request->get('error') ?: 'no code returned';
            return redirect('/admin/ceo')->with('error', "GSC OAuth: {$err}");
        }

        try {
            $resp = Http::asForm()->timeout(15)->post(self::TOKEN_URL, [
                'code' => $code,
                'client_id' => config('services.gsc.oauth_client_id'),
                'client_secret' => config('services.gsc.oauth_client_secret'),
                'redirect_uri' => route('admin.gsc.callback'),
                'grant_type' => 'authorization_code',
            ]);
        } catch (\Throwable $e) {
            Log::error('GSC OAuth code exchange failed', ['err' => $e->getMessage()]);
            return redirect('/admin/ceo')->with('error', 'GSC OAuth exchange failed — check Laravel log.');
        }

        if (!$resp->successful()) {
            Log::warning('GSC OAuth exchange HTTP ' . $resp->status(), ['body' => $resp->body()]);
            return redirect('/admin/ceo')->with('error', 'GSC OAuth exchange failed (HTTP ' . $resp->status() . ').');
        }

        $refresh = $resp->json('refresh_token');
        if (!$refresh) {
            return redirect('/admin/ceo')->with('error', 'Google did not return a refresh_token — remove the app from your Google account\'s "Third-party apps" and reconnect.');
        }

        // Persist the refresh token, encrypted, plus which Google account did it.
        // GscClient reads these back to mint access tokens on demand.
        Setting::updateOrCreate(['key' => 'gsc_oauth_refresh_token'], [
            'value' => Crypt::encryptString($refresh),
        ]);
        Setting::where('key', 'gsc_oauth_access_token_cache')->delete();

        // Best-effort: capture the connected email so the dashboard can
        // show "Connected as X".
        try {
            $access = $resp->json('access_token');
            $me = Http::withToken($access)->timeout(10)->get('https://openidconnect.googleapis.com/v1/userinfo')->json();
            if (!empty($me['email'])) {
                Setting::updateOrCreate(['key' => 'gsc_oauth_connected_email'], ['value' => $me['email']]);
            }
        } catch (\Throwable) { /* non-fatal */ }

        return redirect('/admin/ceo')->with('success', 'Google Search Console connected.');
    }

    /** Sever the connection — deletes stored tokens. */
    public function disconnect(): RedirectResponse
    {
        Setting::whereIn('key', [
            'gsc_oauth_refresh_token',
            'gsc_oauth_access_token_cache',
            'gsc_oauth_connected_email',
        ])->delete();
        return redirect('/admin/ceo')->with('success', 'GSC disconnected.');
    }
}
