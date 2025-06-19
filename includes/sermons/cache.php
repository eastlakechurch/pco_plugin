<?php
if (!defined('ABSPATH')) exit;

/**
 * Get the number of seconds until next Monday at 6pm.
 */
function pcp_get_next_update_interval() {
    $current_time = current_time('timestamp');
    if (date('N', $current_time) == 1 && date('H:i', $current_time) < '18:00') {
        $next_update = strtotime(date('Y-m-d', $current_time) . ' 18:00:00');
    } else {
        $next_update = strtotime('next monday 18:00:00', $current_time);
    }
    return $next_update - $current_time;
}

/**
 * Fetch and cache the latest episode from Planning Center.
 */
function pcp_update_latest_episode() {
    $token_id = pco_events_decrypt(get_option('pco_events_username'));
    $token_secret = pco_events_decrypt(get_option('pco_events_password'));

    $channel_id = get_option('pco_sermons_channel_id');
    $api_url = $channel_id
        ? 'https://api.planningcenteronline.com/publishing/v2/channels/' . $channel_id . '/episodes'
        : 'https://api.planningcenteronline.com/publishing/v2/episodes';

    $args = array(
        'headers' => array(
            'Authorization' => 'Basic ' . base64_encode($token_id . ':' . $token_secret)
        )
    );

    $response = wp_remote_get($api_url, $args);
    if (is_wp_error($response)) {
        error_log('PCP API error: ' . $response->get_error_message());
        return false;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    if ($response_code !== 200) {
        error_log('PCP API returned status code: ' . $response_code);
        error_log('PCP API response: ' . wp_remote_retrieve_body($response));
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['data']) && is_array($data['data']) && !empty($data['data'])) {
        $episodes = $data['data'];
        $latest_episode = $episodes[count($episodes) - 1];

        $expiration = pcp_get_next_update_interval();
        set_transient('pcp_latest_episode', $latest_episode, $expiration);

        return $latest_episode;
    }

    return false;
}

/**
 * Manual update trigger via frontend URL.
 */
function pcp_manual_update_trigger() {
    if (isset($_GET['pcp_update']) && sanitize_text_field($_GET['pcp_update']) === '1') {
        $episode = pcp_update_latest_episode();

        echo '<div style="background: #e0ffe0; border: 1px solid #00aa00; padding: 20px; margin: 20px; font-size: 18px;">';
        if ($episode) {
            echo '✅ Planning Centre Publishing episode cache successfully updated!';
        } else {
            echo '❌ Failed to update episode cache. Check API credentials or response.';
        }
        echo '</div>';
        exit;
    }
}
add_action('init', 'pcp_manual_update_trigger');

/**
 * Manual cache clearing from admin settings page.
 */
add_action('admin_init', function() {
    if (
        isset($_POST['pco_sermons_refresh_cache']) &&
        check_admin_referer('pco_sermons_refresh_cache', 'pco_sermons_nonce')
    ) {
        global $wpdb;
        $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_pco_sermons_%' OR option_name LIKE '_transient_timeout_pco_sermons_%'");
        set_transient('pco_sermons_settings_success', 'Sermons cache cleared.', 10);
        wp_safe_redirect(admin_url('admin.php?page=pco-sermons-settings'));
        exit;
    }
});