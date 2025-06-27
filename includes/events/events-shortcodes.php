<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

function pco_events_license_message() {
    return '<p><strong>This plugin is not activated. Please enter a valid license key in the settings page.</strong></p>';
}

// Shortcode usage:
// [pco_events] — show all events
// [pco_events tags="tag1,tag2" start="YYYY-MM-DD" end="YYYY-MM-DD"] — filter by tags and/or date range
// [pco_event id="INSTANCE_ID" type="instance"] — show a single event instance
// [pco_event id="EVENT_ID" type="event"] — show next instance of an event
// [pco_events show_description="false"] — hide description
// Use the Shortcode Generator in the admin for easy shortcode creation!
require_once plugin_dir_path(__FILE__) . 'template.php';

function pco_events_all($atts) {

    $atts = shortcode_atts([
        'count' => '25',
        'show_description' => 'true',
        'show_image' => 'true',
        'show_tags' => 'true',
    ], $atts, 'pco_events');

    $show_desc = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
    $show_image = ($atts['show_image'] !== 'false');
    $show_tags = ($atts['show_tags'] !== 'false');

    $count = isset($atts['count']) ? intval($atts['count']) : 25;
    return fetch_pco_events_from_api([], $count, $show_desc, $show_image, $show_tags);
}
add_shortcode('pco_events', 'pco_events_all');

function pco_events_featured($atts) {

    $atts = shortcode_atts([
        'tags' => 'featured',
        'count' => 3,
        'show_description' => 'true',
        'show_image' => 'true',
        'show_tags' => 'true',
    ], $atts);

    $tags = (trim($atts['tags']) === '') ? [] : array_map('trim', explode(',', $atts['tags']));
    $show_desc = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
    $show_image = ($atts['show_image'] !== 'false');
    $show_tags = ($atts['show_tags'] !== 'false');

    return fetch_pco_events_from_api($tags, intval($atts['count']), $show_desc, $show_image, $show_tags);
}
add_shortcode('pco_featured_events', 'pco_events_featured');

function pco_events_single($atts) {

    $atts = shortcode_atts([
        'id' => '',
        'type' => 'event', // 'event' or 'instance'
        'show_description' => 'true',
        'show_image' => 'true',
        'show_tags' => 'true',
    ], $atts);

    $id = trim($atts['id']);
    $type = strtolower(trim($atts['type']));
    $show_desc = filter_var($atts['show_description'], FILTER_VALIDATE_BOOLEAN);
    $show_image = ($atts['show_image'] !== 'false');
    $show_tags = ($atts['show_tags'] !== 'false');

    if (empty($id)) {
        return '<p>No event ID provided.</p>';
    }

    $event_data = fetch_pco_single_event($id, $type);

    if (!$event_data) {
        return '<p>Event not found.</p>';
    }

    // Use the same card rendering function
    return '<div class="events">' . pco_events_render_event_card($event_data['event_instance'], $event_data['included'], $show_desc, $show_image, $show_tags) . '</div>';
}
add_shortcode('pco_event', 'pco_events_single');