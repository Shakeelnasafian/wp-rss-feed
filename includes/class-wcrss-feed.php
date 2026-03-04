<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCRSS_Feed {
	/** @var WCRSS_Settings */
	private $settings_handler;

	public function __construct( WCRSS_Settings $settings_handler ) {
		$this->settings_handler = $settings_handler;

		add_action( 'save_post', array( $this, 'purge_cache' ) );
		add_action( 'deleted_post', array( $this, 'purge_cache' ) );
		add_action( 'transition_post_status', array( $this, 'purge_cache' ) );
	}

	public function register_feed() {
		$settings = $this->settings_handler->get_settings();
		add_feed( $settings['feed_slug'], array( $this, 'render_feed' ) );
	}

	public function build_query_args( $settings ) {
		$args = array(
			'post_type'           => $settings['post_types'],
			'post_status'         => 'publish',
			'posts_per_page'      => $settings['items_per_feed'],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		return apply_filters( 'wcrss_feed_query_args', $args, $settings );
	}

	public function get_cache_key( $settings ) {
		$hash = md5( wp_json_encode( $settings ) );
		return 'wcrss_feed_' . $hash;
	}

	public function render_feed() {
		$settings  = $this->settings_handler->get_settings();
		$cache_key = $this->get_cache_key( $settings );
		$cached    = get_transient( $cache_key );

		header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );

		if ( false !== $cached ) {
			echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$query_args = $this->build_query_args( $settings );
		$query      = new WP_Query( $query_args );

		global $wcrss_feed_context;
		$wcrss_feed_context = array(
			'settings' => $settings,
			'query'    => $query,
		);

		ob_start();
		load_template( plugin_dir_path( __DIR__ ) . 'templates/feed-rss2.php', false );
		$xml = ob_get_clean();

		wp_reset_postdata();

		$xml = apply_filters( 'wcrss_feed_xml', $xml, $settings );
		set_transient( $cache_key, $xml, absint( $settings['cache_ttl'] ) );

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	public function purge_cache() {
		$settings = $this->settings_handler->get_settings();
		delete_transient( $this->get_cache_key( $settings ) );
	}
}
