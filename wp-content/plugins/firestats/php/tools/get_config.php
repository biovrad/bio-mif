<?php
/*
 * dear hacker:
 * you can't really get any useful information from this file, it just here to echo what ever the user is sending as a prepared configuration file the user will then upload into 
 * firestats (to be used when firestats can't store the configuraiton file on the server due to security restrictions).
 */
$host = get('host');
$user = get('user');
$pass = get('pass');
$dbname = get('dbname');
$table_prefix = get('table_prefix');
header('Content-disposition: attachment; filename=fs-config.php');

require_once(dirname(dirname(__FILE__))."/db-config-utils.php");
echo fs_get_config($host, $user, $pass, $dbname, $table_prefix);

function get($s)
{
	if (isset($_GET[$s])) return $_GET[$s];
	return "";
}
?>