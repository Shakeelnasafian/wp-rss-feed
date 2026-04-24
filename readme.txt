=== WP Custom RSS Feed ===
Contributors: shakeelnasafian
Tags: rss, feed, xml, content syndication, media
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a configurable custom RSS2 feed endpoint with WordPress-native rendering, HTTP cache headers, and taxonomy filtering.

== Description ==
WP Custom RSS Feed registers a configurable feed endpoint (default `xml-feed`) and outputs a standards-friendly RSS2 feed using WordPress feed helpers.

Features:
* Configurable feed slug
* Configurable item count (capped at 500) and post types (validated against registered public types)
* Excerpt or full-content output mode
* Optional featured image output as <media:thumbnail>
* Optional taxonomy + term filter
* Configurable transient cache TTL (1–1440 minutes)
* Smart cache invalidation that skips autosaves/revisions and off-topic post types
* HTTP cache headers (Cache-Control, Last-Modified, ETag) with 304 support
* Manual Purge Cache button on the settings page
* Settings shortcut on the Plugins list
* Extensible filters for query args, per-item data, and final XML
* uninstall.php cleans up all options and transients

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **WP Custom RSS Feed** through the Plugins screen.
3. Go to **Settings → Custom RSS Feed** and configure options.
4. Visit `/feed/xml-feed/` (or your configured slug).

== Frequently Asked Questions ==
= Does this flush rewrite rules automatically? =
Yes. Activation flushes rewrites and slug changes trigger a one-time flush.

= How do I customize query args? =
Use the `wcrss_feed_query_args` filter.

= Does the plugin support HTTP conditional requests? =
Yes. Responses include `Last-Modified` and `ETag`; matching `If-Modified-Since` / `If-None-Match` requests return `304 Not Modified`.

== Changelog ==
= 1.2.0 =
* Added configurable cache TTL, taxonomy/term filter, and featured-image `<media:thumbnail>` output.
* Added HTTP cache headers (`Cache-Control`, `Last-Modified`, `ETag`) with `304 Not Modified` support.
* Added `X-Robots-Tag: noindex, follow` on feed responses.
* Added manual Purge Cache button and Settings action link.
* Replaced free-text post types input with validated checkboxes of registered public types.
* Skip cache purges for autosaves, revisions, and unrelated post types.
* Switched template to `load_template()` args (no more globals).
* Modernized `register_setting()` args array.
* Added `uninstall.php` that cleans options and transients (multisite-aware).
* Capped items-per-feed at 500 and cache TTL at 1440 minutes.
* Fixed fragile textdomain path using a new `WCRSS_PLUGIN_FILE` constant.

= 1.1.0 =
* Production-ready rewrite with OOP structure.
* Added settings page, custom slug, item count, and post type configuration.
* Switched query logic to `WP_Query`.
* Added template-based RSS2 rendering and transient caching.
