<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'api.php';
require_once plugin_dir_path(__FILE__) . 'render.php';
require_once plugin_dir_path(__FILE__) . 'shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'cache.php';
require_once plugin_dir_path(__FILE__) . '/../admin/settings.php';

/**
 * Debug output.
 *
 * If the URL query parameter "pcp_debug=1" is appended, output the raw API response.
 */
function pcp_debug_output() {
    if ( isset( $_GET['pcp_debug'] ) && '1' === sanitize_text_field( $_GET['pcp_debug'] ) ) {
        // Output decrypted credentials for debug
        $token_id = pco_events_decrypt(get_option('pco_events_username'));
        $token_secret = pco_events_decrypt(get_option('pco_events_password'));

        echo '<pre style="background:#ffefef; padding:10px;">';
        echo "🔐 Decrypted Token ID: " . htmlentities($token_id) . "\n";
        echo "🔐 Decrypted Token Secret: " . htmlentities($token_secret) . "\n";
        echo '</pre>';

        // Make test API call
        $api_url = 'https://api.planningcenteronline.com/publishing/v2/episodes';
        $args    = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $token_id . ':' . $token_secret )
            )
        );
        $response = wp_remote_get( $api_url, $args );
        echo '<pre style="background:#f5f5f5; padding:10px;">';
        if ( is_wp_error( $response ) ) {
            echo '❌ Error fetching API: ' . $response->get_error_message();
        } else {
            echo '✅ API Response: ' . "\n\n";
            echo wp_remote_retrieve_body( $response );
        }
        echo '</pre>';
    }
}
add_action( 'wp_footer', 'pcp_debug_output' );