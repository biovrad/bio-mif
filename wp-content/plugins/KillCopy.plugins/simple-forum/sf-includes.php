<?php
/*
Simple Forum 2.1
Includes and Control Routines
*/

global $wpdb, $wp_version;

define('SFVERSION', '2.1');
define('SFBUILD', 237);

$siteurl = trailingslashit(get_option('siteurl'));

define('SFURL', sf_get_sfurl(get_option('sfpermalink')));
define('SFQURL', sf_get_sfqurl(SFURL));

define('SFADMINURL', $siteurl.'wp-content/plugins/'.basename(dirname(__FILE__)).'/admin/');

$csspath = get_option('sfskin');
if(empty($csspath)) $csspath='default';
define('SFSKINCSS', $siteurl.'wp-content/plugins/'.basename(dirname(__FILE__)).'/skins/'.$csspath.'/'.$csspath.'.css');

$iconpath = get_option('sficon');
if(empty($iconpath)) $iconpath='default';
define('SFRESOURCES', $siteurl.'wp-content/plugins/'.basename(dirname(__FILE__)).'/icons/'.$iconpath.'/');

define('SFJSCRIPT', $siteurl.'wp-content/plugins/'.basename(dirname(__FILE__)).'/jscript/');

//2.1 Patch 1
//define('SFAVATARS', 'wp-content/forum-avatars/');
define('SFAVATARS', ABSPATH.'wp-content/forum-avatars/');

define('SFAVATARURL', $siteurl.'wp-content/forum-avatars/');
define('SFRTE', $siteurl.'wp-content/plugins/'.basename(dirname(__FILE__)).'/tinymce/');

define('SFGROUPS', $wpdb->prefix.'sfgroups');
define('SFFORUMS', $wpdb->prefix.'sfforums');
define('SFTOPICS', $wpdb->prefix.'sftopics');
define('SFPOSTS', $wpdb->prefix.'sfposts');
define('SFWAITING', $wpdb->prefix.'sfwaiting');
define('SFTRACK', $wpdb->prefix.'sftrack');
define('SFSETTINGS', $wpdb->prefix.'sfsettings');
define('SFNOTICE', $wpdb->prefix.'sfnotice');
define('SFUSERS', $wpdb->prefix.'users');
define('SFUSERMETA', $wpdb->prefix.'usermeta');

define('SFLOGIN', SFQURL.'&action=login');
define('SFLOGOUT', $siteurl.'wp-login.php?action=logout&amp;redirect_to='.SFURL);
define('SFLOSTPASSWORD', SFQURL.'&action=lostpassword');
define('SFRESETPASSWORD', SFQURL.'&action=resetpass');
define('SFREGISTER', SFQURL.'&action=register');
define('SFPROFILE', SFQURL.'&amp;profile=user');

define('ADMINID', get_option('sfadmin'));
define('ADMINNAME', get_usermeta(ADMINID, $wpdb->prefix.'sfadmin'));

define('SFPNSHOW', 3);

define('SFDATES', get_option('sfdates'));
define('SFTIMES', get_option('sftimes'));


function sf_wp_version()
{
	global $wp_version;
	
	return substr($wp_version, 2, 1);
}

function sf_get_sfurl($url)
{	
	// remove trailing slash
	if('/' == substr($url, -1)) 
	{
		$url = substr($url, 0, strlen($url)-1);
	}
	return $url;
}

function sf_get_sfqurl($url)
{
	// if no ? then add one on the end
	if(strpos($url, '?') === false)
	{
		$url .= '?';
	}
	return $url;
}

// 2.1 Patch 1
// New function to detect if default permalink which also adds ampersand...
function sf_get_sfurl_plus_amp($url)
{
	// if no ? then add one on the end
	if(strpos($url, '?') === false)
	{
		$url .= '?';
	} else {
		$url .= '&amp;';
	}
	return $url;
}





?>