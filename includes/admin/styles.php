<?php
// Add this as the first line in all PHP files except the main plugin file
if (!defined('ABSPATH')) exit;

function pco_events_styles() {
    wp_register_style('events-style', false);
    wp_enqueue_style('events-style');

    $tag_color = get_option('pco_events_primary_color', '#0073aa');
    $card_bg = get_option('pco_events_card_background', '#fafafa');
    $title_color = get_option('pco_events_title_color', '#222222');
    $font_size = get_option('pco_events_font_size', 'normal');
    $font_family = get_option('pco_events_font_family', 'inherit');
    $border_strength = get_option('pco_events_border_strength', 'subtle');
    $image_style = get_option('pco_events_image_style', 'rounded');
    $extra_css = get_option('pco_events_custom_css', '');

    $image_padding = get_option('pco_events_image_padding', '15px');
    $card_radius = get_option('pco_events_card_border_radius', '6px');
    $card_border_width = get_option('pco_events_card_border_width', '1px');

    $recurring_color = get_option('pco_events_recurring_color', '#0073aa');
    $border_color = get_option('pco_events_card_border_color', '#e0e0e0');
    $image_fill = get_option('pco_events_image_fill', 'false');

    $font_size_css = ($font_size === 'small') ? '0.9em' : (($font_size === 'large') ? '1.2em' : '1em');
    $border_css = ($border_strength === 'none') ? 'none' : (($border_strength === 'strong') ? '2px solid #ccc' : '1px solid #e0e0e0');
    $image_radius = ($image_style === 'square') ? '0' : (($image_style === 'circle') ? '50%' : '4px');

    $custom_css = "
        .events {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
            padding: 20px 0;
        }

        .events .event {
            background: {$card_bg};
            border: {$card_border_width} solid {$border_color};
            border-radius: {$card_radius};
            padding: 20px;
            transition: box-shadow 0.2s ease-in-out;
            font-size: {$font_size_css};
            font-family: {$font_family};
        }

        .events .event:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }

        .events img {
            width: 100%;
            height: auto;
            border-radius: {$image_radius};
            margin-bottom: {$image_padding};
            margin-top: 0;
            margin-left: 0;
            margin-right: 0;
            display: block;
        }

        .events .event h3,
        .events .event h3 a {
            margin: 0 !important;
            font-size: 1.3em;
            color: {$title_color};
        }

        .events .event h3 a {
            text-decoration: none;
        }

        .event-title-wrap {
            margin-bottom: 2px;
            line-height: 1.2;
        }

        .events .event-date {
            font-size: 0.85em;
            color: #555;
            display: block;
            margin: 0 0 10px 0;
            padding: 0;
            line-height: 1.4;
            position: relative;
            padding-left: 1.5em;
        }

        .recurring-label {
            color: inherit;
            font-weight: normal;
            display: block;
        }

        .next-date-label {
            color: {$recurring_color};
            font-style: italic;
            display: block;
        }

        .events h3 a:hover {
            text-decoration: underline;
        }


        .events .event-date::before {
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

        .events .event p a {
            display: inline-block;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            vertical-align: bottom;
            word-break: break-all;
        }

        .event-tags {
            margin: 10px 0;
        }

        .event-tag {
            display: inline-block;
            background: {$tag_color};
            color: #fff;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 0.75em;
            margin-right: 5px;
        }

        @media (max-width: 1024px) {
            .events {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 600px) {
            .events {
                grid-template-columns: 1fr;
            }
        }
    ";

    if ($image_fill === 'true') {
        $custom_css .= "
            .events img {
                margin: -20px -20px 0 -20px;
                width: calc(100% + 40px);
                border-radius: 0;
            }
        ";
    }

    if (!empty($extra_css)) {
        $custom_css .= "\n/* Custom CSS */\n" . $extra_css;
    }

    wp_add_inline_style('events-style', $custom_css);
}
add_action('wp_enqueue_scripts', 'pco_events_styles');

function pco_events_script() {
    ?>
    <script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function() {
      var dateElements = document.querySelectorAll('.event-date');
      dateElements.forEach(function(el) {
          var iso = el.getAttribute('data-start');
          if (iso) {
              var dt = new Date(iso);
              var options = { 
                weekday: 'short', month: 'short', day: 'numeric', 
                hour: 'numeric', minute: 'numeric',
                hour12: true
              };
              el.textContent = dt.toLocaleString(undefined, options);
          }
      });
    });
    </script>
    <?php
}
add_action('wp_footer', 'pco_events_script');

function pco_events_admin_css() {
    echo '<style>.form-table + form { margin-top: 20px; }</style>';
}
add_action('admin_head', 'pco_events_admin_css');