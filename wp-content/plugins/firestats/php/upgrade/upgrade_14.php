<?php

function fs_db_upgrade_14(&$fsdb, $db_version, &$response)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$useragents = fs_useragents_table();
	$sqls = array
	(
		fs_mysql_newer_or_eq_to("4.1"), "ALTER TABLE `$urls` CHANGE `url` `url` TEXT CHARACTER SET binary NULL DEFAULT NULL", 
		fs_mysql_newer_or_eq_to("4.1"), "ALTER TABLE `$useragents` CHANGE `useragent` `useragent` TEXT CHARACTER SET binary NULL DEFAULT NULL",
	);
	
	$res = fs_apply_db_upgrade($fsdb,$sqls);
	if ($res !== true) return $res;

	return fs_upgrade_complete($fsdb, $response, 14);
}
?>
