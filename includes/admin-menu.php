<?php

add_action('admin_menu', 'pco_events_admin_menu');

function pco_events_admin_menu() {
    add_menu_page(
        'PCO Events',
        'PCO Events',
        'manage_options',
        'pco-events',
        'pco_events_about_page',
        'dashicons-calendar-alt'
    );

    add_submenu_page(
        'pco-events',
        'About',
        'About',
        'manage_options',
        'pco-events',
        'pco_events_about_page'
    );

    add_submenu_page(
        'pco-events',
        'Settings',
        'Settings',
        'manage_options',
        'pco-events-settings',
        'pco_events_settings_page'
    );

    add_submenu_page(
        'pco-events',
        'Styles',
        'Styles',
        'manage_options',
        'pco-events-styles',
        'pco_events_styles_page'
    );

    add_submenu_page(
        'pco-events',
        'Tags',
        'Tags',
        'manage_options',
        'pco-events-tags',
        'pco_events_tags_page'
    );

    add_submenu_page(
        'pco-events',
        'Shortcode Generator',
        'Shortcode Generator',
        'manage_options',
        'pco-events-shortcode-generator',
        'pco_events_shortcode_generator_page'
    );
}

// About page content
function pco_events_about_page() {
    ?>
    <div class="wrap">
        <h1>About PCO Events</h1>
        <p>This plugin integrates your WordPress site with Planning Center to display upcoming events.</p>
        <h2>Shortcodes</h2>
        <p>Use these shortcodes anywhere in your pages or posts:</p>
        <ul>
            <li>
                <code>[pco_events]</code> – shows all upcoming events.
            </li>
            <li>
                <code>[pco_events show_description="false"]</code> – hides the event description.
            </li>
            <li>
                <code>[pco_featured_events tags="featured"]</code> – only events tagged with “featured”.
            </li>
            <li>
                <code>[pco_featured_events tags="youth,camp" count="5"]</code> – max 5 events with those tags.
            </li>
            <li>
                <code>[pco_event id="EVENT_ID" type="event"]</code> – show a single event by Event ID (shows next upcoming instance).
            </li>
            <li>
                <code>[pco_event id="INSTANCE_ID" type="instance"]</code> – show a single event by Instance ID (shows that specific instance).
            </li>
            <li>
                <code>[pco_event id="12345" type="event" show_description="false"]</code> – hide description for a single event.
            </li>
            <li>
                <code>[pco_events tags="Youth,Sunday"]</code> – filter events by tag.
            </li>
        </ul>
        <h2>Cache Refresh</h2>
        <p>To force the plugin to refresh the event list (bypassing the cache), add <code>?refresh=true</code> to your page URL.</p>
    </div>
    <?php
}

function pco_events_settings_page() {
    ?>
    <div class="wrap">
        <h1>PCO Events Settings</h1>
        <?php
        $username = pco_events_decrypt(get_option('pco_events_username'));
        $password = pco_events_decrypt(get_option('pco_events_password'));
        $license = get_option('pco_events_license_key');
        if (empty($username) || empty($password) || empty($license)) {
            echo '<div class="notice notice-warning"><p>Please enter your Planning Center API credentials and your license key to activate the plugin.</p></div>';
        } elseif (!empty($license)) {
            $is_valid = pco_events_validate_license_key($license);
            if ($is_valid) {
                echo '<div class="notice notice-success"><p>License key is active.</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>License key is invalid.</p></div>';
            }
        }
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('pco_events_settings_group');
            do_settings_sections('pco-events-settings');
            submit_button(); // Save Settings
            ?>
        </form>

        <form method="post" action="">
            <?php wp_nonce_field('pco_events_test_connection', 'pco_events_nonce'); ?>
            <?php submit_button('Test Connection', 'secondary', 'pco_test_connection'); ?>
        </form>

        <?php if (!empty($license)) : ?>
        <form method="post" action="">
            <?php wp_nonce_field('pco_events_deactivate_license', 'pco_events_nonce'); ?>
            <?php submit_button('Deactivate License', 'delete', 'pco_deactivate_license'); ?>
        </form>
        <?php endif; ?>

        <form method="post" action="">
            <?php wp_nonce_field('pco_events_refresh_cache', 'pco_events_nonce'); ?>
            <?php submit_button('Refresh Event Cache', 'secondary', 'pco_refresh_cache'); ?>
        </form>
    </div>
    <?php
}

function pco_events_tags_page() {
    echo '<div class="wrap"><h1>PCO Events – Tags</h1>';
    echo '<p>This page lists all tags used in your upcoming events. Use these to filter events in your shortcodes.</p>';

    $transient_key = 'pco_events_tags_display';
    $cached = get_transient($transient_key);
    if ($cached !== false) {
        echo $cached;
        echo '</div>';
        return;
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
        echo '<p>Unable to fetch tags at this time.</p></div>';
        return;
    }

    $data = json_decode(wp_remote_retrieve_body($response), true);
    $tags = [];

    if (!empty($data['included'])) {
        foreach ($data['included'] as $item) {
            if ($item['type'] === 'Tag' || $item['type'] === 'TagGroupTag') {
                $tags[$item['id']] = $item['attributes']['name'];
            }
        }
    }

    if (empty($tags)) {
        echo '<p>No tags found.</p></div>';
        return;
    }

    $output = '<ul style="margin-left:20px;">';
    foreach ($tags as $tag) {
        $output .= '<li><code>' . esc_html(strtolower($tag)) . '</code></li>';
    }
    $output .= '</ul>';

    echo $output . '</div>';
    set_transient($transient_key, $output, HOUR_IN_SECONDS);
}

function pco_events_shortcode_generator_page() {
    ?>
    <div class="wrap">
        <h1>PCO Events Shortcode Generator</h1>
        <p>Select an upcoming event to generate a shortcode for displaying that single event.</p>
        <?php
        // Fetch upcoming events (reuse your API logic)
        $username = pco_events_decrypt(get_option('pco_events_username'));
        $password = pco_events_decrypt(get_option('pco_events_password'));
        $url = 'https://api.planningcenteronline.com/calendar/v2/event_instances?include=event&order=start_at&where[after]=' . urlencode(date('c'));
        $response = wp_remote_get($url, [
            'headers' => [
                'Authorization' => 'Basic ' . base64_encode("$username:$password"),
            ]
        ]);
        if (is_wp_error($response)) {
            echo '<p>Could not load events.</p>';
            return;
        }
        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['data'])) {
            echo '<p>No upcoming events found.</p>';
            return;
        }
        // Build dropdown
        echo '<label for="pco_event_selector">Choose an event:</label> ';
        echo '<select id="pco_event_selector">';
        echo '<option value="">-- Select an event --</option>';
        foreach ($data['data'] as $instance) {
            $event_id = $instance['relationships']['event']['data']['id'];
            $instance_id = $instance['id'];
            $title = '';
            // Find event title in included
            if (!empty($data['included'])) {
                foreach ($data['included'] as $inc) {
                    if ($inc['type'] === 'Event' && $inc['id'] === $event_id) {
                        $title = $inc['attributes']['name'];
                        break;
                    }
                }
            }
            $start = date_i18n('D, M j Y g:ia', strtotime($instance['attributes']['starts_at']));
            $option_label = esc_html($title . ' (' . $start . ')');
            // Store both event and instance IDs as data attributes
            echo '<option value="' . esc_attr($event_id) . '" data-instance="' . esc_attr($instance_id) . '">' . $option_label . '</option>';
        }
        echo '</select>';
        ?>
        <div id="pco_shortcode_output" style="margin-top:20px;display:none;">
            <strong>Copy this shortcode:</strong>
            <input type="text" id="pco_shortcode_text" style="width: 400px;" readonly>
            <button type="button" class="button" onclick="document.getElementById('pco_shortcode_text').select();document.execCommand('copy');">Copy</button>
            <p style="margin-top:8px;font-size:0.95em;color:#666;">
                Use <code>type="event"</code> to show the next instance of a recurring event, or <code>type="instance"</code> to show this specific date/time.
            </p>
        </div>
        <script>
        document.getElementById('pco_event_selector').addEventListener('change', function() {
            var eventId = this.value;
            var instanceId = this.options[this.selectedIndex].getAttribute('data-instance');
            var output = document.getElementById('pco_shortcode_output');
            var input = document.getElementById('pco_shortcode_text');
            if (eventId && instanceId) {
                // Default to instance shortcode
                input.value = '[pco_event id="' + instanceId + '" type="instance"]';
                output.style.display = 'block';
            } else {
                output.style.display = 'none';
            }
        });
        </script>
    </div>
    <?php
}