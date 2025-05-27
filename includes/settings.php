<?php

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
            echo '<textarea name="pco_events_custom_css" rows="5" cols="60">' . esc_textarea($value) . '</textarea>';
        },
        'pco-events-styles',
        'pco_events_styles_section_extra'
    );

    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'pco-events-settings') {
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

    // Validate license key immediately after it's saved and update license status.
    if (is_admin() && isset($_POST['option_page']) && $_POST['option_page'] === 'pco_events_settings_group') {
        if (isset($_POST['pco_events_license_key'])) {
            $key = sanitize_text_field($_POST['pco_events_license_key']);
            if (function_exists('pco_events_validate_license_key')) {
                $is_valid = pco_events_validate_license_key($key);
                error_log('License validated on save: ' . var_export($is_valid, true));
                update_option('pco_events_license_status', $is_valid ? 'valid' : 'invalid');
            }
        }
    }
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
}