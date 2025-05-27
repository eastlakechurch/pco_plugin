<?php
require_once plugin_dir_path(__FILE__) . 'license.php';

function pco_events_all($atts) {
    $license_status = get_option('pco_events_license_status');
    error_log('License status on shortcode render (all): ' . $license_status);

    if ($license_status !== 'valid') {
        return '<p><strong>This plugin is not activated. Please enter a valid license key in the settings page.</strong></p>';
    }

    $atts = shortcode_atts([
        'show_description' => 'true'
    ], $atts);
    $show_desc = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
    return fetch_pco_events_from_api([], 0, $show_desc);
}
add_shortcode('pco_events', 'pco_events_all');

function pco_events_featured($atts) {
    $license_status = get_option('pco_events_license_status');
    error_log('License status on shortcode render (featured): ' . $license_status);

    if ($license_status !== 'valid') {
        return '<p><strong>This plugin is not activated. Please enter a valid license key in the settings page.</strong></p>';
    }

    $atts = shortcode_atts([
        'tags' => 'featured',
        'count' => 3,
        'show_description' => 'true'
    ], $atts);

    $tags = (trim($atts['tags']) === '') ? [] : array_map('trim', explode(',', $atts['tags']));
    $show_desc = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);

    return fetch_pco_events_from_api($tags, intval($atts['count']), $show_desc);
}
add_shortcode('pco_featured_events', 'pco_events_featured');