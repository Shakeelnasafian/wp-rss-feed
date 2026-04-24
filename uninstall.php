<?php
/**
 * Uninstall cleanup: remove plugin options and cached transients.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! function_exists( 'wcrss_uninstall_cleanup_site' ) ) {
	/**
	 * Delete plugin options and matching transients for the current site.
	 */
	function wcrss_uninstall_cleanup_site() {
		global $wpdb;

		delete_option( 'wcrss_settings' );
		delete_option( 'wcrss_flush_rewrite' );
		delete_option( 'wcrss_feed_last_modified' );

		$wpdb->query(
			"DELETE FROM {$wpdb->options}
			WHERE option_name LIKE '\\_transient\\_wcrss\\_feed\\_%'
			OR option_name LIKE '\\_transient\\_timeout\\_wcrss\\_feed\\_%'"
		);
	}
}

if ( is_multisite() ) {
	$sites = function_exists( 'get_sites' ) ? get_sites( array( 'fields' => 'ids' ) ) : array();
	foreach ( $sites as $site_id ) {
		switch_to_blog( $site_id );
		wcrss_uninstall_cleanup_site();
		restore_current_blog();
	}
} else {
	wcrss_uninstall_cleanup_site();
}
