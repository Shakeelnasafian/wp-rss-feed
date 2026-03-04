<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WCRSS_Settings {
	private $option_key;
	private $flush_option_key;

	public function __construct( $option_key, $flush_option_key ) {
		$this->option_key       = $option_key;
		$this->flush_option_key = $flush_option_key;

		add_action( 'admin_menu', array( $this, 'register_menu' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	public function defaults() {
		return array(
			'feed_slug'        => 'xml-feed',
			'items_per_feed'   => 70,
			'post_types'       => array( 'post' ),
			'full_content'     => 0,
			'cache_ttl'        => 10 * MINUTE_IN_SECONDS,
		);
	}

	public function get_settings() {
		return wp_parse_args( get_option( $this->option_key, array() ), $this->defaults() );
	}

	public function register_menu() {
		add_options_page(
			__( 'Custom RSS Feed', 'wp-custom-rss-feed' ),
			__( 'Custom RSS Feed', 'wp-custom-rss-feed' ),
			'manage_options',
			'wcrss-settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function register_settings() {
		register_setting(
			'wcrss_settings_group',
			$this->option_key,
			array( $this, 'sanitize_settings' )
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
	}

	public function sanitize_settings( $input ) {
		$current = $this->get_settings();

		$slug = isset( $input['feed_slug'] ) ? sanitize_key( $input['feed_slug'] ) : $current['feed_slug'];
		if ( empty( $slug ) ) {
			$slug = 'xml-feed';
		}

		$items = isset( $input['items_per_feed'] ) ? absint( $input['items_per_feed'] ) : $current['items_per_feed'];
		if ( 0 === $items ) {
			$items = 70;
		}

		$post_types = array();
		if ( isset( $input['post_types'] ) ) {
			$list = explode( ',', (string) $input['post_types'] );
			foreach ( $list as $post_type ) {
				$post_type = sanitize_key( trim( $post_type ) );
				if ( $post_type ) {
					$post_types[] = $post_type;
				}
			}
		}
		if ( empty( $post_types ) ) {
			$post_types = array( 'post' );
		}

		$sanitized = array(
			'feed_slug'      => $slug,
			'items_per_feed' => $items,
			'post_types'     => array_values( array_unique( $post_types ) ),
			'full_content'   => ! empty( $input['full_content'] ) ? 1 : 0,
			'cache_ttl'      => 10 * MINUTE_IN_SECONDS,
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
		echo '<input type="number" min="1" name="' . esc_attr( $this->option_key ) . '[items_per_feed]" value="' . esc_attr( $settings['items_per_feed'] ) . '" class="small-text" />';
	}

	public function render_post_types() {
		$settings = $this->get_settings();
		echo '<input type="text" name="' . esc_attr( $this->option_key ) . '[post_types]" value="' . esc_attr( implode( ',', $settings['post_types'] ) ) . '" class="regular-text" />';
		echo '<p class="description">' . esc_html__( 'Comma-separated post type slugs (example: post,page).', 'wp-custom-rss-feed' ) . '</p>';
	}

	public function render_full_content() {
		$settings = $this->get_settings();
		echo '<label><input type="checkbox" name="' . esc_attr( $this->option_key ) . '[full_content]" value="1" ' . checked( 1, $settings['full_content'], false ) . ' /> ' . esc_html__( 'Include full content instead of excerpt', 'wp-custom-rss-feed' ) . '</label>';
	}

	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = $this->get_settings();
		$feed_url = trailingslashit( home_url( 'feed/' . $settings['feed_slug'] ) );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Custom RSS Feed', 'wp-custom-rss-feed' ); ?></h1>
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
		</div>
		<?php
	}
}
