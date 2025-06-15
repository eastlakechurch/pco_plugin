<?php
if (!defined('ABSPATH')) exit;

add_shortcode('pco_groups', 'pco_groups_shortcode');

function pco_groups_shortcode($atts) {
    wp_enqueue_style('pco-groups-style');
    wp_enqueue_script('pco-groups-script');
    $groups = pco_groups_fetch_groups();
    $all_group_types = pco_groups_fetch_group_types();

    // 1. Collect unique group types, locations, and days
    $locations = [];
    $days = [];
    $used_group_type_ids = [];
    foreach ($groups as $group) {
        $group_type_id = $group['relationships']['group_type']['data']['id'] ?? '';
        if ($group_type_id) {
            $used_group_type_ids[$group_type_id] = true;
        }
        $location_id = $group['relationships']['location']['data']['id'] ?? '';
        $location_name = $location_id ? pco_groups_fetch_location_name($location_id) : '';
        if ($location_id && $location_name) {
            $locations[$location_id] = $location_name;
        }
        // Extract day from schedule (e.g., "Every Monday, 7:00pm" → "Monday")
        $attr = $group['attributes'];
        if (!empty($attr['schedule'])) {
            // Try to match "on Mondays", "on Tuesday", "on Wednesdays", etc.
            if (preg_match('/on\s+([A-Za-z]+days?)/i', $attr['schedule'], $matches)) {
                // Remove plural 's' if present (e.g., "Mondays" -> "Monday")
                $day = rtrim($matches[1], 's');
                // Capitalize first letter for consistency
                $day = ucfirst(strtolower($day));
                $days[$day] = $day;
            }
            // Fallback: Try to match "Every Monday"
            elseif (preg_match('/Every\s+([A-Za-z]+)/i', $attr['schedule'], $matches)) {
                $day = ucfirst(strtolower($matches[1]));
                $days[$day] = $day;
            }
        }
    }
    // Only keep group types that are used
    $group_types = array_intersect_key($all_group_types, $used_group_type_ids);

    // Optional: Sort days in week order
    $weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    uksort($days, function($a, $b) use ($weekdays) {
        return array_search($a, $weekdays) - array_search($b, $weekdays);
    });

    ?>
    <script>
    const PCO_GROUP_TYPE_MAP = <?php echo json_encode($group_types); ?>;
    const PCO_GROUP_LOCATION_MAP = <?php echo json_encode($locations); ?>;
    const PCO_GROUP_DAY_LIST = <?php echo json_encode(array_keys($days)); ?>;
    </script>
    <?php
    ob_start();
    ?>
    <div class="pco-groups-filters">
        <input type="text" id="pco-groups-search" placeholder="Search groups...">
        <select id="pco-groups-type">
            <option value="">All Types</option>
            <?php foreach ($group_types as $type_id => $type_name): ?>
                <option value="<?php echo esc_attr($type_id); ?>"><?php echo esc_html($type_name); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="pco-groups-day">
            <option value="">All Days</option>
            <?php foreach (array_keys($days) as $day): ?>
                <option value="<?php echo esc_attr($day); ?>"><?php echo esc_html($day); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="pco-groups-location">
            <option value="">All Locations</option>
            <?php foreach ($locations as $location_id => $location_name): ?>
                <option value="<?php echo esc_attr($location_id); ?>"><?php echo esc_html($location_name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="pco-groups-list">
        <?php foreach ($groups as $group): 
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
            // Extract day for data attribute (use same logic as for dropdown)
            $meeting_day = '';
            if (!empty($attr['schedule'])) {
                if (preg_match('/on\s+([A-Za-z]+days?)/i', $attr['schedule'], $matches)) {
                    $meeting_day = rtrim($matches[1], 's');
                    $meeting_day = ucfirst(strtolower($meeting_day));
                } elseif (preg_match('/Every\s+([A-Za-z]+)/i', $attr['schedule'], $matches)) {
                    $meeting_day = ucfirst(strtolower($matches[1]));
                }
            }
        ?>
            <a class="pco-group-card"
               href="<?php echo esc_url($group_url); ?>"
               target="_blank"
               style="text-decoration:none;color:inherit;display:block;"
               data-name="<?php echo esc_attr(strtolower($attr['name'])); ?>"
               data-type="<?php echo esc_attr($group_type_id); ?>"
               data-location="<?php echo esc_attr($location_id); ?>"
               data-day="<?php echo esc_attr($meeting_day); ?>">
                <?php if ($image_url): ?>
                    <div class="pco-group-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($attr['name']); ?>" style="width:100%;height:auto;border-radius:8px;">
                    </div>
                <?php endif; ?>
                <h3><?php echo esc_html($attr['name']); ?></h3>
                <?php if ($location_name): ?>
                    <div class="meta-location"><?php echo esc_html($location_name); ?></div>
                <?php endif; ?>
                <?php if ($recurrence): ?>
                    <div class="meta-recurrence"><?php echo esc_html($recurrence); ?></div>
                <?php endif; ?>
                <?php if (!empty($attr['description'])): ?>
                    <div class="desc"><?php echo esc_html($attr['description']); ?></div>
                <?php endif; ?>
                <div class="meta"></div>
            </a>
        <?php endforeach; ?>
    </div>
    <div id="pco-groups-no-results" style="display:none; text-align:center; margin:2em 0; color:#888;">
        No groups found matching that criteria.
    </div>
    <?php
    return ob_get_clean();
}

add_shortcode('pco_integrations_groups', 'pco_integrations_groups_shortcode');

function pco_integrations_groups_shortcode($atts) {
    wp_enqueue_style('pco-groups-style');
    wp_enqueue_script('pco-groups-script');
    $groups = pco_integrations_groups_fetch_groups();
    $group_types = pco_groups_fetch_group_types();

    // Collect unique group types, locations, and days
    $locations = [];
    $days = [];
    foreach ($groups as $group) {
        $type_id = $group['relationships']['group_type']['data']['id'] ?? '';
        $type_name = $group['relationships']['group_type']['data']['name'] ?? '';
        if ($type_id && $type_name) {
            $group_types[$type_id] = $type_name;
        }
        $location_id = $group['relationships']['location']['data']['id'] ?? '';
        $location_name = $location_id ? pco_groups_fetch_location_name($location_id) : '';
        if ($location_id && $location_name) {
            $locations[$location_id] = $location_name;
        }
        // Extract day from schedule (e.g., "Every Monday, 7:00pm" → "Monday")
        $attr = $group['attributes'];
        if (!empty($attr['schedule'])) {
            // Try to match "on Mondays", "on Tuesday", "on Wednesdays", etc.
            if (preg_match('/on\s+([A-Za-z]+days?)/i', $attr['schedule'], $matches)) {
                // Remove plural 's' if present (e.g., "Mondays" -> "Monday")
                $day = rtrim($matches[1], 's');
                // Capitalize first letter for consistency
                $day = ucfirst(strtolower($day));
                $days[$day] = $day;
            }
            // Fallback: Try to match "Every Monday"
            elseif (preg_match('/Every\s+([A-Za-z]+)/i', $attr['schedule'], $matches)) {
                $day = ucfirst(strtolower($matches[1]));
                $days[$day] = $day;
            }
        }
    }
    // Sort days in week order
    $weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    uksort($days, function($a, $b) use ($weekdays) {
        return array_search($a, $weekdays) - array_search($b, $weekdays);
    });

    ob_start();
    ?>
    <script>
    const PCO_GROUP_DAY_LIST = <?php echo json_encode(array_keys($days)); ?>;
    </script>
    <div class="pco-groups-filters">
        <input type="text" id="pco-groups-search" placeholder="Search groups...">
        <select id="pco-groups-type">
            <option value="">All Types</option>
            <?php foreach ($group_types as $type_id => $type_name): ?>
                <option value="<?php echo esc_attr($type_id); ?>"><?php echo esc_html($type_name); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="pco-groups-day">
            <option value="">All Days</option>
            <?php foreach (array_keys($days) as $day): ?>
                <option value="<?php echo esc_attr($day); ?>"><?php echo esc_html($day); ?></option>
            <?php endforeach; ?>
        </select>
        <select id="pco-groups-location">
            <option value="">All Locations</option>
            <?php foreach ($locations as $location_id => $location_name): ?>
                <option value="<?php echo esc_attr($location_id); ?>"><?php echo esc_html($location_name); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="pco-groups-list">
        <?php foreach ($groups as $group): 
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
            // Extract day for data attribute (use same logic as for dropdown)
            $meeting_day = '';
            if (!empty($attr['schedule'])) {
                if (preg_match('/on\s+([A-Za-z]+days?)/i', $attr['schedule'], $matches)) {
                    $meeting_day = rtrim($matches[1], 's');
                    $meeting_day = ucfirst(strtolower($meeting_day));
                } elseif (preg_match('/Every\s+([A-Za-z]+)/i', $attr['schedule'], $matches)) {
                    $meeting_day = ucfirst(strtolower($matches[1]));
                }
            }
        ?>
            <a class="pco-group-card"
               href="<?php echo esc_url($group_url); ?>"
               target="_blank"
               style="text-decoration:none;color:inherit;display:block;"
               data-name="<?php echo esc_attr(strtolower($attr['name'])); ?>"
               data-type="<?php echo esc_attr($group_type_id); ?>"
               data-location="<?php echo esc_attr($location_id); ?>"
               data-day="<?php echo esc_attr($meeting_day); ?>">
                <?php if ($image_url): ?>
                    <div class="pco-group-image">
                        <img src="<?php echo esc_url($image_url); ?>" alt="<?php echo esc_attr($attr['name']); ?>" style="width:100%;height:auto;border-radius:8px;">
                    </div>
                <?php endif; ?>
                <h3><?php echo esc_html($attr['name']); ?></h3>
                <?php if ($location_name): ?>
                    <div class="meta-location"><?php echo esc_html($location_name); ?></div>
                <?php endif; ?>
                <?php if ($recurrence): ?>
                    <div class="meta-recurrence"><?php echo esc_html($recurrence); ?></div>
                <?php endif; ?>
                <?php if (!empty($attr['description'])): ?>
                    <div class="desc"><?php echo esc_html($attr['description']); ?></div>
                <?php endif; ?>
                <div class="meta"></div>
            </a>
        <?php endforeach; ?>
    </div>
    <div id="pco-groups-no-results" style="display:none; text-align:center; margin:2em 0; color:#888;">
        No groups found matching that criteria.
    </div>
    <?php
    return ob_get_clean();
}