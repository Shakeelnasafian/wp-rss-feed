<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin bootstrapper and singleton coordinator.
 */
class WCRSS_Plugin {
	const OPTION_KEY       = 'wcrss_settings';
	const FLUSH_OPTION_KEY = 'wcrss_flush_rewrite';

	/** @var WCRSS_Plugin|null */
	private static $instance = null;

	/** @var WCRSS_Settings */
	private $settings;

	/** @var WCRSS_Feed */
	private $feed;

	/**
	 * Get or create the singleton.
	 *
	 * @return WCRSS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->settings = new WCRSS_Settings( self::OPTION_KEY, self::FLUSH_OPTION_KEY );
		$this->feed     = new WCRSS_Feed( $this->settings );

		add_action( 'plugins_loaded', array( $this, 'load_textdomain' ) );
		add_action( 'init', array( $this->feed, 'register_feed' ) );
		add_action( 'init', array( $this, 'maybe_flush_rewrites' ), 20 );

		add_filter( 'plugin_action_links_' . plugin_basename( WCRSS_PLUGIN_FILE ), array( $this, 'plugin_action_links' ) );
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'wp-custom-rss-feed',
			false,
			dirname( plugin_basename( WCRSS_PLUGIN_FILE ) ) . '/languages'
		);
	}

	/**
	 * Flush rewrite rules once if a slug change scheduled it.
	 */
	public function maybe_flush_rewrites() {
		if ( get_option( self::FLUSH_OPTION_KEY ) ) {
			flush_rewrite_rules();
			delete_option( self::FLUSH_OPTION_KEY );
		}
	}

	/**
	 * Add a Settings shortcut on the Plugins list row.
	 *
	 * @param array $links
	 * @return array
	 */
	public function plugin_action_links( $links ) {
		$settings_link = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'options-general.php?page=wcrss-settings' ) ),
			esc_html__( 'Settings', 'wp-custom-rss-feed' )
		);
		array_unshift( $links, $settings_link );
		return $links;
	}

	/**
	 * Activation hook: register feed and flush rewrites.
	 */
	public static function activate() {
		$plugin = self::instance();
		$plugin->feed->register_feed();
		flush_rewrite_rules();
	}

	/**
	 * Deactivation hook: flush rewrites so our feed endpoint is removed.
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
