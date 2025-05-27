<?php
/*
Plugin Name: PCO Events for WordPress
Description: Display and filter upcoming Planning Center Calendar events in your WordPress site using shortcodes. Includes caching, tags, and recurring event support.
Version: 1.0.0
Author: Josh Edwards
Plugin URI: https://pcointegrations.lemonsqueezy.com
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

require_once plugin_dir_path(__FILE__) . 'includes/encryption.php';
require_once plugin_dir_path(__FILE__) . 'includes/api.php';
require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/styles.php';
require_once plugin_dir_path(__FILE__) . 'includes/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/license.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/preview.php';

add_action('admin_notices', 'pco_events_admin_notices');
function pco_events_admin_notices() {
    if ($msg = get_transient('pco_events_settings_error')) {
        echo '<div class="notice notice-error"><p>' . esc_html($msg) . '</p></div>';
        delete_transient('pco_events_settings_error');
    }
    if ($msg = get_transient('pco_events_settings_success')) {
        echo '<div class="notice notice-success"><p>' . esc_html($msg) . '</p></div>';
        delete_transient('pco_events_settings_success');
    }
}
