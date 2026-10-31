<?php

/**
 * PeptideMap Connect (POST /api/vendor-plugin/connect) hardening.
 *
 * Every flag defaults off, which is today's behaviour: plaintext token
 * lookup, no throttle, no expiry, no SSRF check, specific error text,
 * no admin alert. Turn a flag on only after the owner approves it.
 *
 * Booleans accept true/false/1/0/yes/no. An unset variable stays off.
 */

$flag = static function (string $key, bool $default = false): bool {
    $value = env($key);
    if ($value === null || $value === '') {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOLEAN);
};

$ttl = env('VENDOR_PLUGIN_TOKEN_TTL_MINUTES');
$tokenTtlMinutes = (is_numeric($ttl) && (int) $ttl > 0) ? (int) $ttl : null;

return [
    // Rate-limit POST /api/vendor-plugin/connect per IP.
    'throttle' => $flag('VENDOR_PLUGIN_THROTTLE'),
    'throttle_max' => (int) env('VENDOR_PLUGIN_THROTTLE_MAX', 5),
    'throttle_decay_seconds' => (int) env('VENDOR_PLUGIN_THROTTLE_DECAY_SECONDS', 60),

    // Store and compare sha256(token) with hash_equals.
    // Connect then rejects plaintext rows. Run
    // `php artisan vendors:rotate-connection-tokens --all` in the same
    // window and deliver the printed tokens. New brands still receive a
    // plaintext token until that command is run for them.
    'hash_tokens' => $flag('VENDOR_PLUGIN_HASH_TOKENS'),

    // After a successful connect, that token cannot be used again
    // until it is rotated.
    'single_use_tokens' => $flag('VENDOR_PLUGIN_SINGLE_USE_TOKENS'),

    // Reject tokens whose connection_token_issued_at is older than this
    // many minutes. Null (unset or 0) disables expiry. Rows with a null
    // issued_at stay valid until they are rotated, which stamps the time.
    'token_ttl_minutes' => $tokenTtlMinutes,

    // Refuse store_url hosts that are private, loopback, or link-local,
    // including public hostnames that resolve to those addresses.
    // The WooCommerce probe does not follow redirects while this is on.
    'reject_private_store_urls' => $flag('VENDOR_PLUGIN_REJECT_PRIVATE_STORE_URLS'),

    // One status and one message for token, store, and credential
    // failures, so the response does not reveal whether a token or
    // brand exists.
    'generic_errors' => $flag('VENDOR_PLUGIN_GENERIC_ERRORS'),

    // When a reconnect changes ScrapingConfig store_url or products_url,
    // write an alert. The connection token and WooCommerce secrets are
    // not included.
    'alert_on_url_change' => $flag('VENDOR_PLUGIN_ALERT_ON_URL_CHANGE'),
    'alert_log_channel' => env('VENDOR_PLUGIN_ALERT_LOG_CHANNEL', 'stack'),
    'alert_mail' => env('VENDOR_PLUGIN_ALERT_MAIL'),
];
