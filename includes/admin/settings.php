<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

add_action('admin_init', 'pco_events_register_settings');

function pco_events_register_settings() {
    register_setting('pco_events_settings_group', 'pco_events_username');
    register_setting('pco_events_settings_group', 'pco_events_password');
    register_setting('pco_events_settings_group', 'pco_events_license_key');
    register_setting('pco_events_settings_group', 'pco_events_local_timezone');

    // Style-related options
    register_setting('pco_events_style_settings_group', 'pco_events_card_border_width');
    register_setting('pco_events_style_settings_group', 'pco_events_card_border_radius');
    register_setting('pco_events_style_settings_group', 'pco_events_image_padding');
    // Original style-related settings
    register_setting('pco_events_style_settings_group', 'pco_events_primary_color');
    register_setting('pco_events_style_settings_group', 'pco_events_card_background');
    register_setting('pco_events_style_settings_group', 'pco_events_title_color');
    register_setting('pco_events_style_settings_group', 'pco_events_font_size');
    register_setting('pco_events_style_settings_group', 'pco_events_font_family');
    register_setting('pco_events_style_settings_group', 'pco_events_custom_css');

    register_setting('pco_events_style_settings_group', 'pco_events_card_border_color');
    register_setting('pco_events_style_settings_group', 'pco_events_recurring_color');
    register_setting('pco_events_style_settings_group', 'pco_events_image_fill');

    // Encrypt credentials
    add_filter('pre_update_option_pco_events_username', function($value) {
        return pco_events_encrypt($value);
    });
    add_filter('pre_update_option_pco_events_password', function($value) {
        return pco_events_encrypt($value);
    });
    add_filter('pre_update_option_pco_events_custom_css', function($css) {
        // Remove PHP tags and encode HTML special chars
        $css = wp_strip_all_tags($css);
        $css = htmlspecialchars($css, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $css;
    });

    add_settings_section('pco_events_main', 'API Credentials', null, 'pco-events-settings');

    add_settings_field(
        'pco_events_username',
        'Username (API ID)',
        'pco_username_field_html',
        'pco-events-settings',
        'pco_events_main'
    );

    add_settings_field(
        'pco_events_password',
        'Password (PAT Token)',
        'pco_password_field_html',
        'pco-events-settings',
        'pco_events_main'
    );

    add_settings_field(
        'pco_events_license_key',
        'License Key',
        'pco_events_license_key_field_html',
        'pco-events-settings',
        'pco_events_main'
    );

    add_settings_field(
        'pco_events_local_timezone',
        'Local Timezone',
        function() {
            $selected = get_option('pco_events_local_timezone', date_default_timezone_get());
            echo '<select name="pco_events_local_timezone">';
            foreach (timezone_identifiers_list() as $tz) {
                echo '<option value="' . esc_attr($tz) . '"' . selected($selected, $tz, false) . '>' . esc_html($tz) . '</option>';
            }
            echo '</select>';
            echo '<p class="description">Used for formatting event start times. Choose your local timezone (e.g. Australia/Perth).</p>';
        },
        'pco-events-settings',
        'pco_events_main'
    );

    add_settings_section('pco_events_styles_section_card', 'Card Styling', null, 'pco-events-styles');
    add_settings_section('pco_events_styles_section_image', 'Image Styling', null, 'pco-events-styles');
    add_settings_section('pco_events_styles_section_typography', 'Typography', null, 'pco-events-styles');
    add_settings_section('pco_events_styles_section_extra', 'Custom CSS', null, 'pco-events-styles');
    add_settings_section('pco_events_styles_section_advanced', 'Advanced', null, 'pco-events-styles');


    // Card Styling fields
    add_settings_field(
        'pco_events_primary_color',
        'Primary Color (for Tags)',
        function() {
            echo '<input type="color" name="pco_events_primary_color" value="' . esc_attr(get_option('pco_events_primary_color', '#0073aa')) . '">';
        },
        'pco-events-styles',
        'pco_events_styles_section_card'
    );

    add_settings_field(
        'pco_events_card_background',
        'Card Background Color',
        function() {
            echo '<input type="color" name="pco_events_card_background" value="' . esc_attr(get_option('pco_events_card_background', '#fafafa')) . '">';
        },
        'pco-events-styles',
        'pco_events_styles_section_card'
    );

    add_settings_field(
        'pco_events_card_border_width',
        'Card Border Width',
        function() {
            $value = get_option('pco_events_card_border_width', '1px');
            echo '<input type="text" name="pco_events_card_border_width" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Specify border width (e.g. 1px).</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_card'
    );

    add_settings_field(
        'pco_events_card_border_radius',
        'Card Border Radius',
        function() {
            $value = get_option('pco_events_card_border_radius', '4px');
            echo '<input type="text" name="pco_events_card_border_radius" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Specify border radius (e.g. 4px).</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_card'
    );

    add_settings_field(
        'pco_events_card_border_color',
        'Card Border Color',
        function() {
            echo '<input type="color" name="pco_events_card_border_color" value="' . esc_attr(get_option('pco_events_card_border_color', '#e0e0e0')) . '">';
        },
        'pco-events-styles',
        'pco_events_styles_section_card'
    );

    // Add to "Card Styling" section for Next Date toggle
    add_settings_field(
        'pco_events_show_next_date',
        'Show "Next Date" for Recurring PCO Integrations - Events',
        function() {
            $value = get_option('pco_events_show_next_date', 'yes');
            echo '<select name="pco_events_show_next_date">
                <option value="yes"' . selected($value, 'yes', false) . '>Show</option>
                <option value="no"' . selected($value, 'no', false) . '>Hide</option>
            </select>';
            echo '<p class="description">Show or hide the "Next Date" label for recurring events.</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_card'
    );

    // Add to "Card Styling" section for Tag visibility toggle
    add_settings_field(
        'pco_events_show_tags',
        'Show Event Tags',
        function() {
            $value = get_option('pco_events_show_tags', 'yes');
            echo '<select name="pco_events_show_tags">
                <option value="yes"' . selected($value, 'yes', false) . '>Show</option>
                <option value="no"' . selected($value, 'no', false) . '>Hide</option>
            </select>';
            echo '<p class="description">Show or hide event tags on event cards.</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_card'
    );

    // Image Styling fields
    add_settings_field(
        'pco_events_image_padding',
        'Image Padding',
        function() {
            $value = get_option('pco_events_image_padding', '5px');
            echo '<input type="text" name="pco_events_image_padding" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Specify image padding (e.g. 5px).</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_image'
    );

    add_settings_field(
        'pco_events_image_fill',
        'Image Fill Container',
        function() {
            $value = get_option('pco_events_image_fill', 'false');
            echo '<select name="pco_events_image_fill">
                <option value="false"' . selected($value, 'false', false) . '>Default</option>
                <option value="true"' . selected($value, 'true', false) . '>Fill Card</option>
            </select>';
        },
        'pco-events-styles',
        'pco_events_styles_section_image'
    );

    // Typography fields
    add_settings_field(
        'pco_events_title_color',
        'Event Title Color',
        function() {
            echo '<input type="color" name="pco_events_title_color" value="' . esc_attr(get_option('pco_events_title_color', '#222222')) . '">';
        },
        'pco-events-styles',
        'pco_events_styles_section_typography'
    );

    add_settings_field(
        'pco_events_font_size',
        'Font Size',
        function() {
            $value = get_option('pco_events_font_size', 'normal');
            echo '<select name="pco_events_font_size">
                <option value="small"' . selected($value, 'small', false) . '>Small</option>
                <option value="normal"' . selected($value, 'normal', false) . '>Normal</option>
                <option value="large"' . selected($value, 'large', false) . '>Large</option>
            </select>';
        },
        'pco-events-styles',
        'pco_events_styles_section_typography'
    );

    add_settings_field(
        'pco_events_font_family',
        'Font Family',
        function() {
            $value = get_option('pco_events_font_family', '');
            echo '<input type="text" name="pco_events_font_family" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Use any valid CSS font-family value (e.g. Arial, sans-serif).</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_typography'
    );

    add_settings_field(
        'pco_events_recurring_color',
        'Recurring Label Color',
        function() {
            echo '<input type="color" name="pco_events_recurring_color" value="' . esc_attr(get_option('pco_events_recurring_color', '#0073aa')) . '">';
            echo '<p class="description">This controls the color of the “Next Date:” label in recurring events.</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_typography'
    );

    // Custom CSS field
    add_settings_field(
        'pco_events_custom_css',
        'Custom CSS',
        function() {
            $value = get_option('pco_events_custom_css', '');
            echo '<textarea name="pco_events_custom_css" rows="7" cols="50" class="large-text code">' . esc_textarea($value) . '</textarea>';
            echo '<p class="description">Only trusted administrators should use this field. Malicious CSS can affect your site appearance.</p>';
        },
        'pco-events-styles',
        'pco_events_styles_section_advanced'
    );

    // --- GROUPS STYLE SETTINGS ---

    // Register the settings group for groups styles
    register_setting('pco_groups_style_settings_group', 'pco_groups_card_background');

    // Add a section for group card styling
    add_settings_section(
        'pco_groups_styles_section_card',
        'Group Card Styling',
        null,
        'pco-groups-styles'
    );

    // Add a sample field for group card background color
    add_settings_field(
        'pco_groups_card_background',
        'Group Card Background Color',
        function() {
            echo '<input type="color" name="pco_groups_card_background" value="' . esc_attr(get_option('pco_groups_card_background', '#fafafa')) . '">';
        },
        'pco-groups-styles',
        'pco_groups_styles_section_card'
    );

    add_settings_field(
        'pco_groups_title_color',
        'Group Title Color',
        function() {
            echo '<input type="color" name="pco_groups_title_color" value="' . esc_attr(get_option('pco_groups_title_color', '#1a1a1a')) . '">';
        },
        'pco-groups-styles',
        'pco_groups_styles_section_card'
    );

    add_settings_field(
        'pco_groups_text_color',
        'Group Text Color',
        function() {
            echo '<input type="color" name="pco_groups_text_color" value="' . esc_attr(get_option('pco_groups_text_color', '#6b6b6b')) . '">';
        },
        'pco-groups-styles',
        'pco_groups_styles_section_card'
    );

    register_setting('pco_groups_style_settings_group', 'pco_groups_title_color');
    register_setting('pco_groups_style_settings_group', 'pco_groups_text_color');

    if (
        is_admin() &&
        isset($_GET['page']) &&
        sanitize_text_field($_GET['page']) === 'pco-events-settings' &&
        !(isset($_POST['pco_refresh_license_submit']) || isset($_POST['pco_events_license_key']))
    ) {
        $license_key = get_option('pco_events_license_key');
        $license_status = get_option('pco_events_license_status');

        if (empty($license_key) || $license_status !== 'valid') {
            add_settings_error(
                'pco_events_license_key',
                'pco_events_license_key_missing',
                '⚠️ Please enter and activate your license key to enable full plugin functionality.',
                'error'
            );
        }
    }

    // Validate license key immediately after it's saved and update license status/expiry.
    if (current_user_can('manage_options') && is_admin() && isset($_POST['option_page']) && sanitize_text_field($_POST['option_page']) === 'pco_events_settings_group') {
        if (
            isset($_POST['pco_events_license_key']) ||
            (isset($_POST['pco_refresh_license_submit']) && sanitize_text_field($_POST['pco_refresh_license_submit']))
        ) {
            $key = sanitize_text_field($_POST['pco_events_license_key']);
            if (function_exists('pco_events_validate_license_key')) {
                // Force re-check of license (bypass any transient)
                delete_transient('pco_events_license_status_cache');

                $response = pco_events_validate_license_key($key, true); // true = force refresh

                if (is_array($response)) {
                    $status = $response['valid'] ? 'valid' : 'invalid';
                    update_option('pco_events_license_status', $status);
                    if (!empty($response['expires_at'])) {
                        update_option('pco_events_license_expires_at', $response['expires_at']);
                    }
                } else {
                    update_option('pco_events_license_status', 'invalid');
                }

                // --- Debug Logging ---
                error_log('🧪 License Key Submitted: ' . $key);
                error_log('🧪 Validation Response: ' . print_r($response, true));
                error_log('🧪 Resulting License Status: ' . get_option('pco_events_license_status'));
                error_log('🧪 License Expiry Stored: ' . get_option('pco_events_license_expires_at'));
            }
        }
    }

    // Register settings for sermons cache refresh schedule
    register_setting('pco_sermons_settings_group', 'pco_sermons_cache_refresh_day');
    register_setting('pco_sermons_settings_group', 'pco_sermons_cache_refresh_time');
    // Register new sermon autoplay options
    register_setting('pco_sermons_settings_group', 'pco_sermons_autoplay');
    register_setting('pco_sermons_settings_group', 'pco_sermons_autoplay_delay');
    register_setting('pco_sermons_settings_group', 'pco_sermons_channel_id');

    // Add settings section if not already present
    add_settings_section(
        'pco_sermons_cache_section',
        'Sermons Cache Refresh Schedule',
        null,
        'pco-sermons-settings'
    );

    // Day of week dropdown
    add_settings_field(
        'pco_sermons_cache_refresh_day',
        'Cache Refresh Day',
        function() {
            $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
            $selected = get_option('pco_sermons_cache_refresh_day', 'Monday');
            echo '<select name="pco_sermons_cache_refresh_day">';
            foreach ($days as $day) {
                echo '<option value="' . esc_attr($day) . '"' . selected($selected, $day, false) . '>' . esc_html($day) . '</option>';
            }
            echo '</select>';
        },
        'pco-sermons-settings',
        'pco_sermons_cache_section'
    );

    // Time input (24-hour format)
    add_settings_field(
        'pco_sermons_cache_refresh_time',
        'Cache Refresh Time',
        function() {
            $value = get_option('pco_sermons_cache_refresh_time', '18:00');
            echo '<input type="time" name="pco_sermons_cache_refresh_time" value="' . esc_attr($value) . '">';
            echo '<p class="description">Set the time (24-hour) for cache refresh, e.g. 18:00 for 6:00pm.</p>';
        },
        'pco-sermons-settings',
        'pco_sermons_cache_section'
    );

    // Autoplay toggle
    add_settings_field(
        'pco_sermons_autoplay',
        'Enable Autoplay',
        function() {
            $value = get_option('pco_sermons_autoplay', 'yes');
            echo '<select name="pco_sermons_autoplay">
                <option value="yes"' . selected($value, 'yes', false) . '>Yes</option>
                <option value="no"' . selected($value, 'no', false) . '>No</option>
            </select>';
            echo '<p class="description">Toggle whether the sermon video should autoplay when loaded.</p>';
        },
        'pco-sermons-settings',
        'pco_sermons_cache_section'
    );

    // Autoplay delay
    add_settings_field(
        'pco_sermons_autoplay_delay',
        'Autoplay Delay (s)',
        function() {
            $value = get_option('pco_sermons_autoplay_delay', '1');
            echo '<input type="number" name="pco_sermons_autoplay_delay" value="' . esc_attr($value) . '" class="small-text">';
            echo '<p class="description">Delay in seconds before autoplay starts (e.g., 1 = 1 second).</p>';
        },
        'pco-sermons-settings',
        'pco_sermons_cache_section'
    );

    // Channel ID
    add_settings_field(
        'pco_sermons_channel_id',
        'Planning Center Channel ID',
        function() {
            $value = get_option('pco_sermons_channel_id', '');
            echo '<input type="text" name="pco_sermons_channel_id" value="' . esc_attr($value) . '" class="regular-text">';
            echo '<p class="description">Optional. If set, ensures the plugin filters videos from this specific Planning Center channel.</p>';
        },
        'pco-sermons-settings',
        'pco_sermons_cache_section'
    );
}

function pco_username_field_html() {
    $value = pco_events_decrypt(get_option('pco_events_username'));
    echo '<input type="text" name="pco_events_username" value="' . esc_attr($value) . '" style="width: 400px;">';
}

function pco_password_field_html() {
    $value = pco_events_decrypt(get_option('pco_events_password'));
    echo '<input type="password" name="pco_events_password" value="' . esc_attr($value) . '" style="width: 400px;">';
}

function pco_events_license_key_field_html() {
    $value = get_option('pco_events_license_key');
    echo '<input type="text" name="pco_events_license_key" value="' . esc_attr($value) . '" style="width: 400px;">';
    echo "<p class='description'>Enter the license key provided after purchase.</p>";

    // Real-time license validation display
    $status = get_option('pco_events_license_status');
    error_log('🔍 License Status Retrieved: ' . $status);
    if ($status === 'valid') {
        $raw_expires = get_option('pco_events_license_expires_at', '');
        error_log('📅 License Expiry Retrieved: ' . $raw_expires);
        $expires = $raw_expires ? date('j F Y, g:ia', strtotime($raw_expires)) : 'Unknown';
        echo '<p style="color: green;"><strong>✔ Valid license.</strong> Expires: ' . esc_html($expires) . '</p>';

        // Add Refresh License button
        echo '<p><button type="submit" name="pco_refresh_license_submit" class="button">Refresh License</button></p>';
    } elseif ($status === 'invalid') {
        $raw_expires = get_option('pco_events_license_expires_at', '');
        error_log('📅 License Expiry Retrieved: ' . $raw_expires);
        $expired = $raw_expires && strtotime($raw_expires) < time();
        $message = $expired
            ? '<strong>✖ Your license has expired.</strong>'
            : '<strong>✖ Invalid license key.</strong>';
        echo '<p style="color: red;">' . $message . '</p>';
    }

}
// --- SERMONS SETTINGS PAGE DISPLAY ---

function pco_sermons_settings_page() {
    ?>
    <div class="wrap">
        <h2>Planning Center – Sermons Settings</h2>
        <?php if ($message = get_transient('pco_sermons_settings_success')) : ?>
            <div id="message" class="updated notice is-dismissible">
                <p><?php echo esc_html($message); ?></p>
            </div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('pco_sermons_settings_group');
            do_settings_sections('pco-sermons-settings');
            submit_button('Save Settings');
            ?>
        </form>
        <form method="post">
            <?php wp_nonce_field('pco_sermons_refresh_cache', 'pco_sermons_nonce'); ?>
            <p><input type="submit" name="pco_sermons_refresh_cache" class="button" value="Refresh Sermons Cache Now"></p>
        </form>
    </div>
    <?php
}