<?php
/**
 * FireStats integration plugin for Joomla
 * 
 * author: Omry Yadan (omry@yadan.net).
 */



// Edit this line to match your FireStats directory.
// $_SERVER["DOCUMENT_ROOT"] represents the document root on Apache.
define('FS_PATH',$_SERVER["DOCUMENT_ROOT"].'/firestats');


// This is the site ID of your Joomla site inside FireStats sites table.
// You need to add a Joomla site in the Sites tab in FireStats and then change the
// value here to match the ID.
// this will allow FireStats to show the statistics of your Joomla site seperated from your other sites.
//
// Note: This is optional, the default value will also work.
define('FS_SITE_ID',1);


// no direct access
if (!defined('_VALID_MOS') && !defined('_JEXEC'))
{
	echo 'FireStats: Restricted access';
	return;
}

if (!isset($_MAMBOTS))
{
	echo 'FireStats: $_MAMBOTS is not set, if you are using Joomla 1.5 use the 1.5 plugin!';
	return;
}

$_MAMBOTS->registerFunction('onAfterStart', 'firestats_joomla_add_hit');

if (!file_exists(FS_PATH.'/php/db-hit.php'))
{
	return;
}

require_once(FS_PATH.'/php/db-hit.php');

function firestats_joomla_add_hit()
{
	if (function_exists('fs_add_site_hit'))
	{
		fs_add_site_hit(FS_SITE_ID);
		return true;
	}
	else
	{
		return false;
	}
}

?>
