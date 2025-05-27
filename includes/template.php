<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

function pco_events_render_event_card($event_instance, $included = [], $show_description = true) {
    // Find the related Event object
    $event_id = $event_instance['relationships']['event']['data']['id'] ?? null;
    $event = null;
    $tags = [];
    if (!empty($included)) {
        foreach ($included as $item) {
            if ($item['type'] === 'Event' && $item['id'] === $event_id) {
                $event = $item;
            } elseif ($item['type'] === 'Tag' || $item['type'] === 'TagGroupTag') {
                $tags[$item['id']] = $item['attributes']['name'];
            }
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

    $image_url = $event_attrs['image_url'] ?? '';
    $event_url = $event_attrs['registration_url'] ?? '';
    $event_title = esc_html($event_attrs['name']);

    // Timezone
    $timezone = get_option('pco_events_local_timezone');
    $tz_object = null;
    if (!empty($timezone)) {
        try {
            $tz_object = new DateTimeZone($timezone);
        } catch (Exception $e) {}
    }

    // Date/time
    $attributes = $event_instance['attributes'];
    try {
        $datetime = new DateTime($attributes['starts_at']);
        if ($tz_object) $datetime->setTimezone($tz_object);
        $date_string = $datetime->format('D, j M');
        $time_string = $datetime->format('g:ia');
    } catch (Exception $e) {
        $starts_at = strtotime($attributes['starts_at']);
        $date_string = date_i18n('D, j M', $starts_at);
        $time_string = date_i18n('g:ia', $starts_at);
    }

    // Recurrence
    $is_recurring_event = false;
    $next_date = '';
    if (
        !empty($attributes['recurrence']) &&
        strtolower(trim($attributes['recurrence'])) !== 'none' &&
        !empty($attributes['recurrence_description'])
    ) {
        $is_recurring_event = true;
        preg_match('/Every\s+\w+/', $attributes['recurrence_description'], $matches);
        if (!empty($matches[0])) {
            $recurrence_text = esc_html($matches[0]);
            $next_date = esc_html($date_string);
        }
    }

    // Start output
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

    $output .= '<div class="event-title-wrap">';
    if (!empty($event_url)) {
        $output .= '<h3><a href="' . esc_url($event_url) . '" target="_blank">' . $event_title . '</a></h3>';
    } else {
        $output .= '<h3>' . $event_title . '</h3>';
    }
    $output .= '</div>';

    $output .= '<div class="event-date">';
    if ($is_recurring_event) {
        $output .= '<span class="recurring-label">' . $recurrence_text . ', ' . esc_html($time_string) . '<br></span>';
        $output .= '<span class="next-date-label">Next Date: ' . esc_html($next_date) . '</span>';
    } else {
        $output .= '<span>' . esc_html($date_string) . ', ' . esc_html($time_string) . '</span>';
    }
    $output .= '</div>';

    // Show "Next Date" for recurring events if enabled
    $show_next_date = get_option('pco_events_show_next_date', 'yes');
    if ($is_recurring_event && $show_next_date === 'yes') {
        $output .= '<div class="pco-event-next-date" style="color:' . esc_attr(get_option('pco_events_recurring_color', '#0073aa')) . ';">Next Date: ' . esc_html($next_date) . '</div>';
    }

    // Show tags if enabled
    $show_tags = get_option('pco_events_show_tags', 'yes');
    if (!empty($event_tags) && $show_tags === 'yes') {
        $output .= '<div class="pco-event-tags">';
        foreach ($event_tags as $tag) {
            $output .= '<span class="pco-event-tag" style="background:' . esc_attr(get_option('pco_events_primary_color', '#0073aa')) . ';">' . esc_html($tag) . '</span> ';
        }
        $output .= '</div>';
    }

    if ($show_description && !empty($event_attrs['description'])) {
        $output .= '<p>' . esc_html(strip_tags($event_attrs['description'])) . '</p>';
    }

    $output .= '</div>';

    return $output;
}

// ...where you output custom CSS...
echo '<style>' . esc_html(get_option('pco_events_custom_css', '')) . '</style>';