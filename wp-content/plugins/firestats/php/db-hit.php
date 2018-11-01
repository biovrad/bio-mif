<?php

if(!defined('FS_NO_SESSION')) define ('FS_NO_SESSION','');
define('FS_NO_SESSION', true);

require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/db-common.php');

/**
 * Like add hit, but with a mandatory site ID
 */
function fs_add_site_hit($site_id, $close_connection = true)
{
	fs_add_hit($close_connection,$site_id);
}

function fs_add_hit($close_connection = true, $site_id = 1)
{
	if (JS_HIT)
	{
		$link = fs_url("js/fs.js.php?site_id=$site_id");
		echo "<script type='text/javascript' src='$link'></script>\n";
	}
	else
	{
		require_once(dirname(__FILE__).'/init.php');
		$res = fs_add_hit__($site_id);
		if ($close_connection)
		{
			$fsdb = &fs_get_db_conn();
			$fsdb->disconnect();
			fs_get_db_conn(false,true); // clear connection object.
		}

		if ($res !== true)
		{
			echo "FireStats error : $res";
		}
	}
}

function fs_add_hit__($site_id, $time = null)
{
	return fs_add_hit_with_with_strategy(FS_COMMIT_STRATEGY, $site_id, $time);
}

function fs_add_hit_with_with_strategy($commit_strategy, $site_id, $time = null, $is_rss = false)
{

	if ($commit_strategy == FS_COMMIT_BY_OPTION)
	{
		$commit_strategy = fs_get_system_option('commit_strategy',FS_COMMIT_IMMEDIATE);
	}

	if ($commit_strategy == FS_COMMIT_IMMEDIATE)
	{
		if (!$is_rss)
		{
			return fs_add_hit_immediate__($site_id, $time);
		}
		else
		{
			return fs_add_rss_immediate__($site_id, $time);
		}
	}
	else
	if ($commit_strategy == FS_COMMIT_MANUAL)
	{
		return fs_add_hit_delayed__($site_id, $time, $is_rss);
	}
	else
	if ($commit_strategy == FS_COMMIT_AUTOMATIC)
	{
		return fs_add_hit_automatic__($site_id, $time, $is_rss);
	}
	else
	{
		return "FireStats: Unknown commit strategy";
	}
}

function fs_get_hit_data($fsdb,$site_id)
{
	$d = new stdClass();
	$remoteaddr = $useragent = $url = $referer = "''";
	$site_id = $fsdb->escape(empty($site_id) ? 1 : $site_id);
	$real_ip = fs_get_ip_address();
	$remoteaddr = $fsdb->escape(fs_limited_htmlentities($real_ip));
	if (isset($_SERVER['HTTP_USER_AGENT']))
		$useragent 	= $fsdb->escape(fs_limited_htmlentities($_SERVER['HTTP_USER_AGENT'])); // turns out wp likes to add slashes to useragents, which messes things up
	if (isset($_SERVER['REQUEST_URI']))
		$url = $fsdb->escape(fs_limited_htmlentities(fs_get_absolute_url($_SERVER['REQUEST_URI'])));
	$unescaped_referrer = null;
	if (isset($_SERVER['HTTP_REFERER']))
	{
		// if referrer is relative, convert it to absolute using the requested URI (see RFC 2616 section 14.36 : http://www.ietf.org/rfc/rfc2616.txt)
		$unescaped_referrer = fs_get_absolute_url($_SERVER['HTTP_REFERER'], $_SERVER['REQUEST_URI']);
		$referer = $fsdb->escape(fs_limited_htmlentities($unescaped_referrer));
	}
	$d->ip_address = $real_ip;
	$d->site_id = $site_id;
	$d->remoteaddr = $remoteaddr;
	$d->useragent = $useragent;
	$d->url = $url;
	$d->referer= $referer;
	$d->unescaped_referrer = $unescaped_referrer;
	return $d;
}

function fs_add_hit_immediate__($site_id, $time = null)
{
	if (!fs_db_valid())
	{
		return fs_get_database_status_message();
	}

	$fsdb = &fs_get_db_conn();
	$d = fs_get_hit_data($fsdb, $site_id);
	$site_id = $d->site_id;
	$remoteaddr = $d->remoteaddr;
	$useragent = $d->useragent;
	$url = $d->url;
	$referer = $d->referer;

	if ($time === null)
	{
		$time = "NOW()";
	}
	else
	{
		$time = $fsdb->escape($time);
	}

	// break ip address (for ipv6 support)
	$ips = fs_ip2hex($d->ip_address);
	if ($ips === false)
	{
		// set bad ip addresses to 0.
		// this will allow detection of said ip addresses by the users, and provide a motivation to
		// investigate the cause for it.
		$ips = fs_ip2hex("0.0.0.0");
	}
	$ip0 = $ips[0];
	$ip1 = $ips[1];

	$res = fs_is_excluded_hit($fsdb, $site_id, $ip0, $ip1, $url, $referer, $useragent);
	if (true === $res)
	{
		return true;
	}

	if (is_string($res)) return $res;

	if($fsdb->query("START TRANSACTION") === false) return fs_debug_rollback();

	$useragent_id = fs_insert_useragent_hit($fsdb, $useragent);
	if ($useragent_id === false)  return fs_debug_rollback();

	$url_id = fs_insert_url_hit($fsdb, $site_id, $url, $time);
	if ($url_id === false)  return fs_debug_rollback();

	$referer_id = fs_insert_ref_hit($fsdb, $d->unescaped_referrer, $time);
	if ($referer_id === false)  return fs_debug_rollback();

	require_once(dirname(__FILE__).'/ip2country.php');
	$ip2c_res =	fs_ip2c($d->ip_address, true);
	$ccode = ($ip2c_res ? $fsdb->escape($ip2c_res) : "NULL");

	$hits = fs_hits_table();
	// insert to database.
	$sql = "INSERT IGNORE INTO `$hits`
			(ip_int1,ip_int2,timestamp,url_id,url_site_id,referer_id,useragent_id,session_id,country_code) 
					VALUES (0x$ip0,
							0x$ip1,
							$time,
							$url_id,
							$site_id,
							$referer_id,
							$useragent_id,
							".(isset($session_id) ? "$session_id" : "NULL").",
							$ccode
							)";

	if($fsdb->query($sql) === false) return fs_debug_rollback();

	if($fsdb->query("COMMIT") === false)  return fs_debug_rollback();

	return true;
}


function fs_add_hit_automatic__($site_id, $time, $is_rss = false)
{
	$res = fs_add_hit_delayed__($site_id, $time, $is_rss);
	if ($res === true)
	{
		require_once(FS_ABS_PATH."/lib/sync/mutex.php");
		$mutex = new Mutex(__FILE__);
		$ret = $mutex->lock();
		if (is_string($ret))
		{
			return $ret;
		}

		$fsdb = &fs_get_db_conn();

		$pending = fs_pending_date_table();
		$now = time();
		if ((((int)$now - (int)fs_get_system_option('firestats_last_automatic_commit_time',0)) > FS_AUTOMATIC_COMMIT_INTERVAL_SECONDS))
		{
			require(FS_ABS_PATH."/php/commit-pending.php");
			// not require_once on purpose!
			fs_update_system_option('firestats_last_automatic_commit_time',$now, true);
		}

		$mutex->unlock();
	}
	return $res;
}

function fs_add_hit_delayed__($site_id, $time, $is_rss = false)
{
	$pending = fs_pending_date_table();
	$fsdb = &fs_get_db_conn();
	$d = fs_get_hit_data($fsdb, $site_id);
	$site_id = $d->site_id;
	$remoteaddr = $d->remoteaddr;
	$useragent = $d->useragent;
	$url = $d->url;
	$referer = $d->referer;

	if ($time === null)
	{
		$time = "NOW()";
	}
	else
	{
		$time = $fsdb->escape($time);
	}

	$type = $is_rss ? FS_URL_TYPE_RSS : "NULL";
	$sql = "INSERT DELAYED INTO `$pending` (
				`timestamp`,
				`site_id` ,
				`url` ,
				`referrer` ,
				`useragent` ,
				`ip`,
				`type`
			)
			VALUES (
			$time,
			$site_id,
				$url,
				$referer,
				$useragent,
				$remoteaddr,
				$type
			)";	
	if($fsdb->query($sql) === false)
	{
		return fs_db_error();
	}
	return true;
}

/**
 * This function returns the best ip address for the client.
 * the if the client passed through a proxy it tries to detect the correct client ip.
 * if its a private (LAN) address it uses first public IP (usually the proxy itself).
 */
function fs_get_ip_address()
{
	// obtain the X-Forwarded-For value.
	$headers = function_exists('getallheaders') ? getallheaders() : null;
	$xf = isset($headers['X-Forwarded-For']) ? $headers['X-Forwarded-For'] : "";
	if (empty($xf))
	{
		$xf = isset($GLOBALS['FS_X-Forwarded-For']) ? $GLOBALS['FS_X-Forwarded-For'] : "";
	}

	if (empty($xf))
	{
		$xf = $_SERVER['REMOTE_ADDR'];
	}
	else
	{
		$xf = $xf.",".$_SERVER['REMOTE_ADDR'];
	}
	$fwd = explode(",",$xf);
	foreach($fwd as $ip)
	{
		$ip = trim($ip);
		$ipver = fs_ip_version($ip);
		if ($ipver == 4)
		{
			$long = ip2long($ip);
			if ($long != -1 && $long !== false)
			{
				if (fs_is_public_ipv4($long)) return $ip;
			}
		}
		else
		{
			// assume all ipv6 are public. this is incorrect but it's not really that important with the current state of ipv6.
			return $ip;
		}
	}

	// if we got this far and still didn't find a public ip, just use the first ip address in the chain.
	
	if (fs_ip_version($fwd[0]) == 0) // invalid ip, hack attempt?
		return $_SERVER['REMOTE_ADDR'];
	
	return $fwd[0];
}

function fs_is_public_ipv4($long)
{

	// 167772160 - 10.0.0.0
	// 184549375 - 10.255.255.255
	//
	// -1408237568 - 172.16.0.0
	// -1407188993 - 172.31.255.255
	//
	// -1062731776 - 192.168.0.0
	// -1062666241 - 192.168.255.255
	//
	// -1442971648 - 169.254.0.0
	// -1442906113 - 169.254.255.255
	//
	// 2130706432 - 127.0.0.0
	// 2147483647 - 127.255.255.255 (32 bit integer limit!!!)
	//
	// -1 is also b0rked
	if (($long >= 167772160 AND $long <= 184549375) OR
	($long >= -1408237568 AND $long <= -1407188993) OR
	($long >= -1062731776 AND $long <= -1062666241) OR
	($long >= 2130706432 AND $long <= 2147483647) OR $long == -1)
	{
		return false;
	}

	return true;
}

function fs_limited_htmlentities($str)
{
	return str_replace (array ( '<', '>'),
	array ( '&lt;' , '&gt;'),
	$str);

}

function fs_debug_rollback()
{
	$fsdb = &fs_get_db_conn();
	$msg = sprintf(fs_r('Database error: %s'), $fsdb->last_error)."<br/>\n". sprintf('SQL: %s', $fsdb->last_query);
	$fsdb->query("ROLLBACK");
	echo $msg;
	return $msg;
}

function fs_insert_useragent_hit(&$fsdb, $useragent)
{
	$useragents = fs_useragents_table();
	// insert to user agent table (no duplicates)
	$ret = $fsdb->query("INSERT IGNORE INTO `$useragents` (`useragent`,`md5`) VALUES ($useragent ,MD5(`useragent`))");
	if($ret === false)
	{
		return false;
	}

	// get index of useragent in table, can't use LAST_INSERT_ID() here because of the no-dups policy
	return $fsdb->get_var("SELECT id from `$useragents` WHERE md5 = MD5($useragent)");
}

function fs_insert_url_hit(&$fsdb, $site_id, $url, $time, $type = null)
{
	$urls = fs_urls_table();
	$type_str = $type != null ? $fsdb->escape($type) : 'NULL';
	if($fsdb->query("INSERT IGNORE INTO `$urls` (`url`,`md5`,`add_time`,`type`,`host`) VALUES ($url,MD5(url),$time,$type_str,substring_index(substring_index(`url`,'/',3),'/',-1))") === false)
	{
		return false;
	}

	// get index of url in table, can't use LAST_INSERT_ID() here because of the no-dups policy
	$url_id = $fsdb->get_var("SELECT id FROM $urls WHERE md5 = MD5($url)");
	if ($url_id === false) return false;

	// update site id of url to current site id.
	// this is only done for the url and not for the referrer:
	// we don't know the site id of the referrer. if it will appear as a url it will be assigned the site_id.
	if (false === $fsdb->get_var("UPDATE `$urls` SET `site_id` = $site_id WHERE `id` = $url_id"))
	{
		return false;
	}

	return $url_id;
}


function fs_insert_ref_hit(&$fsdb, $unescaped_referrer, $time)
{
	$urls = fs_urls_table();
	// insert referers into urls table (no duplicates)
	require_once(FS_ABS_PATH.'/php/searchengines.php');
	$search_engine_id = "NULL";
	$search_terms = "NULL";
	if ($unescaped_referrer != null)
	{
		$engine = null;
		$terms = null;
		$res = fs_process_search_engine_referrer($unescaped_referrer, $engine, $terms);

		if ($res === true && !empty($terms))
		{
			$id = $engine->id;
			if (!empty($id)) $search_engine_id = $fsdb->escape($id);
			$search_terms = $fsdb->escape($terms);
		}
	}

	$referrer_breakdown = @parse_url($unescaped_referrer);
	$host = isset($referrer_breakdown['host']);
	$has_host = $host != null;
	$optional_host = ($has_host ? ",`host`":"");
	$host = $fsdb->escape($host);
	$optional_host_query = ($has_host ? ",$host":"");
	$referer = $fsdb->escape($unescaped_referrer);
	if($fsdb->query("INSERT IGNORE INTO `$urls`(`url`,`md5`,`add_time`,`search_engine_id`,`search_terms` $optional_host) VALUES ($referer,MD5(url),$time,$search_engine_id ,$search_terms $optional_host_query)") === false)
	{
		return false;
	}

	// get index of url in table, can't use LAST_INSERT_ID() here because of the no-dups policy
	return $fsdb->get_var("SELECT id from $urls WHERE md5 = MD5($referer)");
}


function fs_is_excluded_hit(&$fsdb, $site_id, $ip0, $ip1, $url, $referer, $useragent)
{
	$r = fs_is_excluded_ip($fsdb, $ip0, $ip1);
	if ($r !== false) return $r;

	$r = fs_is_excluded_useragent($fsdb, $useragent);
	if ($r !== false) return $r;

	$r = fs_is_excluded_url_or_referrer($fsdb, $url, $referer);
	if ($r !== false) return $r;

	return false;
}

function fs_is_excluded_url_or_referrer(&$fsdb, $url, $referer)
{
	$excluded_urls = fs_excluded_urls_table();
	$ret = $fsdb->get_var("SELECT COUNT(*) FROM $excluded_urls WHERE $referer REGEXP `url_pattern` OR  $url REGEXP `url_pattern`");
	if ($ret === false)
	{
		return fs_db_error();
	}

	return (int)$ret > 0;
}

function fs_is_excluded_useragent(&$fsdb, $useragent)
{
	$bots = fs_bots_table();
	$ret = $fsdb->get_var("SELECT COUNT(*) FROM $bots WHERE $useragent REGEXP `wildcard`");
	if ($ret === false)
	{
		return fs_db_error();
	}
	return (int)$ret > 0;
}

function fs_is_excluded_ip(&$fsdb, $ip0, $ip1)
{
	$excluded_ips = fs_excluded_ips_table();

	$excluded_ip = $fsdb->get_var("SELECT COUNT(*) FROM `$excluded_ips`
						 			WHERE 	`start_ip1` <= 0x$ip0 AND 
						 	   				`end_ip1` >= 0x$ip0 AND
						 	   				`start_ip2` <= 0x$ip1 AND 
						 	   				`end_ip2` >= 0x$ip1");
	if ($excluded_ip === false)  return fs_db_error();
	return ((int)$excluded_ip > 0);
}

function fs_add_rss_feed_hit($site_id, $close_connection = true)
{
	require_once(dirname(__FILE__).'/init.php');
	$res = fs_add_rss_feed_hit__($site_id);
	if ($close_connection)
	{
		$fsdb = &fs_get_db_conn();
		$fsdb->disconnect();
		fs_get_db_conn(false,true); // clear connection object.
	}

	if ($res !== true)
	{
		echo "FireStats error : $res";
	}
}


function fs_add_rss_feed_hit__($site_id)
{
	$res = fs_add_hit_with_with_strategy(FS_COMMIT_STRATEGY,$site_id, null, true);
	if ($res === true)
	{
		return fs_delete_old_rss_records();
	}
	else
	{
		return $res;
	}
}

function fs_delete_old_rss_records()
{
	$today = date("l");
	if ($today != fs_get_system_option('firestats_last_rss_maintenance_day',""))
	{
		$rss = fs_rss_subscribers_table();
		$fsdb = &fs_get_db_conn();
		$time = fs_days_ago_to_unix_time(3); // delete records which were updated more than 3 days ago.
		$res = $fsdb->query("DELETE FROM $rss WHERE `timestamp` < FROM_UNIXTIME($time)");
		if ($res === false) return fs_db_error();
		fs_update_system_option('firestats_last_rss_maintenance_day',$today, true);
	}

	return true;
}

function fs_add_rss_immediate__($site_id, $time)
{
	$fsdb = &fs_get_db_conn();
	$d = fs_get_hit_data($fsdb, $site_id);
	$site_id = $d->site_id;
	$remoteaddr = $d->remoteaddr;
	$useragent = $d->useragent;
	$url = $d->url;
	$referer = $d->referer;
	$ip_address = $d->ip_address;
	if ($time == null) $time = "NOW()";

	// break ip address (for ipv6 support)
	$ips = fs_ip2hex($ip_address);
	if ($ips === false)
	{
		// set bad ip addresses to 0.
		// this will allow detection of said ip addresses by the users, and provide a motivation to
		// investigate the cause for it.
		$ips = fs_ip2hex("0.0.0.0");
	}
	$ip0 = $ips[0];
	$ip1 = $ips[1];

	$res = fs_is_excluded_hit($fsdb, $site_id, $ip0, $ip1, $url, $referer, $useragent);
	if (is_string($res)) return $res;
	if (true === $res)
	{
		return true;
	}

	$useragent_id = fs_insert_useragent_hit($fsdb, $useragent);
	if ($useragent_id === false)  return fs_debug_rollback();


	$url_id = fs_insert_url_hit($fsdb, $site_id, $url, "NOW()", FS_URL_TYPE_RSS);
	if ($url_id === false)  return fs_debug_rollback();

	$r = fs_get_feed_hit_data($useragent, $ip_address);

	$rss = fs_rss_subscribers_table();
	$identifier = $fsdb->escape($r->identifier);
	$subscribers = $fsdb->escape($r->subscribers);
	$sql = "REPLACE INTO $rss (`timestamp`,site_id,identifier,url_id,useragent_id,num_subscribers) VALUES ($time,$site_id,$identifier,$url_id,$useragent_id,$subscribers)";
	$ret = $fsdb->query($sql);
	if ($ret === false)
	{
		return fs_db_error();
	}
	return true;
}
?>
