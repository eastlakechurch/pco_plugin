<?php
// includes/sermons/api.php

if (!defined('ABSPATH')) exit;

// Use the main plugin's encrypted API credentials
$token_id = pco_events_decrypt(get_option('pco_events_username'));
$token_secret = pco_events_decrypt(get_option('pco_events_password'));

/**
 * Calculate the number of seconds until the next update time (Monday at 6pm).
 */
function pcp_get_next_update_interval() {
    $current_time = current_time('timestamp');
    if (date('N', $current_time) == 1 && date('H:i', $current_time) < '18:00') {
        $next_update = strtotime(date('Y-m-d', $current_time) . ' 18:00:00');
    } else {
        $next_update = strtotime('next monday 18:00:00', $current_time);
    }
    return $next_update - $current_time;
}

/**
 * Fetch and update the latest episode.
 */
function pcp_update_latest_episode() {
    global $token_id, $token_secret;
    $channel_id = get_option('pco_sermons_channel_id');
    $api_url = $channel_id
        ? 'https://api.planningcenteronline.com/publishing/v2/channels/' . $channel_id . '/episodes'
        : 'https://api.planningcenteronline.com/publishing/v2/episodes';

    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($token_id . ':' . $token_secret)
        )
    );

    $response = wp_remote_get($api_url, $args);
    if (is_wp_error($response)) {
        error_log('PCP API error: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if (200 !== $response_code) {
        error_log('PCP API returned status code: ' . $response_code);
        error_log('PCP API response: ' . wp_remote_retrieve_body($response));
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (empty($channel_id) && isset($data['data'][0]['relationships']['channel']['data']['id'])) {
        error_log('Detected default Publishing Channel ID: ' . $data['data'][0]['relationships']['channel']['data']['id']);
    }

    if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
        $episodes = $data['data'];
        $latest_episode = $episodes[count($episodes) - 1];
        $expiration = pcp_get_next_update_interval();

        set_transient('pcp_latest_episode', $latest_episode, $expiration);
        return $latest_episode;
    }

    return false;
}

/**
 * Retrieve cached episode or force update if ?pcp_nocache=1 is used.
 */
function pcp_fetch_latest_episode() {
    $episode = get_transient('pcp_latest_episode');

    if (isset($_GET['pcp_nocache']) && '1' === $_GET['pcp_nocache'] || false === $episode) {
        return pcp_update_latest_episode();
    }

    return $episode;
}