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
        <?php foreach ($groups as $group): 
            // FILTER: Skip group type "unique" by ID (replace 462775 with your actual unique group type ID)
            $group_type_id = $group['relationships']['group_type']['data']['id'] ?? '';
            if ($group_type_id === '462775') {
                continue;
            }
            $attr = $group['attributes'];
            $image_url = isset($attr['header_image']['original']) ? $attr['header_image']['original'] : '';
            $recurrence = $attr['schedule'] ?? '';
            $location_id = $group['relationships']['location']['data']['id'] ?? '';
            $location_name = $location_id ? pco_groups_fetch_location_name($location_id) : '';
            $group_url = $attr['public_church_center_web_url'] ?? '';
        ?>
            <a class="pco-group-card" href="<?php echo esc_url($group_url); ?>" target="_blank" style="text-decoration:none;color:inherit;display:block;">
                <?php if ($image_url): ?>
                    <div class="pco-group-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($attr['name']); ?>" style="width:100%;height:auto;border-radius:8px;">
                    </div>
                <?php endif; ?>

                <h3><?php echo esc_html($attr['name']); ?></h3>
                <div class="desc"><?php echo esc_html($attr['description']); ?></div>
                
                <?php if ($location_name): ?>
                    <div class="meta-location"><?php echo esc_html($location_name); ?></div>
                <?php endif; ?>
                <?php if ($recurrence): ?>
                    <div class="meta-recurrence"><?php echo esc_html($recurrence); ?></div>
                <?php endif; ?>

                <div class="meta">
                    <!-- You can add more meta fields here if needed -->
                </div>
            </a>
        <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}