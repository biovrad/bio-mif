<?php

require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/utils.php');

function &fs_get_db_conn($force_new = false,$clear = false)
{
	static $fsdb;
	if ($clear) 
	{
		unset($fsdb);
	}
	else
	{
		if (!isset($fsdb) || $force_new)
		{
			fs_load_config();
			global $fs_config;
			require_once(FS_ABS_PATH."/lib/ezsql/shared/ez_sql_core.php");
			require_once(FS_ABS_PATH."/lib/ezsql/mysql/ez_sql_mysql.php");
			$fsdb = fs_create_db_conn(	$fs_config['DB_USER'], 
					$fs_config['DB_PASS'], 
					$fs_config['DB_NAME'], 
					$fs_config['DB_HOST']);
		}
	}

	return $fsdb;
}

function fs_create_db_conn($user, $pass, $dbname, $dbhost)
{
	$conn = new fs_ezSQL_mysql($user,$pass,$dbname,$dbhost);
	$conn->hide_errors();
	$conn->connect();
	return $conn;
}

function fs_get_db_status($fsdb = null)
{
	if (fs_get_db_config_type() == FS_DB_CONFIG_UNAVAILABLE)
	{
		return array('status'=>FS_DB_NOT_CONFIGURED,'ver'=>0);
	}

	if (!$fsdb)
	{
		$fsdb = &fs_get_db_conn();
	}
	if (!$fsdb)
	{
		return array('status'=>FS_DB_NOT_INSTALLED,'ver'=>0);
	}

	if (!$fsdb->is_connected())
	{
		return array('status'=>FS_DB_CONNECTION_ERROR,'ver'=>0);
	}

  	$version_table = fs_version_table();
  	$sql = "SHOW TABLES LIKE '$version_table'";
	
  	$results = $fsdb->query($sql);
  	if ($results === FALSE)
  	{
  		return array('status'=>FS_DB_GENERAL_ERROR,'ver'=>0);
	}

	if ($results == 0)
		return array('status'=>FS_DB_NOT_INSTALLED,'ver'=>0);
	
	$ver = (int)$fsdb->get_var("SELECT `version` FROM `$version_table`");
	if ($ver == 0)
 	{
 		return array('status'=>FS_DB_NOT_INSTALLED,'ver'=>0);
	}
	else
	if ($ver == FS_REQUIRED_DB_VERSION)
	{
		return array('status'=>FS_DB_VALID,'ver'=>$ver);
	}
	else
	if ($ver < FS_REQUIRED_DB_VERSION)
	{
		return array('status'=>FS_DB_NEED_UPGRADE,'ver'=>$ver);
	}
	else
	if ($ver > FS_REQUIRED_DB_VERSION)
	{
		return array('status'=>FS_DB_IS_NEWER_THAN_CODE,'ver'=>$ver);
	}

	die('Logic is broken, life sucks');
}

function fs_db_valid()
{
	$db = fs_get_db_status();
	$res = $db['status'] == FS_DB_VALID;
	return $res;
}

function fs_get_database_status_message($db_status_array = null)
{
	if (!$db_status_array)
	{
		$fsdb = &fs_get_db_conn();
		$db_status_array = fs_get_db_status($fsdb);
	}
	$db_status = $db_status_array['status'];

	$msg = '';
	switch ($db_status)
	{
		case FS_DB_VALID:
			$msg = fs_r('FireStats is properly installed in the database');
		break;
		case FS_DB_NOT_CONFIGURED:
			$msg = fs_r('FireStats is not configured');
		break;
		case FS_DB_GENERAL_ERROR:
			$msg = sprintf(fs_r('Database error, check your configuration: %s'), fs_db_error());
		break;
		case FS_DB_NOT_INSTALLED:
			$msg = fs_r('FireStats is not installed in the database');
		break;
		case FS_DB_NEED_UPGRADE:
			$msg = fs_r('FireStats database needs to be upgraded');
		break;
		case FS_DB_IS_NEWER_THAN_CODE:
			$msg = fs_r('The FireStats database version is newer than this code version, you need to upgrade FireStats');
		break;
		case FS_DB_CONNECTION_ERROR:
			$msg = fs_r('Error connecting to database');
		break;
		default:
			$msg = fs_r('Unknown database status code');
	}
	return $msg;
}

function fs_mysql_older_than($version)
{
	return ver_comp($version,fs_mysql_version(),true) > 0;
}

function fs_mysql_newer_than($version)
{
	return ver_comp($version,fs_mysql_version(),true) < 0;
}

function fs_mysql_newer_or_eq_to($version)
{
	return ver_comp($version,fs_mysql_version(),true) <= 0;
}


function fs_mysql_version()
{
	$fsdb = &fs_get_db_conn();
	global $fs_mysql_version;
	if (!isset($fsdb))
	{		
		return false;
	}
	else
	{
		static $fs_mysql_version;
		if (!isset($fs_mysql_version))
		{
			$fs_mysql_version = $fsdb->get_var("select version()");
			if ($fs_mysql_version == null)
			{
				return false;
			}
		}

		return $fs_mysql_version;
	}
}

if (!isset($GLOBALS['fs_options_cache']))
	$GLOBALS['fs_options_cache'] = array();

function fs_get_system_option($key, $default=null)
{
	return fs_get_option_impl(-1, $key, $default);
}

/**
 * Updates a system option.
 * System options are options which are global to the entire FireStats installation and effect all users.
 * Normally only administrators can set user options, but in some cases the system option is something 
 * that does not effect preceived system behavior, like keeping a timestamp for performance reasons.
 * calling code may pass $any_user = true to indicate even non-admin users may save this variable.
 *
 * @param string $key option key to save
 * @param string $value option value to save
 * @param boolean $any_user see above comment, use with caution!
 * @return unknown
 */
function fs_update_system_option($key, $value, $any_user = false)
{
	if (fs_is_admin() || $any_user)
	{
		return fs_update_option_impl(-1, $key, $value);
	}
	else
	{
		echo "Access denied : fs_update_system_option($key)";
	}
}

function fs_get_option($key, $default=null)
{
	$uid = fs_current_user_id();
	if ($uid === false) 
	{
		return $default;
	}
	return fs_get_option_impl($uid, $key, $default);
}


function fs_update_option($key, $value, $save_to_session = false)
{
	if ($save_to_session)
	{
		global $FS_SESSION;
		if (!isset($FS_SESSION)) return;
		$FS_SESSION['session_options'][$key] = $value; 		
	}
	else
	{
		$uid = fs_current_user_id();
		if ($uid === false) 
		{
			echo "Unknown user when updating option $key";
			return;
		}
		return fs_update_option_impl($uid, $key, $value);
	}
}

function fs_get_option_impl($user_id, $key, $default=null)
{
	global $FS_SESSION;
	if (isset($FS_SESSION['session_options'][$key])) return $FS_SESSION['session_options'][$key]; 
	
	global $options_cache;
	if (isset($options_cache) && array_key_exists($key,$options_cache))
	{
		return $options_cache[$key];
	}
	else
	{
		$fsdb = &fs_get_db_conn();
		if (!$fsdb->is_connected()) trigger_error('Database not connected');
		$key1 = $fsdb->escape($key);
		$user_id = $fsdb->escape($user_id);
		$options_table = fs_options_table();
		$sql = "SELECT `option_value` FROM `$options_table` WHERE `option_key`=$key1 AND `user_id` = $user_id";
		$val = $fsdb->get_var($sql);
		if ($val === null) $val = $default;
		$options_cache[$key] = $val;
		return $val;
	}
}

function fs_update_option_impl($user_id, $key, $value)
{
	global $options_cache;
	if (isset($options_cache) && array_key_exists($key,$options_cache) && $options_cache[$key] == $value) return; // nothing to do, already in cache.
	$fsdb = &fs_get_db_conn();
	if (!$fsdb->is_connected()) trigger_error('Database not connected');
	$key1 = $fsdb->escape($key);
	$value1 = $fsdb->escape($value);
	$user_id = $fsdb->escape($user_id);
	$options_table = fs_options_table();
	$sql = "REPLACE INTO `$options_table` (`user_id`,`option_key`,`option_value`) VALUES ($user_id,$key1,$value1)";
	$res = $fsdb->query($sql) !== false;
	if ($res) $options_cache[$key] = $value;
	return $res;
}

function fs_get_local_options_list()
{
	static $fs_local_options_list;
	if (!isset($fs_local_options_list))
	{
		// a list of local keys that we are allowed to save into the hosting system.
		// this is the last line of defense againt hack attempts trying to save crap to the hosting platform.
		$fs_local_options_list = array();
		$a = &$fs_local_options_list;
		$a[] = 'firestats_site_id';
		$a[] = 'firestats_excluded_users';
		$a[] = 'firestats_add_comment_flag';
		$a[] = 'firestats_add_comment_browser_os';
		$a[] = 'firestats_sites_filter';
		$a[] = 'firestats_show_footer';
		$a[] = 'firestats_show_footer_stats';
		$a[] = 'firestats_min_view_security_level';
		/*
		$a[] = '';
		$a[] = '';
		*/
	}
	return $fs_local_options_list;
}

// if we are in the context of site (like when viewing from within wordpress) 
// save the value in the storage system of that site
// else use firestats options storage.
function fs_update_local_option($key, $value)
{
	if(function_exists('fs_update_local_option_impl'))
	{
		// only administrators may change local options.
		// local options are site wide, but on the level of the site that implements
		// the fs_update_local_option_impl function.
	
		if (fs_in_wordpress() && fs_is_wpmu())
		{
			// even non admin user is allowed to save those options in a wpmu blog.
			$allowed = array(
				'firestats_show_footer',
				'firestats_show_footer_stats',
				'firestats_add_comment_flag',
				'firestats_add_comment_browser_os',
				'firestats_sites_filter'
			);
		}
		else
		{
			$allowed = array();
		}
	
		if (!fs_is_admin() && !in_array($key,$allowed))
		{
			echo "Access denied : fs_update_local_option($key)";
			return;
		}		
		
		$fs_local_options_list = fs_get_local_options_list();
		if (!in_array($key, $fs_local_options_list))
		{
			echo "fs_update_local_option: $key is not an authorized local option<br/>";
			return;
		}		
		
		fs_update_local_option_impl($key,$value);
	}
	else
	{
		fs_update_option($key,$value);
	}
}

// if we are in the context of site (like when viewing from within wordpress) 
// try to get the value from the storage system of that site
// if its not there, try the firestats options storage, and if its not there, return the default.
function fs_get_local_option($key, $default=null)
{
	$fs_local_options_list = fs_get_local_options_list();
	if (!in_array($key, $fs_local_options_list))
	{
		echo "Not allowed to access local option : $key<br/>";
		return $default;
	}

	if(function_exists('fs_get_local_option_impl'))
	{
		$value = fs_get_local_option_impl($key);
		if (empty($value)) 
			return fs_get_option($key,$default);
		else 
			return $value;
	}
	else
	{
		return fs_get_option($key,$default);
	}
}

function fs_get_tables_list()
{
	$a = array(
		fs_version_table(),
		fs_hits_table(),
		fs_useragents_table(),
		fs_urls_table(),
		fs_excluded_ips_table(),
		fs_bots_table(),
		fs_options_table(),
		fs_sites_table(),
		fs_archive_ranges(),
		fs_archive_sites(),
		fs_archive_pages(),
		fs_archive_referrers(),
		fs_archive_useragents(),
		fs_archive_countries(),
		fs_users_table(),
		fs_pending_date_table(),
		fs_url_metadata_table(),
		fs_excluded_urls_table(),
		fs_user_sites_table(),
		fs_rss_subscribers_table(),
	);
	return $a;
}
function fs_version_table()
{
	return fs_table_prefix().'firestats_version';
}

function fs_hits_table()
{
	return fs_table_prefix().'firestats_hits';
}

function fs_useragents_table()
{
	return fs_table_prefix().'firestats_useragents';
}

function fs_urls_table()
{
	return fs_table_prefix().'firestats_urls';
}

function fs_excluded_ips_table()
{
	return fs_table_prefix().'firestats_excluded_ips';
}

function fs_bots_table()
{
	return fs_table_prefix().'firestats_useragent_classes';
}

function fs_temp_table()
{
	return fs_table_prefix().'firestats_temp';
}

function fs_options_table()
{
	return fs_table_prefix().'firestats_options';
}

function fs_sites_table()
{
	return fs_table_prefix().'firestats_sites';
}

function fs_archive_ranges()
{
	return fs_table_prefix().'firestats_archive_ranges';
}

function fs_archive_sites()
{
	return fs_table_prefix().'firestats_archive_sites';
}

function fs_archive_pages()
{
	return fs_table_prefix().'firestats_archive_pages';
}

function fs_archive_referrers()
{
	return fs_table_prefix().'firestats_archive_referrers';
}

function fs_archive_useragents()
{
	return fs_table_prefix().'firestats_archive_useragents';
}

function fs_archive_countries()
{
	return fs_table_prefix().'firestats_archive_countries';
}

function fs_users_table()
{
	return fs_table_prefix().'firestats_users';
}

function fs_pending_date_table()
{
	return fs_table_prefix().'firestats_pending_data';
}

function fs_url_metadata_table()
{
	return fs_table_prefix().'firestats_url_metadata';
}

function fs_excluded_urls_table()
{
	return fs_table_prefix().'firestats_excluded_urls';
}

function fs_user_sites_table()
{
	return fs_table_prefix().'firestats_user_sites';
}

function fs_rss_subscribers_table()
{
	return fs_table_prefix().'firestats_rss_subscribers';
}

function fs_table_prefix()
{
	global $fs_config;
	return $fs_config['DB_PREFIX'];
}

/*
 * Option getters 
 */
function fs_get_max_referers_num()
{
    return fs_get_option('firestats_num_max_recent_referers', 10);
}

function fs_get_recent_referers_days_ago()
{
    return fs_get_option('firestats_recent_referers_days_ago', 30);
}

function fs_get_max_popular_num()
{
    return fs_get_option('firestats_num_max_recent_popular', 10);
}

function fs_get_recent_popular_pages_days_ago()
{
    return fs_get_option('firestats_recent_popular_pages_days_ago', 30);
}

function fs_get_num_hits_in_table()
{
    return fs_get_option('firestats_num_entries_to_show',50);
}

function fs_countries_list_days_ago()
{
    return fs_get_option('firestats_countries_list_days_ago', 30);
}

function fs_get_max_countries_num()
{
    return fs_get_option('firestats_max_countries_in_list', 5);
}

function fs_os_tree_days_ago()
{
    return fs_get_option('firestats_os_tree_days_ago', 30);
}

function fs_browsers_tree_days_ago()
{
    return fs_get_option('firestats_browsers_tree_days_ago', 30);
}

function fs_get_auto_ip2c_ver_check()
{
	return fs_get_system_option('ip-to-country-db_version_check_enabled','true');
}

function fs_get_version_check_enabled()
{
	return fs_get_system_option('firestats_version_check_enabled','true');
}

function fs_get_auto_bots_list_update()
{
	return fs_get_option('auto_bots_list_update','true');
}

function fs_get_archive_older_than() 
{
	return fs_get_system_option('archive_older_than', 90);
}

function fs_get_max_search_terms()
{
    return fs_get_option('num_max_search_terms', 10);
}

function fs_db_error($rollback = false)
{
	$fsdb = &fs_get_db_conn();
	$last_error = $fsdb->last_error;
	$last_query = $fsdb->last_query;
	$mysql_version = fs_mysql_version();
	$msg = sprintf(fs_r('Database error: %s'), $last_error).'<br/><br/>'. sprintf('MySQL Version: %s<br/>SQL Query:<br/>%s', $mysql_version, $last_query);
	if ($rollback)
	{
		$fsdb->query("ROLLBACK");
	}
	return $msg;
}


function fs_is_valid_regexp($regexp)
{
	if (empty($regexp)) return true;
	$fsdb = &fs_get_db_conn();
	$regexp = $fsdb->escape($regexp);
	$r = $fsdb->get_var("SELECT '' REGEXP $regexp");
	return $r !== false ? true : sprintf(fs_r("Invalid regular expression, see %s for more information"), fs_link("http://dev.mysql.com/doc/refman/5.0/en/regexp.html", fs_r("this"), true, '_blank'));  
}


function fs_timestamp_between($is_unix_time, $start_time, $end_time)
{
	$sql = '';
	if ($start_time)
	{
		$ts = "'$start_time'";
		if ($is_unix_time)
		{
			$ts = "FROM_UNIXTIME($start_time)";
		}
		$sql .= "`timestamp` >= $ts";
		
		if ($end_time)
		{
			$ts = "'$end_time'";
			if ($is_unix_time)
			{
				$ts = "FROM_UNIXTIME($end_time)";
			}
			if ($sql != "") $sql .= " AND ";
			$sql .= "`timestamp` < $ts";
		}
	}
	return $sql != "" ? $sql : "1";
}

function fs_time_range_between($is_unix_time, $start_time, $end_time)
{
	$sql = '';
	if ($start_time)
	{
		$ts = "'$start_time'";
		if ($is_unix_time)
		{
			$ts = "SUBSTRING(FROM_UNIXTIME($start_time),1,10)";
		}
		$sql .= "`range_start` >= $ts";
		if ($end_time)
		{
			$ts = "'$end_time'";
			if ($is_unix_time)
			{
				$ts = "SUBSTRING(FROM_UNIXTIME($end_time),1,10)";
			}
			if ($sql != "") $sql .= " AND ";
			$sql .= "`range_end` <= $ts";
		}
	}
	return $sql != "" ? $sql : "1";
}

/**
 * Converts a number of 'days ago' to a unix timestamp
 * $round_to_midnight : if true, the timestamp will be rounded to midnight (for example, to give hits since midnight)
 * $now : now timestamp, used for unit testing. default is for actual time() return value.
 * $user_timezone : used for unit testing, the timezone the user is in. (by default, the firestats_user_timezone options is used.
 * $system_timezone : used for unit testing. the timezone the server is in. (by default, uses the mysql literal 'system' timezone.
 */
function fs_days_ago_to_unix_time($days_ago, $round_to_midnight = false, $now = null, $user_timezone = null, $server_timeszone = 'system')
{
	if ($days_ago === null) return null;
	if ($now == null) $now = time();
	if ($round_to_midnight)
	{
		$user_now = $now;
		$db_support_tz = (ver_comp("4.1.3",fs_mysql_version()) <= 0);
		if ($db_support_tz)
		{
			//echo(date('j M o H:i', $now)."\n");
			$fsdb = &fs_get_db_conn();
			$timezone = $user_timezone == null ? fs_get_option('firestats_user_timezone','system') : $user_timezone;
			$res = $fsdb->get_var("SELECT UNIX_TIMESTAMP(CONVERT_TZ(FROM_UNIXTIME($now),'$server_timeszone','$timezone'))");
			if ($res === false) 
			{
				return null;
			}
			$user_now = (int)$res;
			//echo(date('j M o H:i', $user_now)."\n");
		}
		$d = sscanf(date('H i s', $user_now), "%d %d %d");
		$offset = $d[0] * 60 * 60 + $d[1] * 60 + $d[2];
		$days_ago--; // the rounding 'eats' the first day.
		return $now - $days_ago * 24 * 60 * 60 - $offset;
	}
	else
	{
		return $now - $days_ago * 24 * 60 * 60;
	}
}

function fs_get_date_format()
{
	return fs_get_option("date_format","%d/%m/%Y");
}

function fs_get_time_format()
{
	return fs_get_option("time_format","%H:%i:%s");
}

function fs_get_datetime_format()
{
	return fs_get_date_format() . " " . fs_get_time_format();
}

function fs_date_to_unixtime($timestr, $format)
{
	$day = 0;
	$mon = 0;
	$year = 0;
	$hour = 0;
	$minute = 0;
	$second = 0;		
	if ($format == "%d/%m/%Y")
	{
		$d = sscanf($timestr,"%d/%d/%d");
		$day = $d[0];
		$mon = $d[1];
		$year = $d[2];		
	}
	else
	if ($format == "%m/%d/%Y")
	{
		$d = sscanf($timestr,"%d/%d/%d");
		$mon = $d[0];
		$day = $d[1];
		$year = $d[2];		
	}
	else
		return false;
		
	return mktime($hour, $minute,$second,$mon,$day,$year);
}
?>
