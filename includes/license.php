<?php

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
    $api_key = defined('PCO_EVENTS_LS_API_KEY') ? PCO_EVENTS_LS_API_KEY : '';
    $product_id = defined('PCO_EVENTS_LS_PRODUCT_ID') ? PCO_EVENTS_LS_PRODUCT_ID : 0;

    if (empty($api_key) || empty($product_id) || empty($license_key)) {
        return false;
    }

    $url = 'https://api.lemonsqueezy.com/v1/licenses/validate';
    $args = [
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ],
        'body' => json_encode([
            'license_key' => $license_key,
            'product_id'  => $product_id,
        ]),
        'timeout' => 10,
    ];

    $response = wp_remote_post($url, $args);
    if (is_wp_error($response)) {
        return false;
    }

    $code = wp_remote_retrieve_response_code($response);
    $body = json_decode(wp_remote_retrieve_body($response), true);
    error_log('PCO License Validation Response: ' . print_r($body, true));

    $is_valid = $code === 200 && !empty($body['valid']) && $body['valid'] === true;
    update_option('pco_events_license_status', $is_valid ? 'valid' : 'invalid');
    return $is_valid;
}

add_action('update_option_pco_events_license_key', function($old_value, $new_value) {
    if (!empty($new_value) && function_exists('pco_events_validate_license_key')) {
        pco_events_validate_license_key($new_value);
    }
}, 10, 2);