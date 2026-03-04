<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCRSS_Plugin {
	const OPTION_KEY      = 'wcrss_settings';
	const FLUSH_OPTION_KEY = 'wcrss_flush_rewrite';

	/** @var WCRSS_Plugin|null */
	private static $instance = null;

	/** @var WCRSS_Settings */
	private $settings;

	/** @var WCRSS_Feed */
	private $feed;

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
	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wp-custom-rss-feed', false, dirname( plugin_basename( __DIR__ . '/../wp-custom-rss-feed.php' ) ) . '/languages' );
	}

	public function maybe_flush_rewrites() {
		if ( get_option( self::FLUSH_OPTION_KEY ) ) {
			flush_rewrite_rules();
			delete_option( self::FLUSH_OPTION_KEY );
		}
	}

	public static function activate() {
		$settings = new WCRSS_Settings( self::OPTION_KEY, self::FLUSH_OPTION_KEY );
		$feed     = new WCRSS_Feed( $settings );
		$feed->register_feed();
		flush_rewrite_rules();
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}
}
