# WP Custom RSS Feed

Production-ready WordPress plugin that registers a configurable RSS2 endpoint and renders feed XML using WordPress best practices.

## Features

- Custom feed endpoint (`/feed/xml-feed/` by default)
- Settings page under **Settings → Custom RSS Feed**
- Configurable slug, items-per-feed, post types, excerpt/full-content mode
- Transient caching (10 minute TTL)
- Cache invalidation on content changes
- Extensibility hooks for query args, item payload, and final XML

## Plugin Structure

```text
wp-custom-rss-feed.php
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
4. Change slug/items settings, save, and confirm the updated URL works without manually saving permalinks.
5. Load the feed twice (warm cache), then edit/publish a post and reload the feed to confirm cache invalidation.
