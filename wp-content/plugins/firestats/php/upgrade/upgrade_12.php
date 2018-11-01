<?php

fs_register_incremental_process('fs_db_upgrade_12_convert_ip', 'fs_db_upgrade_12_convert_ip_calc_max', 'fs_db_upgrade_12_convert_ip_step', null,array(__FILE__), "fs_db_upgrade_12_convert_ip_done");

function fs_db_upgrade_12(&$fsdb, $db_version, &$response)
{
	$hits = fs_hits_table();
	$urls = fs_urls_table();
	$archive_pages = fs_archive_pages();
	$excluded_ips = fs_excluded_ips_table();
	
	if (!fs_create_excluded_urls($fsdb)) return false;
	
	if (!fs_create_excluded_urls($fsdb)) return false;
	
	if (!fs_create_user_sites_table($fsdb)) return fs_db_error();
	
	if (!fs_collapse_duplicate_archived_pages($fsdb)) return false;
	
	$sqls = array
	(
		fs_column_exists($fsdb,$hits,'site_id'), "ALTER TABLE `$hits` DROP `site_id`",
		fs_column_exists($fsdb,$archive_pages,'site_id'), "ALTER TABLE `$archive_pages` DROP `site_id`",
		fs_column_not_exists($fsdb,$excluded_ips,'start_ip1'), "ALTER TABLE `$excluded_ips` ADD `start_ip1` BIGINT UNSIGNED NULL",
		fs_column_not_exists($fsdb,$excluded_ips,'start_ip2'), "ALTER TABLE `$excluded_ips` ADD `start_ip2` BIGINT UNSIGNED NULL",
		fs_column_not_exists($fsdb,$excluded_ips,'end_ip1'), "ALTER TABLE `$excluded_ips` ADD `end_ip1` BIGINT UNSIGNED NULL",
		fs_column_not_exists($fsdb,$excluded_ips,'end_ip2'), "ALTER TABLE `$excluded_ips` ADD `end_ip2` BIGINT UNSIGNED NULL",
		fs_column_not_exists($fsdb,$hits,'ip_int1'), "ALTER TABLE `$hits` ADD `ip_int1` BIGINT UNSIGNED NULL AFTER ip",
		fs_column_not_exists($fsdb,$hits,'ip_int2'), "ALTER TABLE `$hits` ADD `ip_int2` BIGINT UNSIGNED NULL AFTER ip_int1",
		fs_column_not_exists($fsdb,$urls,'matching_exclude_patterns'), "ALTER TABLE `$urls` ADD `matching_exclude_patterns` INT NOT NULL DEFAULT '0' AFTER `add_time`",
	);
	
	if (!fs_populate_user_sites_table($fsdb)) return false;
	
	$res = fs_apply_db_upgrade($fsdb,$sqls);
	if ($res !== true) return $res; 
	
	if (!fs_convert_excluded_ips_to_int($fsdb))
	{
		echo fs_db_error();
		return false;
	}
	
	// turn version checks back on because we now check for new version asyncrhounsly.
	fs_update_system_option("firestats_version_check_enabled",'true');
	fs_update_system_option("ip-to-country-db_version_check_enabled",'true');
	fs_update_system_option("botlist_version_check_enabled",'true');
	
	if (fs_column_exists($fsdb,$hits,'ip') && fs_db_upgrade_12_convert_ip_calc_max() > 0)
	{
		$process_id = 'fs_db_upgrade_12_convert_ip';
		$process_id_progress = $process_id."_process_progress";
		$response['execute'] = "FS.executeProcess('$process_id','php/upgrade/upgrade_12.php')";
		$response['fields']['upgrade_progress'] = "<div id='$process_id_progress'></div>"; 
	}
	else
	{
		$res = fs_upgrade_complete($fsdb, $response, 12);
		if ($res !== true) return $res;
	}
	return true;
}

function fs_convert_excluded_ips_to_int(&$fsdb)
{
	$excluded_ips = fs_excluded_ips_table();
	
	if (fs_column_not_exists($fsdb,$excluded_ips,'ip')) return true;
	
	$ips = $fsdb->get_results("SELECT `ip` FROM `$excluded_ips`");
	if ($ips === false) return false;
	
	if (count($ips) > 0)
	{
		foreach($ips as $ip)
		{
			$v = fs_ip2hex($ip->ip);
			if ($v == false)
			{
				$res = $fsdb->query("DELETE FROM `$excluded_ips` WHERE `ip` = '$ip->ip'");
				if ($res === false)
				{
					echo fs_db_error();
					return false;
				}
			}
			else
			{
				// ignore the first nibble as it's obviously 0 because only ipv4 addresses are supported for filtering ATM
				$nib1 = $v[0];
				$nib2 = $v[1];
				$res = $fsdb->query("UPDATE `$excluded_ips` 
									 SET start_ip1 = 0x$nib1, 
									 	 start_ip2 = 0x$nib2,
									 	 end_ip1 = 0x$nib1, 
									 	 end_ip2 = 0x$nib2  
									 WHERE `ip` = '$ip->ip'");
				if ($res === false)
				{
					echo fs_db_error();
					return false;
				}
			}
		}
	}
	
	if (false === $fsdb->query("ALTER TABLE `$excluded_ips` DROP `ip`"))
	{
		echo fs_db_error();
		return false;
	}
	
	return true;
	
}

function fs_db_upgrade_12_convert_ip_calc_max()
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$count = $fsdb->get_var("SELECT COUNT(DISTINCT(IP)) c FROM `$hits` WHERE IP IS NOT NULL");
	if ($count === false)
	{
		return fs_db_error();
	}
	else
	{
		return $count;
	}	
}

function fs_db_upgrade_12_convert_ip_step($value, $max)
{
	$reset = $value == 0;
	$num_to_build = 1000;
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	if ($reset)
	{
		if (false === $fsdb->get_results("UPDATE `$hits` SET `ip_int1` = NULL,`ip_int2` = NULL"))
		{
			return fs_db_error();
		}
	}
	
	$res = $fsdb->get_results("SELECT DISTINCT(IP) FROM `$hits` WHERE `ip_int1` IS NULL ORDER BY `timestamp` LIMIT $num_to_build");
	if ($res === false)
	{
		return fs_db_error();
	}
	else
	{
		$chunk_size = 200;
		$c = count($res);
		$index = 0;
		if ($c > 0)
		{
			while($index < $c)
			{
				$ii = 0;
				$sql = "UPDATE `$hits` SET";  
				$sql1 = " `ip_int1` = CASE ";
				$sql2 = " `ip_int2` = CASE ";
				$ips = '';
				while ($ii < $chunk_size && $index < $c)
				{
					$record = $res[$index++];
					$ii++;
					
					$ip = $record->IP;
					if ($ips == '')
					{
						$ips .= "'$ip'";
					}
					else 
						$ips .= ",'$ip'";
					
					$v = fs_ip2hex($ip);
					if ($v !== false)
					{
						$dec1 = base_convert($v[0], 16, 10);
						$dec2 = base_convert($v[1], 16, 10);
					}
					else
					{
						$dec1 = 0;
						$dec2 = 0;
					}
					$sql1 .= "WHEN IP='$ip' THEN '$dec1' ";
					$sql2 .= "WHEN IP='$ip' THEN '$dec2' ";
				}
				$sql1 .= " ELSE `IP` END";
				$sql2 .= " ELSE `IP` END";
				$sql .= "$sql1,$sql2 WHERE IP IN ($ips)";
				$r2 = $fsdb->query($sql);
				if ($r2 === false)
				{
					return fs_db_error();
				}
			}
		}
		return $index;
	}
}

function fs_db_upgrade_12_convert_ip_done(&$response)
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	
	if (fs_column_exists($fsdb,$hits,'ip'))
	{
		$r2 = $fsdb->query("ALTER TABLE `$hits` DROP `ip`");
		if ($r2 === false)
		{
			$response['message'] = fs_db_error();
			$response['status'] = 'error';
			return;
		}		
	}
	
	return fs_upgrade_complete($fsdb, $response, 12);
}

/**
 * Merges duplicate archive pages, for example, if we have:
 * range_id=10, site_id=2,url_id=10, visits=15,views=20
 * range_id=10, site_id=1,url_id=10, visits=5,views=8
 * 
 * it will merge it to
 * range_id=10, site_id=1,url_id=10, visits=20,views=28
 * 
 * This required before deleting the site_id row (as of 1.5, the site_id for archive_pages is taken from the urls table)
 */
function fs_collapse_duplicate_archived_pages(&$fsdb)
{
	$archive_pages = fs_archive_pages();
	
	// if site_id is not there, there is nothing to do.
	if (fs_column_not_exists($fsdb,$archive_pages,'site_id')) return true;
	
	do
	{
		$sql = "SELECT range_id,site_id,url_id,CONCAT_WS( '-', `range_id` , `url_id` ) s, count(*) c FROM `$archive_pages` GROUP BY s HAVING c > 1";
		
		$res = $fsdb->get_results($sql);
		if ($res === false) 
		{
			return fs_db_error();
		}
		
		if (count($res) == 0)
		{
			break;	
		}
		$f = $res[0];
		$sql= "SELECT SUM(views) views FROM `$archive_pages` WHERE range_id = '$f->range_id' and url_id = '$f->url_id' GROUP BY '$f->url_id'";
		$sum_views = $fsdb->get_row($sql);
		if ($sum_views === false) 
		{
			return fs_db_error();
		}
		
		
		
		$sql= "SELECT SUM(visits) visits FROM `$archive_pages` WHERE range_id = '$f->range_id' and url_id = '$f->url_id' GROUP BY '$f->url_id'";
		$sum_visits = $fsdb->get_row($sql);
		if ($sum_visits === false) 
		{
			return fs_db_error();
		}

		$fsdb->query("START TRANSACTION");
		$sql = "DELETE FROM `$archive_pages` WHERE site_id != '$f->site_id' AND range_id = '$f->range_id' and url_id = '$f->url_id'";
		if (false === $fsdb->query($sql))
		{
			return fs_db_error(true);
		}
		$update = "UPDATE `$archive_pages` SET views='$sum_views->views',visits='$sum_visits->visits' WHERE site_id = '$f->site_id' AND range_id = '$f->range_id' and url_id = '$f->url_id'";
		if (false === $fsdb->query($update))
		{
			return fs_db_error(true);
		}
		
		$fsdb->query("COMMIT");
		
	} while (true);
	
	return true;
}

function fs_populate_user_sites_table(&$fsdb)
{
	$user_sites = fs_user_sites_table();
	$users = fs_get_users();
	if (count($users) == 0) return true;

	$sql = "REPLACE INTO `$user_sites` (`user_id`,`site_id`) VALUES";
	$context = array();
	$context["fsdb"] = $fsdb;
	$func = create_function('$context,$user','extract($context);$user_id = $fsdb->escape($user->id);return "($user_id,-1)";');	
	$values = fs_implode(",", $users, $func, $context);
	if (false === $fsdb->query($sql . $values)) 
	{
		return fs_db_error(true);
	}
	return true;
}
?>
