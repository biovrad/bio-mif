<?php
function fs_db_upgrade_10(&$fsdb, $db_version, &$response)
{
	$version_table = fs_version_table();
	// a nice little convert loop.
	$useragents = fs_useragents_table();
	$hits = fs_hits_table();

	// upgrade to version 2
	if ($db_version < 2)
	{
		if (!fs_create_options_table($fsdb)) return false;
		$res = fs_update_db_version($fsdb, 2);
		if ($res !== true) return $res;
	}


	// convert charsets, this is instead of collate which does not work on mysql 4.0
	if ($db_version < 3)
	{
		if (ver_comp("4.1.0",fs_mysql_version()) < 0)
		{
			$sqls = array("ALTER TABLE `$useragents` DROP INDEX `unique`",
			"ALTER TABLE `$useragents` ADD `md5` CHAR( 32 ) NOT NULL AFTER `useragent`",
			"UPDATE `$useragents` SET `md5` = MD5( `useragent` )",
			"ALTER TABLE `$useragents` ADD UNIQUE (`md5`)",
			"ALTER TABLE `$hits` CHANGE `timestamp` `timestamp` DATETIME NOT NULL");
			foreach ($sqls as $sql)
			{
				if ($fsdb->query($sql) === false)
				{
					return fs_db_error();
				}
			}

			// deprecated table, function no longer exists.
			$referers = fs_table_prefix().'firestats_referers';
			
			// convert tables charset to utf-8
			$tables = array(fs_excluded_ips_table(),fs_hits_table(),
			fs_bots_table(),fs_options_table(),
			$referers,fs_urls_table(),
			fs_version_table(), fs_useragents_table());

			foreach ($tables as $table)
			{
				$sql = "ALTER TABLE `$table` CONVERT TO CHARSET utf8";
				if ($fsdb->query($sql) === false)
				{
					return fs_db_error();
				}
			}
		}
		$res = fs_update_db_version($fsdb, 3);
		if ($res !== true) return $res;
		
	}

	if ($db_version < 4)
	{
		/*no longer recalculates bots count*/
		$res = fs_update_db_version($fsdb, 4);
		if ($res !== true) return $res;
	}

	if ($db_version < 5)
	{
			
		if ($fsdb->query("ALTER TABLE `$hits` ADD `country_code` BLOB NULL DEFAULT NULL AFTER `user_id`") === false)
		{
			return fs_db_error();
		}

		$res = fs_update_db_version($fsdb, 5);
		if ($res !== true) return $res;
	}

	if ($db_version < 6)
	{
		require_once(FS_ABS_PATH.'/php/rebuild-db.php');
		require_once(FS_ABS_PATH.'/php/db-sql.php');
		$res = fs_botlist_import(FS_ABS_PATH.'/php/botlist.txt',true);
		if ($res != '')
		{
			
			return $res;
		}
		// bots are now matched using regular expressions. need to recalculate.
		fs_recalculate_match_bots();

		$res = fs_update_db_version($fsdb, 6);
		if ($res !== true) return $res;
	}

	if ($db_version < 7)
	{
		if (fs_column_not_exists($fsdb,$hits,'site_id'))
		{
			if ($fsdb->query("ALTER TABLE `$hits` ADD `site_id` INT NOT NULL DEFAULT 1 AFTER `id`") === false)
			{
				return fs_db_error();
			}
		}

		if (fs_index_not_exists($fsdb, $hits, 'site_id'))
		{
			if ($fsdb->query("ALTER TABLE `$hits` ADD INDEX (`site_id`)") === false)
			{
				return fs_db_error();
			}
		}
		$res = fs_update_db_version($fsdb, 7);
		if ($res !== true) return $res;
	}

	if ($db_version < 8)
	{
		if (!fs_create_sites_table($fsdb)) return false;
		$res = fs_update_db_version($fsdb, 8);
		if ($res !== true) return $res;
	}

	if ($db_version < 9)
	{
		
		if (!fs_create_old_archive_tables($fsdb)) return false;
		$urls = fs_urls_table();
		$refs = fs_table_prefix().'firestats_referers'; // deprecated table, function no longer exists.

		$sqls = array
		(
			//Change urls table so that can hold text of any length.
			fs_index_exists($fsdb, $urls, 'url')				,"ALTER TABLE `$urls` DROP INDEX `url`",
			fs_column_type_is_not($fsdb, $urls, 'url', 'Text')	,"ALTER TABLE `$urls` CHANGE `url` `url` TEXT NULL DEFAULT NULL",
			fs_column_not_exists($fsdb,$urls,'md5')				,"ALTER TABLE `$urls` ADD `md5` CHAR( 32 ) NOT NULL AFTER `url`",
			true												,"UPDATE `$urls` SET `md5` = MD5( `url` )",
			fs_index_not_exists($fsdb,$urls,'md5')				,"ALTER TABLE `$urls` ADD UNIQUE (`md5`)",
				
			//Change referrers table so that can hold text of any length.
			fs_index_exists($fsdb, $refs, 'referer')			,"ALTER TABLE `$refs` DROP INDEX `referer`",
			fs_column_type_is_not($fsdb, $refs,'referer','Text'),"ALTER TABLE `$refs` CHANGE `referer` `referer` TEXT NULL DEFAULT NULL",
			fs_column_not_exists($fsdb,$refs,'md5')	,"ALTER TABLE `$refs` ADD `md5` CHAR( 32 ) NOT NULL AFTER `referer`",
			true												,"UPDATE `$refs` SET `md5` = MD5( `referer`)",
			fs_index_not_exists($fsdb,$refs,'md5')				,"ALTER TABLE `$refs` ADD UNIQUE (`md5`)",
				
				
			// add search engines id and search terms
			fs_column_type_is_not($fsdb, $refs, 'search_engine_id', 'SMALLINT(6)')	,"ALTER TABLE `$refs` ADD `search_engine_id` SMALLINT(6) NULL DEFAULT NULL ".fs_comment('Search engine ID'),
			fs_column_type_is_not($fsdb, $refs, 'search_terms', 'VARCHAR(255)'),"ALTER TABLE `$refs` ADD `search_terms` VARCHAR(255) NULL DEFAULT NULL ".fs_comment('Search terms'),
			fs_index_not_exists($fsdb,$refs,'search_engine_id'),"ALTER TABLE `$refs` ADD INDEX ( `search_engine_id` )",
	
			// Add host row
			fs_column_type_is_not($fsdb, $refs, 'host', 'VARCHAR(40)'),"ALTER TABLE `$refs` ADD `host` VARCHAR(40) NULL DEFAULT NULL AFTER `md5`",
			// add index for hosts row
			fs_index_not_exists($fsdb,$refs,'host'),"ALTER TABLE `$refs` ADD INDEX (`host`)",
			// populate hosts row
			true,"UPDATE `$refs` SET `host`=substring_index(substring_index(`referer`,'/',3),'/',-1) WHERE `referer` REGEXP 'http://.*'",
	
			// drop useragent count row.
			fs_column_exists($fsdb,$useragents,'count'),"ALTER TABLE `$useragents` DROP `count`",
		);

		$res = fs_apply_db_upgrade($fsdb,$sqls);
		if ($res !== true) return $res; 
			

		$res = fs_update_db_version($fsdb, 9);
		if ($res !== true) return $res;
	}

	if ($db_version < 10)
	{
		// This is a special case.
		// Version 9 was a short lived version that already includes this change.
		// I moved it to version 10 to eliminate the problem of users not completing the upgrade and
		// getting stuck with version 9.5 (This operation is the longest in 8->9 upgrade and is the most likely cause for things like that).
		//Converts country code from blob to int.
		$sqls = array
		(
			fs_column_type_is_not($fsdb, $hits, 'country_code', 'INT(4)'),"ALTER TABLE `$hits` CHANGE `country_code` `country_code` INT(4) NULL DEFAULT NULL"
		);
		
		$res = fs_apply_db_upgrade($fsdb,$sqls);
		if ($res !== true) return $res; 
		
		$res = fs_update_db_version($fsdb, 10);
		if ($res !== true) return $res;
	}
	
	return fs_upgrade_complete($fsdb, $response, 10);
}

function fs_create_old_archive_tables(&$fsdb)
{
	$ranges = fs_archive_ranges();
	$sql = "CREATE TABLE IF NOT EXISTS `$ranges` (
		`range_id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY ".fs_comment('Range ID').",
		`range_start` DATETIME NOT NULL ".fs_comment('Range start time').",
		`range_end` DATETIME NOT NULL ".fs_comment('Range end time').",
		UNIQUE `ranges index` (`range_start`,`range_end`)
		)".fs_comment('Archive ranges table').fs_engine("InnoDB");
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	// create baseline range
	$r = $fsdb->query("INSERT IGNORE INTO `$ranges` (`range_id`,`range_start`,`range_end`) VALUES ('1' , '1000-01-01 00:00:00', '1000-01-01 00:00:00')");
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_site_archive());
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_old_archive_with_id(fs_archive_pages(), 'url_id','Archive for pages'));
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_old_archive_with_id(fs_archive_referrers(), 'url_id', 'Archive for referrers'));
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$r = $fsdb->query(fs_get_create_old_archive_with_id(fs_archive_useragents(), 'useragent_id', 'Archive for useragents'));
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	$countries_archive = fs_archive_countries();
	$sql = "CREATE TABLE IF NOT EXISTS `$countries_archive` (
		`range_id` INT NOT NULL ".fs_comment('Range ID').",
		`site_id` INTEGER NOT NULL ".fs_comment('Site ID of this data').",
		`country_code` INTEGER NOT NULL ".fs_comment('Country code for this data').",
		`views`  INTEGER NOT NULL ".fs_comment('Number of views from country in time range').",
		`visits` INTEGER NOT NULL ".fs_comment('Number of visits from country in time range').",
		UNIQUE `index` (`range_id`,`site_id`,`country_code`)
		) ".fs_comment("Countries archive table").fs_engine("InnoDB");
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		$fsdb->debug();
		return false;
	}

	return true;
}

function fs_get_create_old_archive_with_id($table_name, $id_name, $comment)
{
	$sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
		`range_id` INT NOT NULL ".fs_comment('Range ID').",
		`site_id` INTEGER NOT NULL ".fs_comment('Site ID of this data').",
		`$id_name` INTEGER NOT NULL ".fs_comment('Url ID for this data').",
		`views`  INTEGER NOT NULL ".fs_comment('Number of views in time range').",
		`visits` INTEGER NOT NULL ".fs_comment('Number of visits in time range').",
		UNIQUE `index` (`range_id`,`site_id`,`$id_name`)
		) ".fs_comment("$comment").fs_engine("InnoDB");
	return $sql;
}

?>