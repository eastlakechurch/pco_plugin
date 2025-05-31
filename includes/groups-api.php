<?php
if (!defined('ABSPATH')) exit;

function pco_groups_fetch_groups($params = []) {
    $app_id = get_option('pco_events_app_id');
    $secret = get_option('pco_events_app_secret');
    if (!$app_id || !$secret) return [];

    $url = 'https://api.planningcenteronline.com/groups/v2/groups';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$app_id:$secret"),
            'Accept' => 'application/json',
        ],
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) return [];
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['data'] ?? [];
}