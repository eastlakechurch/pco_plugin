<?php
/*
Plugin Name: PCO Integrations for WordPress
Description: Integrate Planning Center with WordPress for events, groups, and publishing. Display and filter upcoming Planning Center data using shortcodes. Includes caching, tags, and recurring event support.
Version: 1.3.2
Author: Josh Edwards
Plugin URI: https://github.com/eastlakechurch/pco_plugin
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

require_once __DIR__ . '/plugin-update-checker/plugin-update-checker.php';

$myUpdateChecker = YahnisElsts\PluginUpdateChecker\v5p6\PucFactory::buildUpdateChecker(
    'https://raw.githubusercontent.com/eastlakechurch/pco-events-updates/main/metadata.json',
    __FILE__,
    'pco_plugin'
);

require_once plugin_dir_path(__FILE__) . 'includes/encryption.php';
require_once plugin_dir_path(__FILE__) . 'includes/events/events-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/events/events-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/styles.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/license.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/admin-menu.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin/preview.php';
require_once plugin_dir_path(__FILE__) . 'includes/groups/groups-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/sermons/sermons.php';
require_once plugin_dir_path(__FILE__) . 'includes/groups/groups-shortcodes.php';

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

if (!defined('PCO_EVENTS_LS_API_KEY')) {
    define('PCO_EVENTS_LS_API_KEY', 'eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9.eyJhdWQiOiI5NGQ1OWNlZi1kYmI4LTRlYTUtYjE3OC1kMjU0MGZjZDY5MTkiLCJqdGkiOiJiZTk4MmI1Yjg5YTdlMTUyMzFiNTdiZjUzMTM4YzZmOTM1NWEwOGI4M2VkZGQ2OWZhYTlkYTRhYTNlZTU2NTM3ZjMwOTk5NGQ3ODZiZmUzZCIsImlhdCI6MTc0ODI2NTI3OS42MTM5NzIsIm5iZiI6MTc0ODI2NTI3OS42MTM5NzUsImV4cCI6MjA2Mzc5ODA3OS41ODA3NTYsInN1YiI6IjQ5NTEwNDUiLCJzY29wZXMiOltdfQ.a254BEGjP3KuDwVFdAKsqi01Mm8UOAQ9YkHSEgkZG4-I5mJ1vxCWflHDhbdLQUQuCJkePJdNm69etnaGishvHMWGUi1XS3V4HZsFLfGzoCvYc4ts7aAp4EI5euHhsrPOaD14e5i5AoHCDF47j263q1PPAJ8orRj14YXaa-7aDDe7MsnN2PHVvUrJzqU4EAjiBqMAuQr2qgwku0Rgp819ugzbkUqsZ4DCOpFKKRLZ4fl1FkwyuKjTcJzd-ZopR9cGwUYRpKpi678OjtmAj6PN1tEfik4zp82L_JSrIhIt_FDok5mwEawTCG4sTA59Y1ePqVreHgDXnstmo4axOXqymFJeS2VmPaW0CAc6frMe7QOy3rA5E1rWJyORDb2wMrC5kGi-H6SUNSaDKW1IrnLWeDNY8FJ069Um8IAZVofXwRGXHWG-7ucPmxHyUn0NRAdvAVG54FwHLWuiYlgaL2EYDyx14pFtsIbueVck8WXx4L4o-SsFvbuzU54cJduauA-a');
}
if (!defined('PCO_EVENTS_LS_PRODUCT_ID')) {
    define('PCO_EVENTS_LS_PRODUCT_ID', '532075');
}

add_action('wp_enqueue_scripts', function() {
    wp_register_style('pco-groups-style', plugins_url('assets/groups.css', __FILE__));
    wp_register_script('pco-groups-script', plugins_url('assets/groups.js', __FILE__), ['jquery'], null, true);
});
