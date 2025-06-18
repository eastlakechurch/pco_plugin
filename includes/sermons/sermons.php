<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function pcp_license_message() {
    return '<p><strong>This plugin is not activated. Please enter a valid license key in the settings page.</strong></p>';
}

// Use the main plugin's encrypted API credentials
$token_id = pco_events_decrypt(get_option('pco_events_username'));
$token_secret = pco_events_decrypt(get_option('pco_events_password'));

/**
 * Calculate the number of seconds until the next update time (Monday at 6pm).
 *
 * @return int Seconds until next Monday 6pm.
 */
function pcp_get_next_update_interval() {
    $current_time = current_time('timestamp');
    // If today is Monday and current time is before 6pm, use today's 6pm.
    if ( date('N', $current_time) == 1 && date('H:i', $current_time) < '18:00' ) {
        $next_update = strtotime( date('Y-m-d', $current_time) . ' 18:00:00' );
    } else {
        // Otherwise, use next Monday at 6pm.
        $next_update = strtotime( 'next monday 18:00:00', $current_time );
    }
    return $next_update - $current_time;
}

/**
 * Fetch the latest episode from the Planning Centre Publishing API.
 *
 * Uses a transient to cache the result until next Monday at 6pm.
 *
 * @return array|false The latest episode array on success, false on failure.
 */
function pcp_fetch_latest_episode() {
    $episode = get_transient( 'pcp_latest_episode' );

    // If bypassing cache OR no transient OR transient expired
    if ( isset( $_GET['pcp_nocache'] ) && '1' === $_GET['pcp_nocache'] || false === $episode ) {
        return pcp_update_latest_episode(); // Force fresh fetch
    }

    return $episode;
}

/**
 * Fetch and update the latest episode.
 *
 * @return array|false
 */
function pcp_update_latest_episode() {
    global $token_id, $token_secret;
    $channel_id = get_option('pco_sermons_channel_id');
    $api_url = $channel_id
        ? 'https://api.planningcenteronline.com/publishing/v2/channels/' . $channel_id . '/episodes'
        : 'https://api.planningcenteronline.com/publishing/v2/episodes';

    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( $token_id . ':' . $token_secret )
        )
    );

    $response = wp_remote_get( $api_url, $args );
    if ( is_wp_error( $response ) ) {
        error_log( 'PCP API error: ' . $response->get_error_message() );
        return false;
    }

    $response_code = wp_remote_retrieve_response_code( $response );
    if ( 200 !== $response_code ) {
        error_log( 'PCP API returned status code: ' . $response_code );
        error_log( 'PCP API response: ' . wp_remote_retrieve_body( $response ) );
        return false;
    }

    $body = wp_remote_retrieve_body( $response );
    $data = json_decode( $body, true );

    if ( empty($channel_id) && isset($data['data'][0]['relationships']['channel']['data']['id']) ) {
        error_log('Detected default Publishing Channel ID: ' . $data['data'][0]['relationships']['channel']['data']['id']);
    }

    if ( isset( $data['data'] ) && is_array( $data['data'] ) && ! empty( $data['data'] ) ) {
        $episodes = $data['data'];
        $latest_episode = $episodes[ count( $episodes ) - 1 ];

        // Calculate seconds until next Monday 6pm
        $expiration = pcp_get_next_update_interval();

        set_transient( 'pcp_latest_episode', $latest_episode, $expiration );

        return $latest_episode;
    }

    return false;
}

/**
 * Debug output.
 *
 * If the URL query parameter "pcp_debug=1" is appended, output the raw API response.
 */
function pcp_debug_output() {
    if ( isset( $_GET['pcp_debug'] ) && '1' === $_GET['pcp_debug'] ) {
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

/**
 * Helper function to convert a YouTube URL to an embed URL.
 *
 * Supports standard URLs, shortened URLs, and live URLs.
 *
 * @param string $url The original YouTube URL.
 * @return string The embed URL.
 */
function pcp_convert_youtube_url( $url ) {
    $parsed_url = parse_url( $url );
    if ( isset( $parsed_url['host'] ) &&
         ( false !== strpos( $parsed_url['host'], 'youtube.com' ) || false !== strpos( $parsed_url['host'], 'youtu.be' ) ) ) {
        
        // Standard YouTube URL with query string.
        if ( isset( $parsed_url['query'] ) && false !== strpos( $url, 'watch?v=' ) ) {
            parse_str( $parsed_url['query'], $query );
            if ( isset( $query['v'] ) ) {
                return 'https://www.youtube.com/embed/' . $query['v'];
            }
        }
        // Shortened youtu.be URL.
        elseif ( false !== strpos( $parsed_url['host'], 'youtu.be' ) ) {
            $path = trim( $parsed_url['path'], '/' );
            return 'https://www.youtube.com/embed/' . $path;
        }
        // YouTube Live URL (e.g., https://youtube.com/live/VIDEO_ID?feature=share).
        elseif ( isset( $parsed_url['path'] ) && false !== strpos( $parsed_url['path'], '/live/' ) ) {
            $parts = explode( '/live/', $parsed_url['path'] );
            if ( isset( $parts[1] ) && ! empty( $parts[1] ) ) {
                $video_id = $parts[1];
                return 'https://www.youtube.com/embed/' . $video_id;
            }
        }
    }
    // Fallback to the original URL.
    return $url;
}

/**
 * Shortcode: [planning_centre_video]
 *
 * Outputs the YouTube video embed.
 * Uses the library_video_url field for the video and library_video_thumbnail_url for a thumbnail.
 * The thumbnail is shown for 2 seconds before the video auto-plays on mute with minimal branding.
 * The video fills the available width of its container while maintaining its 16:9 aspect ratio.
 *
 * @return string The video embed HTML.
 */
function pcp_video_shortcode() {
    $license_status = get_option('pco_events_license_status');
    if ($license_status !== 'valid') {
        return pcp_license_message();
    }

    $episode = pcp_fetch_latest_episode();
    if ( ! $episode ) {
        return '<p>No episode found.</p>';
    }

    $attributes    = isset( $episode['attributes'] ) ? $episode['attributes'] : array();
    $video_url     = isset( $attributes['library_video_url'] ) ? $attributes['library_video_url'] : '';
    $thumbnail_url = isset( $attributes['library_video_thumbnail_url'] ) ? $attributes['library_video_thumbnail_url'] : '';

    if ( empty( $video_url ) || empty( $thumbnail_url ) ) {
        return '<p>Video information not available.</p>';
    }

    // Convert to YouTube embed URL.
    $embed_url = pcp_convert_youtube_url( $video_url );

    $autoplay = get_option('pco_sermons_autoplay', 1);
    $delay = intval( get_option('pco_sermons_autoplay_delay', 2) );

    ob_start();
    ?>
    <div class="pcp-video-container" style="position: relative; width: 100%; margin: auto;">
        <div id="pcp-video-thumb" style="background-image: url('<?php echo esc_url( $thumbnail_url ); ?>'); background-size: cover; width: 100%; height: 0; padding-bottom: 56.25%; position: relative; overflow: hidden;">
        </div>
    </div>
    <script type="text/javascript">
        setTimeout(function(){
            console.log("Replacing thumbnail with YouTube video. Embed URL: <?php echo esc_js( $embed_url ); ?>");
            var container = document.getElementById("pcp-video-thumb");
            if (container) {
                container.innerHTML = '<iframe src="<?php echo esc_url( $embed_url ); ?>?' +
                    '<?php echo $autoplay ? 'autoplay=1&mute=1&' : ''; ?>modestbranding=1&rel=0&playsinline=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%;"></iframe>';
            }
        }, <?php echo esc_js( $delay * 1000 ); ?>); // Delay from settings
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode( 'planning_centre_video', 'pcp_video_shortcode' );

/**
 * Shortcode: [planning_centre_title]
 *
 * Outputs the episode title using the 'title' field.
 *
 * @return string The episode title.
 */
function pcp_title_shortcode() {
    $license_status = get_option('pco_events_license_status');
    if ($license_status !== 'valid') {
        return pcp_license_message();
    }

    $episode = pcp_fetch_latest_episode();
    if ( ! $episode ) {
        return '<p>No episode found.</p>';
    }
    $attributes = isset( $episode['attributes'] ) ? $episode['attributes'] : array();
    $title      = isset( $attributes['title'] ) ? $attributes['title'] : 'No title available';
    return esc_html( $title );
}
add_shortcode( 'planning_centre_title', 'pcp_title_shortcode' );

/**
 * Shortcode: [planning_centre_published]
 *
 * Outputs the published date using the 'published_live_at' field.
 * The date is converted to the format "Day DD.MM.YYYY" (e.g. Sun 4.6.2025), stripping out time and other details.
 *
 * @return string The formatted published date.
 */
function pcp_published_shortcode() {
    $license_status = get_option('pco_events_license_status');
    if ($license_status !== 'valid') {
        return pcp_license_message();
    }

    $episode = pcp_fetch_latest_episode();
    if ( ! $episode ) {
        return '<p>No episode found.</p>';
    }
    $attributes = isset( $episode['attributes'] ) ? $episode['attributes'] : array();
    $published  = isset( $attributes['published_live_at'] ) ? $attributes['published_live_at'] : '';

    if ( ! $published ) {
        return 'No publish date available';
    }
    
    // Convert the published date to a timestamp.
    $timestamp = strtotime( $published );
    if ( ! $timestamp ) {
        return esc_html( $published );
    }
    
    // Format the date as "Day DD.MM.YYYY" (e.g., Sun 4.6.2025)
    $formatted_date = date( "D j.n.Y", $timestamp );
    
    return esc_html( $formatted_date );
}
add_shortcode( 'planning_centre_published', 'pcp_published_shortcode' );

// Manual Update Trigger: Visit ?pcp_update=1 to force update
function pcp_manual_update_trigger() {
    if ( isset( $_GET['pcp_update'] ) && '1' === $_GET['pcp_update'] ) {
        $episode = pcp_update_latest_episode();

        echo '<div style="background: #e0ffe0; border: 1px solid #00aa00; padding: 20px; margin: 20px; font-size: 18px;">';
        if ( $episode ) {
            echo '✅ Planning Centre Publishing episode cache successfully updated!';
        } else {
            echo '❌ Failed to update episode cache. Check API credentials or response.';
        }
        echo '</div>';
        exit;
    }
}
add_action( 'init', 'pcp_manual_update_trigger' );

/**
 * Sermons Settings Page
 *
 * This function outputs the HTML for the Sermons settings page.
 */
function pco_sermons_settings_page() {
    if ($msg = get_transient('pco_sermons_settings_success')) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html($msg) . '</p></div>';
        delete_transient('pco_sermons_settings_success');
    }
    ?>
    <div class="wrap">
        <h1>Sermons Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('pco_sermons_settings_group');
            do_settings_sections('pco-sermons-settings');
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Publishing Channel ID</th>
                    <td>
                        <input type="text" name="pco_sermons_channel_id" value="<?php echo esc_attr( get_option('pco_sermons_channel_id', '') ); ?>" />
                        <p class="description">Enter your Planning Center Publishing Channel ID. Example: <code>123456</code></p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Autoplay Video</th>
                    <td>
                        <input type="checkbox" name="pco_sermons_autoplay" value="1" <?php checked( get_option('pco_sermons_autoplay', 1 ), 1 ); ?> />
                        <label for="pco_sermons_autoplay">Enable autoplay when video loads</label>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Autoplay Delay (seconds)</th>
                    <td>
                        <input type="number" name="pco_sermons_autoplay_delay" value="<?php echo esc_attr( get_option('pco_sermons_autoplay_delay', 2) ); ?>" min="0" />
                        <p class="description">Delay in seconds before replacing thumbnail with video.</p>
                    </td>
                </tr>
            </table>
            <?php
            submit_button();
            ?>
        </form>
        <form method="post">
            <?php wp_nonce_field('pco_sermons_refresh_cache', 'pco_sermons_nonce'); ?>
            <?php submit_button('Refresh Sermons Cache', 'secondary', 'pco_sermons_refresh_cache'); ?>
        </form>
    </div>
    <?php
}


// Handle manual sermons cache refresh
add_action('admin_init', function() {
    if (
        isset($_POST['pco_sermons_refresh_cache']) &&
        check_admin_referer('pco_sermons_refresh_cache', 'pco_sermons_nonce')
    ) {
        global $wpdb;
        // Remove all sermons-related transients
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_pco_sermons_%' OR option_name LIKE '_transient_timeout_pco_sermons_%'");
        // Optionally, add a notice
        set_transient('pco_sermons_settings_success', 'Sermons cache cleared.', 10);
        wp_safe_redirect(admin_url('admin.php?page=pco-sermons-settings'));
        exit;
    }
});