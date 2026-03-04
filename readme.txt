=== WP Custom RSS Feed ===
Contributors: shakeelnasafian
Tags: rss, feed, xml, content syndication
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a configurable custom RSS2 feed endpoint with WordPress-native rendering and cache controls.

== Description ==
WP Custom RSS Feed registers a configurable feed endpoint (default `xml-feed`) and outputs a standards-friendly RSS2 feed using WordPress feed helpers.

Features:
* Configurable feed slug
* Configurable item count and post types
* Excerpt or full-content output mode
* Transient cache (10-minute default)
* Cache invalidation when content changes
* Extensible filters for query args, per-item data, and final XML

== Installation ==
1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate **WP Custom RSS Feed** through the Plugins screen.
3. Go to **Settings → Custom RSS Feed** and configure options.
4. Visit `/feed/xml-feed/` (or your configured slug).

== Frequently Asked Questions ==
= Does this flush rewrite rules automatically? =
Yes. Activation flushes rewrites and slug changes trigger a one-time flush.

= How do I customize query args? =
Use `wcrss_feed_query_args` filter.

== Changelog ==
= 1.1.0 =
* Production-ready rewrite with OOP structure.
* Added settings page, custom slug, item count, and post type configuration.
* Switched query logic to `WP_Query`.
* Added template-based RSS2 rendering and transient caching.
