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
        <p>Use these shortcodes anywhere in your pages or posts, or use the <strong>Shortcode Generator</strong> tab to easily build and copy a shortcode for:</p>
        <ul>
            <li>
                <code>[pco_events]</code> – shows all upcoming events.
            </li>
            <li>
                <code>[pco_events tags="Youth,Sunday"]</code> – filter events by one or more tags.
            </li>
            <li>
                <code>[pco_events start="2025-06-01" end="2025-06-30"]</code> – filter events by date range.
            </li>
            <li>
                <code>[pco_event id="INSTANCE_ID" type="instance"]</code> – show a single event instance (use the generator to select).
            </li>
            <li>
                <code>[pco_event id="EVENT_ID" type="event"]</code> – show a single event by Event ID (shows next upcoming instance).
            </li>
            <li>
                <code>[pco_events show_description="false"]</code> – hides the event description.
            </li>
        </ul>
        <p>
            <strong>Tip:</strong> Use the <strong>Shortcode Generator</strong> tab to select tags, date ranges, or a single event and copy the exact shortcode you need—no need to look up IDs manually!
        </p>
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

function pco_events_shortcode_generator_page() {
    ?>
    <div class="wrap">
        <h1>PCO Events Shortcode Generator</h1>
        <p>Select options below to generate a shortcode for displaying events.</p>
        <?php
        // Fetch upcoming events (reuse your API logic)
        $username = pco_events_decrypt(get_option('pco_events_username'));
        $password = pco_events_decrypt(get_option('pco_events_password'));
        $url = 'https://api.planningcenteronline.com/calendar/v2/event_instances?include=event,event.tags,event.tag_group_tags,event.tag_groups&order=start_at&where[after]=' . urlencode(date('c'));
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

        // Gather tags from included data
        $tags = [];
        if (!empty($data['included'])) {
            foreach ($data['included'] as $item) {
                if ($item['type'] === 'Tag' || $item['type'] === 'TagGroupTag') {
                    $tags[$item['id']] = $item['attributes']['name'];
                }
            }
        }
        asort($tags);

        // Gather events for single event dropdown
        $events = [];
        foreach ($data['data'] as $instance) {
            $event_id = $instance['relationships']['event']['data']['id'];
            $instance_id = $instance['id'];
            $title = '';
            $event_tags = [];
            if (!empty($data['included'])) {
                foreach ($data['included'] as $inc) {
                    if ($inc['type'] === 'Event' && $inc['id'] === $event_id) {
                        $title = $inc['attributes']['name'];
                        // Get tags for this event
                        if (!empty($inc['relationships']['tags']['data'])) {
                            foreach ($inc['relationships']['tags']['data'] as $tag_ref) {
                                $tag_id = $tag_ref['id'];
                                if (isset($tags[$tag_id])) {
                                    $event_tags[] = $tags[$tag_id];
                                }
                            }
                        }
                        break;
                    }
                }
            }
            $start_raw = $instance['attributes']['starts_at'];
            $start = date_i18n('D, M j Y g:ia', strtotime($start_raw));
            $events[] = [
                'event_id' => $event_id,
                'instance_id' => $instance_id,
                'title' => $title,
                'start' => $start,
                'start_raw' => $start_raw,
                'tags' => $event_tags,
            ];
        }

        // Tag multi-select
        echo '<label for="pco_tag_selector">Filter by tag(s):</label> ';
        if (empty($tags)) {
            echo '<span style="margin-left:10px;color:#888;">No tags used in upcoming events</span>';
        } else {
            echo '<select id="pco_tag_selector" multiple size="4" style="margin-right:20px;min-width:160px;">';
            foreach ($tags as $tag) {
                echo '<option value="' . esc_attr($tag) . '">' . esc_html($tag) . '</option>';
            }
            echo '</select>';
        }

        // Date range pickers
        echo '<label for="pco_start_date">Start date:</label> ';
        echo '<input type="date" id="pco_start_date" style="margin-right:10px;">';
        echo '<label for="pco_end_date">End date:</label> ';
        echo '<input type="date" id="pco_end_date" style="margin-right:20px;">';

        // Events dropdown (will be filtered by JS)
        echo '<br><br><label for="pco_event_selector">Or select a single event:</label> ';
        echo '<select id="pco_event_selector">';
        echo '<option value="">-- Select an event --</option>';
        foreach ($events as $event) {
            $option_label = esc_html($event['title'] . ' (' . $event['start'] . ')');
            $data_tags = esc_attr(implode(',', $event['tags']));
            $data_start = esc_attr($event['start_raw']);
            echo '<option value="' . esc_attr($event['event_id']) . '" data-instance="' . esc_attr($event['instance_id']) . '" data-tags="' . $data_tags . '" data-start="' . $data_start . '">' . $option_label . '</option>';
        }
        echo '</select>';
        ?>

        <div id="pco_shortcode_output" style="margin-top:20px;display:none;">
            <strong>Copy this shortcode:</strong>
            <input type="text" id="pco_shortcode_text" style="width: 400px;" readonly>
            <button type="button" class="button" onclick="document.getElementById('pco_shortcode_text').select();document.execCommand('copy');">Copy</button>
            <p style="margin-top:8px;font-size:0.95em;color:#666;">
                Use <code>type="event"</code> to show the next instance of a recurring event, or <code>type="instance"</code> to show this specific date/time.<br>
                If you use the tag or date filters, the shortcode will show a filtered list of events.
            </p>
        </div>
        <script>
        // Helper to get selected tags as array
        function getSelectedTags() {
            var sel = document.getElementById('pco_tag_selector');
            var tags = [];
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].selected) {
                    tags.push(sel.options[i].value);
                }
            }
            return tags;
        }

        // Filter event dropdown by selected tags and date range
        function filterEventsDropdown() {
            var tags = getSelectedTags();
            var start = document.getElementById('pco_start_date').value;
            var end = document.getElementById('pco_end_date').value;
            var eventSel = document.getElementById('pco_event_selector');
            for (var i = 1; i < eventSel.options.length; i++) { // skip first option
                var opt = eventSel.options[i];
                var eventTags = opt.getAttribute('data-tags').split(',');
                var eventStart = opt.getAttribute('data-start');
                var show = true;

                // Tag filter (all selected tags must be present)
                if (tags.length > 0) {
                    show = tags.some(function(tag) {
                        return eventTags.includes(tag);
                    });
                }

                // Date filter
                if (show && start) {
                    show = (eventStart >= start);
                }
                if (show && end) {
                    show = (eventStart <= end + 'T23:59:59');
                }

                opt.style.display = show ? '' : 'none';
            }
            // Reset selection if current is hidden
            if (eventSel.selectedIndex > 0 && eventSel.options[eventSel.selectedIndex].style.display === 'none') {
                eventSel.selectedIndex = 0;
            }
        }

        function buildShortcode() {
            var tags = getSelectedTags();
            var start = document.getElementById('pco_start_date').value;
            var end = document.getElementById('pco_end_date').value;
            var eventSel = document.getElementById('pco_event_selector');
            var eventId = eventSel.value;
            var instanceId = eventSel.options[eventSel.selectedIndex] ? eventSel.options[eventSel.selectedIndex].getAttribute('data-instance') : '';
            var output = document.getElementById('pco_shortcode_output');
            var input = document.getElementById('pco_shortcode_text');
            var shortcode = '';

            if (eventId && instanceId && eventSel.options[eventSel.selectedIndex].style.display !== 'none') {
                shortcode = '[pco_event id="' + instanceId + '" type="instance"]';
            } else {
                shortcode = '[pco_events';
                if (tags.length > 0) shortcode += ' tags="' + tags.join(',') + '"';
                if (start) shortcode += ' start="' + start + '"';
                if (end) shortcode += ' end="' + end + '"';
                shortcode += ']';
            }

            if (shortcode) {
                input.value = shortcode;
                output.style.display = 'block';
            } else {
                output.style.display = 'none';
            }
        }

        document.getElementById('pco_tag_selector').addEventListener('change', function() {
            document.getElementById('pco_event_selector').selectedIndex = 0;
            filterEventsDropdown();
            buildShortcode();
        });
        document.getElementById('pco_start_date').addEventListener('change', function() {
            document.getElementById('pco_event_selector').selectedIndex = 0;
            filterEventsDropdown();
            buildShortcode();
        });
        document.getElementById('pco_end_date').addEventListener('change', function() {
            document.getElementById('pco_event_selector').selectedIndex = 0;
            filterEventsDropdown();
            buildShortcode();
        });
        document.getElementById('pco_event_selector').addEventListener('change', function() {
            // Clear tag and date if event is chosen
            if (this.value) {
                document.getElementById('pco_tag_selector').selectedIndex = -1;
                document.getElementById('pco_start_date').value = '';
                document.getElementById('pco_end_date').value = '';
                filterEventsDropdown();
            }
            buildShortcode();
        });

        // Initial filter
        filterEventsDropdown();
        </script>
    </div>
    <?php
}