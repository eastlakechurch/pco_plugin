<?php
if (!defined('ABSPATH')) exit;

add_shortcode('pco_groups', 'pco_groups_shortcode');

function pco_groups_shortcode($atts) {
    wp_enqueue_style('pco-groups-style');
    wp_enqueue_script('pco-groups-script');
    $groups = pco_groups_fetch_groups();
    ob_start();
    ?>
    <div class="pco-groups-filters">
        <input type="text" id="pco-groups-search" placeholder="Search groups...">
        <select id="pco-groups-type"><option value="">All Types</option></select>
        <select id="pco-groups-day"><option value="">All Days</option></select>
        <select id="pco-groups-location"><option value="">All Locations</option></select>
    </div>
    <div class="pco-groups-list">
        <?php foreach ($groups as $group): ?>
            <div class="pco-group-card" 
                data-type="<?php echo esc_attr($group['attributes']['group_type'] ?? ''); ?>"
                data-day="<?php echo esc_attr($group['attributes']['day'] ?? ''); ?>"
                data-location="<?php echo esc_attr($group['attributes']['location'] ?? ''); ?>">
                <h3><?php echo esc_html($group['attributes']['name']); ?></h3>
                <div class="desc"><?php echo esc_html($group['attributes']['description']); ?></div>
                <div class="meta">
                    <span><?php echo esc_html($group['attributes']['group_type'] ?? ''); ?></span>
                    <span><?php echo esc_html($group['attributes']['day'] ?? ''); ?></span>
                    <span><?php echo esc_html($group['attributes']['location'] ?? ''); ?></span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}