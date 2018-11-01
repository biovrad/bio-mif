<?php

function fs_db_upgrade_15(&$fsdb, $db_version, &$response)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$page_archive = fs_archive_pages();
	$pending_hits = fs_pending_date_table();
	$sqls = array
	(
		fs_column_not_exists($fsdb, $hits, "url_site_id")	, "ALTER TABLE `$hits` ADD `url_site_id` INT NULL AFTER `url_id`",
		fs_index_not_exists ($fsdb, $hits, "url_site_id")	, "ALTER TABLE `$hits` ADD INDEX ( `url_site_id` )",
		true, "UPDATE $hits h, $urls u SET h.url_site_id = u.site_id WHERE h.url_id = u.id",
		fs_column_not_exists($fsdb, $page_archive, "site_id")	, "ALTER TABLE `$page_archive` ADD `site_id` INT NULL AFTER `url_id`",
		true,"UPDATE $page_archive a, $urls u SET a.site_id = u.site_id WHERE a.url_id = u.id",
		true,"UPDATE $page_archive a, $urls u SET a.site_id = u.site_id WHERE a.url_id = u.id",
		true,"ALTER TABLE `$page_archive` DROP INDEX `index`",
		true,"ALTER TABLE `$page_archive` ADD UNIQUE `index` ( `range_id` , `url_id` , `site_id` )",
		// drop IP column which was unused for some time now and was forgotten :)
		fs_column_exists($fsdb,$hits,'ip'),"ALTER TABLE `$hits` DROP `ip`",
		fs_column_not_exists($fsdb, $pending_hits, "type"), "ALTER TABLE `$pending_hits` ADD `type` TINYINT NULL",
		fs_column_not_exists($fsdb, $hits, "user_id"), "ALTER TABLE `$hits` DROP `user_id`",
		fs_column_not_exists($fsdb, $pending_hits, "user_id"), "ALTER TABLE `$pending_hits` DROP `user_id`",    
		fs_column_not_exists($fsdb, $pending_hits, "excluded_user"), "ALTER TABLE `$pending_hits` DROP `excluded_user`"
	);
	
	$res = fs_apply_db_upgrade($fsdb,$sqls);
	if ((bool)$res !== true) return $res;
	
	if (!fs_create_rss_subscribers_table($fsdb)) return fs_db_error();

	return fs_upgrade_complete($fsdb, $response, 15);
}
?>
