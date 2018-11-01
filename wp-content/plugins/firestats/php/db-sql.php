<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-common.php');
require_once(dirname(__FILE__).'/utils.php');
require_once(dirname(__FILE__).'/db-config-utils.php');

function fs_get_site_baseline_values($site_id)
{
	$archive_sites = fs_archive_sites();
	$fsdb = &fs_get_db_conn();
	$res = $fsdb->get_row("SELECT visits,views FROM `$archive_sites` WHERE `site_id` = '$site_id' AND `range_id` = '1'");
	if ($res === NULL)
	{
		$res = new stdClass();
		$res->visits = 0;
		$res->views = 0;
	}
	return $res;
}

function fs_get_num_old_days()
{
	$DAY = 60 * 60 * 24;
	$archive_older_than_days = fs_get_archive_older_than();
	$older_than = time() - $archive_older_than_days * $DAY;
	
	$hits = fs_hits_table();
	$sql = "SELECT DISTINCT SUBSTRING(timestamp,1,10) start, DATE_ADD(SUBSTRING(timestamp,1,10), INTERVAL 1 DAY) end FROM `$hits` WHERE timestamp < FROM_UNIXTIME('$older_than') ORDER BY `timestamp`";
	$fsdb = &fs_get_db_conn();
	$days = $fsdb->get_results($sql);
	if ($days === false) return fs_db_error(false);
	return count($days);
}

function fs_archive_old_data($older_than, $max_days_to_archive)
{
	require_once(FS_ABS_PATH."/lib/sync/mutex.php");
	$mutex = new Mutex(__FILE__);
	$res = $mutex->lock();
	if ($res === true)
	{
		$res = fs_archive_old_data_impl($older_than, $max_days_to_archive);
		$mutex->unlock();
		return $res;
	}
	else
	if ($res === false)
	{
		return fs_r("Data compacting is already in progress");
	}
	else
	{
		return fs_println("Error : $res");
	}
	
}

function fs_archive_old_data_impl($older_than, $max_days_to_archive)
{
	
	if (!isset($max_days_to_archive) || $max_days_to_archive <= 0)
		return "Invalid max value : $max_days_to_archive";
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	
	$ranges = fs_archive_ranges();
	$archive_sites = fs_archive_sites();
	$archive_pages = fs_archive_pages();
	$archive_referrers = fs_archive_referrers();			
	$archive_useragents = fs_archive_useragents();
	$archive_countries = fs_archive_countries();

	$supports_subquery = fs_mysql_newer_than("4.1.13"); // mysql bug http://bugs.mysql.com/bug.php?id=13385
	if (!$supports_subquery) return sprintf(fs_r("MySQL 4.1.14 or newer is required for data compacting support"));

	$fsdb = &fs_get_db_conn();
	
	// no need to archive excluded entries
	// its faster to purge them now than to consider them when archiving.
	fs_purge_excluded_entries($older_than);
	$sql = "SELECT DISTINCT SUBSTRING(timestamp,1,10) start, DATE_ADD(SUBSTRING(timestamp,1,10), INTERVAL 1 DAY) end FROM `$hits` WHERE timestamp < FROM_UNIXTIME('$older_than') ORDER BY `timestamp`";
	$days = $fsdb->get_results($sql);
	if ($days === false) return fs_db_error();
	$num_processed = 0;
	if (count($days) > 0)
	{
		foreach($days as $d)
		{
			if ($num_processed >= $max_days_to_archive) break;
			
			if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
			
			$start = $d->start." 00:00:00";
			$end = $d->end." 00:00:00";
			$sql = "INSERT IGNORE INTO `$ranges` ( `range_id` , `range_start` , `range_end` )	VALUES (NULL , '$start','$end')";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
			
			$r = $fsdb->get_var("SELECT LAST_INSERT_ID()");
			if ($r === false) return fs_db_error(true);
			
			$range_id = $r;
			// $range_id will be 0 if the range was already in the database.
			if ($range_id == "0")
			{
				$range_id = $fsdb->get_var("SELECT `range_id` FROM `$ranges` WHERE `range_start` = '$start' AND `range_end` = '$end'");
				if (!$range_id)  return fs_db_error(true);
			}
			
			
			$sites = $fsdb->get_results("SELECT url_site_id as site_id, COUNT(*) views, COUNT(DISTINCT `ip_int1`,`ip_int2`) visits FROM $hits h WHERE `timestamp` >= '$start' AND `timestamp` < '$end' GROUP BY url_site_id ORDER BY url_site_id");
			if ($sites === false) return fs_db_error(true);
			
			if (count($sites) > 0)
			{
				$func = create_function('$context,$row','extract($context); $sid = $row->site_id;$views = $row->views; $visits = $row->visits; return "($range_id,$sid,$views,$visits)";');	
				$values = fs_implode(",",$sites,$func,array("range_id" => $range_id));
				$sql =  "INSERT INTO `$archive_sites` (`range_id`,`site_id`,`views`,visits) VALUES $values ON DUPLICATE KEY UPDATE views=views+VALUES(views), visits=visits+VALUES(visits)";
				$r = $fsdb->query($sql);
				if ($r === false) return fs_db_error(true);
			}
			
			
			$pages = $fsdb->get_results("SELECT url_id,url_site_id AS site_id,COUNT(*) views,COUNT(DISTINCT ip_int1,ip_int2) visits  FROM $hits h WHERE timestamp >= '$start' AND timestamp < '$end' GROUP BY `url_id`");
			if ($pages === false) return fs_db_error(true);
			
			if (count($pages) > 0)
			{
				$func = create_function('$context,$row','extract($context); $sid = $row->site_id;$url_id = $row->url_id;$views = $row->views; $visits = $row->visits; return "($range_id,$sid,$url_id,$views,$visits)";');	
				$values = fs_implode(",",$pages,$func,array("range_id" => $range_id));
				$sql =  "INSERT INTO `$archive_pages` (range_id,site_id,url_id,views,visits) VALUES $values ON DUPLICATE KEY UPDATE views=views+VALUES(views), visits=visits+VALUES(visits)";
				$r = $fsdb->query($sql);
				if ($r === false) return fs_db_error(true);
			}
			

			$referrers = $fsdb->get_results("SELECT site_id,referer_id,COUNT(*) views,COUNT(DISTINCT ip_int1,ip_int2) visits  FROM $hits h, $urls urls WHERE h.url_id = urls.id AND `timestamp` >= '$start' AND `timestamp` < '$end' GROUP BY `site_id`,`referer_id`");
			if ($referrers === false) return fs_db_error(true);
			
			if (count($referrers) > 0)
			{
				$func = create_function('$context,$row','extract($context); $sid = $row->site_id;$referer_id = $row->referer_id;$views = $row->views; $visits = $row->visits; return "($range_id,$sid,$referer_id,$views,$visits)";');	
				$values = fs_implode(",",$referrers,$func,array("range_id" => $range_id));
				$sql =  "INSERT INTO `$archive_referrers` (range_id,site_id,url_id,views,visits) VALUES $values ON DUPLICATE KEY UPDATE views=views+VALUES(views), visits=visits+VALUES(visits)";
				$r = $fsdb->query($sql);
				if ($r === false) return fs_db_error(true);
			}			
			
			
			$useragent = $fsdb->get_results("SELECT url_site_id AS site_id,useragent_id,COUNT(*) views, COUNT(DISTINCT ip_int1,ip_int2) visits FROM $hits h WHERE `timestamp` >= '$start' AND `timestamp` < '$end' GROUP BY `site_id`,`useragent_id`");
			if ($useragent === false) return fs_db_error(true);
			
			if (count($useragent) > 0)
			{
				$func = create_function('$context,$row','extract($context); $sid = $row->site_id;$useragent_id = $row->useragent_id;$views = $row->views; $visits = $row->visits; return "($range_id,$sid,$useragent_id,$views,$visits)";');	
				$values = fs_implode(",",$useragent,$func,array("range_id" => $range_id));
				$sql =  "INSERT INTO `$archive_useragents` (range_id,site_id,useragent_id,views,visits) VALUES $values ON DUPLICATE KEY UPDATE views=views+VALUES(views), visits=visits+VALUES(visits)";
				$r = $fsdb->query($sql);
				if ($r === false) return fs_db_error(true);
			}
			
			
			$countries = $fsdb->get_results("SELECT url_site_id AS site_id,country_code,COUNT(country_code) views, COUNT(DISTINCT ip_int1,ip_int2) visits FROM $hits h WHERE country_code IS NOT NULL AND `timestamp` >= '$start' AND `timestamp` < '$end' GROUP BY `site_id`,`country_code`");
			if ($countries === false) return fs_db_error(true);
			
			if (count($countries) > 0)
			{
				$func = create_function('$context,$row','extract($context); $sid = $row->site_id;$country_code = $row->country_code;$views = $row->views; $visits = $row->visits; return "($range_id,$sid,$country_code,$views,$visits)";');	
				$values = fs_implode(",",$countries,$func,array("range_id" => $range_id));
				$sql =  "INSERT INTO `$archive_countries` (range_id,site_id,country_code,views,visits) VALUES $values ON DUPLICATE KEY UPDATE views=views+VALUES(views), visits=visits+VALUES(visits)";
				$r = $fsdb->query($sql);
				if ($r === false) return fs_db_error(true);
			}					
	
			
			if ($fsdb->query("DELETE FROM `$hits` WHERE `timestamp` >= '$start' AND `timestamp` < '$end'") === false)
				return fs_db_error(true);
			
			if ($fsdb->query("COMMIT") === false) return fs_db_error(true);
			$num_processed++;
		}
	}
	
	return $num_processed;
}


function fs_get_database_size()
{
	$fsdb = &fs_get_db_conn();
    $res = $fsdb->get_results( "SHOW TABLE STATUS");
    if ($res === false) return fs_db_error();
    $dbsize = 0;
    $tables = fs_get_tables_list();
    foreach($res as $table) 
   	{
   		if (in_array($table->Name, $tables))
   		{
	        $dbsize += $table->Data_length + $table->Index_length;
   		}
    }
    return $dbsize;
}

/**
 * $site_id = null, registers a new empty site and return its site id.
 * else registers a new site with the specified id.
 *
 * @return the new site id or false in case of error.
 */
function fs_register_site($site_id = null)
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$sid = $site_id === null ? "NULL" : $fsdb->escape($site_id); 
	$sql = "INSERT INTO `$sites` ( `id` , `type` , `name` ) VALUES   ($sid , '0', '')";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}

	return $fsdb->insert_id;
}

/**
 * Creates a new site with the specified id.
 * if new_sid is 'auto', the site id is chosen automatically, otherwise the new_sid is used as the site id.
 *
 * @param $new_sid the site id to use, 'auto' to choose automatically.
 * @param $name site name
 * @param $type site type as defined in constants.php
 * @param $baseline_views the initial value for views for this site. (default 0)
 * @param $baseline_visitors the initial value for visitor for this site. (default 0)
 *  *  
 * @return true on success, or error message on failure.
 */

function fs_create_new_site($new_sid, $name, $type, $baseline_views = 0, $baseline_visitors = 0)
{
	if (empty($name)) return fs_r('Site name not specified');
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();

	if ($new_sid == 'auto')
	{
		$newSite = true;
		$new_sid = fs_register_site();
		if (!is_numeric($new_sid)) return $new_sid;
	}
	else
	{
		if (!is_numeric($new_sid) || (int)($new_sid) <= 0) return fs_r('Site ID must be a positive number');
		$exists = fs_site_exists($new_sid);
		if (is_string($exists)) return $exists;
		if ($exists === true) return sprintf(fs_r("A site with the ID %s already exists"),$new_sid);
	}

	$new_sid = $fsdb->escape($new_sid);
	$type = $fsdb->escape($type);
	$name = $fsdb->escape($name);
	$sql = "REPLACE INTO `$sites` (`id`,`type`,`name`) VALUES ($new_sid,$type,$name)";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();

	if (!is_numeric($baseline_views)) $baseline_views = 0;
	if (!is_numeric($baseline_visitors)) $baseline_visitors = 0;
	$baseline_views = $fsdb->escape($baseline_views);
	$baseline_visitors = $fsdb->escape($baseline_visitors);
	$archive_sites = fs_archive_sites();
	$sql = "REPLACE INTO  `$archive_sites` (`range_id`,`site_id`,`views`,`visits`) VALUES(1,$new_sid,$baseline_views,$baseline_visitors)";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();
	return true;
}

function fs_update_site_params($new_sid,$orig_sid, $name,$type, $baseline_views = null, $baseline_visitors = null)
{
	if (empty($name)) return fs_r('Site name not specified');
	if (empty($orig_sid)) return "Uspecified site id";
	
	$changing_sid = $new_sid != $orig_sid;
	if (!is_numeric($new_sid) || (int)($new_sid) <= 0) return fs_r('Site ID must be a positive number');

	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();

	$exists = fs_site_exists($orig_sid);
	if (is_string($exists)) return $exists;
	if ($exists === false) return sprintf(fs_r("No site with the id %s exists"),$new_sid);

	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();


	if ($changing_sid)
	{
		$exists = fs_site_exists($new_sid);
		if (is_string($exists)) return fs_db_error(true);
		if ($exists === true) 
		{
			$fsdb->query("ROLLBACK");
			return sprintf(fs_r("A site with the ID %s already exists"),$new_sid);
		}
		$r = fs_transfer_site_hits($orig_sid, $new_sid);
		if ($r !== true) return $r;
	}

	$orig_sid = $fsdb->escape($orig_sid);
	$new_sid = $fsdb->escape($new_sid);
	$type = $fsdb->escape($type);
	$name = $fsdb->escape($name);

	$sql = "UPDATE `$sites` SET `type` = $type, `name` = $name, `id` = $new_sid WHERE `id` = $orig_sid";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error(true);

	if ($baseline_views !== null && $baseline_visitors !== null)
	{
		if (!is_numeric($baseline_views)) $baseline_views = 0;
		if (!is_numeric($baseline_visitors)) $baseline_visitors = 0;
		$baseline_views = $fsdb->escape($baseline_views);
		$baseline_visitors = $fsdb->escape($baseline_visitors);
		$archive_sites = fs_archive_sites();
		$sql = "REPLACE INTO  `$archive_sites` (`range_id`,`site_id`,`views`,`visits`) VALUES(1,$new_sid,$baseline_views,$baseline_visitors)";
		$r = $fsdb->query($sql);
		if ($r === false) return fs_db_error();
	}
	if($fsdb->query("COMMIT") === false) return fs_db_error(true);
	return true;
}

function fs_site_exists($site_id)
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$site_id = $fsdb->escape($site_id);
	$sql = "SELECT count(*) FROM `$sites` WHERE `id` = $site_id";
	$r = $fsdb->get_var($sql);
	if ($r === false)
	{
		return fs_db_error();
	}
	return $r != "0";
}

/**
 * Deletes the site  with the specified site_id.
 * action can be 'delete' or 'change'.
 * if aciton is 'delete' :
 *   the site is deleted, and all site hits are deleted as well.
 * if action is 'change' :
 *   the site is deleted, and the site hits are transfered to the site who's id is new_sid.
 */
function fs_delete_site($site_id, $action, $new_sid)
{
	if (empty($site_id)) return "Uspecified site id";
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$hits = fs_hits_table();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();

	$exists = fs_site_exists($site_id);
	if (is_string($exists)) return fs_db_error(true);
	if ($exists === false) 
	{
		$fsdb->query("ROLLBACK");
		return sprintf(fs_r("No site with the id %s exists"),$site_id);
	}

	if ($action == "delete")
	{
		$id = $fsdb->escape($site_id);
		$urls = fs_urls_table();
		$sql = "DELETE FROM `$hits` USING `$hits`,`$urls` WHERE $hits.url_id = $urls.id AND $urls.site_id = $id";
		$r = $fsdb->query($sql);
		if ($r === false) return fs_db_error(true);
		
		$archives = array
		(
			fs_archive_sites(),
			fs_archive_referrers(),
			fs_archive_useragents(),
			fs_archive_countries()
		);
		
		foreach($archives as $archive)
		{
			$sql = "DELETE FROM `$archive` WHERE site_id = $id";
			$r = $fsdb->query($sql);
			if ($r === false) return fs_db_error(true);
		}
		$pages = fs_archive_pages();
		$sql = "DELETE FROM `$pages` USING `$pages`,`$urls` WHERE $urls.id = $pages.url_id AND $urls.site_id = $id";
		$r = $fsdb->query($sql);
		if ($r === false) return fs_db_error(true);
	}
	else
	if ($action == "change")
	{
		if (empty($new_sid)) 
		{
			$fsdb->query("ROLLBACK");
			return fs_r("New site_id must not be empty");
		}
		
		if ($site_id == $new_sid)
		{
			$fsdb->query("ROLLBACK");
			return fs_r("Can't move the hits to the same site");
		}
		

		$exists = fs_site_exists($new_sid);
		if (is_string($exists)) return fs_db_error(true);
		if ($exists === false) 
		{
			$fsdb->query("ROLLBACK");
			return sprintf(fs_r("No site with the id %s exists"),$new_sid);
		}
		$r = fs_transfer_site_hits($site_id, $new_sid);
		if ($r !== true) return $r;
	}
	else
	{
		$fsdb->query("ROLLBACK");
		return "Unknown action $action";
	}
	$id = $fsdb->escape($site_id);
	$sql = "DELETE FROM `$sites` WHERE `id` = $id";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error(true);

	if($fsdb->query("COMMIT") === false) return fs_db_error(true);
	return true;
}

function fs_transfer_site_hits($old_sid, $new_sid)
{
	$fsdb = &fs_get_db_conn();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	
	$archive_sites = fs_archive_sites();
	$base_old = fs_get_site_baseline_values($old_sid);
	$base_new = fs_get_site_baseline_values($new_sid);
	$visits = $base_new->visits + $base_old->visits;
	$views = $base_new->views + $base_old->views;
	$sql = "DELETE FROM `$archive_sites` WHERE `range_id` = '1' AND `site_id` = '$old_sid'";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error(true);
	$sql = "REPLACE INTO `$archive_sites` (`range_id`,`site_id`,`views`,`visits`) VALUES(1,$new_sid,$views,$visits)";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error(true);

	// update archive tables that contains site_id column
	$tables = array();
	$tables[] = fs_urls_table();
	$tables[] = fs_archive_sites();
	$tables[] = fs_archive_referrers();
	$tables[] = fs_archive_useragents();
	$tables[] = fs_archive_countries();
	foreach($tables as $table)
	{
		$sql = "UPDATE `$table` SET `site_id` = '$new_sid' WHERE `site_id` = $old_sid";
		$r = $fsdb->query($sql);
		if ($r === false) return fs_db_error(true);
	}

	// update hits table.
	$hits = fs_hits_table();
	$sql = "UPDATE `$hits` SET `url_site_id` = '$new_sid' WHERE `url_site_id` = $old_sid";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error(true);

	$fsdb->query("COMMIT");
	return true;
}

function fs_get_orphan_site_ids()
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$sites = fs_sites_table();

	$sql = "SELECT DISTINCT `site_id` AS `id` FROM `$hits` h,`$urls` u WHERE h.url_id = u.id AND `site_id` NOT IN (SELECT `id` FROM `$sites`)";
	return $fsdb->get_results($sql);
}

// adds a url pattern to exclude 
// returns an error message, or true if okay
function fs_add_excluded_url($url_pattern)
{
	return fs_edit_excluded_url($url_pattern, null);
}

function fs_edit_excluded_url($url_pattern, $edit_id)
{
	if (!fs_is_admin()) return "Access denied : fs_edit_excluded_url";
	if (empty($url_pattern)) return fs_r("Empty url pattern");
	$r = fs_is_valid_regexp($url_pattern);
	if ($r !== true) return $r;
	
	$fsdb = &fs_get_db_conn();
	$excluded_urls = fs_excluded_urls_table();
	$url_pattern = $fsdb->escape($url_pattern);
	
	$r = $fsdb->get_var("SELECT count(*) c FROM `$excluded_urls` WHERE url_pattern = $url_pattern");
	if($r === false) return fs_db_error(true);
	if ((int)$r > 0)
	{
		return fs_r("The URL already exists");
	}
	
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	
	if ($edit_id != null)
	{
		$edit_id = $fsdb->escape($edit_id);
		$urls = fs_urls_table();
		$old_pattern = $fsdb->get_var("SELECT url_pattern FROM $excluded_urls WHERE id = $edit_id");
		if ($old_pattern === false) return fs_db_error(true);
		if ($old_pattern === null) return "url pattern id $edit_id not found";
		$old_pattern = $fsdb->escape($old_pattern);
		if ($fsdb->query("UPDATE `$urls` SET matching_exclude_patterns=matching_exclude_patterns-1 WHERE url REGEXP $old_pattern") === false)
		{
			return fs_db_error(true);
		}
		$sql = "UPDATE `$excluded_urls` SET url_pattern = $url_pattern WHERE id=$edit_id";
	}
	else
	{
		$sql = "INSERT INTO `$excluded_urls` (`id`, url_pattern) VALUES (NULL, $url_pattern)";	
	}
	
	if($fsdb->query($sql) === false)
	{
		return fs_db_error(true);
	}
	else
	{
		$urls = fs_urls_table();
		if ($fsdb->query("UPDATE `$urls` SET matching_exclude_patterns=matching_exclude_patterns+1 WHERE url REGEXP $url_pattern") === false)
		{
			return fs_db_error(true);
		}
		
		if($fsdb->query("COMMIT") === false) return fs_db_error(true);
		return true;
	}
}



function fs_remove_excluded_urls($ids)
{
	$res = explode(",",$ids);
	foreach($res as $id)
	{
		$r = fs_remove_excluded_url($id);
		if ($r !== true) return $r;
	}
	
	return true;
}

function fs_remove_excluded_url($id)
{
	if (!fs_is_admin()) return "Access denied : fs_remove_excluded_url";

	$fsdb = &fs_get_db_conn();

	$eu = fs_excluded_urls_table();
	$id = $fsdb->escape($id);

	$r = $fsdb->get_row("SELECT * FROM `$eu`  WHERE `id` = $id");
	if ($r === false) return fs_db_error();
	if ($r == null) return "Unknown URL pattern : $id";
	$url_pattern = $fsdb->escape($r->url_pattern);
	
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();

	if($fsdb->query("DELETE from `$eu` WHERE id = $id") === false)
	{
		return fs_db_error(true);
	}
	else
	{
		$urls = fs_urls_table();
		if ($fsdb->query("UPDATE `$urls` SET matching_exclude_patterns=matching_exclude_patterns-1 WHERE url REGEXP $url_pattern") === false)
		{
			return fs_db_error(true);
		}
		
		if($fsdb->query("COMMIT") === false) return fs_db_error(true);
		return true;
	}

}


// adds an ip address to exclude.
// returns an error message, or an empty string if okay.
function fs_add_excluded_ip($start_ip, $end_ip = null)
{
	return fs_edit_excluded_ip($start_ip, $end_ip, null);	
}

function fs_edit_excluded_ip($start_ip, $end_ip = null, $edit_id)
{
	if (!fs_is_admin()) return "Access denied : fs_add_excluded_ip";
	$fsdb = &fs_get_db_conn();
	if ($end_ip == null) $end_ip  = $start_ip;
	$sv = fs_ip2hex($start_ip);
	$ev = fs_ip2hex($end_ip);
	if ($sv == false)
	{
		return sprintf(fs_r("Invalid IP address: %s"),$start_ip);
	}
	
	if ($ev == false)
	{
		return sprintf(fs_r("Invalid IP address: %s"),$end_ip);
	}
	
	$ips = fs_excluded_ips_table();
	$sip1 = $sv[0];
	$sip2 = $sv[1];
	$eip1 = $ev[0];
	$eip2 = $ev[1];
	
	if (strcmp($sip1, $eip1) == 1 || strcmp($sip2, $eip2) == 1) 
	{
		return fs_r("First IP address must be smaller than second IP address");
	}
	
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	
	if ($edit_id != null)
	{
		$edit_id = $fsdb->escape($edit_id);
		
		$r = $fsdb->get_row("SELECT id,HEX(start_ip1) start_ip1,HEX(start_ip2) start_ip2,HEX(end_ip1) end_ip1,HEX(end_ip2) end_ip2 FROM `$ips`  WHERE `id` = $edit_id");
		if ($r === false) return fs_db_error();
		if ($r == null) return "Unknown IP address";
		$old_sip1 = $r->start_ip1;
		$old_sip2 = $r->start_ip2;
		$old_eip1 = $r->end_ip1;
		$old_eip2 = $r->end_ip2;
		
		$hits = fs_hits_table();
		$sql = "UPDATE `$hits` SET `excluded_ip`=`excluded_ip`-1 
				WHERE `ip_int1` >= 0x$old_sip1 AND 
					  `ip_int1` <= 0x$old_eip1 AND
					  `ip_int2` >= 0x$old_sip2 AND 
					  `ip_int2` <= 0x$old_eip2";
		if($fsdb->query($sql)===false) return fs_db_error(true);
		
		$sql = "UPDATE `$ips` SET start_ip1 = 0x$sip1 ,start_ip2 = 0x$sip2,end_ip1=0x$eip1,end_ip2 = 0x$eip2 WHERE id=$edit_id";
	}
	else
	{
		$r = $fsdb->get_var("SELECT COUNT(*) c FROM `$ips` WHERE start_ip1 = 0x$sip1 AND start_ip2 = 0x$sip2 AND end_ip1 = 0x$eip1 AND end_ip2 = 0x$eip2");
		if($r === false) return fs_db_error(true);
		if ((int)$r > 0)
		{
			return fs_r("The IP address(s) already exists");
		}		
		$sql = "INSERT INTO `$ips` (`id`, start_ip1,start_ip2,end_ip1,end_ip2) VALUES (NULL, 0x$sip1,0x$sip2,0x$eip1,0x$eip2)";	
	}
	
	if($fsdb->query($sql) === false)
	{
		return fs_db_error(true);
	}
	else
	{
		$hits = fs_hits_table();
		$sql = "UPDATE `$hits` SET `excluded_ip`=`excluded_ip`+1 
				WHERE `ip_int1` >= 0x$sip1 AND 
					  `ip_int1` <= 0x$eip1 AND
					  `ip_int2` >= 0x$sip2 AND 
					  `ip_int2` <= 0x$eip2";
		if($fsdb->query($sql)===false) return fs_db_error(true);
		if($fsdb->query("COMMIT") === false) return fs_db_error(true);
		return "";
	}
}

function fs_remove_excluded_ips($ids)
{
	if (!fs_is_admin()) return "Access denied : fs_remove_excluded_ips";

	$fsdb = &fs_get_db_conn();

	$exip = fs_excluded_ips_table();
	$hits = fs_hits_table();
	$id = $fsdb->escape($ids);

	$res = $fsdb->get_results("SELECT id,HEX(start_ip1) start_ip1,HEX(start_ip2) start_ip2,HEX(end_ip1) end_ip1,HEX(end_ip2) end_ip2 FROM `$exip`  WHERE `id` in ($ids)");
	if ($res === false) return fs_db_error();
	if (count($res) == 0) return "Unknown IP address";
	
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	if($fsdb->query("DELETE from `$exip` WHERE id in ($ids)") === false)
	{
		return fs_db_error(true);
	}
	else
	{
		foreach($res as $r)
		{
			$sip1 = $r->start_ip1;
			$sip2 = $r->start_ip2;
			$eip1 = $r->end_ip1;
			$eip2 = $r->end_ip2;
			
			$hits = fs_hits_table();
			$sql = "UPDATE `$hits` SET `excluded_ip`=`excluded_ip`-1 
					WHERE `ip_int1` >= 0x$sip1 AND 
						  `ip_int1` <= 0x$eip1 AND
						  `ip_int2` >= 0x$sip2 AND 
						  `ip_int2` <= 0x$eip2";
			if($fsdb->query($sql)===false) return fs_db_error(true);
			if($fsdb->query("COMMIT") === false) return fs_db_error(true);
		}
		return "";
	}

}


function fs_add_bot($wildcard1, $fail_if_exists = true)
{
	if (!fs_is_admin()) return "Access denied : fs_add_bot";

	$r = fs_is_valid_regexp($wildcard1);
	if ($r !== true) return $r;
		
	$fsdb = &fs_get_db_conn();
	$wildcard = $fsdb->escape(trim($wildcard1));
	$bots_table = fs_bots_table();
	$hits_table = fs_hits_table();
	$ua_table = fs_useragents_table();

	// check for duplicate wildcard
	if ($fsdb->get_var("SELECT DISTINCT wildcard FROM `$bots_table` WHERE `wildcard` = $wildcard") != null)
	{
		if ($fail_if_exists) return sprintf(fs_r("The bot wildcard %s is already in the database"),$wildcard);
		else return "";
	}

	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	// insert wildcard to table
	if ($fsdb->query("INSERT INTO `$bots_table` (`wildcard`) VALUES ($wildcard)") === false)
	{
		return fs_db_error(true);
	}
	else
	{
		$search_wildcard = $fsdb->escape(trim($wildcard1));
		if ($fsdb->query("UPDATE `$ua_table`
			SET match_bots=match_bots+1 
			WHERE useragent REGEXP $search_wildcard") === false)
		{
			return fs_db_error(true);
		}
		if ($fsdb->query("COMMIT") === false) return fs_db_error(true);
		return "";
	}
}

function fs_remove_bots($bot_ids)
{
	$res = explode(",",$bot_ids);
	foreach($res as $id)
	{
		$r = fs_remove_bot($id);
		if ($r !== "") return $r;
	}
	
	return "";
}

function fs_remove_bot($bot_id)
{
	if (!fs_is_admin()) return "Access denied : fs_remove_bot";
	$fsdb = &fs_get_db_conn();
	$bot_id = $fsdb->escape($bot_id);
	$bots_table = fs_bots_table();
	$ua_table = fs_useragents_table();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();

	$wildcard = $fsdb->get_var("SELECT `wildcard` FROM `$bots_table` WHERE `id`=$bot_id");
	if ($wildcard === false) return fs_db_error(true);
	$wildcard = $fsdb->escape($wildcard);
	if ($fsdb->query("UPDATE `$ua_table`  SET match_bots=match_bots-1 WHERE useragent REGEXP $wildcard") === false)
	{
		return fs_db_error(true);
	}

	if ($fsdb->query("DELETE from `$bots_table` WHERE `id` = $bot_id") === false) return fs_db_error(true);
	if ($fsdb->query("COMMIT") === false) return fs_db_error(true);
	return "";
}

function fs_clear_bots_list()
{
	$res = fs_get_bots();
	if ($res)
	{
		foreach($res as $r)
		{
			$id = $r['id'];
			$res1 = fs_remove_bot($id);
			if ($res1 != '') return $res1;
		}
	}
	return '';
}

function fs_get_unique_hit_count($days_ago = NULL, $site_id = true, $url_id = null, $round_to_midnight = false)
{
	return fs_get_unique_hit_count_range($site_id, true,fs_days_ago_to_unix_time($days_ago, $round_to_midnight), null, $url_id);
}

/**
 * returns the number of unique hits in the specified time range
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see fs_get_site_id_query() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time.
 * $url_id if specified, the value returned will be for the url with this id only.
 * returns number of unique hits.
 */
function fs_get_unique_hit_count_range($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$for_site = fs_get_site_id_query($site_id, null, 'url_site_id');
	$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	$and_url_id = $url_id ? " AND `url_id` = '$url_id'" : "";	
	
	if (fs_mysql_newer_or_eq_to("4.1.0")) 
	{
		$select = "SELECT COUNT(DISTINCT ip_int1, ip_int2) c
					FROM `$hits` h
					WHERE $for_site AND $timestamp_between $and_url_id 
					GROUP BY SUBSTRING(`timestamp`,1,10),url_site_id";
		
		$sql = "SELECT SUM(u.c) c FROM ($select) u";
		$res = $fsdb->get_var($sql);
		if ($res === false) return fs_db_error();
		$non_archive_count = $res;
	}
	else
	{
		$sql = "SELECT COUNT(DISTINCT ip_int1, ip_int2) c
				FROM `$hits` h
				WHERE $for_site AND $timestamp_between $and_url_id 
				GROUP BY SUBSTRING(`timestamp`,1,10),url_site_id,ip_int1, ip_int2";
					
		$res = $fsdb->get_results($sql);
		if ($res === false) return fs_db_error();
		$non_archive_count = count($res);
	}	

	$r = fs_get_unique_hit_count_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id);
	if ($r === false) return fs_db_error();
	
	return $r + $non_archive_count;
}

function fs_get_hit_count($days_ago = null, $site_id = true, $url_id = null, $round_to_midnight = false)
{
	return fs_get_page_views_range($site_id,true,fs_days_ago_to_unix_time($days_ago, $round_to_midnight), null, $url_id);
}

/**
 * returns the number of page views in the specified time range
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see fs_get_site_id_query() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time
 * $url_id if specified, the value returned will be for the url with this id only.
 * returns number of unique hits.
 */
function fs_get_page_views_range($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$for_site = fs_get_site_id_query($site_id, null, 'url_site_id');
	$ts_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	$sql = "SELECT COUNT(*) 
			FROM `$hits` h 
			WHERE $for_site AND $ts_between";
	
	if($url_id)
	{
		$sql .= " AND `url_id` = '$url_id'";	
	}
	$non_archive_count = $fsdb->get_var($sql);
	if ($non_archive_count === false) return fs_db_error();
	
	$r = fs_get_page_views_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id);
	if ($r === false) return fs_db_error();
	return $r + $non_archive_count;
}

function fs_get_page_views_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
	if ($url_id == null)
	{
		$table = fs_archive_sites();
	}
	else
	{
		$table = fs_archive_pages();
	}
	return fs_get_data_count_from_archive($table, "views",$site_id, $is_unix_time, $start_time, $end_time, $url_id);
}

function fs_get_unique_hit_count_range_from_archive($site_id, $is_unix_time, $start_time, $end_time, $url_id)
{	
	if ($url_id == null)
	{
		$table = fs_archive_sites();
	}
	else
	{
		$table = fs_archive_pages();
	}
	return fs_get_data_count_from_archive($table,"visits",$site_id, $is_unix_time, $start_time, $end_time, $url_id);
}

function fs_get_data_count_from_archive($table_name, $row_name, $site_id, $is_unix_time, $start_time, $end_time, $url_id)
{
 	$fsdb = &fs_get_db_conn();
 	$ranges = fs_archive_ranges();
 	$from_site = fs_get_site_id_query($site_id);
 	$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
	$sql = "SELECT SUM(`$row_name`) FROM `$ranges` r,`$table_name` d WHERE d.range_id=r.range_id AND $from_site AND $range_between";
	
	if($url_id)
	{
		$sql .= " AND `url_id` = '$url_id'";	
	}
	return $fsdb->get_var($sql);
}

function fs_get_num_excluded()
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$urls = fs_urls_table();
	$not_excluded = not_excluded_real(array('urls'=>'urls','referrers'=>'referrers'));
	$sql = "SELECT COUNT(*) 
			FROM `$hits` h,
				`$ua` u, 
				`$urls` urls, 
				`$urls` referrers 
			WHERE 
				h.useragent_id = u.id AND 
				h.url_id = urls.id AND 
				h.referer_id = referrers.id 
				AND NOT ($not_excluded)";
	$res = $fsdb->get_var($sql);
	if ($res === false) return fs_db_error();
	return $res;
}

function fs_not_filtered()
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$arr = array();
	$res = "";
	
	$ip_filter = fs_get_option('firestats_ht_ip_filter');
	if ($ip_filter != null)
	{
		$ip = fs_ip2hex($ip_filter);
		if ($ip == false) return "0"; // if ip filter is not parseable everything is filtered.
		$ip1 = $ip[0];
		$ip2 = $ip[1];
		$res = "ip_int1 = 0x$ip1 AND ip_int2 = 0x$ip2";
	}
	
	$arr['firestats_ht_url_filter'] 	= "urls.url";
	$arr['firestats_ht_referrer_filter'] 	= 'referers.url';
	$arr['firestats_ht_useragent_filter'] = 'useragent';
	foreach($arr as $k=>$v)
	{
		$param = fs_get_option($k);
		if (!empty($param))
		{
			$param = $fsdb->escape($param);
			$cond = "$v REGEXP $param";
			if ($res == "") $res = $cond;
			else
			$res .= " AND $cond";
		}
	}

	if ($res == "")
	$res = "1";

	return $res;
}

function not_excluded_real($url_tables = null)
{
	if ($url_tables == null)
	{
		$exclude_urls = "AND `matching_exclude_patterns` = '0'";
	}
	else
	{
		$urls = $url_tables['urls'];
		$referrers = $url_tables['referrers'];
		$exclude_urls = "AND $urls.matching_exclude_patterns = '0' AND $referrers.matching_exclude_patterns = '0'";
	}
	
	return "`excluded_ip` = '0'
			AND `excluded_by_user` = '0' 
			AND `match_bots`='0'
			$exclude_urls";
}

/**
 * $site_id:
 * true to exclude all sites but the one in the sites_filter option.
 * false to include all sites.
 * a specific number to exclude all other sites (number is site id to include).
 * 
 * $site_table_name: The name of the table that contains the site_id column
 */
function fs_get_site_id_query($site_id,$site_table_name = null, $site_column_name = 'site_id')
{
	$sql = "";
	if (is_numeric($site_id))
	{
		$sql = "$site_column_name = $site_id";
	}
	else
	{
		if ($site_id)
		{
			$site = fs_get_local_option('firestats_sites_filter','all');
			if ($site != 'all')
			{
				$sql = "`$site_column_name` = $site";
				if ($site_table_name != null)
				{
					$sql = "$site_table_name.$sql";
				}
			}
			else
			{
				// if current user have no access to all sites
				// only use allowed sites
				if(!fs_current_user_allowed_to_access_site(-1))
				{
					$allowed_sites = fs_get_user_sites_list(fs_current_user_id());
					if ($allowed_sites == "")
					{
						$sql = "0";
					}
					else
					{
						$sql = "$site_column_name IN ($allowed_sites)";
						if ($site_table_name != null)
						{
							$sql = "$site_table_name.$sql";
						}
					}
				}
			}
		}
	}
	return $sql != "" ? $sql : "1";
}

function fs_purge_excluded_entries($older_than = null)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$urls = fs_urls_table();
	$not_excluded = not_excluded_real(array('urls'=>'urls','referrers'=>'referrers'));
	$sql = "DELETE `$hits` 
			FROM `$hits` ,`$ua` u, `$urls` urls, `$urls` referrers 
			WHERE 
				$hits.useragent_id=u.id AND
				$hits.url_id = urls.id AND
				$hits.referer_id = referrers.id AND 
				NOT ($not_excluded)";
	if ($older_than)
	{
		$sql .= " AND `timestamp` < FROM_UNIXTIME('$older_than')";
	}
	
	$res = $fsdb->get_var($sql);
	return $res;
}

//function fs_hits_table_get_date()
//{
//	$page_num = (int)fs_get_option("current_hits_table_page", "0");
//	$amount = fs_get_num_hits_in_table();
//	$first_row	= ($page_num * $amount);
//	$fsdb = &fs_get_db_conn();
//	$hits = fs_hits_table();
//	$for_site = fs_get_site_id_query(true, null,"url_site_id");
//	$not_filtered = fs_not_filtered();
//	$date_format = fs_get_date_format();
//	$timezone = fs_get_option('firestats_user_timezone','system');
//	$db_support_tz = (ver_comp("4.1.3",fs_mysql_version()) <= 0);
//	$ts = $db_support_tz && $timezone != 'system' ? "CONVERT_TZ(`timestamp`,'system','$timezone')" : "timestamp";
//	
//	$sql = "SELECT DATE_FORMAT($ts,'$date_format') from $hits WHERE $for_site AND $not_filtered ORDER BY timestamp DESC LIMIT $first_row,1";
//	return $fsdb->get_var($sql);
//}

function fs_hits_table_get_page_for_date($date)
{
	$amount = fs_get_num_hits_in_table();
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	
	$for_site = fs_get_site_id_query(true, null,"url_site_id");
	$not_filtered = fs_not_filtered();
	
	$date_format = fs_get_date_format();
	$timezone = fs_get_option('firestats_user_timezone','system');
	$db_support_tz = (ver_comp("4.1.3",fs_mysql_version()) <= 0);
	$ts = $db_support_tz && $timezone != 'system' ? "CONVERT_TZ(`timestamp`,'system','$timezone')" : "timestamp";
	
	$date1 = fs_date_to_unixtime($date,$date_format);
	$date1 += 24 * 60 * 60; // add one day, because we count days newer than.
	$sql = "SELECT COUNT(*) FROM $hits WHERE $for_site AND $not_filtered AND UNIX_TIMESTAMP($ts) > $date1 ORDER BY timestamp DESC";
	$count = $fsdb->get_var($sql);
	if ($count === false) return fs_db_error();
	return $count / $amount;
}

function fs_get_num_hits_in_hits_table()
{
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$urls = fs_urls_table();
	$for_site = fs_get_site_id_query(true, null,"url_site_id");
	$not_filtered = fs_not_filtered();	
	$sql = "SELECT COUNT(*)
			FROM 
				`$hits` AS hits,
				`$ua` AS agents,
				`$urls` AS urls,
				`$urls` AS referers
			WHERE 
				hits.useragent_id = agents.id AND 
				hits.url_id = urls.id AND 
				hits.referer_id = referers.id  AND 
				$for_site AND 
				$not_filtered
	";
	$fsdb = &fs_get_db_conn();
	return $fsdb->get_var($sql);
}

# Fetches entries in DB
function fs_getentries()
{
	$page_num = (int)fs_get_option("current_hits_table_page", "0");
	$amount = fs_get_num_hits_in_table();
	$first_row	= $page_num * $amount;
	$timezone = fs_get_option('firestats_user_timezone','system');
	$db_support_tz = (ver_comp("4.1.3",fs_mysql_version()) <= 0);
	$ts = $db_support_tz && $timezone != 'system' ? "CONVERT_TZ(`timestamp`,'system','$timezone')" : "timestamp";
	if ($amount === false) return false;

	$datatime_format = fs_get_datetime_format();
	$hits = fs_hits_table();
	$ua = fs_useragents_table();
	$urls = fs_urls_table();
	$for_site = fs_get_site_id_query(true, null,"url_site_id");
	$not_filtered = fs_not_filtered();
	$sql = "SELECT hits.id,
					url_site_id site_id,
					HEX(ip_int1) ip_int1,
					HEX(ip_int2) ip_int2,
					useragent,
					referers.url as referer,
					referers.search_terms,
					referers.search_engine_id,
					urls.url as url,
					DATE_FORMAT($ts,'$datatime_format') as time,
					country_code,
					urls.title as url_title, 
					referers.title as referrer_title
			FROM 
				`$hits` AS hits,
				`$ua` AS agents,
				`$urls` AS urls,
				`$urls` AS referers
			WHERE 
				hits.useragent_id = agents.id AND 
				hits.url_id = urls.id AND 
				hits.referer_id = referers.id 
				AND $for_site 
				AND $not_filtered
			ORDER BY timestamp DESC 
			LIMIT $first_row,$amount";
	$fsdb = &fs_get_db_conn();
	return $fsdb->get_results($sql);
}

function fs_get_excluded_ips($id = null)
{
	$fsdb = &fs_get_db_conn();
	return $fsdb->get_results("SELECT 
									id,HEX(start_ip1) start_ip1,
									HEX(start_ip2) start_ip2,
									HEX(end_ip1) end_ip1,
									HEX(end_ip2) end_ip2 
								FROM ".fs_excluded_ips_table().
								($id != null ? " WHERE id=".$fsdb->escape($id) : "").
								" ORDER BY start_ip1,start_ip2,end_ip1,end_ip2");
}

function fs_get_excluded_urls($id = null)
{
	$fsdb = &fs_get_db_conn();
	$eu = fs_excluded_urls_table();
	return $fsdb->get_results("SELECT id,url_pattern FROM $eu " . ($id != null ? " WHERE id=".$fsdb->escape($id) : ""));
}

function fs_get_bots()
{
	$fsdb = &fs_get_db_conn();
	return $fsdb->get_results("SELECT id,wildcard from ".fs_bots_table(). " ORDER BY wildcard", ARRAY_A);
}

function fs_ensure_initialized(&$x)
{
	if (!isset($x)) $x = 0;
}

function fs_group_others($list)
{
	$MIN = 2;
	$others = array();
	$others['name'] = 'Others'; // not translated, cause tree layout problems with hebrew
	$others['image'] = fs_pri_get_image_url('others', 'Others');
	$others['count'] = 0;
	$others['percent'] = 0;
	foreach ($list as $code=>$data)
	{
		if ($data['percent'] < 2)
		{
			$others['count'] += $data['count'];
			$others['percent'] += $data['percent'];
			$others['sublist'][$code]=$data;
			unset($list[$code]);
		}
	}
	if ($others['count'] > 0)
	{
		$list['others'] = $others;
	}
	return $list;
}

function fs_get_useragents($days_ago = null, $site_id = true)
{
	return fs_get_useragent_views_range($site_id, true,fs_days_ago_to_unix_time($days_ago), null, null);
}

/**
 * returns a table mapping useragent_id to the number of times it viewed the specified site_id in the specified time range.
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see fs_get_site_id_query() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time
 * returns number of unique hits.
 */
function fs_get_useragent_views_range($site_id, $is_unix_time, $start_time, $end_time)
{
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$useragents = fs_useragents_table();
	$archive_useragents = fs_archive_useragents();
	$ranges = fs_archive_ranges();
	$for_site = fs_get_site_id_query($site_id, null, "url_site_id");
	$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	
	if (fs_mysql_newer_than("4.1.13")) 
	{
		$from_site = fs_get_site_id_query($site_id);
		$select1 = "SELECT useragent_id,COUNT(useragent_id) AS c
					FROM $hits h, $useragents u 
					WHERE h.useragent_id = u.id AND $for_site AND $timestamp_between 
					GROUP BY useragent_id";
		
		$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		
		$select2 = "SELECT useragent_id,SUM(views) AS c
					FROM $archive_useragents u,$ranges r
					WHERE r.range_id = u.range_id AND $range_between AND $from_site
					GROUP BY useragent_id";
							
		$sql = "SELECT `useragent`,`useragent_id`, SUM(`c`) `c`
				FROM ($select1 UNION ALL $select2) `u`,`$useragents` `u2` 
				WHERE u2.id = u.useragent_id
				GROUP BY `useragent_id` 
				ORDER BY `c` DESC";
	}
	else
	{
		$sql = "SELECT useragent_id,useragent,COUNT(useragent_id) AS c
				FROM $hits h, $useragents u
				WHERE  h.useragent_id = u.id AND $for_site AND $timestamp_between GROUP BY useragent_id";
	}

	$fsdb = &fs_get_db_conn();
	$results = $fsdb->get_results($sql,ARRAY_A);
	if ($results === false) return false;
	return $results;	
}

function fs_get_site($id)
{
	if (!fs_is_admin()) return "Access denied : fs_get_site";
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$id = $fsdb->escape($id);
	$sql = "SELECT * FROM $sites WHERE id=$id";
	return $fsdb->get_row($sql);
}

/**
 * return an array of sites.
 *
 * @param $site_ids a comma separated list of site ids, if null all sites are returned. 
 * @return an array of sites.
 */
function fs_get_sites($site_ids = null)
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$sql = "SELECT * FROM $sites";
	if ($site_ids != null)
	{
		$sql .= " WHERE id in ($site_ids)";
	}
	return $fsdb->get_results($sql);
}

function fs_get_sites_page($page_number = 0, $name_filter = null)
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$offset = $page_number * SITES_TAB_MAX_SITES_PER_PAGE;
	$limit = SITES_TAB_MAX_SITES_PER_PAGE;
	
	$sql = "SELECT * FROM $sites";
	if ($name_filter != null)
	{
		$sql .= " WHERE name REGEXP '$name_filter'";
	}
	$sql .= " LIMIT $offset, $limit";
	
	return $fsdb->get_results($sql);
}


function fs_get_num_sites($name_filter = null)
{
	$fsdb = &fs_get_db_conn();
	$sites = fs_sites_table();
	$sql = "SELECT COUNT(*) c FROM $sites";
	if ($name_filter != null)
	{
		$sql .= " WHERE name REGEXP '$name_filter'";
	}
	return $fsdb->get_var($sql);
}

function fs_get_users()
{
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$sql = "SELECT `id`,`username`,`email`,`security_level` FROM $users ORDER BY `id`";
	return $fsdb->get_results($sql);
}

function fs_get_user($id)
{
	$fsdb = &fs_get_db_conn();
	$id = $fsdb->escape($id);
	$users = fs_users_table();
	$sql = "SELECT `id`,`username`,`email`,`security_level` FROM $users WHERE `id` = $id";
	return $fsdb->get_row($sql);
}



function fs_stats_sort($stats_data)
{
	$foo = create_function('$a, $b', 'return $b["count"] - $a["count"];');
	uasort($stats_data,$foo);
	$size=count($stats_data);

	foreach($stats_data as $key=>$value)
	{
		$ar = $value['sublist'];
		if ($ar != NULL)
		{
			uasort($ar, $foo);
		}
	}
	return $stats_data;
}

// TODO:
// use classes here for the tree model. this is getting silly.
function fs_get_os_statistics($days_ago = NULL)
{
	$results = fs_get_useragents($days_ago);
	if ($results === false) return fs_db_error();
	
	if (count($results) > 1)
	{
		$total = 0;
		foreach ($results as $r)
		{
			$total += $r['c'];
		}

		foreach ($results as $r)
		{
			$ua = $r['useragent'];
			$count = $r['c'];

			$a = fs_pri_detect_browser($ua);
			$os_name 	= $a[3];
			$os_code 	= $a[4];
			$os_ver		= $a[5];
			
			$os_img = fs_pri_get_image_url($os_code != '' ? $os_code : 'unknown', "$os_name $os_ver", $os_ver);

			fs_ensure_initialized($os[$os_code]['count']);
			fs_ensure_initialized($os[$os_code]['sublist'][$os_ver]['count']);

			// operating systems
			$os[$os_code]['name']=$os_name != '' ? $os_name : fs_r('Unknown');
			$os[$os_code]['image'] = $os_img;
			$os[$os_code]['count'] += (int)$count;
			$os_total = $os[$os_code]['count'];
			$os[$os_code]['percent'] = (float)($os_total / $total) * 100;
			$os[$os_code]['sublist'][$os_ver]['count'] += (int)$count;
			$os_ver_count = $os[$os_code]['sublist'][$os_ver]['count'];
			$os[$os_code]['sublist'][$os_ver]['percent'] = (float)($os_ver_count / $total) * 100;
			$os[$os_code]['sublist'][$os_ver]['useragent'] = $ua;
			$os[$os_code]['sublist'][$os_ver]['name'] = $os_name;
			$os[$os_code]['sublist'][$os_ver]['image'] = $os_img;
		}
		
		return fs_stats_sort(fs_group_others($os));
	}
	else
	{
		return null;
	}
}

// TODO:
// use classes here for the tree model. this is getting silly.
function fs_get_browser_statistics($days_ago = NULL)
{
	$results = fs_get_useragents($days_ago);
	if ($results === false) return fs_db_error();
	
	if (count($results) > 1)
	{
		$total = 0;
		foreach ($results as $r)
		{
			$total += $r['c'];
		}

		foreach ($results as $r)
		{
			$ua = $r['useragent'];
			$count = $r['c'];

			$a = fs_pri_detect_browser($ua);
			$br_name 	= $a[0];$br_code 	= $a[1];$br_ver		= $a[2];

			$br_img = fs_pri_get_image_url($br_code != '' ? $br_code : 'unknown', $br_name);

			fs_ensure_initialized($br[$br_code]['count']);
			fs_ensure_initialized($br[$br_code]['sublist'][$br_ver]['count']);

			$br[$br_code]['name'] = $br_name != '' ? $br_name : fs_r('Unknown');
			$br[$br_code]['image'] = $br_img;

			// browsers
			$br[$br_code]['count'] += (int)$count;
			$browser_total = $br[$br_code]['count'];
			$br[$br_code]['percent'] = (float)($browser_total / $total) * 100;
			$br[$br_code]['sublist'][$br_ver]['count'] += (int)$count;
			$br_ver_count = $br[$br_code]['sublist'][$br_ver]['count'];
			$br[$br_code]['sublist'][$br_ver]['percent'] = (float)($br_ver_count / $total) * 100;
			$br[$br_code]['sublist'][$br_ver]['useragent'] = $ua;
			$br[$br_code]['sublist'][$br_ver]['name'] = $br_name;
			$br[$br_code]['sublist'][$br_ver]['image'] = $br_img;
		}

		return fs_stats_sort(fs_group_others($br));
	}
	else
	{
		return null;
	}
}

function fs_save_excluded_users($list)
{
	if (!fs_is_admin()) return "Access denied : fs_save_excluded_users";
	if(fs_update_local_option('firestats_excluded_users', $list) === false) return fs_db_error(true);
}

function fs_get_recent_search_terms($num_limit, $days_ago = null,$search_term = null)
{
	return fs_get_recent_search_terms_range($num_limit, true, fs_days_ago_to_unix_time($days_ago), null, true, ORDER_BY_HIGH_COUNT_FIRST,$search_term);
}


function fs_get_recent_search_terms_range($num_limit, $is_unix_time, $start_time, $end_time, $site_id, $order_by = ORDER_BY_HIGH_COUNT_FIRST, $search_terms = null)
{
	$fsdb = &fs_get_db_conn();
	$num_limit = is_numeric($num_limit) ? $num_limit : 0;
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$ranges = fs_archive_ranges();
	$archive_referrers = fs_archive_referrers();
	$order_by_str = '';
	switch($order_by)
	{
		case ORDER_BY_HIGH_COUNT_FIRST:
			$order_by_str = "ORDER BY `c` DESC,`referer_id`";
		break;
		case ORDER_BY_RECENT_FIRST:
			$order_by_str = "ORDER BY `ts` DESC,`referer_id`";
		break;	
	}
	
	if (fs_mysql_newer_than("4.1.13")) 
	{
		$for_site = fs_get_site_id_query($site_id, null, 'url_site_id');
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",MAX(SUBSTRING(timestamp,1,10)) `ts`" : "";
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$select1 = "SELECT url_site_id site_id, referer_id, COUNT(referer_id) c $get_timestamp 
					FROM $hits h
					WHERE $for_site AND $timestamp_between
					GROUP BY referer_id";
		
		$for_site = fs_get_site_id_query($site_id,'d');
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",SUBSTRING(`range_start`,1,10) AS `ts`" : "";
		$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		$select2 = "SELECT d.site_id,url_id AS referer_id, SUM(views) c $get_timestamp 
					FROM $archive_referrers d,$ranges r 
					WHERE d.range_id = r.range_id 
					AND $range_between AND $for_site
					GROUP BY url_id";
		
		$limit = $num_limit ? " LIMIT $num_limit" : "";
		
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",`ts`" : "";

		// first level
		if ($search_terms == null)
		{
			$group_by = "GROUP BY refs.search_terms";
			$and_search_term_is = "";	 
		}
		else // second level
		{
			$group_by = "GROUP BY search_engine_id";
			$and_search_term_is = "AND refs.search_terms = ". $fsdb->escape($search_terms);
		}
		
		$sql = "SELECT u.site_id,search_terms ,SUM(u.c) c $get_timestamp , search_engine_id,url referer,COUNT(DISTINCT(search_engine_id)) num_engines
				FROM ($select1 UNION ALL $select2) u, $urls refs
				WHERE u.referer_id = refs.id AND search_engine_id IS NOT NULL AND search_terms IS NOT NULL $and_search_term_is
				$group_by 
				$order_by_str $limit";
		return $fsdb->get_results($sql);
	}
	else // mysql < 4.1.14
	{
		$get_timestamp = $order_by == ORDER_BY_RECENT_FIRST ? ",MAX(SUBSTRING(timestamp,1,10)) `ts`" : "";
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$limit = $num_limit ? " LIMIT $num_limit" : "";
		
		// Stupid mysql 4.0 is actually faster when joining the urls tables twice (1.66 sec vs 1.90 sec). 
		$for_site = fs_get_site_id_query($site_id, "urls");
		/**
		 * first level
		 */
		if ($search_terms == null) 
		{
			$sql = "SELECT urls.site_id,refs.search_terms, count(refs.search_terms) c,refs.search_engine_id,refs.url as referer,COUNT(DISTINCT(refs.`search_engine_id`)) `num_engines` $get_timestamp
							FROM `$hits` h,`$urls` refs,`$urls` urls   
							WHERE h.referer_id = refs.id AND h.url_id = urls.id AND refs.`search_engine_id` IS NOT NULL 
							AND refs.`search_terms` IS NOT NULL 
							AND $for_site AND $timestamp_between 
					GROUP BY refs.`search_terms` $order_by_str $limit";
		}
		else // second level
		{
			$search_terms = $fsdb->escape($search_terms);
			$sql = "SELECT urls.site_id,refs.search_terms, count(refs.search_terms) `c` $get_timestamp ,refs.url as referer,refs.search_engine_id
							FROM $hits h, $urls refs , $urls urls
							WHERE h.referer_id = refs.id AND h.url_id = urls.id AND refs.search_engine_id IS NOT NULL 
							AND $for_site AND $timestamp_between  AND refs.search_terms = $search_terms
					GROUP BY refs.search_engine_id $order_by_str $limit";
		}
		return $fsdb->get_results($sql);
	}
}

function fs_get_recent_referers($num_limit, $days_ago = null, $site_id = true, $order_by = ORDER_BY_FIRST_SEEN)
{
	return fs_get_recent_referers_range($num_limit, true, fs_days_ago_to_unix_time($days_ago), null, $site_id, $order_by);
}  

function fs_get_recent_referers_range($num_limit, $is_unix_time, $start_time, $end_time, $site_id, $order_by = ORDER_BY_FIRST_SEEN, $exclude_internal = true)
{
	$fsdb = &fs_get_db_conn();
	$num_limit = is_numeric($num_limit) ? $num_limit : 0;
	$hits = fs_hits_table();
	$refs = fs_urls_table();
	$ranges = fs_archive_ranges();
	$archive_referrers = fs_archive_referrers();
	$for_site = fs_get_site_id_query($site_id, null, "url_site_id");
	$order_by_str = '';
	switch($order_by)
	{
		case ORDER_BY_HIGH_COUNT_FIRST:
			$order_by_str = "ORDER BY `refcount` DESC";
		break;
		case ORDER_BY_RECENT_FIRST:
			$order_by_str = "ORDER BY `ts` DESC,`referer_id`";
		break;	
		case ORDER_BY_FIRST_SEEN:
			$order_by_str = "ORDER BY `add_time` DESC";
		break;	
	}
	
	if (fs_mysql_newer_than("4.1.13")) 
	{
		$limit = $num_limit ? " LIMIT $num_limit" : "";
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$select1 = "SELECT `referer_id`,COUNT(`referer_id`) `c`,MAX(SUBSTRING(timestamp,1,10)) `ts` 
					FROM `$hits` h
					WHERE $for_site AND $timestamp_between  
					GROUP BY `referer_id`";
		
		$for_site = fs_get_site_id_query($site_id, 'd');
		$range_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		$select2 = "SELECT `url_id` AS `referer_id`, SUM(`views`) `c`,SUBSTRING(`range_start`,1,10) AS `ts` 
					FROM `$archive_referrers` `d`,`$ranges` `r` 
					WHERE d.range_id = r.range_id AND $range_between AND $for_site 
					GROUP BY `url_id`";
					
		$and_exclude_internal = $exclude_internal? "AND refs.site_id IS NULL" : "";
		$sql = "SELECT UNIX_TIMESTAMP(refs.add_time) add_time,refs.url ,SUM(`c`) `refcount`,`ts`,refs.title
				FROM ($select1 UNION ALL $select2) `u`, `$refs` refs 
				WHERE refs.id = u.referer_id AND refs.url != ''
				$and_exclude_internal
				AND refs.search_engine_id IS NULL
				GROUP BY `referer_id` 
				$order_by_str $limit";
		return $fsdb->get_results($sql);
	}
	else
	{
		$and_exclude_internal = $exclude_internal? "AND refs.site_id IS NULL" : "";
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$sql = "SELECT UNIX_TIMESTAMP(refs.add_time) add_time,refs.`url`,count(refs.url) `refcount`,MAX(SUBSTRING(timestamp,1,10)) `ts`,refs.title
						FROM `$hits` h,`$refs` refs
						WHERE 
						$for_site  
						AND h.referer_id = refs.id AND refs.url != ''
						AND $timestamp_between $and_exclude_internal
						AND refs.search_engine_id IS NULL";
		$sql .= " GROUP BY url $order_by_str".($num_limit ? " LIMIT $num_limit" : "");
		return $fsdb->get_results($sql);
	}		
}

function fs_get_popular_pages($num_limit, $days_ago, $site_id, $type = null)
{
	return fs_get_popular_pages_range($num_limit, true, fs_days_ago_to_unix_time($days_ago), null, $site_id, $type);
}

function fs_get_popular_pages_range($num_limit, $is_unix_time, $start_time, $end_time,$site_id, $type = null)
{
	$fsdb = &fs_get_db_conn();
	$num_limit = is_numeric($num_limit) ? $num_limit : 0;
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$url_metadata = fs_url_metadata_table();
	$ranges = fs_archive_ranges();
	$archive_pages = fs_archive_pages();
	$limit = ($num_limit ? " LIMIT $num_limit" : "");
	
	if (fs_mysql_newer_than("4.1.13"))
	{
		$for_site = fs_get_site_id_query($site_id, null, "url_site_id");
	    $select1 = "SELECT url_site_id site_id, url_id, count(url_id) cc 
	    			FROM $hits h
	    			WHERE $for_site AND ".fs_timestamp_between($is_unix_time, $start_time, $end_time)."
	    			GROUP BY url_id";
	    
		$for_site = fs_get_site_id_query($site_id, null, "site_id");
		$select2 = "SELECT site_id, url_id, sum(views) AS cc 
					FROM $archive_pages d, $ranges r 
					WHERE d.range_id = r.range_id AND $for_site 
					AND ".fs_time_range_between($is_unix_time, $start_time, $end_time)." GROUP BY url_id";
		$sql = "SELECT url_id, u.site_id, url, sum(cc) c, title 
				FROM ($select1 UNION ALL $select2) u , $urls urls 
				WHERE u.url_id = urls.id".($type != null ? " AND type = '$type'" : "")."
				GROUP BY url_id
				ORDER BY c DESC ".$limit;
		return $fsdb->get_results($sql);
	}
	else
	{
		$for_site = fs_get_site_id_query($site_id, "urls");
		$sql = "SELECT url_id,`site_id`, COUNT(h.url_id) `c`, title 
    			FROM `$hits` h, `$urls` urls 
    			WHERE 
    				h.url_id = urls.id  
					".($type != null ? " AND type = '$type'" : "")."
					AND $for_site 
					AND ".fs_timestamp_between($is_unix_time, $start_time, $end_time)."
    			GROUP BY h.url_id ORDER BY c DESC ".$limit;
		$res = $fsdb->get_results($sql);
		if ($res === false) return false;
		
		/**
		 * This is a fucked up optimization for stupid mysql.
		 * instead of selecting the url straight in the first select, we first select the id, and then the url
		 * and then merge the results. 
		 * for some obscure reason this is much faster.
		 */
		 
		// create list of url ids. 
		$list = "";
		if (count($res) > 0)
		{
			foreach($res as $r)
			{
				if ($list === "")
				{
					$list = $r->url_id;
				}
				else
				{
					$list .= ", $r->url_id";
				}
			}
		}
		
		if ($list == "") return array();
		// select urls
		$res2 = $fsdb->get_results("SELECT url,id FROM $urls WHERE id in ($list)");
		if ($res2 === false) return false;
		
		// create a map that maps url id to url
		$h = array();
		foreach ($res2 as $r)
		{
			$h[$r->id] = $r->url;	
		}
		
		// populate the urls in the first result.
		if ($res2 === false) return false;
		for($i = 0;$i<count($res);$i++)
		{
			$res[$i]->url = $h[$res[$i]->url_id];
		}
		return $res;
	}
}

function fs_get_country_codes($days_ago = null, $site_id = true)
{
	return fs_get_views_per_country_range($site_id, true,fs_days_ago_to_unix_time($days_ago), null);
}

/**
 * Returns a table mapping country codes to page views from the country in each row, for the specified time range.
 * range is half inclusive : [). 
 *
 * $site_id site id to work on, or false for all sites, or true for current site in options table. (see fs_get_site_id_query() doc).
 * $is_unix_time true if the start and end times are unix time, false for mysql datetime
 * $start_time timestamp of start time
 * $end_time timestamp of end time.
 */
function fs_get_views_per_country_range($site_id, $is_unix_time, $start_time, $end_time)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$archive_countries = fs_archive_countries();
	$ranges = fs_archive_ranges();
	$for_site = fs_get_site_id_query($site_id, null, "url_site_id");
	$valid_country_code = "country_code IS NOT NULL AND country_code != 0";

	if (fs_mysql_newer_than("4.1.13")) 
	{
		$from_site = fs_get_site_id_query($site_id);
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
	    $select1 = "SELECT country_code, count(country_code) c
	    			FROM $hits h
	    			WHERE $for_site AND $timestamp_between AND $valid_country_code
	    			GROUP BY country_code";
	    $timerange_between = fs_time_range_between($is_unix_time, $start_time, $end_time);
		$select2 = "SELECT country_code ,SUM(views) AS c FROM $archive_countries d, $ranges r 
					WHERE d.range_id = r.range_id AND $from_site AND $timerange_between AND $valid_country_code GROUP BY country_code";
		
		$sql = "SELECT country_code, sum( u.c ) c FROM ($select1 UNION ALL $select2) u GROUP BY country_code ORDER BY c DESC";
	}
	else
	{
		$timestamp_between = fs_timestamp_between($is_unix_time, $start_time, $end_time);
		$sql = "SELECT country_code, count(country_code) c
						FROM $hits h
						WHERE  $for_site AND $valid_country_code 
						AND $timestamp_between GROUP BY country_code ORDER BY c DESC";
	}
	return $fsdb->get_results($sql);
}


/**
 * store some usage FireStats usage information
 */
function fs_maintain_usage_stats()
{
	if (fs_is_admin())
	{
		$first_run_time = fs_get_system_option('first_run_time');
		if (!$first_run_time)
		{
			fs_update_system_option('first_run_time',time());
		}
		
		$firestats_id = fs_get_system_option('firestats_id');
		if (!$firestats_id)
		{
			fs_update_system_option('firestats_id',mt_rand());
		}
		
		$commit_strategy = fs_get_system_option('commit_strategy');
		$mysql_supports_automatic_commit = fs_mysql_newer_or_eq_to("5.0");
		if (null == $commit_strategy || ($commit_strategy == FS_COMMIT_AUTOMATIC && !$mysql_supports_automatic_commit))
		{
			// initialize commit strategy to AUTOMATIC if the MySQL version supports it, or to immediate if there is no option.
			fs_update_system_option('commit_strategy',$mysql_supports_automatic_commit ? FS_COMMIT_AUTOMATIC : FS_COMMIT_IMMEDIATE);
		}
	}

	$first_login = fs_get_option('first_login');
	if (!$first_login)
	{
		fs_update_option('first_login',time());
	}
}


function fs_wp_get_users($user_id = null)
{
	if (!fs_in_wordpress())
	{
		echo "not in wp";
		return array(); // currently users are only suppored when installed under wordpress
	}
	$wpdb =& $GLOBALS['wpdb'];
	$sql = "SELECT ID,display_name FROM $wpdb->users";
	$users = $wpdb->get_results($sql,ARRAY_A);
	if ($users === false) return false;
	foreach($users as $u)
	{
		$res[] = array('id'=>$u['ID'],'name'=>$u['display_name']);
	}
	return $res;
}

function fs_botlist_import_url($url, $remove_existing)
{
	$error = '';
	$data = fs_fetch_http_file($url, $error);
	if (!empty($error)) return $error;
	return fs_botlist_import_array(explode("\n",$data), $remove_existing);

}

function fs_botlist_import($file, $remove_existing)
{
	$lines = @file($file);
	if ($lines === false) return sprintf(fs_r('Error opening file : %s'),"<b>$file</b>");
	return fs_botlist_import_array($lines, $remove_existing);
}

function fs_botlist_import_array($lines, $remove_existing)
{
	if ($remove_existing)
	{
		$res = fs_clear_bots_list();
		if ($res != '')
		{
			return $res;
		}
	}

	foreach($lines as $line)
	{
		$l = trim($line);
		if (strlen($l) > 0 && $l[0] != '#')
		{
			$ok = fs_add_bot($line, false);
			if ($ok != '') return $ok;
		}
	}
	return '';
}


function fs_set_url_title($url, $title)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$title = $fsdb->escape($title);
	$url = $fsdb->escape($url);
	$res = $fsdb->query("UPDATE `$urls` SET `title`=$title WHERE `url` = $url");
	if ($res === false)
		return fs_db_error();
	else return true;
}

function fs_set_url_title_by_id($url_id, $title)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$res = $fsdb->query("UPDATE `$urls` SET `title`='$title' WHERE `id` = '$id'");
	if ($res === false)
		return fs_db_error();
	else return true;
}

function fs_set_url_type($url, $type)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$res = $fsdb->query("UPDATE `$urls` SET `type`='$type' WHERE `url` = '$url'");
	if ($res === false)
		return fs_db_error();
	else return true;
}

function fs_set_url_type_by_id($url_id, $type)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$res = $fsdb->query("UPDATE `$urls` SET `type`='$type' WHERE `id` = '$id'");
	if ($res === false)
		return fs_db_error();
	else return true;
}


function fs_insert_url_metadata($url, $type, $value)
{
	$url_id = fs_get_url_id($url);
	if (empty($url_id)) return "URL Not found: <b>$url</b>";
	return fs_insert_url_metadata_by_id($url_id, $type, $value);
}

function fs_delete_url_metadata($url, $type, $value)
{
	$url_id = fs_get_url_id($url);
	if (empty($url_id)) return "URL Not found: <b>$url</b>";
	return fs_delete_url_metadata_by_id($url_id, $type, $value);
}

function fs_replace_url_metadata($url, $type, $value = null)
{
	$url_id = fs_get_url_id($url);
	if (empty($url_id)) return "URL Not found: <b>$url</b>";
	return fs_replace_url_metadata_by_id($url_id, $type, $value);
}

function fs_insert_url_metadata_by_id($url_id, $type, $value = null)
{
	$fsdb = &fs_get_db_conn();
	$url_metadata = fs_url_metadata_table();
	$value = $value != null ? $fsdb->escape($value) : 'NULL';
	$url_id = $fsdb->escape($url_id);
	$type = $fsdb->escape($type);
	$sql = "INSERT INTO `$url_metadata` (`url_id`,`type`,`value`) VALUES ($url_id,$type,$value)";
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();
	return true;
}

function fs_delete_url_metadata_by_id($url_id, $type, $value = null)
{
	$fsdb = &fs_get_db_conn();
	$url_metadata = fs_url_metadata_table();
	$value = $value != null ? $fsdb->escape($value) : null;
	$url_id = $fsdb->escape($url_id);
	$type = $fsdb->escape($type);
	$sql = "DELETE FROM `$url_metadata` WHERE `url_id` = $url_id AND `type` = $type";
	if ($value !== null)
	{
		$sql .= " AND `value` = $value";
	}
	$r = $fsdb->query($sql);
	if ($r === false) return fs_db_error();
	return true;
}

function fs_replace_url_metadata_by_id($url_id, $type, $value = null)
{
	$fsdb = &fs_get_db_conn();
	if ($fsdb->query("START TRANSACTION") === false) return fs_db_error();
	$r1 = fs_delete_url_metadata_by_id($url_id, $type);
	if ($r1 === true)
	{
		$r2 = fs_insert_url_metadata_by_id($url_id, $type, $value);
		$fsdb->query("COMMIT");
		return $r2;
	}
	else
	{
		$fsdb->query("ROLLBACK");
		return $r1;
	}
}

function fs_insert_url($url, $site_id)
{
	$ret = fs_is_url_excluded($url);
	if (is_string($ret)) return $ret;
	
	if ($ret == true) return true;
	
	$urls = fs_urls_table();
	$fsdb = &fs_get_db_conn();
	
	if (!is_numeric($site_id)) return "Invalid site id : $site_id";
	$site_id = $fsdb->escape($site_id);
	$url = $fsdb->escape($url);
	$url_id = $fsdb->get_var("SELECT id FROM $urls WHERE md5 = MD5($url)");
	if($url_id === false)
		return fs_db_error();
	if ($url_id != null)
	{
		$sql = "UPDATE $urls SET url = $url, site_id = $site_id WHERE id = $url_id"; 
		if($fsdb->query($sql) === false)
			return fs_db_error();
	}
	else
	{
		$sql = "INSERT INTO `$urls` (`url`,`site_id`,`md5`,`host`,`add_time`) 
							 VALUES ($url,$site_id,MD5(url),substring_index(substring_index(`url`,'/',3),'/',-1),NOW())";
		if($fsdb->query($sql) === false)
			return fs_db_error();
	}
		
	return true;
}

function fs_is_url_excluded($url)
{
	$fsdb = &fs_get_db_conn();
	$excluded_urls = fs_excluded_urls_table();
	$url = $fsdb->escape($url);
	$ret = $fsdb->get_row("SELECT * FROM $excluded_urls WHERE $url REGEXP `url_pattern`");
	if ($ret === false)  
	{
		return fs_db_error();
	}

	// if the url is excluded return true (this is not an error condition)
	if (count($ret) > 0) return true;
	return false;
}


function fs_get_url_id($url)
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$url = $fsdb->escape($url);
	$sql = "SELECT `id` FROM `$urls` WHERE `md5` = MD5($url)";
	return $fsdb->get_var($sql);
}

function fs_get_last_day_hits()
{
	$mode = fs_get_option("last_day_stats_mode","24h");
	switch ($mode)
	{
		case "24h": return fs_get_hit_count(1, true, null, false);
		case "midnight": return fs_get_hit_count(1, true, null, true);
		default: return 'Error';
	}
}

function fs_get_last_day_visits()
{
	$mode = fs_get_option("last_day_stats_mode","24h");
	switch ($mode)
	{
		case "24h": return fs_get_unique_hit_count(1, true, null, false);
		case "midnight": return fs_get_unique_hit_count(1, true, null, true);
		default: return 'Error';
	}
}

function fs_get_commit_strategy()
{
	$commit_strategy = FS_COMMIT_STRATEGY;
	if ($commit_strategy == FS_COMMIT_BY_OPTION)
	{
		$commit_strategy = fs_get_system_option('commit_strategy',FS_COMMIT_IMMEDIATE);	
	}
	return $commit_strategy;
}
?>
