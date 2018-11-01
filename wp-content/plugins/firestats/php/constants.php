<?php
// general
define('FS_VERSION','1.6.7-stable');
define('FS_HOMEPAGE','http://firestats.cc');
define('FS_FIRESTATS_VER_CHECK_URL','http://files.firestats.cc/firestats.latest?version='.FS_VERSION);
define('FS_IP2COUNTRY_DB_VER_CHECK_URL','http://files.firestats.cc/ip2c/ip-to-country.latest');
define('FS_WIKI','http://firestats.cc/wiki/');
define('FS_SYSINFO_URL','http://misc.firestats.cc/sysinfo.php');

// database related constants
define('FS_DB_VALID', 0);
define('FS_DB_NOT_INSTALLED', -1);
define('FS_DB_NEED_UPGRADE', -2);
define('FS_DB_IS_NEWER_THAN_CODE', -3);
define('FS_DB_GENERAL_ERROR', -4);
define('FS_DB_NOT_CONFIGURED', -5);
define('FS_DB_CONNECTION_ERROR', -6);

// the database schema version this code works with
define('FS_REQUIRED_DB_VERSION',15);

// site type constants
define('FS_SITE_TYPE_GENERIC'	,0);
define('FS_SITE_TYPE_WORDPRESS'	,1);
define('FS_SITE_TYPE_DJANGO'	,2);
define('FS_SITE_TYPE_DRUPAL'	,3);
define('FS_SITE_TYPE_GREGARIUS'	,4);
define('FS_SITE_TYPE_JOOMLA'	,5);
define('FS_SITE_TYPE_MEDIAWIKI'	,6);
define('FS_SITE_TYPE_TRAC'		,7);
define('FS_SITE_TYPE_GALLERY2'	,8);
define('FS_SITE_TYPE_HTML',9);

// security constants
define("SEC_ADMIN", 1);
define("SEC_USER", 2);
define("SEC_NONE", 3);


define('ORDER_BY_RECENT_FIRST'		,1);
define('ORDER_BY_HIGH_COUNT_FIRST'	,2);
define('ORDER_BY_FIRST_SEEN'		,3);

// Incomming hits are commited in a normalized form immediatelly.
define('FS_COMMIT_IMMEDIATE'	,1);

// Incomming hits are commited in a non-normalized form to a pending hits table.
// The site admin is responsible to call php/commit-pending.php to normalize hits and make them available to FireStats.
define('FS_COMMIT_MANUAL'		,2);

// Like FS_COMMIT_MANUAL, except hits are commited automatically period of time.
define('FS_COMMIT_AUTOMATIC'	,3);

// Allow user to select which commit strategy he preferres through the settings tab.
// This is slightly slower than hard coding it in the conf.php file.
define('FS_COMMIT_BY_OPTION'	,4);


/**
 * Cause the site selector to appear based on data in the user_sites_table
 */
define('FS_SITE_SELECTOR_MODE_BY_USERS_SITES_TABLE',1);

/**
 * Cause the site selector to appear only for FireStats administrators.
 * This is useful when FireStats is embedded inside an external system,
 * and is using the user ids of that system while not having those users in it's own table.
 * typically in such cases, it's not desired to maintain the mapping in the user_sites table.
 * instead, each user gets the sites filter hard coded for his site at an early setup stage, 
 * and it remains that way.
 */
define('FS_SITE_SELECTOR_MODE_ADMIN_ONLY',2);


define('FS_ABS_PATH',dirname(dirname(__FILE__)));

/**
 * possible values for metadata type representing url type (no value needed)
 */
define('FS_URL_TYPE_POST',1); // URL is a post
define('FS_URL_TYPE_RSS',2);  // URL is an RSS feed


/**
 * Version check intervals
 */
define('DEFAULT_FIRESTATS_VERSION_CHECK_INTERVAL_SECONDS'	, 60*60*24*1);
define('DEFAULT_IP2C_DB_VERSION_CHECK_INTERVAL_SECONDS'		, 60*60*24*14);
define('DEFAULT_BOTLIST_VERSION_CHECK_INTERVAL_SECONDS'		, 60*60*24*14);

/**
 * Loads configuration file here.
 * at this location, constants have already been defined and they can be used in the conf.php
 */
$fs_conf = dirname(dirname(__FILE__)).'/conf.php';
if (file_exists($fs_conf)) require_once($fs_conf);

if (!defined('FS_LOGGING')) define('FS_LOGGING', false);

if (file_exists(dirname(__FILE__).'/../demo'))
{
    define('DEMO',true);
}

if (!defined('FS_COMMIT_STRATEGY'))
{
	define('FS_COMMIT_STRATEGY', FS_COMMIT_BY_OPTION);
}

if (!defined('FS_AUTOMATIC_COMMIT_INTERVAL_SECONDS')) 
{
	define('FS_AUTOMATIC_COMMIT_INTERVAL_SECONDS',60);
}

if (!defined('FS_AUTOMATIC_COMMIT_WHEN_USER_ACCESS_STATISTICS')) 
{
	define('FS_AUTOMATIC_COMMIT_WHEN_USER_ACCESS_STATISTICS',true);
}

if (!defined('DEFAULT_SHOW_FIRESTATS_FOOTER')) 
{
	define('DEFAULT_SHOW_FIRESTATS_FOOTER',true);
}

if (!defined('JS_HIT')) 
{
	define('JS_HIT',false);
}

if (!defined('FS_PROFILING')) 
{
	define('FS_PROFILING', false);
}

if (!defined('MYSQL_NEW_LINK')) 
{
	define('MYSQL_NEW_LINK', true);
}

define('SITES_TAB_MAX_SITES_PER_PAGE',10);

?>
