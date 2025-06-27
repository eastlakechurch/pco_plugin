<?php
if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'render.php';

/**
 * Fallback message for invalid license.
 */
function pcp_license_message() {
    return '<p><strong>This plugin is not activated. Please enter a valid license key in the settings page.</strong></p>';
}

/**
 * Shortcode: [planning_centre_video]
 *
 * Outputs the video player for the latest sermon episode.
 */
function pcp_video_shortcode() {
    $license_status = get_option('pco_sermons_license_status');
    if ($license_status !== 'valid') {
        return pcp_license_message();
    }

    $episode = pcp_fetch_latest_episode();
    if (!$episode) {
        return '<p>No episode found.</p>';
    }

    return render_sermon_video($episode);
}
add_shortcode('planning_centre_video', 'pcp_video_shortcode');

/**
 * Shortcode: [planning_centre_title]
 *
 * Outputs the title for the latest sermon episode.
 */
function pcp_title_shortcode() {
    $license_status = get_option('pco_sermons_license_status');
    if ($license_status !== 'valid') {
        return pcp_license_message();
    }

    $episode = pcp_fetch_latest_episode();
    if (!$episode) {
        return '<p>No episode found.</p>';
    }

    return render_sermon_title($episode);
}
add_shortcode('planning_centre_title', 'pcp_title_shortcode');

/**
 * Shortcode: [planning_centre_published]
 *
 * Outputs the formatted published date for the latest sermon episode.
 */
function pcp_published_shortcode() {
    $license_status = get_option('pco_sermons_license_status');
    if ($license_status !== 'valid') {
        return pcp_license_message();
    }

    $episode = pcp_fetch_latest_episode();
    if (!$episode) {
        return '<p>No episode found.</p>';
    }

    return render_sermon_published_date($episode);
}
add_shortcode('planning_centre_published', 'pcp_published_shortcode');