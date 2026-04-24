<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the custom feed endpoint and renders cached RSS2 output.
 */
class WCRSS_Feed {
	const LAST_MODIFIED_OPTION = 'wcrss_feed_last_modified';

	/** @var WCRSS_Settings */
	private $settings_handler;

	/**
	 * @param WCRSS_Settings $settings_handler
	 */
	public function __construct( WCRSS_Settings $settings_handler ) {
		$this->settings_handler = $settings_handler;

		add_action( 'transition_post_status', array( $this, 'on_transition_post_status' ), 10, 3 );
		add_action( 'deleted_post', array( $this, 'on_deleted_post' ) );
		add_action( 'admin_post_wcrss_purge_cache', array( $this, 'handle_manual_purge' ) );
	}

	/**
	 * Register the custom feed slug with WordPress.
	 */
	public function register_feed() {
		$settings = $this->settings_handler->get_settings();
		add_feed( $settings['feed_slug'], array( $this, 'render_feed' ) );
	}

	/**
	 * Build the WP_Query args for the feed.
	 *
	 * @param array $settings
	 * @return array
	 */
	public function build_query_args( $settings ) {
		$args = array(
			'post_type'           => $settings['post_types'],
			'post_status'         => 'publish',
			'posts_per_page'      => $settings['items_per_feed'],
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
		);

		if ( ! empty( $settings['taxonomy'] ) && ! empty( $settings['terms'] ) && taxonomy_exists( $settings['taxonomy'] ) ) {
			$args['tax_query'] = array(
				array(
					'taxonomy' => $settings['taxonomy'],
					'field'    => 'slug',
					'terms'    => $settings['terms'],
				),
			);
		}

		return apply_filters( 'wcrss_feed_query_args', $args, $settings );
	}

	/**
	 * Deterministic cache key based on settings.
	 *
	 * @param array $settings
	 * @return string
	 */
	public function get_cache_key( $settings ) {
		$hash = md5( wp_json_encode( $settings ) );
		return 'wcrss_feed_' . $hash;
	}

	/**
	 * Render the feed, serving a cached copy and HTTP cache headers when possible.
	 */
	public function render_feed() {
		$settings  = $this->settings_handler->get_settings();
		$cache_key = $this->get_cache_key( $settings );
		$cached    = get_transient( $cache_key );

		$last_modified_gmt = (int) get_option( self::LAST_MODIFIED_OPTION, 0 );
		if ( ! $last_modified_gmt ) {
			$last_modified_gmt = strtotime( get_lastpostmodified( 'GMT' ) );
			if ( ! $last_modified_gmt ) {
				$last_modified_gmt = time();
			}
		}

		$etag = '"' . md5( $cache_key . '|' . $last_modified_gmt . '|' . WCRSS_PLUGIN_VERSION ) . '"';

		header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );
		header( 'X-Robots-Tag: noindex, follow', true );
		header( 'Cache-Control: public, max-age=' . absint( $settings['cache_ttl'] ) );
		header( 'Last-Modified: ' . gmdate( 'D, d M Y H:i:s', $last_modified_gmt ) . ' GMT' );
		header( 'ETag: ' . $etag );

		if ( $this->client_has_fresh_copy( $etag, $last_modified_gmt ) ) {
			status_header( 304 );
			return;
		}

		if ( false !== $cached ) {
			echo $cached; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		$query_args = $this->build_query_args( $settings );
		$query      = new WP_Query( $query_args );

		$template_args = array(
			'settings' => $settings,
			'query'    => $query,
		);

		ob_start();
		load_template( WCRSS_PLUGIN_DIR . 'templates/feed-rss2.php', false, $template_args );
		$xml = ob_get_clean();

		wp_reset_postdata();

		$xml = apply_filters( 'wcrss_feed_xml', $xml, $settings );
		set_transient( $cache_key, $xml, absint( $settings['cache_ttl'] ) );

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Decide whether to short-circuit with HTTP 304.
	 *
	 * @param string $etag
	 * @param int    $last_modified_gmt
	 * @return bool
	 */
	private function client_has_fresh_copy( $etag, $last_modified_gmt ) {
		$if_none_match = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) ? trim( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) : '';
		if ( $if_none_match && $if_none_match === $etag ) {
			return true;
		}

		$if_modified_since = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ? wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) : '';
		if ( $if_modified_since ) {
			$client_time = strtotime( $if_modified_since );
			if ( $client_time && $client_time >= $last_modified_gmt ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Purge cache when a post transitions into or out of `publish`.
	 *
	 * @param string  $new_status
	 * @param string  $old_status
	 * @param WP_Post $post
	 */
	public function on_transition_post_status( $new_status, $old_status, $post ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}
		if ( wp_is_post_revision( $post ) || wp_is_post_autosave( $post ) ) {
			return;
		}

		$settings = $this->settings_handler->get_settings();
		if ( ! in_array( $post->post_type, (array) $settings['post_types'], true ) ) {
			return;
		}

		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			$this->purge_cache();
		}
	}

	/**
	 * Purge cache when a published post is deleted.
	 *
	 * @param int $post_id
	 */
	public function on_deleted_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post || 'publish' !== $post->post_status ) {
			return;
		}

		$settings = $this->settings_handler->get_settings();
		if ( ! in_array( $post->post_type, (array) $settings['post_types'], true ) ) {
			return;
		}

		$this->purge_cache();
	}

	/**
	 * Handle the manual purge form submission from the settings page.
	 */
	public function handle_manual_purge() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'wp-custom-rss-feed' ) );
		}
		check_admin_referer( 'wcrss_purge_cache' );

		$this->purge_cache();

		wp_safe_redirect( add_query_arg( array( 'wcrss_purged' => '1' ), admin_url( 'options-general.php?page=wcrss-settings' ) ) );
		exit;
	}

	/**
	 * Delete the cached feed XML and bump the last-modified marker.
	 */
	public function purge_cache() {
		$settings = $this->settings_handler->get_settings();
		delete_transient( $this->get_cache_key( $settings ) );
		update_option( self::LAST_MODIFIED_OPTION, time(), false );
	}
}
