<?php
if (!defined('ABSPATH')) exit;

function pco_groups_fetch_groups($params = []) {
    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));
    if (!$username || !$password) {
        echo '<div style="color:red;">PCO PCO Integrations - Groups: API credentials are missing. Please check your plugin settings.</div>';
        return [];
    }

    $url = 'https://api.planningcenteronline.com/groups/v2/groups';
    if (!empty($params)) {
        $url .= '?' . http_build_query($params);
    }

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            'Accept' => 'application/json',
        ],
        'timeout' => 15,
    ]);
    if (is_wp_error($response)) {
        echo '<div style="color:red;">PCO PCO Integrations - Groups API error: ' . esc_html($response->get_error_message()) . '</div>';
        return [];
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['data'])) {
        echo '<div style="color:red;">PCO PCO Integrations - Groups API returned no data. Raw response: <pre>' . esc_html(wp_remote_retrieve_body($response)) . '</pre></div>';
    }
    return $body['data'] ?? [];
}

// Add this function to fetch a location's name by ID
function pco_groups_fetch_location_name($location_id) {
    if (!$location_id) return '';
    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));
    if (!$username || !$password) return '';

    $url = 'https://api.planningcenteronline.com/groups/v2/locations/' . intval($location_id);
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            'Accept' => 'application/json',
        ],
        'timeout' => 10,
    ]);
    if (is_wp_error($response)) return '';
    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['data']['attributes']['name'] ?? '';
}

function pco_groups_fetch_group_types() {
    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));
    if (!$username || !$password) return [];

    $url = 'https://api.planningcenteronline.com/groups/v2/group_types';
    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            'Accept' => 'application/json',
        ],
        'timeout' => 10,
    ]);
    if (is_wp_error($response)) return [];
    $body = json_decode(wp_remote_retrieve_body($response), true);
    $types = [];
    if (!empty($body['data'])) {
        foreach ($body['data'] as $type) {
            $id = $type['id'];
            $name = $type['attributes']['name'] ?? $id;
            $types[$id] = $name;
        }
    }
    return $types;
}