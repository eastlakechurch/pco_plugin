<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

add_action('admin_init', 'pco_events_handle_license_actions');

function pco_events_handle_license_actions() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['pco_deactivate_license']) && sanitize_text_field($_POST['pco_deactivate_license'])) {
        if (!isset($_POST['pco_events_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['pco_events_nonce']), 'pco_events_deactivate_license')) {
            wp_die('Security check failed');
        }

        delete_option('pco_events_license_key');
        delete_option('pco_events_license_status');
        delete_option('pco_events_license_expires_at');
        delete_transient('pco_events_license_status_cache');
        set_transient('pco_events_settings_success', 'License key has been deactivated and cache cleared.', 30);
        wp_redirect(admin_url('admin.php?page=pco-events-settings'));
        exit;
    }
}

function pco_events_validate_license_key($license_key) {
    if (empty($license_key)) {
        return false;
    }

    delete_transient('pco_events_license_status_cache');
    delete_option('pco_events_license_status');


    $site_url = preg_replace('/^www\./', '', parse_url(home_url(), PHP_URL_HOST));
    $url = 'https://pcointegrations.com/lic.php?key=' . urlencode($license_key) . '&site=' . urlencode($site_url);
    $response = wp_remote_get($url, ['timeout' => 10]);
    error_log('🌐 License Validation Request URL: ' . $url);

    if (is_wp_error($response)) {
        return false;
    }

    error_log('📦 Raw JSON Body: ' . wp_remote_retrieve_body($response));
    $body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('PCO License Validation Response: ' . print_r($body, true));
    $is_valid = isset($body['valid']) && filter_var($body['valid'], FILTER_VALIDATE_BOOLEAN);

    $expires_at = $body['expires_at'] ?? '';
    if ($expires_at && strtotime($expires_at) > time()) {
        update_option('pco_events_license_expires_at', $expires_at);
    } else {
        update_option('pco_events_license_expires_at', '');
    }

    update_option('pco_events_license_status', $is_valid ? 'valid' : 'invalid');
    set_transient('pco_events_license_status_cache', $is_valid ? 'valid' : 'invalid', DAY_IN_SECONDS);

    error_log('✅ Stored License Status: ' . ($is_valid ? 'valid' : 'invalid'));
    error_log('📅 Stored License Expiry: ' . $expires_at);

    return $is_valid;
}

add_action('add_option_pco_events_license_key', function($option, $value) {
    if (!empty($value) && function_exists('pco_events_validate_license_key')) {
        pco_events_validate_license_key($value);
        set_transient('pco_license_notice_suppressed', true, 10);
    }
}, 10, 2);

add_action('update_option_pco_events_license_key', function($old_value, $new_value) {
    if (!empty($new_value) && function_exists('pco_events_validate_license_key')) {
        pco_events_validate_license_key($new_value);
        set_transient('pco_license_notice_suppressed', true, 10);
    }
}, 10, 2);

// Display admin notice if license is invalid
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;

    // Check if we just refreshed or saved the license recently
    if (get_transient('pco_license_notice_suppressed')) {
        return;
    }

    $status = get_option('pco_events_license_status');
    if ($status !== 'valid') {
        $expires = get_option('pco_events_license_expires_at', '');
        $expired = $expires && strtotime($expires) < time();
        $message = $expired
            ? 'Your license key has expired. Please renew it in <a href="' . esc_url(admin_url('options-general.php?page=pco-events-settings')) . '">plugin settings</a>.'
            : 'Your license key is invalid or missing. Please enter a valid license in <a href="' . esc_url(admin_url('options-general.php?page=pco-events-settings')) . '">plugin settings</a>.';

        echo '<div class="notice notice-error"><p><strong>PCO Integrations Plugin:</strong> ' . $message . '</p></div>';
    }
});