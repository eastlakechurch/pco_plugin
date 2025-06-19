=== PCO Integrations for WordPress ===
Contributors: joshedwards
Tags: planning center, church, events, groups, sermons, shortcode
Requires at least: 5.0
Tested up to: 6.8.1
Requires PHP: 7.4
Stable tag: 1.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A premium plugin for displaying Planning Center Calendar events beautifully in WordPress using shortcodes.

== Description ==

**PCO Integrations for WordPress** is a powerful plugin built for churches using Planning Center Online (PCO). It pulls upcoming Events, Groups, and Sermons from Planning Center and displays them on your website using clean shortcodes.

Built with security and flexibility in mind, it includes encrypted credential storage, nonce protection, custom styles, license key validation, and responsive designs.

== Features ==

- Display upcoming events from Planning Center Calendar
- Automatically filters past events and detects recurring ones
- Support for event tags, group types, and sermon episodes
- Responsive display for Events, Groups, and Sermons
- Built-in caching with manual refresh and ?refresh=true support
- Full admin styling panel for fonts, colors, borders, and custom CSS
- License key validation with refresh support and expiry checks
- Credential encryption and secure API access

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

**Groups Display**
[pco_groups]

**Sermons Display**
[planning_centre_video]
[planning_centre_title]
[planning_centre_published]

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

= 1.3.2 =
* Refactored sermons structure to follow modular design

= 1.3.2 =
* Security improvements

= 1.3.1 =
* Added ability to enter url in event description
* Fixed long text (url) extending beyond event card

= 1.3.0 =
* Added license refresh button
* Improved license error feedback and expiry handling
* Fixed Sermons shortcode bug where episodes were not displaying
* Internal refactor for centralized enqueueing and shortcode loading

= 1.2.2 =
* Fixed: Group filter dropdowns (day/type/location) dynamically adjust based on available combinations
* Events: Only display next instance of recurring events with “Recurring” label
* General: Improved error handling and debug logging

= 1.2.0 =
* Added license validation and settings integration
* Reorganized shortcode code into grouped includes
* Enhanced Events and Groups shortcode compatibility

= 1.1.0 =
* Added Groups integration
* Added Sermons shortcode for YouTube embed
* Initial shortcode parameter support

= 1.0.0 =
* Initial public release with Planning Center Events integration

== Disclaimer ==

This plugin is not affiliated with or endorsed by Planning Center. Planning Center® is a registered trademark of Ministry Centered Technologies.

This plugin is a third-party tool. Planning Center® is a registered trademark of Ministry Centered Technologies.

== License ==

This plugin is licensed under the GNU General Public License v2 or later.
