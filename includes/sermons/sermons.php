<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
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
    $api_url = 'https://api.planningcenteronline.com/publishing/v2/episodes';
    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode( PCP_TOKEN_ID . ':' . PCP_TOKEN_SECRET )
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
        $api_url = 'https://api.planningcenteronline.com/publishing/v2/episodes';
        $args    = array(
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( PCP_TOKEN_ID . ':' . PCP_TOKEN_SECRET )
            )
        );
        $response = wp_remote_get( $api_url, $args );
        echo '<pre style="background:#f5f5f5; padding:10px;">';
        if ( is_wp_error( $response ) ) {
            echo 'Error fetching API: ' . $response->get_error_message();
        } else {
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
                container.innerHTML = '<iframe src="<?php echo esc_url( $embed_url ); ?>?autoplay=1&mute=1&modestbranding=1&rel=0&playsinline=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%;"></iframe>';
            }
        }, 2000); // Delay reduced to 2 seconds
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
    ?>
    <div class="wrap">
        <h1>Sermons Settings</h1>
        <p>This section will contain settings and documentation for the Sermons integration.</p>
        <!-- Add settings fields here if needed in the future -->
    </div>
    <?php
}