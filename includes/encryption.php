<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

function pco_events_encrypt($data) {
    $key = defined('PCO_EVENTS_SECRET_KEY') ? PCO_EVENTS_SECRET_KEY : '';
    if (empty($key)) return $data;
    $iv = openssl_random_pseudo_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
    return base64_encode($iv . $encrypted);
}

function pco_events_decrypt($data) {
    $key = defined('PCO_EVENTS_SECRET_KEY') ? PCO_EVENTS_SECRET_KEY : '';
    if (empty($key)) return $data;
    $data = base64_decode($data);
    $iv = substr($data, 0, 16);
    $encrypted = substr($data, 16);
    return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
}