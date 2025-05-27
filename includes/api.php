<?php

function fetch_pco_events_from_api($filter_tags = [], $limit = 0, $show_description = true) {
    $transient_key = 'pco_events_' . md5(serialize($filter_tags) . $limit . $show_description);

    if (isset($_GET['refresh']) && $_GET['refresh'] === 'true') {
        delete_transient($transient_key);
    }

    $cached = get_transient($transient_key);
    if ($cached !== false) {
        return $cached;
    }

    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));

    $url = 'https://api.planningcenteronline.com/calendar/v2/event_instances?include=event,event.tags,event.tag_group_tags,event.tag_groups&order=start_at&where[after]=' . urlencode(date('c'));

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
        ]
    ]);

    if (is_wp_error($response)) {
        return '<p>Could not load events.</p>';
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $output = '<div class="events">';
    $count = 0;
    $seen_event_ids = [];

    $events = [];
    $tags = [];

    if (!empty($data['included'])) {
        foreach ($data['included'] as $item) {
            if ($item['type'] === 'Event') {
                $events[$item['id']] = $item;
            } elseif ($item['type'] === 'Tag' || $item['type'] === 'TagGroupTag') {
                $tags[$item['id']] = $item['attributes']['name'];
            }
        }
    }

    // Get saved timezone option and create DateTimeZone object if available
    $timezone = get_option('pco_events_local_timezone');
    if (!empty($timezone)) {
        try {
            $tz_object = new DateTimeZone($timezone);
        } catch (Exception $e) {
            $tz_object = null;
        }
    } else {
        $tz_object = null;
    }

    foreach ($data['data'] as $event_instance) {
        $attributes = $event_instance['attributes'];

        if (strtotime($attributes['starts_at']) < time()) {
            continue;
        }

        $event_id = $event_instance['relationships']['event']['data']['id'];
        if (in_array($event_id, $seen_event_ids)) {
            continue;
        }
        $seen_event_ids[] = $event_id;

        $event = isset($events[$event_id]) ? $events[$event_id] : null;
        if (!$event) continue;

        $event_attrs = $event['attributes'];
        $event_tags = [];
        if (!empty($event['relationships']['tags']['data'])) {
            foreach ($event['relationships']['tags']['data'] as $tag_ref) {
                $tag_id = $tag_ref['id'];
                if (isset($tags[$tag_id])) {
                    $event_tags[] = $tags[$tag_id];
                }
            }
        }

        if (!empty($filter_tags)) {
            $matched = false;
            foreach ($event_tags as $et) {
                if (in_array(strtolower($et), array_map('strtolower', $filter_tags))) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) continue;
        }

        // Instead of duplicating HTML, use the shared function:
        $output .= pco_events_render_event_card($event_instance, $data['included'], $show_description);

        $count++;
        if ($limit > 0 && $count >= $limit) break;
    }

    $output .= '</div>';
    set_transient($transient_key, $output, HOUR_IN_SECONDS);
    return $output;
}

add_action('update_option_pco_events_username', 'pco_events_test_credentials', 10, 2);
add_action('update_option_pco_events_password', 'pco_events_test_credentials', 10, 2);

function pco_events_test_credentials($old_value, $new_value) {
    if (!is_admin()) return;

    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));

    $response = wp_remote_get('https://api.planningcenteronline.com/calendar/v2/event_instances', [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
        ],
        'timeout' => 10
    ]);

    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        set_transient('pco_events_settings_error', 'Invalid Planning Center credentials.', 30);
    } else {
        set_transient('pco_events_settings_success', 'Credentials validated successfully!', 30);
    }
}

function fetch_pco_single_event($id, $type = 'event') {
    $username = pco_events_decrypt(get_option('pco_events_username'));
    $password = pco_events_decrypt(get_option('pco_events_password'));

    if ($type === 'instance') {
        $url = 'https://api.planningcenteronline.com/calendar/v2/event_instances/' . urlencode($id) . '?include=event,event.tags,event.tag_group_tags,event.tag_groups';
    } else {
        $url = 'https://api.planningcenteronline.com/calendar/v2/events/' . urlencode($id) . '?include=event_instances,tags,tag_group_tags,tag_groups';
    }

    $response = wp_remote_get($url, [
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode("$username:$password"),
        ]
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);

    if ($type === 'instance' && !empty($data['data'])) {
        return [
            'event_instance' => $data['data'],
            'included' => $data['included'] ?? [],
        ];
    } elseif ($type === 'event' && !empty($data['data'])) {
        // Find the next upcoming instance
        $instances = $data['included'] ?? [];
        foreach ($instances as $item) {
            if ($item['type'] === 'EventInstance' && strtotime($item['attributes']['starts_at']) > time()) {
                return [
                    'event_instance' => $item,
                    'included' => $instances,
                ];
            }
        }
    }

    return false;
}

function pco_events_render_event_card($event_instance, $included, $show_description) {
    $attributes = $event_instance['attributes'];
    $event_id = $event_instance['relationships']['event']['data']['id'];

    $event = null;
    foreach ($included as $item) {
        if ($item['type'] === 'Event' && $item['id'] === $event_id) {
            $event = $item;
            break;
        }
    }

    if (!$event) return '';

    $event_attrs = $event['attributes'];
    $event_tags = [];
    if (!empty($event['relationships']['tags']['data'])) {
        foreach ($event['relationships']['tags']['data'] as $tag_ref) {
            $tag_id = $tag_ref['id'];
            if (isset($tags[$tag_id])) {
                $event_tags[] = $tags[$tag_id];
            }
        }
    }

    $image_url = isset($event_attrs['image_url']) ? $event_attrs['image_url'] : '';
    $event_url = !empty($event_attrs['registration_url']) ? $event_attrs['registration_url'] : '';

    $output = '<div class="event">';

    if ($image_url) {
        if (!empty($event_url)) {
            $output .= '<a href="' . esc_url($event_url) . '" target="_blank">';
            $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($event_attrs['name']) . '" />';
            $output .= '</a>';
        } else {
            $output .= '<img src="' . esc_url($image_url) . '" alt="' . esc_attr($event_attrs['name']) . '" />';
        }
    }

    $event_title = esc_html($event_attrs['name']);

    $output .= '<div class="event-title-wrap">';
    if (!empty($event_url)) {
        $output .= '<h3><a href="' . esc_url($event_url) . '" target="_blank">' . $event_title . '</a></h3>';
    } else {
        $output .= '<h3>' . $event_title . '</h3>';
    }
    $output .= '</div>';

    // Enhanced event date and recurrence formatting with timezone support
    try {
        $datetime = new DateTime($attributes['starts_at']);
        if ($tz_object) {
            $datetime->setTimezone($tz_object);
        }
        $date_string = $datetime->format('D, j M');
        $time_string = $datetime->format('g:ia');
    } catch (Exception $e) {
        // Fallback to original formatting if DateTime fails
        $starts_at = strtotime($attributes['starts_at']);
        $date_string = date_i18n('D, j M', $starts_at);
        $time_string = date_i18n('g:ia', $starts_at);
    }

    $output .= '<div class="event-date">';

    if (
        !empty($attributes['recurrence']) &&
        strtolower(trim($attributes['recurrence'])) !== 'none' &&
        !empty($attributes['recurrence_description'])
    ) {
        preg_match('/Every\s+\w+/', $attributes['recurrence_description'], $matches);
        if (!empty($matches[0])) {
            $recurrence_text = esc_html($matches[0]);
            $output .= '<span class="recurring-label">' . $recurrence_text . ', ' . esc_html($time_string) . '<br></span>';
            $output .= '<span class="next-date-label">Next Date: ' . esc_html($date_string) . '</span>';
        }
    } else {
        $output .= '<span>' . esc_html($date_string) . ', ' . esc_html($time_string) . '</span>';
    }

    $output .= '</div>';

    if (!empty($event_tags)) {
        $output .= '<div class="event-tags">';
        foreach ($event_tags as $tag) {
            $output .= '<span class="event-tag">' . esc_html($tag) . '</span>';
        }
        $output .= '</div>';
    }

    if ($show_description && !empty($event_attrs['description'])) {
        $output .= '<p>' . esc_html(strip_tags($event_attrs['description'])) . '</p>';
    }

    $output .= '</div>';

    return $output;
}