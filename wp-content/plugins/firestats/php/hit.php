<?php
/**
This file is used to add hits data to firestats using post or get http requests.
*/
// this is normally called by the fs.js.php, now we really want to save to the database.
define('JS_HIT',false);

require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-hit.php');

$ip = fs_get('IP', true);
$url = fs_get('URL');
$ref = fs_get('REF',true);
$useragent = fs_get('USERAGENT', true);
$site_id = fs_get('SITE_ID');
if ($ip != '') 
	$_SERVER['REMOTE_ADDR'] = $ip;
if ($useragent != '')
	$_SERVER['HTTP_USER_AGENT'] = $useragent;
	
$_SERVER['REQUEST_URI'] = $url;
$_SERVER['HTTP_REFERER'] = $ref;

fs_add_site_hit($site_id, true);

function fs_get($k, $optional = false)
{
	if(isset($_POST[$k]))
	{
		return $_POST[$k];
	}
	else
	if(isset($_GET[$k]))
	{
		return $_GET[$k];
	}
	if ($optional)
	{
		return '';
	}
	else
	{
		die("Missing key : $k");
	}
}
?>
