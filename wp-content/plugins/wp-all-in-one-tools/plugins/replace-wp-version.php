<?php

function fb_replace_wp_version() {
	if ( !is_admin() ) {
		global $wp_version;
		$v = intval(rand(0, 9999)); // random value
		if ( function_exists('the_generator') ) { // eliminate version for wordpress >= 2.4
			add_filter( 'the_generator', create_function('$a', "return null;") );
			// add_filter( 'wp_generator_type', create_function( '$a', "return null;" ) );
			// for $wp_version and db_version
			$wp_version = $v;
		} else { // for wordpress < 2.4
			add_filter( "bloginfo_rss('version')", create_function('$a', "return $v;") ); // for rdf and rss v0.92
			$wp_version = $v;
		}
	}
}

if (function_exists('add_action')) {add_action('init', fb_replace_wp_version, 1);}

?>