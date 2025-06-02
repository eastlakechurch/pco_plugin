<?php
if (!defined('ABSPATH')) exit;

add_shortcode('pco_groups', 'pco_groups_shortcode');

function pco_groups_shortcode($atts) {
    wp_enqueue_style('pco-groups-style');
    wp_enqueue_script('pco-groups-script');
    $groups = pco_groups_fetch_groups();

    // 1. Collect unique group types, locations, and days
    $group_types = [];
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
            if (preg_match('/Every\s+([A-Za-z]+)/', $attr['schedule'], $matches)) {
                $day = $matches[1];
                $days[$day] = $day;
            }
        }
    }
    // Optional: Sort days in week order
    $weekdays = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    uksort($days, function($a, $b) use ($weekdays) {
        return array_search($a, $weekdays) - array_search($b, $weekdays);
    });

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
            <?php foreach ($days as $day): ?>
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
            // Extract day for data attribute
            $meeting_day = '';
            if (!empty($attr['schedule']) && preg_match('/Every\s+([A-Za-z]+)/', $attr['schedule'], $matches)) {
                $meeting_day = $matches[1];
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
                <div class="desc"><?php echo esc_html($attr['description']); ?></div>
                <?php if ($location_name): ?>
                    <div class="meta-location"><?php echo esc_html($location_name); ?></div>
                <?php endif; ?>
                <?php if ($recurrence): ?>
                    <div class="meta-recurrence"><?php echo esc_html($recurrence); ?></div>
                <?php endif; ?>
                <div class="meta"></div>
            </a>
        <?php endforeach; ?>
    </div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('pco-groups-search');
        const typeSelect = document.getElementById('pco-groups-type');
        const daySelect = document.getElementById('pco-groups-day');
        const locationSelect = document.getElementById('pco-groups-location');
        const cards = document.querySelectorAll('.pco-group-card');
        function filterCards() {
            const term = searchInput.value.trim().toLowerCase();
            const type = typeSelect.value;
            const day = daySelect.value;
            const location = locationSelect.value;
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                const cardType = card.getAttribute('data-type');
                const cardDay = card.getAttribute('data-day');
                const cardLocation = card.getAttribute('data-location');
                const matchesSearch = !term || name.includes(term);
                const matchesType = !type || cardType === type;
                const matchesDay = !day || cardDay === day;
                const matchesLocation = !location || cardLocation === location;
                card.style.display = (matchesSearch && matchesType && matchesDay && matchesLocation) ? '' : 'none';
            });
        }
        searchInput.addEventListener('input', filterCards);
        typeSelect.addEventListener('change', filterCards);
        daySelect.addEventListener('change', filterCards);
        locationSelect.addEventListener('change', filterCards);
    });
    </script>
    <?php
    return ob_get_clean();
}

add_shortcode('pco_integrations_groups', 'pco_integrations_groups_shortcode');

function pco_integrations_groups_shortcode($atts) {
    wp_enqueue_style('pco-groups-style');
    wp_enqueue_script('pco-groups-script');
    $groups = pco_integrations_groups_fetch_groups();
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
            <a class="pco-group-card" href="<?php echo esc_url($group_url); ?>" target="_blank" style="text-decoration:none;color:inherit;display:block;" data-name="<?php echo esc_attr(strtolower($attr['name'])); ?>">
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
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('pco-groups-search');
        const cards = document.querySelectorAll('.pco-group-card');
        searchInput.addEventListener('input', function() {
            const term = this.value.trim().toLowerCase();
            cards.forEach(card => {
                const name = card.getAttribute('data-name');
                if (!term || name.includes(term)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    </script>
    <?php
    return ob_get_clean();
}