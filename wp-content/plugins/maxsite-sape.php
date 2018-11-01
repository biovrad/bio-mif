<?php
/*
Plugin Name: MaxSite Sape
Version: 0.2
Plugin URI: http://maxsite.org/
Description:  SAPE.RU
Author: MAX
Author URI: http://maxsite.org/
*/


$sape_user = 'wp-content/plugins';


$sape_parameters['UTF-8c'] = 'UTF-8T'; 


##################################
##         Stop editing!
##################################

remove_filter('the_content', 'wptexturize');
add_filter('the_content', 'maxsite_sape_replace');
remove_filter('the_excerpt', 'wptexturize');
add_filter('the_excerpt', 'maxsite_sape_replace');

global $sape_user, $sape_context;

if ( !defined('_SAPE_USER') ) define('_SAPE_USER', $sape_user);
require_once($_SERVER['DOCUMENT_ROOT'].'/'._SAPE_USER.'/sape.php');
 
if ( !isset($sape_context) ) $sape_context = new SAPE_context($sape_parameters);
unset($sape_charset);

function maxsite_sape_replace($content) {
	global $sape_context;
	$content = $sape_context->replace_in_text_segment($content);
	return $content;
}

?>
