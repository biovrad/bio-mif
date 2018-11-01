<?php

fs_register_incremental_process('fs_db_upgrade_13_delete_unused_urls', 'fs_db_upgrade_13_delete_unused_urls_calc_max', 'fs_db_upgrade_13_delete_unused_urls_step', 'fs_db_upgrade_13_delete_unused_urls_desc',array(__FILE__), "fs_db_upgrade_13_delete_unused_urls_done");

function fs_db_upgrade_13(&$fsdb, $db_version, &$response)
{
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$archive_pages = fs_archive_pages();
	$excluded_ips = fs_excluded_ips_table();
	
	$process_id = 'fs_db_upgrade_13_delete_unused_urls';
	$process_id_progress = $process_id."_process_progress";
	$response['execute'] = "FS.executeProcess('$process_id','php/upgrade/upgrade_13.php')";
	$response['fields']['upgrade_progress'] = "<div id='$process_id_progress'></div>"; 
	return true;
}

function fs_db_upgrade_13_delete_unused_urls_calc_max()
{
	if (fs_mysql_older_than("4.1")) return 0; // while this would be nice, it's not critical because
											  // mysql older than 5.0 does not use buffered hits mode, where there was a bug 
											  // that created many unused urls in some cases.  
	
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$ref_archive = fs_archive_referrers();
	$page_archive = fs_archive_pages();
	$temp1 = fs_table_prefix()."firestats_temp1";
	$temp2 = fs_table_prefix()."firestats_temp2";
	$sqls = array
	(
		fs_table_exists($fsdb,$temp1), "DROP TABLE $temp1",
		fs_table_exists($fsdb,$temp2), "DROP TABLE $temp2",
		true, "CREATE TABLE $temp1 (id int, KEY(id))",
		true, "CREATE TABLE $temp2 (id int, KEY(id))",
		true, "INSERT INTO $temp1(id) SELECT * FROM (SELECT url_id id FROM $hits UNION SELECT referer_id id FROM $hits UNION SELECT url_id from $ref_archive UNION SELECT url_id FROM $page_archive) l",
		true, "INSERT INTO $temp2(id) SELECT id FROM $urls WHERE id NOT IN (SELECT * FROM $temp1)",
		true, "DROP TABLE $temp1",
	);
	
	$res = fs_apply_db_upgrade($fsdb,$sqls);
	if ($res !== true) return $res;		
	
	$count = $fsdb->get_var("SELECT COUNT(*) c FROM $temp2");
	if ($count === false)
	{
		return fs_db_error();
	}
	else
	{
		return $count;
	}	
}

function fs_db_upgrade_13_delete_unused_urls_step($value, $max)
{
	$limit = min(1000, $max - $value);
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$temp2 = fs_table_prefix()."firestats_temp2";
	$fsdb->query("DELETE FROM $urls WHERE id IN(SELECT * from $temp2) LIMIT $limit");
	return $limit;
}

function fs_db_upgrade_13_delete_unused_urls_done(&$response)
{
	$fsdb = &fs_get_db_conn();
	$temp2 = fs_table_prefix()."firestats_temp2";
	$sqls = array(fs_table_exists($fsdb,$temp2), "DROP TABLE $temp2");
	
	$res = fs_apply_db_upgrade($fsdb,$sqls);
	if ($res !== true) return $res;	
	
	return fs_upgrade_complete($fsdb, $response, 13);
}

function fs_db_upgrade_13_delete_unused_urls_desc($value, $max)
{
	return "Deleting $max unused URLs"; // no need to translate this.
}
?>
