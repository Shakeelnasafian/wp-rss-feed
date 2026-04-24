<?php
/**
 * Plugin Name: WP Custom RSS Feed
 * Description: Adds a configurable custom RSS2 feed endpoint with caching, taxonomy filters, featured images, and HTTP cache headers.
 * Version: 1.2.0
 * Author: Shakeel Ahmad
 * License: GPL-2.0-or-later
 * Text Domain: wp-custom-rss-feed
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Tested up to: 6.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WCRSS_PLUGIN_FILE', __FILE__ );
define( 'WCRSS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WCRSS_PLUGIN_VERSION', '1.2.0' );

require_once WCRSS_PLUGIN_DIR . 'includes/class-wcrss-settings.php';
require_once WCRSS_PLUGIN_DIR . 'includes/class-wcrss-feed.php';
require_once WCRSS_PLUGIN_DIR . 'includes/class-wcrss-plugin.php';

/**
 * Retrieve the plugin singleton.
 *
 * @return WCRSS_Plugin
 */
function wcrss_plugin() {
	return WCRSS_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'WCRSS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WCRSS_Plugin', 'deactivate' ) );

wcrss_plugin();
