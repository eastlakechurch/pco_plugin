<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'pco_events_admin_menu');

function pco_events_admin_menu() {
    add_menu_page(
        'PCO Integrations',
        'PCO Integrations',
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

    add_submenu_page(
        'pco-events',
        'Sermons Settings',
        'Sermons',
        'manage_options',
        'pco-sermons-settings',
        'pco_sermons_settings_page'
    );
}

// About page content
function pco_events_about_page() {
    ?>
    <div class="wrap">
        <h1>About PCO Integrations</h1>

        <p><strong>PCO Integrations</strong> is a plugin that connects your WordPress site to Planning Center, allowing you to display Events, Groups, and Sermons with zero manual syncing.</p>

        <hr>

        <h2>Getting Started</h2>
        <ol>
            <li>Go to <strong>PCO Integrations > Settings</strong> in your WordPress Dashboard.</li>
            <li>Enter your Planning Center API Username and Password.</li>
            <li>Enter your plugin License Key (provided on purchase or signup).</li>
            <li>Click <strong>Test Connection</strong> to verify your API access.</li>
            <li>Once validated, you're ready to use the plugin!</li>
        </ol>

        <p><strong>Note:</strong> If your API connection fails, please check credentials and firewall restrictions. You can regenerate your credentials from the <a href="https://api.planningcenteronline.com/oauth/applications" target="_blank">Planning Center Developer Dashboard</a>.</p>

        <hr>

        <h2>Shortcodes</h2>

        <h3>📅 Events</h3>
        <p>Display upcoming events synced from Planning Center Calendar.</p>
        <ul>
            <li><code>[pco_events]</code> – All upcoming events.</li>
            <li><code>[pco_events tags="Youth,Sunday"]</code> – Filter by tag(s).</li>
            <li><code>[pco_events start="2025-06-01" end="2025-06-30"]</code> – Filter by date range.</li>
            <li><code>[pco_events show_description="false"]</code> – Hide event descriptions.</li>
            <li><code>[pco_event id="INSTANCE_ID" type="instance"]</code> – Single event instance.</li>
            <li><code>[pco_event id="EVENT_ID" type="event"]</code> – Show next instance of recurring event.</li>
        </ul>

        <h3>👥 Groups</h3>
        <p>Display your church groups in a filterable, searchable layout.</p>
        <ul>
            <li><code>[pco_groups]</code> – Display all active groups with filters by type, day, and location.</li>
        </ul>

        <h3>🎤 Sermons</h3>
        <p>Display the latest sermon from Planning Center Publishing.</p>
        <ul>
            <li><code>[planning_centre_video]</code> – Show the most recent sermon automatically.</li>
            <li><code>[planning_centre_title]</code> – Use the most recent sermon title on your page.</li>
            <li><code>[planning_centre_published]</code> – Use the most recent sermon date on your page.</li>
        </ul>

        <hr>

        <h2>Using the Shortcode Generator</h2>
        <p>Not sure how to build a shortcode? Use the <strong>Shortcode Generator</strong> tab:</p>
        <ol>
            <li>Go to <strong>PCO Integrations > Shortcode Generator</strong>.</li>
            <li>Select filters like Tags, Dates, or a specific Event.</li>
            <li>Toggle options like hiding images or tags.</li>
            <li>Copy and paste the generated shortcode into any page or post.</li>
        </ol>

        <hr>

        <h2>Styling the Plugin</h2>
        <p>Under the <strong>Styles</strong> tab, you can adjust colors, fonts, and layout settings for how events, groups, and sermons appear. These styles apply globally.</p>

        <hr>

        <h2>Cache & Refresh</h2>
        <p>To improve speed, the plugin caches your data. You can manually refresh the data:</p>
        <ul>
            <li>Go to <strong>Settings</strong> and click <strong>Refresh Cache</strong>.</li>
            <li>Or add <code>?refresh=true</code> to any page URL to force a live refresh.</li>
        </ul>

        <hr>

        <h2>License & Activation</h2>
        <ul>
            <li>Enter your License Key under <strong>Settings</strong>.</li>
            <li>Click <strong>Test Connection</strong> to verify it's active.</li>
            <li>You can also <strong>Deactivate</strong> the key for use on a different site.</li>
        </ul>
        <p>If your license is invalid or expired, some plugin functionality may be restricted.</p>

        <hr>

        <h2>Troubleshooting</h2>
        <ul>
            <li><strong>No events or groups showing?</strong> Check your API credentials and refresh the cache.</li>
            <li><strong>License not activating?</strong> Confirm your key is correct and hasn’t been used on another site.</li>
            <li><strong>Sermon not updating?</strong> Make sure a new sermon is uploaded in Planning Center Publishing before Monday 6pm.</li>
        </ul>

        <hr>

        <h2>Support</h2>
        <p>For help or feedback, contact us via <a href="mailto:josh@pcointegrations.com">josh@pcointegrations.com</a> or visit <a href="https://www.pcointegrations.com" target="_blank">pcointegrations.com</a>.</p>
    </div>
    <?php
}

function pco_events_settings_page() {
    ?>
    <div class="wrap">
        <h1>PCO Integrations Settings</h1>
        <div style="display:flex;gap:40px;align-items:flex-start;flex-wrap:wrap;">
            <div style="flex:1;min-width:300px;">
        <?php
        $username = pco_events_decrypt(get_option('pco_events_username'));
        $password = pco_events_decrypt(get_option('pco_events_password'));
        $license = get_option('pco_events_license_key');
        if (empty($username) || empty($password) || empty($license)) {
            echo '<div class="notice notice-warning"><p>Please enter your Planning Center API credentials and your license key to activate the plugin.</p></div>';
        } elseif (!empty($license)) {
            $is_valid = pco_events_validate_license_key($license);
            set_transient('pco_license_notice_suppressed', true, 10);
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

        <form method="post" action="<?php echo esc_url(admin_url('admin.php?page=pco-events-settings')); ?>">
    <?php wp_nonce_field('pco_events_refresh_cache', 'pco_events_nonce'); ?>
    <?php submit_button('Refresh Cache', 'secondary', 'pco_refresh_cache'); ?>
</form>
            </div>
            <div style="flex:1;min-width:300px;max-width:500px;background:#f9f9f9;padding:20px;border-left:3px solid #2271b1;">
                <h2>How to Get Your Planning Center API Token</h2>
                <p>To connect your Planning Center account to this app, you’ll need a Personal Access Token. It’s easy and takes less than a minute.</p>
                <ol>
                    <li><strong>Log in to Planning Center</strong><br>
                        Go to <a href="https://api.planningcenteronline.com/oauth/applications" target="_blank">this link</a> and log in.
                    </li>
                    <li><strong>Go to “Developer” Settings</strong><br>
                        Click your profile photo top right &gt; Choose “Developer.”
                    </li>
                    <li><strong>Create a Personal Access Token</strong><br>
                        Scroll to the Personal Access Tokens section and click “New Personal Access Token.”
                    </li>
                    <li><strong>Name the Token</strong><br>
                        Use a name like “PCO Integrations Plugin” and click “Create Token.”
                    </li>
                    <li><strong>Copy Your Token</strong><br>
                        You’ll see it once – copy it and paste it into this plugin's settings.
                    </li>
                </ol>
                <p><strong>⚠️ Keep this token secure.</strong><br>Don’t share it publicly or post it online.</p>
            </div>
        </div>
    </div>
    <?php
}

function pco_events_shortcode_generator_page() {
    ?>
    <div class="wrap">
        <h1>PCO Integrations Shortcode Generator</h1>
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

        // Add checkboxes for Hide Image and Hide Tags
        echo '<br><br>';
        echo '<label><input type="checkbox" id="pco_hide_image"> Hide event image</label> ';
        echo '<label style="margin-left:20px;"><input type="checkbox" id="pco_hide_tags"> Hide event tags</label>';

        // PCO Integrations - Events dropdown (will be filtered by JS)
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
        document.addEventListener('DOMContentLoaded', function() {
            function getSelectedTags() {
                var sel = document.getElementById('pco_tag_selector');
                var tags = [];
                if (!sel) return tags;
                for (var i = 0; i < sel.options.length; i++) {
                    if (sel.options[i].selected) {
                        tags.push(sel.options[i].value);
                    }
                }
                return tags;
            }

            function filterEventsDropdown() {
                var tags = getSelectedTags();
                var start = document.getElementById('pco_start_date') ? document.getElementById('pco_start_date').value : '';
                var end = document.getElementById('pco_end_date') ? document.getElementById('pco_end_date').value : '';
                var eventSel = document.getElementById('pco_event_selector');
                if (!eventSel) return;
                for (var i = 1; i < eventSel.options.length; i++) { // skip first option
                    var opt = eventSel.options[i];
                    var eventTags = opt.getAttribute('data-tags') ? opt.getAttribute('data-tags').split(',') : [];
                    var eventStart = opt.getAttribute('data-start');
                    var show = true;

                    // Tag filter (any selected tag must be present)
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
                var start = document.getElementById('pco_start_date') ? document.getElementById('pco_start_date').value : '';
                var end = document.getElementById('pco_end_date') ? document.getElementById('pco_end_date').value : '';
                var eventSel = document.getElementById('pco_event_selector');
                var eventId = eventSel && eventSel.value ? eventSel.value : '';
                var instanceId = (eventSel && eventSel.options[eventSel.selectedIndex]) ? eventSel.options[eventSel.selectedIndex].getAttribute('data-instance') : '';
                var output = document.getElementById('pco_shortcode_output');
                var input = document.getElementById('pco_shortcode_text');
                var shortcode = '';

                var hideImage = document.getElementById('pco_hide_image') ? document.getElementById('pco_hide_image').checked : false;
                var hideTags = document.getElementById('pco_hide_tags') ? document.getElementById('pco_hide_tags').checked : false;

                if (eventId && instanceId && eventSel.options[eventSel.selectedIndex].style.display !== 'none') {
                    shortcode = '[pco_event id="' + instanceId + '" type="instance"';
                    if (hideImage) shortcode += ' show_image="false"';
                    if (hideTags) shortcode += ' show_tags="false"';
                    shortcode += ']';
                } else {
                    shortcode = '[pco_events';
                    if (tags.length > 0) shortcode += ' tags="' + tags.join(',') + '"';
                    if (start) shortcode += ' start="' + start + '"';
                    if (end) shortcode += ' end="' + end + '"';
                    if (hideImage) shortcode += ' show_image="false"';
                    if (hideTags) shortcode += ' show_tags="false"';
                    shortcode += ']';
                }

                if (input && output) {
                    if (shortcode) {
                        input.value = shortcode;
                        output.style.display = 'block';
                    } else {
                        output.style.display = 'none';
                    }
                }
            }

            // Add event listeners only if elements exist
            var tagSelector = document.getElementById('pco_tag_selector');
            var startDate = document.getElementById('pco_start_date');
            var endDate = document.getElementById('pco_end_date');
            var eventSelector = document.getElementById('pco_event_selector');
            var hideImage = document.getElementById('pco_hide_image');
            var hideTags = document.getElementById('pco_hide_tags');

            if (tagSelector) {
                tagSelector.addEventListener('change', function() {
                    if (eventSelector) eventSelector.selectedIndex = 0;
                    filterEventsDropdown();
                    buildShortcode();
                });
            }
            if (startDate) {
                startDate.addEventListener('change', function() {
                    if (eventSelector) eventSelector.selectedIndex = 0;
                    filterEventsDropdown();
                    buildShortcode();
                });
            }
            if (endDate) {
                endDate.addEventListener('change', function() {
                    if (eventSelector) eventSelector.selectedIndex = 0;
                    filterEventsDropdown();
                    buildShortcode();
                });
            }
            if (eventSelector) {
                eventSelector.addEventListener('change', function() {
                    // Clear tag and date if event is chosen
                    if (this.value) {
                        if (tagSelector) tagSelector.selectedIndex = -1;
                        if (startDate) startDate.value = '';
                        if (endDate) endDate.value = '';
                        filterEventsDropdown();
                    }
                    buildShortcode();
                });
            }
            if (hideImage) {
                hideImage.addEventListener('change', buildShortcode);
            }
            if (hideTags) {
                hideTags.addEventListener('change', buildShortcode);
            }

            // Initial filter and shortcode build
            filterEventsDropdown();
            buildShortcode();
        });
        </script>
    </div>
    <?php
}

// Handle cache refresh for both events and groups
add_action('admin_init', function() {
    if (
        isset($_POST['pco_refresh_cache']) &&
        check_admin_referer('pco_events_refresh_cache', 'pco_events_nonce')
    ) {
        // Clear event cache (existing logic, if any)
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_pco_events_%' OR option_name LIKE '_transient_timeout_pco_events_%'");

        // Clear group cache
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_pco_groups_%' OR option_name LIKE '_transient_timeout_pco_groups_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_pco_group_location_%' OR option_name LIKE '_transient_timeout_pco_group_location_%'");
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_pco_group_types%' OR option_name LIKE '_transient_timeout_pco_group_types%'");

        set_transient('pco_events_settings_success', 'Event and group cache cleared.', 10);
        wp_safe_redirect(admin_url('admin.php?page=pco-events-settings'));
        exit;
    }
});