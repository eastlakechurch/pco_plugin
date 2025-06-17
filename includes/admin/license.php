<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

add_action('admin_init', 'pco_events_handle_license_actions');

function pco_events_handle_license_actions() {
    if (!current_user_can('manage_options')) return;

    if (isset($_POST['pco_deactivate_license'])) {
        if (!isset($_POST['pco_events_nonce']) || !wp_verify_nonce($_POST['pco_events_nonce'], 'pco_events_deactivate_license')) {
            wp_die('Security check failed');
        }

        delete_option('pco_events_license_key');
        set_transient('pco_events_settings_success', 'License key has been deactivated.', 30);
        wp_redirect(admin_url('admin.php?page=pco-events-settings'));
        exit;
    }
}

function pco_events_validate_license_key($license_key) {
    if (empty($license_key)) {
        return false;
    }

    // Check cached validation result
    $cached_status = get_transient('pco_events_license_status_cache');
    if ($cached_status !== false) {
        return $cached_status === 'valid';
    }

    $site_url = home_url();
    $url = 'https://pcointegrations.com/validate-license.php?key=' . urlencode($license_key) . '&site=' . urlencode($site_url);
    $response = wp_remote_get($url, ['timeout' => 10]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    $is_valid = isset($body['valid']) && $body['valid'] === true;

    update_option('pco_events_license_status', $is_valid ? 'valid' : 'invalid');
    set_transient('pco_events_license_status_cache', $is_valid ? 'valid' : 'invalid', DAY_IN_SECONDS);

    return $is_valid;
}

add_action('update_option_pco_events_license_key', function($old_value, $new_value) {
    if (!empty($new_value) && function_exists('pco_events_validate_license_key')) {
        pco_events_validate_license_key($new_value);
    }
}, 10, 2);

// Display admin notice if license is invalid
add_action('admin_notices', function () {
    if (!current_user_can('manage_options')) return;
    $status = get_option('pco_events_license_status');
    if ($status !== 'valid') {
        echo '<div class="notice notice-error"><p><strong>PCO Integrations Plugin:</strong> Your license key is invalid or missing. Please enter a valid license in <a href="' . esc_url(admin_url('options-general.php?page=pco-events-settings')) . '">plugin settings</a>.</p></div>';
    }
});