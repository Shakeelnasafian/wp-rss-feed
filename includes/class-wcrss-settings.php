<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles plugin settings registration, rendering, and sanitization.
 */
class WCRSS_Settings {
	const MAX_ITEMS = 500;
	const MIN_CACHE_MINUTES = 1;
	const MAX_CACHE_MINUTES = 1440;

	/** @var string */
	private $option_key;

	/** @var string */
	private $flush_option_key;

	/**
	 * @param string $option_key
	 * @param string $flush_option_key
	 */
	public function __construct( $option_key, $flush_option_key ) {
		$this->option_key       = $option_key;
		$this->flush_option_key = $flush_option_key;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Default settings values.
	 *
	 * @return array
	 */
	public function defaults() {
		return array(
			'feed_slug'       => 'xml-feed',
			'items_per_feed'  => 70,
			'post_types'      => array( 'post' ),
			'full_content'    => 0,
			'cache_ttl'       => 10 * MINUTE_IN_SECONDS,
			'taxonomy'        => '',
			'terms'           => array(),
			'include_thumb'   => 1,
		);
	}

	/**
	 * Merged settings (saved values over defaults).
	 *
	 * @return array
	 */
	public function get_settings() {
		return wp_parse_args( get_option( $this->option_key, array() ), $this->defaults() );
	}

	/**
	 * Register admin submenu page.
	 */
	public function register_menu() {
		add_options_page(
			__( 'Custom RSS Feed', 'wp-custom-rss-feed' ),
			__( 'Custom RSS Feed', 'wp-custom-rss-feed' ),
			'manage_options',
			'wcrss-settings',
			array( $this, 'render_settings_page' )
		);
	}

	/**
	 * Register settings, sections, and fields.
	 */
	public function register_settings() {
		register_setting(
			'wcrss_settings_group',
			$this->option_key,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
				'show_in_rest'      => false,
			)
		);

		add_settings_section(
			'wcrss_main_section',
			__( 'Feed Configuration', 'wp-custom-rss-feed' ),
			'__return_false',
			'wcrss-settings'
		);

		add_settings_field( 'feed_slug', __( 'Feed slug', 'wp-custom-rss-feed' ), array( $this, 'render_feed_slug' ), 'wcrss-settings', 'wcrss_main_section' );
		add_settings_field( 'items_per_feed', __( 'Items per feed', 'wp-custom-rss-feed' ), array( $this, 'render_items_per_feed' ), 'wcrss-settings', 'wcrss_main_section' );
		add_settings_field( 'post_types', __( 'Post types', 'wp-custom-rss-feed' ), array( $this, 'render_post_types' ), 'wcrss-settings', 'wcrss_main_section' );
		add_settings_field( 'full_content', __( 'Content output', 'wp-custom-rss-feed' ), array( $this, 'render_full_content' ), 'wcrss-settings', 'wcrss_main_section' );
		add_settings_field( 'include_thumb', __( 'Featured image', 'wp-custom-rss-feed' ), array( $this, 'render_include_thumb' ), 'wcrss-settings', 'wcrss_main_section' );
		add_settings_field( 'cache_ttl', __( 'Cache TTL (minutes)', 'wp-custom-rss-feed' ), array( $this, 'render_cache_ttl' ), 'wcrss-settings', 'wcrss_main_section' );
		add_settings_field( 'taxonomy', __( 'Filter by taxonomy', 'wp-custom-rss-feed' ), array( $this, 'render_taxonomy' ), 'wcrss-settings', 'wcrss_main_section' );
		add_settings_field( 'terms', __( 'Terms (slugs)', 'wp-custom-rss-feed' ), array( $this, 'render_terms' ), 'wcrss-settings', 'wcrss_main_section' );
	}

	/**
	 * Sanitize and validate submitted settings.
	 *
	 * @param array $input
	 * @return array
	 */
	public function sanitize_settings( $input ) {
		$current = $this->get_settings();

		$slug = isset( $input['feed_slug'] ) ? sanitize_key( $input['feed_slug'] ) : $current['feed_slug'];
		if ( empty( $slug ) ) {
			$slug = 'xml-feed';
		}

		$items = isset( $input['items_per_feed'] ) ? absint( $input['items_per_feed'] ) : $current['items_per_feed'];
		if ( $items < 1 ) {
			$items = 70;
		}
		if ( $items > self::MAX_ITEMS ) {
			$items = self::MAX_ITEMS;
		}

		$registered = get_post_types( array( 'public' => true ) );
		$post_types = array();
		if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
			foreach ( $input['post_types'] as $post_type ) {
				$post_type = sanitize_key( $post_type );
				if ( $post_type && isset( $registered[ $post_type ] ) ) {
					$post_types[] = $post_type;
				}
			}
		}
		if ( empty( $post_types ) ) {
			$post_types = array( 'post' );
		}

		$cache_minutes = isset( $input['cache_ttl'] ) ? absint( $input['cache_ttl'] ) : (int) round( $current['cache_ttl'] / MINUTE_IN_SECONDS );
		if ( $cache_minutes < self::MIN_CACHE_MINUTES ) {
			$cache_minutes = self::MIN_CACHE_MINUTES;
		}
		if ( $cache_minutes > self::MAX_CACHE_MINUTES ) {
			$cache_minutes = self::MAX_CACHE_MINUTES;
		}

		$taxonomy = '';
		if ( ! empty( $input['taxonomy'] ) ) {
			$candidate = sanitize_key( $input['taxonomy'] );
			if ( taxonomy_exists( $candidate ) ) {
				$taxonomy = $candidate;
			}
		}

		$terms = array();
		if ( $taxonomy && ! empty( $input['terms'] ) ) {
			$list = is_array( $input['terms'] ) ? $input['terms'] : explode( ',', (string) $input['terms'] );
			foreach ( $list as $term ) {
				$term = sanitize_title( trim( $term ) );
				if ( $term ) {
					$terms[] = $term;
				}
			}
			$terms = array_values( array_unique( $terms ) );
		}

		$sanitized = array(
			'feed_slug'      => $slug,
			'items_per_feed' => $items,
			'post_types'     => array_values( array_unique( $post_types ) ),
			'full_content'   => ! empty( $input['full_content'] ) ? 1 : 0,
			'cache_ttl'      => $cache_minutes * MINUTE_IN_SECONDS,
			'taxonomy'       => $taxonomy,
			'terms'          => $terms,
			'include_thumb'  => ! empty( $input['include_thumb'] ) ? 1 : 0,
		);

		if ( $current['feed_slug'] !== $sanitized['feed_slug'] ) {
			update_option( $this->flush_option_key, 1 );
		}

		return $sanitized;
	}

	public function render_feed_slug() {
		$settings = $this->get_settings();
		echo '<input type="text" name="' . esc_attr( $this->option_key ) . '[feed_slug]" value="' . esc_attr( $settings['feed_slug'] ) . '" class="regular-text" />';
	}

	public function render_items_per_feed() {
		$settings = $this->get_settings();
		echo '<input type="number" min="1" max="' . esc_attr( (string) self::MAX_ITEMS ) . '" name="' . esc_attr( $this->option_key ) . '[items_per_feed]" value="' . esc_attr( $settings['items_per_feed'] ) . '" class="small-text" />';
		echo '<p class="description">' . esc_html( sprintf( /* translators: %d: maximum items */ __( 'Maximum %d.', 'wp-custom-rss-feed' ), self::MAX_ITEMS ) ) . '</p>';
	}

	public function render_post_types() {
		$settings   = $this->get_settings();
		$registered = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $registered as $slug => $object ) {
			$checked = in_array( $slug, (array) $settings['post_types'], true ) ? 'checked="checked"' : '';
			printf(
				'<label style="margin-right:12px;"><input type="checkbox" name="%1$s[post_types][]" value="%2$s" %3$s /> %4$s</label>',
				esc_attr( $this->option_key ),
				esc_attr( $slug ),
				$checked, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				esc_html( $object->labels->singular_name . ' (' . $slug . ')' )
			);
		}
	}

	public function render_full_content() {
		$settings = $this->get_settings();
		echo '<label><input type="checkbox" name="' . esc_attr( $this->option_key ) . '[full_content]" value="1" ' . checked( 1, $settings['full_content'], false ) . ' /> ' . esc_html__( 'Include full content instead of excerpt', 'wp-custom-rss-feed' ) . '</label>';
	}

	public function render_include_thumb() {
		$settings = $this->get_settings();
		echo '<label><input type="checkbox" name="' . esc_attr( $this->option_key ) . '[include_thumb]" value="1" ' . checked( 1, $settings['include_thumb'], false ) . ' /> ' . esc_html__( 'Include featured image as <media:thumbnail>', 'wp-custom-rss-feed' ) . '</label>';
	}

	public function render_cache_ttl() {
		$settings = $this->get_settings();
		$minutes  = (int) round( $settings['cache_ttl'] / MINUTE_IN_SECONDS );
		echo '<input type="number" min="' . esc_attr( (string) self::MIN_CACHE_MINUTES ) . '" max="' . esc_attr( (string) self::MAX_CACHE_MINUTES ) . '" name="' . esc_attr( $this->option_key ) . '[cache_ttl]" value="' . esc_attr( (string) $minutes ) . '" class="small-text" />';
		echo '<p class="description">' . esc_html__( 'Transient cache lifetime in minutes.', 'wp-custom-rss-feed' ) . '</p>';
	}

	public function render_taxonomy() {
		$settings   = $this->get_settings();
		$taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		echo '<select name="' . esc_attr( $this->option_key ) . '[taxonomy]">';
		echo '<option value="">' . esc_html__( '— None —', 'wp-custom-rss-feed' ) . '</option>';
		foreach ( $taxonomies as $slug => $object ) {
			printf(
				'<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( $slug ),
				selected( $settings['taxonomy'], $slug, false ),
				esc_html( $object->labels->singular_name . ' (' . $slug . ')' )
			);
		}
		echo '</select>';
	}

	public function render_terms() {
		$settings = $this->get_settings();
		$value    = is_array( $settings['terms'] ) ? implode( ',', $settings['terms'] ) : '';
		echo '<input type="text" name="' . esc_attr( $this->option_key ) . '[terms]" value="' . esc_attr( $value ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Comma-separated term slugs. Leave empty to include all terms of the selected taxonomy.', 'wp-custom-rss-feed' ) . '</p>';
	}

	/**
	 * Render the settings page and the Purge Cache form.
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$purged = isset( $_GET['wcrss_purged'] ) && '1' === $_GET['wcrss_purged']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$settings = $this->get_settings();
		$feed_url = trailingslashit( home_url( 'feed/' . $settings['feed_slug'] ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Custom RSS Feed', 'wp-custom-rss-feed' ); ?></h1>
			<?php if ( $purged ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Feed cache purged.', 'wp-custom-rss-feed' ); ?></p></div>
			<?php endif; ?>
			<form action="options.php" method="post">
				<?php
				settings_fields( 'wcrss_settings_group' );
				do_settings_sections( 'wcrss-settings' );
				submit_button();
				?>
			</form>
			<p><strong><?php esc_html_e( 'Current feed URL:', 'wp-custom-rss-feed' ); ?></strong>
				<a href="<?php echo esc_url( $feed_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $feed_url ); ?></a>
			</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="wcrss_purge_cache" />
				<?php wp_nonce_field( 'wcrss_purge_cache' ); ?>
				<?php submit_button( __( 'Purge cache now', 'wp-custom-rss-feed' ), 'secondary', 'wcrss_purge', false ); ?>
			</form>
		</div>
		<?php
	}
}
