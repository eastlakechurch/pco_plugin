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
            <li><code>[pco_events]</code> – shows all upcoming events.</li>
            <li><code>[pco_events show_description="false"]</code> – hides the event description.</li>
            <li><code>[pco_featured_events tags="featured"]</code> – only events tagged with “featured”.</li>
            <li><code>[pco_featured_events tags="youth,camp" count="5"]</code> – max 5 events with those tags.</li>
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