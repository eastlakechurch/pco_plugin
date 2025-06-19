<?php
if (!defined('ABSPATH')) exit;

/**
 * Converts a YouTube URL to an embeddable URL.
 */
function pcp_convert_youtube_url($url) {
    $parsed_url = parse_url($url);
    if (isset($parsed_url['host']) &&
        (false !== strpos($parsed_url['host'], 'youtube.com') || false !== strpos($parsed_url['host'], 'youtu.be'))) {

        if (isset($parsed_url['query']) && false !== strpos($url, 'watch?v=')) {
            parse_str($parsed_url['query'], $query);
            if (isset($query['v'])) {
                return 'https://www.youtube.com/embed/' . $query['v'];
            }
        } elseif (false !== strpos($parsed_url['host'], 'youtu.be')) {
            $path = trim($parsed_url['path'], '/');
            return 'https://www.youtube.com/embed/' . $path;
        } elseif (isset($parsed_url['path']) && false !== strpos($parsed_url['path'], '/live/')) {
            $parts = explode('/live/', $parsed_url['path']);
            if (isset($parts[1]) && !empty($parts[1])) {
                return 'https://www.youtube.com/embed/' . $parts[1];
            }
        }
    }
    return $url;
}

/**
 * Returns the video embed HTML for a given episode.
 */
function render_sermon_video($episode) {
    $attributes    = isset($episode['attributes']) ? $episode['attributes'] : array();
    $video_url     = isset($attributes['library_video_url']) ? $attributes['library_video_url'] : '';
    $thumbnail_url = isset($attributes['library_video_thumbnail_url']) ? $attributes['library_video_thumbnail_url'] : '';

    if (empty($video_url) || empty($thumbnail_url)) {
        return '<p>Video information not available.</p>';
    }

    $embed_url = pcp_convert_youtube_url($video_url);
    $autoplay = get_option('pco_sermons_autoplay', 1);
    $delay = intval(get_option('pco_sermons_autoplay_delay', 2));

    ob_start(); ?>
    <div class="pcp-video-container" style="position: relative; width: 100%; margin: auto;">
        <div id="pcp-video-thumb" style="background-image: url('<?php echo esc_url($thumbnail_url); ?>'); background-size: cover; width: 100%; height: 0; padding-bottom: 56.25%; position: relative; overflow: hidden;"></div>
    </div>
    <script type="text/javascript">
        setTimeout(function() {
            console.log("Replacing thumbnail with YouTube video. Embed URL: <?php echo esc_js($embed_url); ?>");
            var container = document.getElementById("pcp-video-thumb");
            if (container) {
                container.innerHTML = '<iframe src="<?php echo esc_url($embed_url); ?>?' +
                    '<?php echo $autoplay ? 'autoplay=1&mute=1&' : ''; ?>modestbranding=1&rel=0&playsinline=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; width: 100%; height: 100%;"></iframe>';
            }
        }, <?php echo esc_js($delay * 1000); ?>);
    </script>
    <?php
    return ob_get_clean();
}

/**
 * Returns the episode title.
 */
function render_sermon_title($episode) {
    $attributes = isset($episode['attributes']) ? $episode['attributes'] : array();
    $title = isset($attributes['title']) ? $attributes['title'] : 'No title available';
    return esc_html($title);
}

/**
 * Returns the formatted published date.
 */
function render_sermon_published_date($episode) {
    $attributes = isset($episode['attributes']) ? $episode['attributes'] : array();
    $published = isset($attributes['published_live_at']) ? $attributes['published_live_at'] : '';

    if (!$published) {
        return 'No publish date available';
    }

    $timestamp = strtotime($published);
    if (!$timestamp) {
        return esc_html($published);
    }

    $formatted_date = date("D j.n.Y", $timestamp);
    return esc_html($formatted_date);
}