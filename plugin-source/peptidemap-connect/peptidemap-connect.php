<?php
/**
 * Plugin Name: PeptideMap Connect
 * Plugin URI: https://peptidemap.com
 * Description: One-click connect your WooCommerce store to PeptideMap. Auto-generates a read-only REST API key and securely sends it to PeptideMap so your products and pricing stay in sync.
 * Version: 1.0.0
 * Author: PeptideMap
 * Author URI: https://peptidemap.com
 * License: MIT
 * Requires at least: 5.0
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * Text Domain: peptidemap-connect
 */

if (!defined('ABSPATH')) {
    exit;
}

define('PEPTIDEMAP_CONNECT_VERSION', '1.0.0');
define('PEPTIDEMAP_API_BASE', 'https://peptidemap.com');

class PeptideMap_Connect {

    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_post_peptidemap_connect', [$this, 'handle_connect']);
        add_action('admin_notices', [$this, 'show_notices']);
    }

    public function add_admin_menu() {
        add_submenu_page(
            'woocommerce',
            'PeptideMap Connect',
            'PeptideMap',
            'manage_woocommerce',
            'peptidemap-connect',
            [$this, 'render_admin_page']
        );
    }

    public function register_settings() {
        register_setting('peptidemap_connect', 'peptidemap_connection_token');
        register_setting('peptidemap_connect', 'peptidemap_connected_at');
        register_setting('peptidemap_connect', 'peptidemap_brand_name');
    }

    public function show_notices() {
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'peptidemap-connect') === false) return;

        if (isset($_GET['pmap_status'])) {
            $status = sanitize_key($_GET['pmap_status']);
            $message = isset($_GET['pmap_message']) ? sanitize_text_field(urldecode($_GET['pmap_message'])) : '';

            if ($status === 'success') {
                echo '<div class="notice notice-success is-dismissible"><p><strong>Connected to PeptideMap!</strong> ' . esc_html($message) . '</p></div>';
            } elseif ($status === 'error') {
                echo '<div class="notice notice-error is-dismissible"><p><strong>Connection failed.</strong> ' . esc_html($message) . '</p></div>';
            }
        }
    }

    public function render_admin_page() {
        $token = get_option('peptidemap_connection_token', '');
        $connected_at = get_option('peptidemap_connected_at', '');
        $brand_name = get_option('peptidemap_brand_name', '');
        ?>
        <div class="wrap">
            <h1 style="display:flex;align-items:center;gap:12px;">
                <span style="font-size:28px;color:#0F172A;">PeptideMap</span>
                <span style="font-size:13px;background:#0F172A;color:#fff;padding:3px 8px;border-radius:4px;font-weight:500;">Connect</span>
            </h1>

            <p style="font-size:14px;color:#52525B;max-width:680px;margin:12px 0 24px;">
                One-click connection to keep your products and pricing in sync on
                <a href="<?php echo esc_url(PEPTIDEMAP_API_BASE); ?>" target="_blank">PeptideMap.com</a>.
                This plugin auto-generates a <strong>read-only</strong> WooCommerce REST API key and
                securely sends it to PeptideMap. We never modify your store.
            </p>

            <?php if ($connected_at): ?>
                <div style="background:#ECFDF5;border:1px solid #A7F3D0;padding:16px 20px;border-radius:6px;margin-bottom:24px;">
                    <p style="margin:0;font-size:14px;color:#065F46;">
                        <strong>✓ Connected</strong>
                        <?php if ($brand_name): ?> as <strong><?php echo esc_html($brand_name); ?></strong><?php endif; ?>
                        — last synced <?php echo esc_html($connected_at); ?>.
                    </p>
                    <p style="margin:8px 0 0;font-size:13px;color:#065F46;">
                        Your products will appear on PeptideMap shortly. To re-connect or update credentials, paste a new token below and click Connect.
                    </p>
                </div>
            <?php endif; ?>

            <div style="background:#fff;border:1px solid #E4E4E7;padding:24px;border-radius:8px;max-width:680px;">
                <h2 style="margin-top:0;font-size:16px;">Step 1 — Get your connection token</h2>
                <ol style="margin:0 0 20px 20px;font-size:14px;line-height:1.7;color:#52525B;">
                    <li>Go to <a href="<?php echo esc_url(PEPTIDEMAP_API_BASE . '/become-a-vendor'); ?>" target="_blank">peptidemap.com/become-a-vendor</a> and complete the application (if you haven't already).</li>
                    <li>Once approved, your unique connection token will be in your welcome email.</li>
                    <li>Don't have one? Copy it from your registration confirmation page.</li>
                </ol>

                <h2 style="font-size:16px;margin-bottom:8px;">Step 2 — Paste it below and connect</h2>

                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="margin-top:16px;">
                    <?php wp_nonce_field('peptidemap_connect_nonce'); ?>
                    <input type="hidden" name="action" value="peptidemap_connect" />

                    <table class="form-table" style="margin:0;">
                        <tr>
                            <th scope="row">
                                <label for="connection_token" style="font-size:14px;font-weight:600;">Connection Token</label>
                            </th>
                            <td>
                                <input
                                    type="text"
                                    id="connection_token"
                                    name="connection_token"
                                    value="<?php echo esc_attr($token); ?>"
                                    placeholder="pmap_xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx"
                                    class="regular-text code"
                                    style="font-family:monospace;width:100%;max-width:480px;"
                                    required
                                />
                                <p class="description" style="margin-top:6px;font-size:12px;color:#71717A;">
                                    A unique token from PeptideMap, starts with <code>pmap_</code>.
                                </p>
                            </td>
                        </tr>
                    </table>

                    <p style="margin-top:24px;">
                        <button
                            type="submit"
                            class="button button-primary button-large"
                            style="background:#0F172A;border-color:#0F172A;font-size:14px;padding:0 28px;height:40px;"
                        >
                            <?php echo $connected_at ? 'Reconnect' : 'Connect to PeptideMap'; ?> →
                        </button>
                    </p>
                </form>
            </div>

            <div style="margin-top:32px;font-size:12px;color:#71717A;max-width:680px;">
                <p><strong>What this plugin does, exactly:</strong></p>
                <ol>
                    <li>Generates a new WooCommerce REST API key with <strong>Read</strong> permissions only.</li>
                    <li>Sends the key + your store URL + your connection token to PeptideMap over HTTPS.</li>
                    <li>PeptideMap verifies the key works against your store, then enables daily product sync.</li>
                </ol>
                <p>Need help? <a href="mailto:info@peptidemap.com">info@peptidemap.com</a></p>
            </div>
        </div>
        <?php
    }

    public function handle_connect() {
        // Verify nonce
        if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'peptidemap_connect_nonce')) {
            wp_die('Security check failed.');
        }

        if (!current_user_can('manage_woocommerce')) {
            wp_die('You don\'t have permission to do this.');
        }

        $token = isset($_POST['connection_token']) ? sanitize_text_field($_POST['connection_token']) : '';

        if (empty($token)) {
            $this->redirect_with_error('Please enter your connection token.');
            return;
        }

        // Generate WooCommerce REST API key (read-only)
        $key_data = $this->create_woocommerce_api_key();
        if (is_wp_error($key_data)) {
            $this->redirect_with_error('Could not generate API key: ' . $key_data->get_error_message());
            return;
        }

        // POST to PeptideMap
        $response = wp_remote_post(PEPTIDEMAP_API_BASE . '/api/vendor-plugin/connect', [
            'timeout' => 30,
            'headers' => [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'PeptideMap-Connect/' . PEPTIDEMAP_CONNECT_VERSION . ' (WordPress)',
            ],
            'body' => wp_json_encode([
                'connection_token' => $token,
                'store_url' => untrailingslashit(home_url()),
                'consumer_key' => $key_data['consumer_key'],
                'consumer_secret' => $key_data['consumer_secret'],
            ]),
        ]);

        if (is_wp_error($response)) {
            $this->redirect_with_error('Network error: ' . $response->get_error_message());
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status = wp_remote_retrieve_response_code($response);

        if ($status >= 200 && $status < 300 && !empty($body['ok'])) {
            // Save state
            update_option('peptidemap_connection_token', $token);
            update_option('peptidemap_connected_at', current_time('mysql'));
            if (!empty($body['brand_name'])) {
                update_option('peptidemap_brand_name', $body['brand_name']);
            }

            $msg = !empty($body['message']) ? $body['message'] : 'Connected.';
            wp_safe_redirect(add_query_arg([
                'page' => 'peptidemap-connect',
                'pmap_status' => 'success',
                'pmap_message' => urlencode($msg),
            ], admin_url('admin.php')));
            exit;
        }

        $err = !empty($body['message']) ? $body['message'] : 'Server returned status ' . $status;
        $this->redirect_with_error($err);
    }

    /**
     * Create a Read-only WooCommerce REST API key for PeptideMap.
     * Returns array with 'consumer_key' and 'consumer_secret' on success,
     * or WP_Error on failure.
     */
    private function create_woocommerce_api_key() {
        if (!class_exists('WC_Auth')) {
            return new WP_Error('no_woocommerce', 'WooCommerce is not installed or activated.');
        }

        global $wpdb;

        $user_id = get_current_user_id();
        $description = 'PeptideMap (auto-generated ' . date('Y-m-d H:i') . ')';
        $permissions = 'read';

        // Generate keys (mirrors WC_REST_API_Keys_Controller logic)
        $consumer_key = 'ck_' . wc_rand_hash();
        $consumer_secret = 'cs_' . wc_rand_hash();

        $result = $wpdb->insert(
            $wpdb->prefix . 'woocommerce_api_keys',
            [
                'user_id' => $user_id,
                'description' => $description,
                'permissions' => $permissions,
                'consumer_key' => wc_api_hash($consumer_key),
                'consumer_secret' => $consumer_secret,
                'truncated_key' => substr($consumer_key, -7),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s']
        );

        if ($result === false) {
            return new WP_Error('db_error', 'Failed to save API key: ' . $wpdb->last_error);
        }

        return [
            'consumer_key' => $consumer_key,
            'consumer_secret' => $consumer_secret,
        ];
    }

    private function redirect_with_error($message) {
        wp_safe_redirect(add_query_arg([
            'page' => 'peptidemap-connect',
            'pmap_status' => 'error',
            'pmap_message' => urlencode($message),
        ], admin_url('admin.php')));
        exit;
    }
}

new PeptideMap_Connect();
