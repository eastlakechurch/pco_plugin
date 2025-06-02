<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

function pco_events_styles_page() {
    if (isset($_POST['pco_events_reset_styles'])) {
        if (!isset($_POST['pco_events_nonce']) || !wp_verify_nonce($_POST['pco_events_nonce'], 'pco_events_reset_styles')) {
            wp_die('Security check failed');
        }
        delete_option('pco_events_primary_color');
        delete_option('pco_events_card_background');
        delete_option('pco_events_title_color');
        echo '<div class="notice notice-success"><p>Styles have been reset to default.</p></div>';
    }
    ?>
    <div class="wrap">
        <h1>PCO Integrations – Styles</h1>
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
                        <img src="<?php echo plugins_url('images/sample.jpg', dirname(__FILE__, 2) . '/pco-integrations.php'); ?>" alt="Sample Event Image" />
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
    </div>
    <?php
}