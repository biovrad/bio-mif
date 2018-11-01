<?php
/*
Plugin Name: Simple Feed Copyright
Author: Quick Online Tips
Author URI: http://www.quickonlinetips.com/
Version: 1.0
Description: Adds a simple copyright notice at end of full text articles in your feed. 
Plugin URI: http://www.quickonlinetips.com/archives/simple-feed-copyright-wordpress-plugin/

*/

$notice = '<p>&copy;' . date("Y") . ' <a href="' . get_bloginfo('url') . '">' . get_bloginfo('name') . '</a>. All Rights Reserved.</p>.';

add_filter('the_content', 'add_notice');

function add_notice( $content ) {
	global $notice;    
     if( is_feed() ) {
        return $content.$notice;
    } else {
        return $content;
    }
}
?>