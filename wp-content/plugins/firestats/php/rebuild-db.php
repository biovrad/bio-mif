<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-common.php');

fs_register_incremental_process('rebuild_cache', 'fs_rebuild_cache_calc_max', 'fs_rebuild_cache', 'fs_rebuild_cache_desc');

function fs_rebuild_cache_calc_max()
{
	return 3;
}

function fs_rebuild_cache_desc($value)
{
	switch($value)
	{
		case 0:
			return fs_r("Recalculating matching bots");
		break;
		case 1:
			return fs_r("Recalculating matching IP addresses");
		break;
		case 2:
			return fs_r("Recalculating matching urls");
		break;
		default:
			return "Unsupported step number : $value";
	}
}
	
function fs_rebuild_cache($value)
{
	switch($value)
	{
		case 0:
			$res = fs_recalculate_match_bots();
		break;
		case 1:
			$res = fs_recalculate_matching_ips();
		break;
		case 2:
			$res = fs_recalculate_match_urls();
		break;
		default:
			return "Unsupported step number : $value";
	}
	
	if ($res !== true) 
	{
		return $res;
	}else 
		return 1;
}

function fs_recalculate_matching_ips()
{
	$fsdb = &fs_get_db_conn();
	$hits = fs_hits_table();
	$excluded = fs_excluded_ips_table();

	$sql = "UPDATE `$hits` SET `excluded_ip`=0"; 
	if($fsdb->query($sql)===false) return fs_db_error(true);
	
		
	$list = fs_get_excluded_ips();
	if ($list === false) return fs_db_error();
	if (count($list) > 0)
	{
		foreach ($list as $row)
		{
			$sip1 = $row->start_ip1;
			$sip2 = $row->start_ip2;
			$eip1 = $row->end_ip1;
			$eip2 = $row->end_ip2;
	
			$sql = "UPDATE `$hits` SET `excluded_ip`=`excluded_ip`+1 
					WHERE `ip_int1` >= 0x$sip1 AND 
						  `ip_int1` <= 0x$eip1 AND
						  `ip_int2` >= 0x$sip2 AND 
						  `ip_int2` <= 0x$eip2";
			if($fsdb->query($sql)===false) return fs_db_error(true);
		}
	}
	return 1;
}

function fs_recalculate_match_bots()
{
	$fsdb = &fs_get_db_conn();
	$useragents = fs_useragents_table();
	$bots = fs_bots_table();

	$res = $fsdb->get_results("SELECT ua.id id,count(wildcard) c
								FROM $bots RIGHT JOIN $useragents ua ON useragent 
								REGEXP wildcard GROUP BY useragent");
	if ($res === false) return fs_db_error();
	if (count($res) > 0)
	{
		foreach($res as $r)
		{	
			$useragent_id = $r->id;
			$count = $r->c;
			if ($fsdb->query("UPDATE $useragents SET match_bots='$count' WHERE id='$useragent_id'") === false)
			{
				return fs_db_error();
			}
		}
	}
	return 1;
}

function fs_recalculate_match_urls()
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$excluded_urls = fs_excluded_urls_table();

	$res = $fsdb->get_results("UPDATE $urls SET matching_exclude_patterns = 0");
	if ($res === false) return fs_db_error();
	
	$res = $fsdb->get_results("SELECT urls.id id,count(url_pattern) c
								FROM $excluded_urls RIGHT JOIN $urls urls ON url 
								REGEXP url_pattern GROUP BY id HAVING c > 0");
	if ($res === false) return fs_db_error();
	if (count($res) > 0)
	{
		foreach($res as $r)
		{	
			if ($fsdb->query("UPDATE $urls SET matching_exclude_patterns='$r->c' WHERE id='$r->id'") === false)
			{
				return fs_db_error();
			}
		}
	}
	return 1;	
}
?>
