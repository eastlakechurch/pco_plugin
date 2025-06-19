<?php
// Exit if uninstall not called from WordPress
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// Delete plugin options
delete_option('pco_events_license_key');
delete_option('pco_events_license_status');
delete_option('pco_events_license_expires_at');
delete_option('pco_events_username');
delete_option('pco_events_password');

delete_option('pco_sermons_cache_refresh_day');
delete_option('pco_sermons_cache_refresh_time');
delete_option('pco_sermons_autoplay');
delete_option('pco_sermons_autoplay_delay');
delete_option('pco_sermons_channel_id');

delete_option('pco_events_card_border_radius');
delete_option('pco_events_card_border_color');
delete_option('pco_events_show_next_date');
delete_option('pco_events_show_tags');
delete_option('pco_events_image_padding');
delete_option('pco_events_image_fill');
delete_option('pco_events_title_color');
delete_option('pco_events_font_size');
delete_option('pco_events_font_family');
delete_option('pco_events_recurring_color');
delete_option('pco_events_custom_css');

delete_option('pco_groups_card_background');
delete_option('pco_groups_title_color');
delete_option('pco_groups_text_color');

// Delete transients
delete_transient('pco_events_settings_error');
delete_transient('pco_events_settings_success');
delete_transient('pco_events_license_status_cache');