<?php
/**
 * Plugin Name: WP Custom RSS Feed
 * Description: Adds a configurable custom RSS2 feed endpoint with caching and feed settings.
 * Version: 1.1.0
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

require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcrss-settings.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcrss-feed.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wcrss-plugin.php';

function wcrss_plugin() {
	return WCRSS_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'WCRSS_Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WCRSS_Plugin', 'deactivate' ) );

wcrss_plugin();
