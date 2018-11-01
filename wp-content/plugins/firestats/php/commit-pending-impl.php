<?php
require_once(dirname(__FILE__).'/init.php');

define('FS_NEW_URL', 1);
define('FS_EXCLUDED_URL', 2);
define('FS_EXISTING_URL', 3);

define('FS_NEW_USERAGENT', 1);
define('FS_EXCLUDED_USERAGENT', 2);
define('FS_EXISTING_USERAGENT', 3);

if (!defined('FS_COMMIT_MAX_CHUNK_SIZE'))
{
	define('FS_COMMIT_MAX_CHUNK_SIZE', 1000);
}

function fs_commit_pending()
{
	require_once(FS_ABS_PATH."/lib/sync/mutex.php");
	
	$mutex = new Mutex(__FILE__);
	$res = $mutex->lock();
	if ($res === false)
	{
		return;
	}
	else
	if (is_string($res))
	{
		fs_println("Error locking : $res");
		return;
	}
	// else, locked successfuly.

	$fsdb = &fs_get_db_conn();

	$pending = fs_pending_date_table();
	
	$processed = 0;
	$start_time = fs_microtime_float();
	$error = false;
	while(true)
	{
		$ret = fs_commit_count_pending($fsdb);
		if (is_string($ret)) 
		{
			$error = $ret;
			break;
		}
		
		if ($ret == 0) break;
	
		ob_start();
		// get a nice chunk of statistics from the pending table.
		$sql = "SELECT * FROM `$pending` ORDER BY id LIMIT ".FS_COMMIT_MAX_CHUNK_SIZE;
		$pending_hits = $fsdb->get_results($sql);
		if ($pending_hits === false)
		{
			$error = fs_db_error();
			break;
		}
		
		$processed += count($pending_hits);
		$processed_ids = "";
	
		$hits_data = array();
	
		// URL to info
		$url_to_info = array();
	
		// ip to info
		$ip_to_info = array();
		
		// useragent to info
		$useragent_to_info = array();
		
		// Process data into arrays, eliminating duplicates.
		for($i = 0;$i<count($pending_hits);$i++)
		{
			$d = &$pending_hits[$i];
			
			if ($processed_ids != "") $processed_ids = "$processed_ids,";
			$processed_ids .= "$d->id";
	
			$d->urlInfo = &fs_commit_add_url($url_to_info, $d->url);
			$d->referrerInfo = &fs_commit_add_url($url_to_info, $d->referrer);
			$d->useragentInfo = &fs_commit_add_useragent($useragent_to_info, $d);
			$d->ipInfo = &fs_commit_add_ip($ip_to_info, $d->ip);
			
			fs_commit_update_url_site_id($url_to_info, $d->url, $d->site_id);
			fs_commit_update_url_add_time($url_to_info, $d->url, $d->timestamp);
			fs_commit_update_url_add_time($url_to_info, $d->referrer, $d->timestamp);
			
		}
		
		// Mark excluded useragents
		$ret = fs_commit_mark_excluded_useragents($fsdb, $useragent_to_info);
		if ($ret !== true)
		{
			$error = $ret;
			break;
		}

		// Mark excluded ip addresses		
		$ret = fs_commit_mark_excluded_ips($fsdb, $ip_to_info);
		if ($ret !== true)
		{
			$error = $ret;
			break;
		}
		
		// Mark excluded urls and referrers		
		$ret = fs_commit_mark_excluded_urls($fsdb, $url_to_info);
		if ($ret !== true)
		{
			$error = $ret;
			break;
		}
		
		// mark excluded hits, and setting final desicion for urls and useragents
		$ret = fs_commit_mark_excluded_hits($pending_hits);
		if ($ret !== true)
		{
			$error = $ret;
			break;
		}
		
		$ret = fs_commit_insert_useragents($fsdb, $useragent_to_info);
		if ($ret !== true)
		{
			$error = $ret;
			break;
		}

		$ret = fs_commit_insert_urls($fsdb, $url_to_info);
		if ($ret !== true)
		{
			$error = $ret;
			break;
		}
		
		$ret = fs_commit_hits_data($fsdb, $pending_hits, $url_to_info, $ip_to_info, $useragent_to_info);
		if ($ret !== true)
		{
			$error = $ret;
			break;
		}

		$ret = $fsdb->get_results("DELETE FROM `$pending` WHERE id in ($processed_ids)");
		if ($ret === false)
		{
			$error = fs_db_error();
			break;
		}
	
		$output = ob_get_clean();
		if (!empty($output))
		{
			$error = $output;
			break;
		}
	}
	
	$elapsed =  fs_microtime_float() - $start_time;
//	fs_println("Processed $processed rows in $elapsed seconds, rate = ". ($processed/$elapsed));
	
	$mutex->unlock();
	
	if ($error !== false)
	{
		fs_println("FireStats error processing pending hits: $error");
	}
}


function fs_commit_hits_data(&$fsdb, &$pending_hits, &$url_to_info, &$ip_to_info, &$useragent_to_info)
{
	require_once(FS_ABS_PATH.'/php/ip2country.php');
	$hit_values = "";
	$rss_values = "";
	$country_codes = array();
	for($i = 0;$i<count($pending_hits);$i++)
	{
		$d  = &$pending_hits[$i];
		if ($d->__excluded) continue; // skip excluded row.
		
		$url = $url_to_info[$d->url];
		$referrer = $url_to_info[$d->referrer];
		$useragent = $useragent_to_info[$d->useragent];
		// at this point, we must have ids for those entities 
		if ($url->id == null) return "Internal error : no id for url $url->url";
		if ($referrer->id == null) return "Internal error : no id for referrer $referrer->url";
		if ($useragent->id == null) return "Internal error : no id for useragent $useragent->useragent";
		
		$ip = $ip_to_info[$d->ip];
		$ip0 = "0x".$ip->ip0;
		$ip1 = "0x".$ip->ip1;
		$timestamp = "'$d->timestamp'";
		$url_id = $url->id;
		$url_site_id = $url->site_id;
		$referrer_id = $referrer->id;
		$useragent_id = $useragent->id;
		if (!isset($country_codes[$ip->ip_text]))
		{
			$ip2c_res =	fs_ip2c($ip->ip_text, true);
			$country_code = ($ip2c_res ? $ip2c_res : "NULL");
			$country_codes[$ip->ip_text] = $country_code;
		}
		else
		{
			$country_code = $country_codes[$ip->ip_text];
		}
			
		if ($d->type == null)
		{
			if ($hit_values != "") $hit_values .= ",";
			$hit_values .= "($ip0,$ip1,$timestamp,$url_id,$url_site_id,$referrer_id,$useragent_id,NULL,$country_code)";
		}
		else
		if ($d->type == FS_URL_TYPE_RSS)
		{
			$rss = fs_rss_subscribers_table();
			$identifier = $fsdb->escape($d->identifier);
			$subscribers = $fsdb->escape($d->subscribers);			
			if ($rss_values != "") $rss_values .= ",";
			$rss_values .= "($timestamp,$url_site_id,$identifier,$url_id,$useragent_id,$subscribers)";
		}
		else
			return "Unsupported hit type $d->type";
	}

	if ($d->type == null)
	{
		if ($hit_values != "")
		{
			$hits = fs_hits_table();
			$sql = "INSERT IGNORE INTO `$hits` (ip_int1,ip_int2,timestamp,url_id,url_site_id,referer_id,useragent_id,session_id,country_code) VALUES $hit_values";
			$ret = $fsdb->query($sql);
			if ($ret === false)
			{
				return fs_db_error();
			}
		}
	}
	else
	if ($d->type == FS_URL_TYPE_RSS)
	{
		if ($rss_values != "")
		{
			$rss = fs_rss_subscribers_table();
			$sql = "REPLACE INTO $rss (`timestamp`,site_id,identifier,url_id,useragent_id,num_subscribers) VALUES $rss_values";
			$ret = $fsdb->query($sql);
			if ($ret === false)
			{
				return fs_db_error();
			}
		}			
	}
	return true;
}

function fs_keys_to_sql_string(&$fsdb, $array, $predicate = null, $md5 = false)
{
	$keys = "";
	foreach($array as $key=>$value)
	{
		if ($predicate == null || $predicate($key, $value))
		{
			$esc = $fsdb->escape($key);
			if ($md5)
				$keys .= ",MD5($esc)";
			else
				$keys .= ",$esc";
		}
	}
	$keys .= ")";
	if (strlen($keys) == 1)
	{
		$keys = '()';
	}
	else
	{
		$keys[0] = '(';
	}
	return $keys;
}

function &fs_commit_add_ip(&$ip_to_info, $ip)
{
	if (!isset($ip_to_info[$ip]))
	{
		$ip_to_info[$ip] = new FsIPInfo($ip);
	}
	
	return $ip_to_info[$ip];
}

function &fs_commit_add_useragent(&$useragent_to_info, &$d)
{
	$useragent = $d->useragent;
	if ($d->type == FS_URL_TYPE_RSS)
	{
		$r = fs_get_feed_hit_data($useragent, $d->ip);
		$useragent = $r->useragent;
		$d->useragent = $useragent;
		$d->subscribers = $r->subscribers;
		$d->identifier = $r->identifier; 
	}
	
	if (!isset($useragent_to_info[$useragent]))
	{
		$useragent_to_info[$useragent] = new FsUseragentInfo($useragent);
	}
	
	return $useragent_to_info[$useragent];
}

function &fs_commit_add_url(&$url_to_info, $url)
{
	if (!isset($url_to_info[$url]))
	{
		$url_to_info[$url] = new FsUrlInfo($url);
	}
	
	return $url_to_info[$url];
}

function fs_commit_update_url_add_time(&$url_to_info, $url, $timestamp)
{
	if ($url_to_info[$url]->add_time == null)
	{
		$url_to_info[$url]->add_time = $timestamp;
	}
	else
	{
		$url_to_info[$url]->add_time = min($timestamp, $url_to_info[$url]->add_time);
	}
}

function fs_commit_update_url_site_id(&$url_to_info, $url, $site_id)
{
	if ($url_to_info[$url]->site_id == null && $site_id != null)
	{
		$url_to_info[$url]->site_id_updated = $url_to_info[$url]->site_id != $site_id;
		$url_to_info[$url]->site_id = $site_id;
	}
}

function fs_commit_update_url_ids(&$fsdb, &$url_to_info, $url_md5s)
{
	$urls_table = fs_urls_table();

	// Extract ids of urls
	$ret = $fsdb->get_results("SELECT $urls_table.id,url,site_id,matching_exclude_patterns FROM $urls_table WHERE md5 IN $url_md5s");
	if ($ret === false)
	{
		return fs_db_error();
	}

	// Update the url to id map
	if (count($ret) > 0)
	{
		foreach($ret as $row)
		{
			$url_to_info[$row->url]->status = $row->matching_exclude_patterns > 0 ? FS_EXCLUDED_URL : FS_EXISTING_URL;
			$url_to_info[$row->url]->id = (int)$row->id;
			$current_sid = isset($url_to_info[$row->url]->site_id) ? $url_to_info[$row->url]->site_id : null;
			if ($current_sid != null && $current_sid != $row->site_id)
			{
				$url_to_info[$row->url]->site_id_updated = true;
			}
			else
			if ($current_sid == null)
			{
				$url_to_info[$row->url]->site_id = $row->site_id;
			}
		}
	}
	
	return true;
}

function fs_commit_count_pending(&$fsdb)
{
	$pending = fs_pending_date_table();
	$c = $fsdb->get_var("SELECT COUNT(*) FROM `$pending`");
	if ($c === false)
	{
		return fs_db_error();
	}
	return (int)$c;
}

function fs_commit_udpdate_changed_site_ids(&$fsdb, &$url_to_info)
{
	$urls_table = fs_urls_table();
	$sql = "UPDATE `$urls_table` SET `site_id` = CASE ";
	$url_ids = "";
	foreach($url_to_info as $url => $info)
	{
		if ($info->status == FS_EXISTING_URL && $info->site_id_updated === true)
		{
			$url_id = $info->id;
			$site_id = $info->site_id;
			if ($url_ids != '') $url_ids = "$url_ids,";
			$url_ids .= "'$url_id'";
			$sql .= "WHEN id='$url_id' THEN '$site_id' ";
		}
	}

	$sql .= " ELSE `site_id` END WHERE id IN ($url_ids)";
	if ($url_ids != "")
	{
		$ret = $fsdb->get_results($sql);
		if ($ret === false)
		{
			return fs_db_error();
		}
	}
	
	return true;
}

function fs_commit_mark_excluded_ips(&$fsdb, &$ip_to_info)
{
	$ip_selects = "";
	foreach($ip_to_info as $ip => $info)
	{
		$ip0 = "0x$info->ip0";
		$ip1 = "0x$info->ip1";

		if ($ip_selects != "")
		{
			$ip_selects .= " UNION ";
		}
		$ipo = $fsdb->escape($ip);
		$ip_selects .= "SELECT $ipo ip,CAST($ip0 AS UNSIGNED) ip0, CAST($ip1 AS UNSIGNED) ip1";
	}
	
	if ($ip_selects != "")
	{
		// convert all hex ips to decimal.
		// this is a work around a mega stupid mysql bug.
		$ret = $fsdb->get_results($ip_selects);
		if ($ret === false)
		{
			return fs_db_error();
		}
		$ip_selects = "";
		foreach($ret as $r)
		{
			$ip = $fsdb->escape($r->ip);
			$ip0 = $r->ip0;
			$ip1 = $r->ip1;
		
			if ($ip_selects != "")
			{
				$ip_selects .= " UNION ";
			}
			$ip_selects .= "SELECT $ip ip,$ip0 ip0, $ip1 ip1";
		}		
		$exclded_ips = fs_excluded_ips_table();
		$ip_selects = "($ip_selects) ips";
		$sql = "SELECT DISTINCT(ip) FROM $exclded_ips,$ip_selects WHERE start_ip2 <= ip1 AND ip1 <= end_ip2 AND start_ip1 <= ip0 AND ip0 <= end_ip1";

		$ret = $fsdb->get_results($sql);	
		if ($ret === false)
		{
			return fs_db_error();
		}
		
		if (count($ret) > 0)
		{
			foreach($ret as $r)
			{
				$ip_to_info[$r->ip]->excluded = true;
			}
		}
	}
	return true;
}

function fs_commit_mark_excluded_urls($fsdb, $url_to_info)
{
	$excluded_urls_table = fs_excluded_urls_table();
	$urls_table = fs_urls_table();

	// Get url ids of urls and site_ids of urls in the database.
	$ret = fs_commit_update_url_ids($fsdb, $url_to_info, fs_keys_to_sql_string($fsdb, $url_to_info, null, true));
	if ($ret !== true) return $ret;


	// Check the new urls against the excluded urls regular expression.
	$url_selects = "";
	
	foreach($url_to_info as $url => $info)
	{
		if ($info->status == FS_NEW_URL)
		{
			$url1 = $fsdb->escape($url);
			if ($url_selects != "")
			{
				$url_selects .= " UNION ";
			}
			$url_selects .= "SELECT $url1 AS url";
		}
	}

	if ($url_selects != "")
	{
		$url_selects = "($url_selects) urls";
		$sql = "SELECT url,count(url_pattern) c FROM $excluded_urls_table right join $url_selects ON url regexp url_pattern GROUP BY url";
		$ret = $fsdb->get_results($sql);
		if ($ret === false)
		{
			return fs_db_error();
		}

		// Mark all excluded urls as exluded
		if (count($ret) > 0)
		{
			foreach($ret as $row)
			{
				if ($row->c > 0)
				{
					$url_to_info[$row->url]->status = FS_EXCLUDED_URL;
				}
			}
		}
	}
	
	return true;
}

function fs_commit_insert_urls(&$fsdb, &$url_to_info)
{
	$excluded_urls_table = fs_excluded_urls_table();
	$urls_table = fs_urls_table();

	// Populate search engine data for urls external urls that are not already in the database.
	// annd create insert query.
	$url_values = "";
	$new_urls = "";
	foreach($url_to_info as $url => $X)
	{
		$info = &$url_to_info[$url];
		if ($info->include &&  $info->status == FS_NEW_URL) // should add to database?
		{
			$url1 = $fsdb->escape($info->url);
			if ($new_urls == "") 
				$new_urls .= "MD5($url1)";
			else
				$new_urls .= ",MD5($url1)";
				
			$info->extract_host();
			if ($info->site_id == null) // external hit, check for search engine terms
			{
				require_once(FS_ABS_PATH.'/php/searchengines.php');
				
				$engine = null;
				$terms = null;
				$res = fs_process_search_engine_referrer($url, $engine, $terms);
				
				if ($res === true && !empty($terms))
				{
					$info->search_engine_id = $engine->id;
					$info->search_terms = $terms;
				}
			}
				
			$addtime = $fsdb->escape($info->add_time);
			$host = $info->host != null ? $fsdb->escape($info->host) : "NULL";
			$se = $info->search_engine_id == null ? "NULL" : $fsdb->escape($info->search_engine_id);
			$st = $info->search_terms == null ? "NULL" : $fsdb->escape($info->search_terms);
			$site_id = $info->site_id== null ? "NULL" : $fsdb->escape($info->site_id);
			if ($url_values != "") $url_values = "$url_values,";
			$url_values .= "($site_id, $url1,MD5(url),$addtime, $host, $se, $st)\n";
		}
	}

	// Add all new urls to the database.
	if ($url_values != "")
	{
		$insert_missing_urls_query = "INSERT IGNORE INTO `$urls_table` (`site_id`,`url`,`md5`,`add_time`,`host`,`search_engine_id`,`search_terms`) VALUES $url_values";
		$ret = $fsdb->get_results($insert_missing_urls_query);
		if ($ret === false)
		{
			return fs_db_error();
		}

		// Update url ids of urls that exists in the database.
		$ret = fs_commit_update_url_ids($fsdb, $url_to_info, "($new_urls)");
		if ($ret !== true) return $ret;
		
	}

	// Update site_id that have been changed for non new urls
	// (This will happen if we first saw a url as a referrer and later as an internal url).
	return fs_commit_udpdate_changed_site_ids($fsdb, $url_to_info);
}

function fs_commit_mark_excluded_useragents(&$fsdb, &$useragent_to_info)
{
	$useragent_table = fs_useragents_table();
	$bots_table = fs_bots_table();

	// Get useragent ids of useragents already in the database.
	$ret = fs_commit_update_useragent_ids($fsdb, $useragent_to_info, fs_keys_to_sql_string($fsdb, $useragent_to_info, null, true));
	if ($ret !== true)
	{
		return $ret;
	}

	$useragent_selects = "";
	foreach($useragent_to_info as $useragent=> $info)
	{
		if ($info->status == FS_NEW_USERAGENT)
		{
			$ua1 = $fsdb->escape($useragent);
			if ($useragent_selects != "")
			{
				$useragent_selects .= " UNION ";
			}
			$useragent_selects .= "SELECT $ua1 AS useragent,MD5($ua1) AS md5";
		}
	}
		
	if ($useragent_selects != "")
	{
		$temp_ua = fs_table_prefix()."temp_useragents"; 
		$sql = "CREATE TEMPORARY TABLE `$temp_ua` (`useragent` TEXT CHARACTER SET binary NOT NULL, `md5` CHAR(32) NOT NULL, INDEX (`md5`)) ENGINE = MyISAM $useragent_selects";
		$ret = $fsdb->query($sql);
		if ($ret === false)
		{
			$error = $fsdb->get_row("SHOW ERRORS");
			if ($error->Code == 1044) // permission denied
			{
				return sprintf(fs_r("You need %s privilege on your database, see %s for more information"),"CREATE TEMPORARY TABLES", fs_link(FS_WIKI."TemporaryTablesPrivilege", "this", true, "_blank"));
			}
			
			return fs_db_error();
		}		
		
		$sql = "SELECT useragent FROM $bots_table right join $temp_ua ON useragent REGEXP wildcard GROUP BY useragent HAVING count(wildcard) > 0";
		$ret = $fsdb->get_results($sql);
		if ($ret === false)
		{
			return fs_db_error();
		}

		// Mark all excluded useragents as exluded
		if (count($ret) > 0)
		{
			foreach($ret as $row)
			{
				$useragent_to_info[$row->useragent]->status = FS_EXCLUDED_USERAGENT;
			}
		}
		$fsdb->query("DROP TABLE $temp_ua");	
	}

	return true;
}

function fs_commit_insert_useragents(&$fsdb, &$useragent_to_info)
{
	$useragent_table = fs_useragents_table();
	$bots_table = fs_bots_table();

	// Add all new useragents to the database.
	$useragents_values = "";
	$new_useragents = "";

	foreach($useragent_to_info as $useragent=> $info)
	{
		if ($info->include && $info->status == FS_NEW_USERAGENT)
		{
			if ($useragents_values != "")
			{
				$useragents_values = "$useragents_values,";
				$new_useragents = "$new_useragents,";
			}
			$ua1 = $fsdb->escape($useragent);
			$useragents_values .= "($ua1, MD5(useragent))";
			$new_useragents .= "MD5($ua1)";
		}
	}

	if ($useragents_values != "")
	{
		$insert_missing_useragents_query = "INSERT IGNORE INTO `$useragent_table` (`useragent`,`md5`) VALUES $useragents_values";
		$ret = $fsdb->get_results($insert_missing_useragents_query);
		if ($ret === false)
		{
			return fs_db_error();
		}

		// Update useragent ids of urls that exists in the database.
		$ret = fs_commit_update_useragent_ids($fsdb, $useragent_to_info, "($new_useragents)");
		if ($ret === false)
		{
			return $ret;
		}
	}
	
	return true;
}

function fs_commit_update_useragent_ids(&$fsdb, &$useragent_to_info, $useragents)
{
	$useragents_table = fs_useragents_table();
	$sql = "SELECT $useragents_table.id,useragent,match_bots FROM $useragents_table WHERE md5 IN $useragents";
	$ret = $fsdb->get_results($sql);
	if ($ret === false)
	{
		return fs_db_error();
	}

	// Update the useragent map
	if (count($ret) > 0)
	{
		foreach($ret as $row)
		{
			if (!isset($useragent_to_info[$row->useragent])) 
			{
				return "Useragent not found in useragent_to_info table : $row->useragent";
			}
			
			$useragent_to_info[$row->useragent]->id = (int)$row->id;
			$useragent_to_info[$row->useragent]->status = $row->match_bots > 0 ? FS_EXCLUDED_USERAGENT : FS_EXISTING_USERAGENT;
		}
	}
	return true;
}

class FsUseragentInfo
{
	var $useragent;
	var $id = null;
	var $status = FS_NEW_USERAGENT;
	
	var $include = false; // final desicion if to insert this useragent into the useragents table.

	function FsUseragentInfo($useragent)
	{
		$this->useragent = $useragent;
	}
}

class FsIPInfo
{
	var $ip_text;
	var $ip0;
	var $ip1;
	var $excluded = false;

	function FsIPInfo($ip)
	{
		$this->ip_text = $ip;
		$ips = fs_ip2hex($ip);
		if ($ips === false)
		{
			// set bad ip addresses to 0.
			// this will allow detection of said ip addresses by the users, and provide a motivation to
			// investigate the cause for it.
			$ips = fs_ip2hex("0.0.0.0");
		}
		
		$this->ip0 = $ips[0];
		$this->ip1 = $ips[1];
	}
}

class FsUrlInfo
{
	// FS_NEW_URL | FS_EXCLUDED_URL | FS_EXISTING_URL
	var $status = FS_NEW_URL;

	var $id = null;
	var $url = null;
	var $add_time = null;
	var $site_id;
	var $site_id_updated = false;
	var $search_engine_id;
	var $search_terms;
	var $host = null;
	
	var $include = false; // final desicion if to insert this url/referrer into the urls table.

	function FsUrlInfo($url)
	{
		$this->url = $url;
	}

	function extract_host()
	{
		$p = @parse_url($this->url);
		if (!$p) return;
		if (isset($p['host']))
		{
			$this->host = $p['host'];
			if (isset($p['port']))
			{
				$this->host .= ":" . $p['port'];
			}
		}
	}
}


function fs_commit_mark_excluded_hits(&$pending_hits)
{
	for($i = 0;$i<count($pending_hits);$i++)
	{
		$hit  = &$pending_hits[$i];
		$exclude_hit = 	$hit->useragentInfo->status == FS_EXCLUDED_USERAGENT ||
		   				$hit->urlInfo->status == FS_EXCLUDED_URL  ||
			   			$hit->referrerInfo->status == FS_EXCLUDED_URL ||
		   				$hit->ipInfo->excluded;
		$hit->__excluded = $exclude_hit;
		if (!$exclude_hit) // included row. mark all referenced data for inclusion
		{
			$hit->useragentInfo->include = true;
			$hit->urlInfo->include = true;
			$hit->referrerInfo->include = true;
		}
	}
	return true;	
}
?>
