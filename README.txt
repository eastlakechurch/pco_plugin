=== PCO Integrations for WordPress ===
Contributors: Joshua Edwards
Tags: planning center, events, calendar, shortcode, church
Requires at least: 5.0
Tested up to: 6.5
Requires PHP: 7.2
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium plugin for displaying Planning Center Calendar events beautifully in WordPress using shortcodes.

== Description ==

**PCO Integrations for WordPress** is a powerful yet simple plugin built for churches using Planning Center Online (PCO). It pulls upcoming events from your Planning Center Calendar and displays them on your website using clean, modern layouts and flexible shortcode options.

Built with security and flexibility in mind, it includes encrypted credential storage, nonce protection for all sensitive actions, and full style customization to match your brand.

== Features ==

- Show upcoming events from Planning Center Calendar
- Automatically filters out past events
- Supports event tags for filtering and dynamic tag list in admin
- Displays event images, descriptions, and start times
- Recurring events show only the next instance with a clean, customisable label
- Built-in caching with manual refresh button
- Fully responsive design with customisable font, color, border, and image settings
- Optional custom CSS field for advanced styling
- Admin settings screen with credential encryption and secure license key handling

== Installation ==

1. Purchase and download the plugin ZIP file.
2. In your WordPress dashboard, go to `Plugins > Add New > Upload Plugin`.
3. Upload the ZIP file and click "Install Now."
4. Activate the plugin.
5. Navigate to `PCO Integrations > Settings` to enter your Planning Center API credentials (username and personal access token).

== Shortcodes ==

Place these shortcodes in any page, post, or widget:

**All PCO Integrations - Events**
[pco_events]

**Hide Descriptions**
[pco_events show_description="false"]

**Only Featured PCO Integrations - Events**
[pco_featured_events tags="featured"]

**Multiple Tags & Limit Count**
[pco_featured_events tags="youth,camp" count="5"]

== Caching ==

This plugin caches event data for performance. Use the “Refresh Event Cache” button in the settings to manually clear and update event data. During development or for URL-based cache clearing, append `?refresh=true` to your events page URL.

== Frequently Asked Questions ==

= Where do I get my Planning Center API credentials? =
You need a Planning Center administrator account. Go to `developer.planning.center`, create a new personal access token (PAT), and use that along with your API ID in the plugin settings page.

= Can I show only youth events or events with a certain tag? =
Yes! Use the `tags` attribute with any Planning Center tags attached to events.

= Does it support recurring events? =
Yes! Only the next upcoming instance of recurring events is shown. It’s also labeled automatically.

= How is my data protected? =
Your Planning Center credentials are securely encrypted before being saved to your database. All plugin forms are protected with WordPress nonces to prevent unauthorised access.

= Can I customise the appearance of events? =
Yes! The plugin includes a full Styles tab with options for colors, font size, font family, border strength, image corner styling, and custom CSS.

== Changelog ==

= 1.0.0 =
* Initial public release

== Disclaimer ==

This plugin is not affiliated with or endorsed by Planning Center. Planning Center® is a registered trademark of Ministry Centered Technologies.

== License ==

This plugin is licensed under the GNU General Public License v2 or later.
