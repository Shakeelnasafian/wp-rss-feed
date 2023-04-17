<?php
/* This file will contain the extra functions required to build the custom feeds and 
to keep the main index file clean. */


function struct_the_feed_data( &$item, $post_categories ){
    $item->id = get_the_ID();
    $item->guid = trim(get_the_guid());
    $item->title = get_the_title_rss();
    $item->link = esc_url(apply_filters('the_permalink_rss', get_permalink()));
    $item->pubDate = mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false);

    $categories = '';
    foreach ($post_categories as $c) {
        $cat = get_category($c);
        $categories .= '<category><![CDATA[' . $cat->name . ']]></category>';
    }

    $item->categories = $categories;

    $rssExcerpt = apply_filters('the_excerpt_rss', get_the_excerpt());

    $description =  $rssExcerpt ;

    $item->description = $description;

    $contentEncoded = '';
    if (!get_option('rss_use_excerpt')) {
        $content = get_the_content_feed('rss2');
        
        if (strlen($content) > 0) {
            $contact_info = get_post_meta(get_the_ID(), 'cf_cont_info', true);
            $c_info_html = '';
            if (isset($contact_info) && $contact_info !== '') {
                $c_info_html = htmlentities("<div>
                          <h3>Contact Information:</h3>
                          <p>" . nl2br($contact_info) . "</p>
                       </div>");
                 $content .= $c_info_html;
            }

            $contentEncoded = '<![CDATA[' . $content . ']]>';
        } 

        
    }else {
        $contentEncoded = '<![CDATA[' . $rssExcerpt . ']]>';

    }
    $item->contentEncoded = $contentEncoded;
}