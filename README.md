# WP Custom RSS Feed

Production-ready WordPress plugin that registers a configurable RSS2 endpoint and renders feed XML using WordPress best practices.

## Features

- Custom feed endpoint (`/feed/xml-feed/` by default)
- Settings page under **Settings → Custom RSS Feed**
- Configurable slug, items-per-feed, post types, excerpt/full-content mode
- Configurable transient cache TTL (1–1440 minutes)
- Manual "Purge cache now" button and Settings action link on the Plugins list
- Optional taxonomy + term filter (e.g. only posts in specific categories)
- Optional `<media:thumbnail>` output for featured images
- Smart cache invalidation: only purges on relevant `publish` transitions, skips autosaves/revisions
- HTTP cache headers (`Cache-Control`, `Last-Modified`, `ETag`) with `304 Not Modified` support
- `X-Robots-Tag: noindex, follow` header on the feed response
- Extensibility hooks for query args, item payload, and final XML
- `uninstall.php` cleans options and transients on plugin delete

## Plugin Structure

```text
wp-custom-rss-feed.php
uninstall.php
includes/
  class-wcrss-plugin.php
  class-wcrss-settings.php
  class-wcrss-feed.php
templates/
  feed-rss2.php
languages/
readme.txt
LICENSE
```

## Hooks

- `wcrss_feed_query_args`
- `wcrss_feed_item`
- `wcrss_feed_xml`

## How to test

1. Activate the plugin in **Plugins**.
2. Open **Settings → Custom RSS Feed** and confirm the feed URL shown on the page.
3. Visit `https://example.com/feed/xml-feed/` (or your configured slug) and verify RSS XML output.
4. Change slug / cache TTL / post types / taxonomy terms, save, and confirm the updated URL and filtering work.
5. Request the feed twice; verify the second request returns `304 Not Modified` when `If-None-Match` is sent.
6. Publish or update a post in a selected post type and reload the feed to confirm cache invalidation.
7. Click **Purge cache now** on the settings page and confirm the feed is regenerated.
