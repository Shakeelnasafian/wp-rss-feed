<?php
    /*
        Plugin Name: WP Custom RSS Feeds
        Plugin URI: https://github.com/Shakeelnasafian
        Description: This plugin will create custom rss feed.
        Author: Shakeel Ahmad
        Version: 1.0
        Author URI: https://stackoverflow.com/users/9167174/shakeel-ahmad
    */

    add_action('init', 'customRSS');
    function customRSS(){
        /*
         * xml-feed is name of feed.
         * e.g
         * http://domain/xml-feed
         */

        add_feed('xml-feed', 'exoprt_xml_feed');
        
    }

    function exoprt_xml_feed(){
        $postCount = 70; // The number of posts to show in the feed
        $posts = query_posts('showposts=' . $postCount);
        header('Content-Type: '.feed_content_type('rss-http').'; charset='.get_option('blog_charset'), true);
        echo '<?xml version="1.0" encoding="utf-8"?>';
        ?>
        <rss version="2.0"
             xmlns:content="http://purl.org/rss/1.0/modules/content/"
             xmlns:wfw="http://wellformedweb.org/CommentAPI/"
             xmlns:dc="http://purl.org/dc/elements/1.1/"
             xmlns:atom="http://www.w3.org/2005/Atom"
             xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
             xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
             xmlns:lg="http://purl.org/dc/elements/1.1/language"
            <?php do_action( 'rss2_ns' ); ?>>
            <channel>
                <title><?php bloginfo_rss('name'); ?> - Feed</title>
                <atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
                <link><?php bloginfo_rss('url') ?></link>
                <description><?php bloginfo_rss('description') ?></description>
                <lastBuildDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_lastpostmodified('GMT'), false); ?></lastBuildDate>
                <language><?php echo get_option('rss_language'); ?></language>
                <sy:updatePeriod><?php echo apply_filters( 'rss_update_period', 'hourly' ); ?></sy:updatePeriod>
                <sy:updateFrequency><?php echo apply_filters( 'rss_update_frequency', '1' ); ?></sy:updateFrequency>
                <?php do_action('rss2_head'); ?>
                <?php while(have_posts()) : the_post(); ?>
                    <item>
                        <title><?php the_title_rss(); ?></title>
                        <link><?php the_permalink_rss(); ?></link>
                        <pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
                        <dc:creator>XML Feed</dc:creator>
                        <guid isPermaLink="false"><?php the_guid(); ?></guid>
                        <description><![CDATA[<?php the_excerpt_rss() ?>]]></description>
                        <content:encoded><![CDATA[<?php echo get_the_content_feed('rss2'); ?>]]></content:encoded>
                        <?php rss_enclosure(); ?>
                        <?php do_action('rss2_item'); ?>
                        <?php // Getting Post Categories
                        $post_categories = wp_get_post_categories( get_the_ID() );
                        $cats = array();
                        foreach($post_categories as $c){
                            $cat = get_category( $c );
                            $cats[] = array( 'name' => $cat->name, 'slug' => $cat->slug );
                            ?>
                        <category><![CDATA[<?php echo $cat->name; ?>]]></category>
                        <?php } ?>
                    </item>
                <?php endwhile; ?>
            </channel>
        </rss>
<?php
    }
    
