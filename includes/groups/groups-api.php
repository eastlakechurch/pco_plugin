<?php
if (!defined('ABSPATH')) exit;

function pco_groups_fetch_groups($params = []) {
    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));
    if (!$username || !$password) {
        echo '<div style="color:red;">PCO Integrations - Groups: API credentials are missing. Please check your plugin settings.</div>';
        return [];
    }

    $cache_key = 'pco_groups_' . md5(serialize($params));
    $cache_time = 10800; // 10 minutes

    $groups = get_transient($cache_key);
    if ($groups !== false) {
        return $groups;
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
        echo '<div style="color:red;">PCO Integrations - Groups API error: ' . esc_html($response->get_error_message()) . '</div>';
        return [];
    }
    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['data'])) {
        echo '<div style="color:red;">PCO Integrations - Groups API returned no data. Raw response: <pre>' . esc_html(wp_remote_retrieve_body($response)) . '</pre></div>';
    }
    $groups = $body['data'] ?? [];
    set_transient($cache_key, $groups, $cache_time);
    return $groups;
}

function pco_groups_fetch_location_name($location_id) {
    if (!$location_id) return '';
    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));
    if (!$username || !$password) return '';

    $cache_key = 'pco_group_location_' . intval($location_id);
    $cache_time = 3600; // 1 hour

    $location_name = get_transient($cache_key);
    if ($location_name !== false) {
        return $location_name;
    }

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
    $location_name = $body['data']['attributes']['name'] ?? '';
    set_transient($cache_key, $location_name, $cache_time);
    return $location_name;
}

function pco_groups_fetch_group_types() {
    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));
    if (!$username || !$password) return [];

    $cache_key = 'pco_group_types';
    $cache_time = 3600; // 1 hour

    $types = get_transient($cache_key);
    if ($types !== false) {
        return $types;
    }

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
    set_transient($cache_key, $types, $cache_time);
    return $types;
}