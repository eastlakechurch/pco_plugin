<?php
// includes/sermons/api.php

if (!defined('ABSPATH')) exit;

// Use the main plugin's encrypted API credentials
$token_id = pco_events_decrypt(get_option('pco_events_username'));
$token_secret = pco_events_decrypt(get_option('pco_events_password'));

/**
 * Retrieve cached episode or force update if ?pcp_nocache=1 is used.
 */
function pcp_fetch_latest_episode() {
    $episode = get_transient('pcp_latest_episode');

    if (isset($_GET['pcp_nocache']) && '1' === $_GET['pcp_nocache'] || false === $episode) {
        return pcp_update_latest_episode();
    }

    return $episode;
}