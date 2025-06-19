<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

function pco_events_styles_page() {
    if (isset($_POST['pco_events_reset_styles'])) {
        if (!isset($_POST['pco_events_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['pco_events_nonce']), 'pco_events_reset_styles')) {
            wp_die('Security check failed');
        }
        delete_option('pco_events_primary_color');
        delete_option('pco_events_card_background');
        delete_option('pco_events_title_color');
        echo '<div class="notice notice-success"><p>Styles have been reset to default.</p></div>';
    }

    // --- GROUPS RESET LOGIC ---
    if (isset($_POST['pco_groups_reset_styles'])) {
        if (!isset($_POST['pco_groups_nonce']) || !wp_verify_nonce(sanitize_text_field($_POST['pco_groups_nonce']), 'pco_groups_reset_styles')) {
            wp_die('Security check failed');
        }
        delete_option('pco_groups_card_background');
        delete_option('pco_groups_title_color');
        delete_option('pco_groups_text_color');
        echo '<div class="notice notice-success"><p>Group styles have been reset to default.</p></div>';
    }

    // Determine active tab
    $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'events';
    ?>
    <div class="wrap">
        <h1>PCO Integrations – Styles</h1>
        <h2 class="nav-tab-wrapper">
            <a href="?page=pco-events-styles&tab=events" class="nav-tab <?php echo $active_tab == 'events' ? 'nav-tab-active' : ''; ?>">Events Styles</a>
            <a href="?page=pco-events-styles&tab=groups" class="nav-tab <?php echo $active_tab == 'groups' ? 'nav-tab-active' : ''; ?>">Groups Styles</a>
        </h2>
        <?php if ($active_tab == 'events'): ?>
            <div style="display: flex; flex-wrap: nowrap; gap: 40px; align-items: flex-start;">
                <div style="flex: 1 1 50%; max-width: 600px;">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('pco_events_style_settings_group');
                        do_settings_sections('pco-events-styles');
                        ?>
                        <?php submit_button('Save Styles'); ?>
                    </form>
                    <form method="post">
                        <?php wp_nonce_field('pco_events_reset_styles', 'pco_events_nonce'); ?>
                        <?php submit_button('Reset to Default Styles', 'secondary', 'pco_events_reset_styles'); ?>
                    </form>
                </div>

                <div id="pco-preview-container" style="flex: 1 1 400px; position: sticky; top: 100px; max-width: 500px;">
                    <h2>Live Preview</h2>
                    <style>
                        #pco-preview-container .events {
                            display: grid;
                            grid-template-columns: 1fr;
                            gap: 30px;
                            padding: 20px 0;
                            font-family: <?php echo esc_attr(get_option('pco_events_font_family', 'inherit')); ?>;
                            font-size: <?php echo get_option('pco_events_font_size') === 'small' ? '0.9em' : (get_option('pco_events_font_size') === 'large' ? '1.2em' : '1em'); ?>;
                            justify-content: center;
                        }
                        #pco-preview-container .event {
                            background: <?php echo esc_attr(get_option('pco_events_card_background', '#fafafa')); ?>;
                            border-width: <?php echo esc_attr(get_option('pco_events_card_border_width', '1px')); ?>;
                            border-style: solid;
                            border-color: <?php echo esc_attr(get_option('pco_events_primary_color', '#0073aa')); ?>;
                            border-radius: <?php echo esc_attr(get_option('pco_events_card_border_radius', '6px')); ?>;
                            padding: 20px;
                            transition: box-shadow 0.2s ease-in-out;
                        }
                        #pco-preview-container .event:hover {
                            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
                        }
                        #pco-preview-container img {
                            width: 100%;
                            height: auto;
                            border-radius: <?php echo get_option('pco_events_image_style') === 'square' ? '0' : (get_option('pco_events_image_style') === 'circle' ? '50%' : '4px'); ?>;
                        }
                        #pco-preview-container h3 {
                            margin: 0 0 10px;
                            font-size: 1.3em;
                            color: <?php echo esc_attr(get_option('pco_events_title_color', '#222222')); ?>;
                        }
                        #pco-preview-container .event-date {
                            font-size: 0.85em;
                            color: #555;
                            display: block;
                            margin: 0 0 10px 0;
                            padding: 0;
                            line-height: 1.4;
                            position: relative;
                            padding-left: 1.5em;
                        }
                        #pco-preview-container .event-date::before {
                            content: '';
                            display: inline-block;
                            position: absolute;
                            left: 0;
                            top: 0.15em;
                            width: 1em;
                            height: 1em;
                            background-image: url('data:image/svg+xml;utf8,<svg fill=%22555%22 xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 24 24%22><path d=%22M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z%22/></svg>');
                            background-repeat: no-repeat;
                            background-size: contain;
                            background-position: center;
                        }
                        #pco-preview-container .recurring-label {
                            font-size: inherit;
                            color: inherit;
                            font-weight: normal;
                            display: block;
                        }
                        #pco-preview-container .next-date-label {
                            font-size: inherit;
                            color: <?php echo esc_attr(get_option('pco_events_recurring_color', '#0073aa')); ?>;
                            font-style: italic;
                            display: block;
                        }
                        #pco-preview-container .event-tags {
                            margin: 10px 0;
                        }
                        #pco-preview-container .event-tag {
                            display: inline-block;
                            background: <?php echo esc_attr(get_option('pco_events_primary_color', '#0073aa')); ?>;
                            color: #fff;
                            padding: 3px 8px;
                            border-radius: 3px;
                            font-size: 0.75em;
                            margin-right: 5px;
                        }
                    </style>
                    <div class="events">
                        <div class="event">
                            <img src="<?php echo plugins_url('images/sample.jpg', dirname(__DIR__, 2) . '/pco-integrations.php'); ?>" alt="Sample Event Image" />
                            <div class="event-title-wrap">
                                <h3>Sample Event Title</h3>
                            </div>
                            <div class="event-date">
                                <span class="recurring-label" id="preview-recurring-label">Every Sunday, 10:00am</span><br>
                                <span class="next-date-label" id="preview-next-date-label">Next Date: Sun, 2 Jun</span>
                            </div>
                            <div class="event-tags" id="preview-event-tags"><span class="event-tag">featured</span></div>
                            <p>This is a sample description of an event to preview your selected styles.</p>
                        </div>
                    </div>
                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            const preview = document.querySelector('#pco-preview-container .event');
                            const img = preview.querySelector('img');
                            const title = preview.querySelector('h3');
                            const tag = preview.querySelector('.event-tag');
                            const recurringLabel = document.getElementById('preview-recurring-label');
                            const nextDateLabel = document.getElementById('preview-next-date-label');
                            const eventTags = document.getElementById('preview-event-tags');

                            function applyStyle(field, value) {
                                switch (field.name) {
                                    case 'pco_events_primary_color':
                                        tag.style.backgroundColor = value;
                                        break;
                                    case 'pco_events_card_background':
                                        preview.style.backgroundColor = value;
                                        break;
                                    case 'pco_events_title_color':
                                        title.style.color = value;
                                        break;
                                    case 'pco_events_font_size':
                                        preview.style.fontSize = value === 'small' ? '0.9em' : value === 'large' ? '1.2em' : '1em';
                                        break;
                                    case 'pco_events_font_family':
                                        preview.style.fontFamily = value;
                                        break;
                                    case 'pco_events_border_strength':
                                        preview.style.border = value === 'none' ? 'none' : value === 'strong' ? '2px solid #ccc' : '1px solid #e0e0e0';
                                        break;
                                    case 'pco_events_image_style':
                                        img.style.borderRadius = value === 'square' ? '0' : value === 'circle' ? '50%' : '4px';
                                        break;
                                    case 'pco_events_card_border_width':
                                        preview.style.borderWidth = value;
                                        break;
                                    case 'pco_events_card_border_radius':
                                        preview.style.borderRadius = value;
                                        break;
                                    case 'pco_events_image_padding':
                                        img.style.marginBottom = value;
                                        break;
                                    case 'pco_events_card_border_color':
                                        preview.style.borderColor = value;
                                        break;
                                    case 'pco_events_recurring_color':
                                        if (recurringLabel) recurringLabel.style.color = value;
                                        break;
                                    case 'pco_events_image_fill':
                                        if (value === 'true') {
                                            img.style.borderRadius = '0';
                                            img.style.display = 'block';
                                            img.style.width = 'calc(100% + 40px)';
                                            img.style.margin = '-20px -20px 0 -20px';
                                        } else {
                                            img.style.borderRadius = '<?php echo get_option('pco_events_image_style') === 'square' ? '0' : (get_option('pco_events_image_style') === 'circle' ? '50%' : '4px'); ?>';
                                            img.style.width = '100%';
                                            img.style.margin = '0 0 <?php echo esc_attr(get_option('pco_events_image_padding', '15px')); ?> 0';
                                        }
                                        break;
                                    // NEW: Toggle Next Date label
                                    case 'pco_events_show_next_date':
                                        if (nextDateLabel) nextDateLabel.style.display = value === 'yes' ? '' : 'none';
                                        break;
                                    // NEW: Toggle Tags
                                    case 'pco_events_show_tags':
                                        if (eventTags) eventTags.style.display = value === 'yes' ? '' : 'none';
                                        break;
                                }
                            }

                            // Initial state for toggles
                            const nextDateToggle = document.querySelector('select[name="pco_events_show_next_date"]');
                            if (nextDateToggle && nextDateLabel) {
                                nextDateLabel.style.display = nextDateToggle.value === 'yes' ? '' : 'none';
                            }
                            const tagsToggle = document.querySelector('select[name="pco_events_show_tags"]');
                            if (tagsToggle && eventTags) {
                                eventTags.style.display = tagsToggle.value === 'yes' ? '' : 'none';
                            }

                            const fields = document.querySelectorAll('form select, form input[type="color"], form input[type="text"], form textarea');
                            fields.forEach(field => {
                                field.addEventListener('input', () => applyStyle(field, field.value));
                            });
                        });
                    </script>
                </div>
            </div>
        <?php elseif ($active_tab == 'groups'): ?>
            <div style="display: flex; flex-wrap: nowrap; gap: 40px; align-items: flex-start;">
                <div style="flex: 1 1 50%; max-width: 600px;">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('pco_groups_style_settings_group');
                        do_settings_sections('pco-groups-styles');
                        submit_button('Save Group Styles');
                        ?>
                    </form>
                    <form method="post">
                        <?php wp_nonce_field('pco_groups_reset_styles', 'pco_groups_nonce'); ?>
                        <?php submit_button('Reset to Default Styles', 'secondary', 'pco_groups_reset_styles'); ?>
                    </form>
                </div>
                <div id="pco-groups-preview-container" style="flex: 1 1 400px; position: sticky; top: 100px; max-width: 500px;">
                    <h2>Live Preview</h2>
                    <style>
                        #pco-groups-preview .pco-group-card {
                            background: <?php echo esc_attr(get_option('pco_groups_card_background', '#fff')); ?>;
                            border-radius: 18px;
                            box-shadow: 0 4px 24px rgba(0,0,0,0.08), 0 1.5px 4px rgba(0,0,0,0.03);
                            overflow: hidden;
                            display: flex;
                            flex-direction: column;
                            transition: transform 0.18s cubic-bezier(.4,0,.2,1), box-shadow 0.18s cubic-bezier(.4,0,.2,1);
                            text-align: left;
                            position: relative;
                            cursor: pointer;
                            max-width: 320px;
                            margin-left: auto;
                            margin-right: auto;
                        }
                        #pco-groups-preview .pco-group-card:hover {
                            transform: translateY(-6px) scale(1.02);
                            box-shadow: 0 8px 32px rgba(0,0,0,0.13), 0 2px 8px rgba(0,0,0,0.06);
                            z-index: 2;
                        }
                        #pco-groups-preview .pco-group-image img {
                            width: 100%;
                            height: 180px;
                            object-fit: cover;
                            border-radius: 18px 18px 0 0 !important;
                            background-color: #eee;
                            display: block;
                        }
                        #pco-groups-preview .pco-group-card h3 {
                            font-size: 1.25em;
                            font-weight: 700;
                            margin: 0.9em 0 0.4em 0;
                            padding: 0 1.1em;
                            color: <?php echo esc_attr(get_option('pco_groups_title_color', '#1a1a1a')); ?>;
                            line-height: 1.2;
                        }
                        #pco-groups-preview .desc,
                        #pco-groups-preview .meta-location,
                        #pco-groups-preview .meta-recurrence {
                            color: <?php echo esc_attr(get_option('pco_groups_text_color', '#6b6b6b')); ?>;
                        }
                        #pco-groups-preview .meta-location {
                            display: flex;
                            align-items: center;
                            color: #444;
                            font-size: 0.95em;
                            font-weight: 400;
                            line-height: 1.4;
                            margin: 0.9em 0 0.4em 0;
                            padding: 0 1.1em;
                        }
                        #pco-groups-preview .meta-location::before {
                            content: '';
                            display: inline-block;
                            width: 14px;
                            height: 14px;
                            margin-right: 0.5em;
                            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%23444' viewBox='0 0 24 24'%3E%3Cpath d='M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5S10.62 6.5 12 6.5s2.5 1.12 2.5 2.5S13.38 11.5 12 11.5z'/%3E%3C/svg%3E");
                            background-size: contain;
                            background-repeat: no-repeat;
                        }
                        #pco-groups-preview .meta-recurrence {
                            color: #444;
                            font-size: 0.95em;
                            font-weight: 400;
                            line-height: 1.4;
                            margin: 0 0 0.9em 0;
                            padding: 0 1.1em;
                        }
                        #pco-groups-preview .meta {
                            margin-top: auto;
                            padding: 0 1.1em 1.1em 1.1em;
                        }
                    </style>
                    <div id="pco-groups-preview">
                        <div class="pco-group-card">
                            <div class="pco-group-image">
                                <img src="<?php echo plugins_url('images/sample.jpg', dirname(__DIR__, 2) . '/pco-integrations.php'); ?>" alt="Sample Group Image" />
                            </div>
                            <h3>Sample Group Name</h3>
                            <div class="meta-location">Sample Location</div>
                            <div class="meta-recurrence">Every Monday, 7:00pm</div>
                            <div class="desc">This is a sample description of a group to preview your selected styles.</div>
                            <div class="meta"></div>
                        </div>
                    </div>
                    <script>
document.addEventListener('DOMContentLoaded', function () {
    const preview = document.querySelector('#pco-groups-preview .pco-group-card');
    const title = preview.querySelector('h3');
    const desc = preview.querySelector('.desc');
    const metaLocation = preview.querySelector('.meta-location');
    const metaRecurrence = preview.querySelector('.meta-recurrence');
    const bgField = document.querySelector('input[name="pco_groups_card_background"]');
    const titleColorField = document.querySelector('input[name="pco_groups_title_color"]');
    const textColorField = document.querySelector('input[name="pco_groups_text_color"]');

    function applyGroupStyle(field, value) {
        if (!preview) return;
        switch (field.name) {
            case 'pco_groups_card_background':
                preview.style.backgroundColor = value;
                break;
            case 'pco_groups_title_color':
                if (title) title.style.color = value;
                break;
            case 'pco_groups_text_color':
                if (desc) desc.style.color = value;
                if (metaLocation) metaLocation.style.color = value;
                if (metaRecurrence) metaRecurrence.style.color = value;
                break;
        }
    }

    if (bgField) {
        bgField.addEventListener('input', function() {
            applyGroupStyle(bgField, bgField.value);
        });
        applyGroupStyle(bgField, bgField.value);
    }
    if (titleColorField) {
        titleColorField.addEventListener('input', function() {
            applyGroupStyle(titleColorField, titleColorField.value);
        });
        applyGroupStyle(titleColorField, titleColorField.value);
    }
    if (textColorField) {
        textColorField.addEventListener('input', function() {
            applyGroupStyle(textColorField, textColorField.value);
        });
        applyGroupStyle(textColorField, textColorField.value);
    }
});
</script>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}