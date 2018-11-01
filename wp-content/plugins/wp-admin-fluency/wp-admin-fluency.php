<?php
/*
Plugin Name: Fluency Admin
Plugin URI: http://deanjrobinson.com/projects/fluency-admin/
Description: A rethink of the WordPress admin interface giving it a slightly more modern application-esque feel, inspired by <a href="htpt://orderedlist.com">Steve Smith's</a> Tiger Admin. <strong>WordPress 2.5+ only</strong>
Author: Dean Robinson
Version: 1.0
Author URI: http://deanjrobinson.com/
*/ 

function wp_admin_fluency_css() {
	echo '<link rel="stylesheet" type="text/css" href="' . get_settings('siteurl') . '/wp-content/plugins/wp-admin-fluency/resources/wp-admin.css?version=1.0" />'."\n";
}

add_action('admin_head', 'wp_admin_fluency_css',100);


/* Plugin Support */
/* I will be progressivly adding additional plugin css support in the future, if you have a plugin that you would ike me to support please contact me. */

/* Post Ratings */
function ratings_admin_css() {
	echo '<link rel="stylesheet" type="text/css" href="' . get_settings('siteurl') . '/wp-content/plugins/wp-admin-fluency/resources/plugins/postratings.css?version=1.0" />'."\n";
}
if(preg_match("/postratings/",$_GET['page'])) { add_action('admin_head', 'ratings_admin_css',101); }


?>