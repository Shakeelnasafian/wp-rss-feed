<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wcrss_feed_context;
$settings = isset( $wcrss_feed_context['settings'] ) ? $wcrss_feed_context['settings'] : array();
$query    = isset( $wcrss_feed_context['query'] ) ? $wcrss_feed_context['query'] : null;

if ( ! $query instanceof WP_Query ) {
	return;
}

echo '<?xml version="1.0" encoding="' . esc_attr( get_option( 'blog_charset' ) ) . '"?';
?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php do_action( 'rss2_ns' ); ?>>
<channel>
	<title><?php bloginfo_rss( 'name' ); ?> - <?php esc_html_e( 'Feed', 'wp-custom-rss-feed' ); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss( 'url' ); ?></link>
	<description><?php bloginfo_rss( 'description' ); ?></description>
	<lastBuildDate><?php echo esc_html( mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ) ); ?></lastBuildDate>
	<language><?php echo esc_html( get_bloginfo( 'language' ) ); ?></language>
	<sy:updatePeriod><?php echo esc_html( apply_filters( 'rss_update_period', 'hourly' ) ); ?></sy:updatePeriod>
	<sy:updateFrequency><?php echo esc_html( apply_filters( 'rss_update_frequency', '1' ) ); ?></sy:updateFrequency>
	<?php do_action( 'rss2_head' ); ?>

	<?php
	while ( $query->have_posts() ) :
		$query->the_post();
		$item = array(
			'title'           => get_the_title(),
			'link'            => get_permalink(),
			'pub_date_rfc822' => get_post_time( 'D, d M Y H:i:s +0000', true ),
			'creator'         => get_the_author(),
			'guid'            => get_the_guid(),
			'excerpt'         => apply_filters( 'the_excerpt_rss', get_the_excerpt() ),
			'content'         => get_the_content_feed( 'rss2' ),
			'categories'      => wp_get_post_categories( get_the_ID(), array( 'fields' => 'names' ) ),
		);

		$item = apply_filters( 'wcrss_feed_item', $item, get_the_ID(), $settings );
		?>
		<item>
			<title><?php echo esc_html( $item['title'] ); ?></title>
			<link><?php echo esc_url( $item['link'] ); ?></link>
			<pubDate><?php echo esc_html( $item['pub_date_rfc822'] ); ?></pubDate>
			<dc:creator><![CDATA[<?php echo esc_html( $item['creator'] ); ?>]]></dc:creator>
			<guid isPermaLink="false"><?php echo esc_html( $item['guid'] ); ?></guid>
			<description><![CDATA[<?php echo wp_kses_post( $item['excerpt'] ); ?>]]></description>
			<?php if ( ! empty( $settings['full_content'] ) ) : ?>
				<content:encoded><![CDATA[<?php echo wp_kses_post( $item['content'] ); ?>]]></content:encoded>
			<?php endif; ?>
			<?php if ( ! empty( $item['categories'] ) && is_array( $item['categories'] ) ) : ?>
				<?php foreach ( $item['categories'] as $category_name ) : ?>
					<category><![CDATA[<?php echo esc_html( $category_name ); ?>]]></category>
				<?php endforeach; ?>
			<?php endif; ?>
			<?php rss_enclosure(); ?>
			<?php do_action( 'rss2_item' ); ?>
		</item>
	<?php endwhile; ?>
</channel>
</rss>
