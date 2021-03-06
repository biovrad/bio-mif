<?PHP
define('FS_AJAX_HANDLER',true);

require_once(dirname(__FILE__).'/init.php');

if (isset($_POST['action']))
{
	ob_start(); // capture output. if there is output it means there is an error.

	require_once(dirname(__FILE__).'/db-config-utils.php');
	require_once(dirname(__FILE__).'/db-common.php');
	require_once(dirname(__FILE__).'/db-setup.php');
	require_once(dirname(__FILE__).'/auth.php');
	require_once(dirname(__FILE__).'/html-utils.php');

	global $FS_SESSION_ERROR;
	$action = $_POST['action'];
	$response['status']='error';
	$allowed = true;
	
	if ($action != 'login')
	{
		if ($FS_SESSION_ERROR !== false)
		{
			$response['message'] = sprintf("Error: %s, Click %s to reload", $FS_SESSION_ERROR, fs_link("javascript:window.location.reload()","this", true));
			$allowed = false;
		}
		else
		if (!fs_authenticated())
		{
			$response['message'] = 'Session not authenticated';
			$allowed = false;
		}
	}
			
	if ($allowed)
	{
		$response['action'] = $action;
		$response['status']='ok';

		switch ($action)
		{
			case 'login':
				fs_ajax_login($response);
				break;
			case 'logout':
				fs_ajax_logout($response);
				break;
			case 'saveOption':
				fs_ajax_saveOption($response);
				break;
			case 'saveOptions':
				fs_ajax_saveOptions($response);
				break;
			case 'getAllStats';
				fs_ajax_get_all_stats($response);
			break;
			case 'addOrEditExcludedIP':
				fs_ajax_addOrEditExcludedIP($response);
				break;
			case 'removeExcludedIP':
				fs_ajax_removeExcludedIP($response);
				break;
			case 'updateExcludedUser':
				fs_ajax_updateExcludedUser($response);
				break;
			case 'addBot':
				fs_ajax_addBot($response);
				break;
			case 'removeBot':
				fs_ajax_removeBot($response);
				break;
			case 'testDBConnection':
				fs_ajax_test_db_connection($response);
				break;
			case 'attachToDatabase':
				fs_ajax_attach_to_database($response);
				break;
			case 'useWordpressDB':
				fs_ajax_useWordpressDB($response);
				break;
			case 'installDBTables':
				fs_ajax_install_db_tables($response);
				break;
			case 'createNewDatabase':
				fs_ajax_create_new_database($response);
				break;
			case 'upgradeDatabase':
				fs_ajax_upgrade_database($response);
				break;
			case 'changeLanguage':
				fs_ajax_change_language($response);
				break;
			case 'updateIP2CountryDB':
				fs_ajax_update_ip_to_country($response);
				break;
			case 'purgeExcludedHits':
				fs_ajax_purge_excluded_hits($response);
				break;
			case 'updateFields':
				fs_ajax_send_update($response);
				break;
			case 'updateBotsList':
				fs_ajax_update_bots_list($response);
				break;
			case 'updateSitesFilter':
				fs_ajax_update_sites_filter($response);
				break;
			case 'updateSiteInfo':
				fs_ajax_update_sites_info($response);
				break;
			case 'createNewSite':
				fs_ajax_create_new_site($response);
				break;
			case 'deleteSite':
				fs_ajax_delete_site($response);
				break;
			case 'archiveOldData':
				fs_ajax_archiveOldData($response);
				break;
			case 'searchTermsBreakdown':
				fs_ajax_searchterms_breakdown($response);
				break;
			case 'incrementalProcess':
				fs_ajax_incremental_process($response);
				break;
			case 'saveSentSysInfo':
				fs_ajax_saveSentSysInfo($response);
				break;
			case 'getNextUserMessage':
				fs_ajax_getNextUserMessage($response);
				break;
			case 'getWindow':
				fs_ajax_get_window($response);
				break;
			case 'createUser':
				fs_ajax_create_user($response);
				break;
			case 'deleteUser':
				fs_ajax_delete_user($response);
				break;
			case 'updateUser':
				fs_ajax_update_user($response);
				break;
			case 'changePassword':
				fs_ajax_change_password($response);
				break;
			case 'update_wordpress_titles':
				fs_ajax_update_wordpress_titles($response);
				break;
			case 'handle_pending_maintanence':
				fs_ajax_handle_pending_maintanence($response);
				break;
			case 'addExcludedUrl':
				fs_ajax_handle_add_excluded_url($response);
				break;
			case 'editExcludedUrl':
				fs_ajax_handle_edit_excluded_url($response);
				break;
			case 'removeExcludedUrl':
				fs_ajax_handle_remove_excluded_url($response);
				break;
			case 'setSitesTablePageNumber':
				fs_ajax_set_sites_table_page_number($response);
				break;
			case 'changeHitsTablePage':
				fs_ajax_change_hits_table_page($response);
				break;
			default:
				ajax_error($response,'AJAX: '.sprintf('Unsupported action code : %s',$action));
		}
	}

	$output = ob_get_clean();
	if ($output != '')
	{
		$response['status']='error';
		if (empty($response['message'])) $response['message'] = '';
		$response['message'] = '<br/><br/>'.sprintf(fs_r('Unexpected output: %s'),$output);
	}
	
	echo fs_json_encode($response);
}
else
{
	$response['status']='error';
	$response['message'] = 'Action not specified';
	echo fs_json_encode($response);
}


function fs_ajax_update_wordpress_titles(&$response)
{
	$res = fs_update_post_titles();
	if ($res !== true)
	{
		ajax_error($response, "Error : " .$res);
	}
}

function fs_ajax_addOrEditExcludedIP(&$response)
{
	$sip = $_POST['start_ip'];
	$eip = isset($_POST['end_ip']) ? $_POST['end_ip'] : null;
	
	if (isset($_POST['edit_id']))
	{
		$res = fs_edit_excluded_ip($sip, $eip, $_POST['edit_id']);
	}
	else
	{
		$res = fs_add_excluded_ip($sip, $eip);
	}
	
	if ($res == '')
	{
		$response['fields']['exclude_ip_placeholder'] = addslashes(fs_get_excluded_ips_list());
		$response['fields']['num_excluded'] = fs_get_num_excluded();
	}
	else
	{
		ajax_error($response, $res);
	}
}

function fs_ajax_removeExcludedIP(&$response)
{
	$res = fs_remove_excluded_ips($_POST['ids']);
	if ($res == '')
	{
		$response['fields']['exclude_ip_placeholder'] = addslashes(fs_get_excluded_ips_list());
		$response['fields']['num_excluded'] = fs_get_num_excluded();
	}
	else
	{
		ajax_error($response, $res);
	}
}

function fs_ajax_saveOptions(&$response)
{
	if (!fs_check_database($response)) return;
	$dest = $_POST['dest'];
	$list = $_POST['list'];
	$pairs = explode(";",$list);
	foreach($pairs as $pair)
	{
		$pp = explode(",",$pair);
		if (count($pp) > 1)
		{
			$key = rawurldecode($pp[0]);
			$value = rawurldecode($pp[1]);
			$type = null; 
			if (count($pp) > 2)
			{
				$type = rawurldecode($pp[2]);
			}
			fs_ajax_saveSingleOption($response, $key,$value,$dest,$type);
		}
	}
	fs_ajax_send_update($response);
}

function fs_ajax_saveOption(&$response)
{
	if (!fs_check_database($response)) return;
	$key = $_POST['key'];
	$value = $_POST['value'];
	$dest = $_POST['dest'];
	$type = $_POST['type'];
	fs_ajax_saveSingleOption($response, $key, $value, $dest, $type);
	fs_ajax_send_update($response);
}

function fs_ajax_saveSingleOption(&$response, $key, $value, $dest, $type = null)
{
	if ($type != null)
	{
		switch($type)
		{
			case 'boolean':
				$value = $value == "true" || $value == "on" ? "true" : "false";
			break;
			case 'integer':
				if (!is_numeric($value)) return ajax_error($response, sprintf(fs_r("Invalid integer : %s"),$value));
			break;
			case 'positive_integer':
				if (!is_numeric($value) || (int)$value <= 0) return ajax_error($response, sprintf(fs_r("Invalid or none positive integer : %s"),$value));
			break;
			case 'regexp':
				$res = fs_is_valid_regexp($value);
				if ($res !== true) return ajax_error($response, $res);
			break;
			case 'ip_address':
				$res = fs_ip2hex($value) === false ? fs_r("Invalid IP address") : true;
				if ($res !== true) return ajax_error($response, $res);
			break;
			case 'string':
				// nothing to validate
			break;
			default:
				return ajax_error($response,"Unknown type $type");
		}
	}
	switch($dest)
	{
		case 'firestats':
			fs_update_option($key, $value);
			break;
		case 'local':
			if (fs_check_is_demo($response)) return;
			fs_update_local_option($key, $value);
			break;
		case 'system':
			fs_update_system_option($key, $value);
			break;
		case 'session':
			fs_update_option($key, $value, true);
			break;
		default:
			return ajax_error($response,"Unknown dest id $dest");
	}
}

function fs_ajax_get_all_stats(&$response)
{
	if (!fs_check_database($response)) return;
	$response['fields']['fs_browsers_tree']	= addslashes(fs_get_browsers_tree());
	$response['fields']['fs_os_tree'] 		= addslashes(fs_get_os_tree());
	$response['fields']['fs_recent_referers'] = addslashes(fs_get_recent_referers_table());
	$response['fields']['fs_search_terms'] = addslashes(fs_get_search_terms_tree());
	$response['type']['fs_browsers_tree']= 'tree';
	$response['type']['fs_os_tree']= 'tree';
	$response['type']['fs_search_terms']= 'tree';
	$response['fields']['stats_total_count'] = fs_get_hit_count();
	$response['fields']['stats_total_unique'] = fs_get_unique_hit_count();
	$response['fields']['stats_total_count_last_day'] = fs_get_last_day_hits();
	$response['fields']['stats_total_unique_last_day'] = fs_get_last_day_visits();
	$response['fields']['records_table'] = fs_get_records_table();
	$response['fields']['hits_table_page_number_indicator'] = fs_get_hits_table_page_number_indicator();
	$response['fields']['popular_pages'] = fs_get_popular_pages_tree();
	$response['fields']['countries_list'] = fs_get_countries_list();
	
	fs_do_action("ajax_get_all_stats",array(&$response));
}

function fs_ajax_updateExcludedUser(&$response)
{
	$user_id = $_POST['user_id'];
	$selected = $_POST['selected'] === 'true';
	$list = fs_get_local_option('firestats_excluded_users');
	if (empty($list))
	{
		$excluded_users = array();
	}
	else
	{
		$excluded_users=explode(",",$list);
	}

	if($selected)
	{
		if (!in_array($user_id,$excluded_users))
		{
			$excluded_users[] = $user_id;
		}
	}
	else
	{
		fs_array_remove($excluded_users,$user_id);
	}
	$list = implode(",",$excluded_users);
	$res = fs_save_excluded_users($list);
	if ($res == '')
	{
		$response['fields']['exclude_users_placeholder'] = addslashes(fs_get_excluded_users_list());
		$response['message'] = fs_r('Excluded users list saved');
	}
	else
	{
		ajax_error($response, $res);
	}

}

function fs_ajax_addBot(&$response)
{
	$wildcard = $_POST['wildcard'];
	if ($wildcard != '')
	{
		$res = fs_add_bot($wildcard);
		if ($res == '')
		{
			$response['message'] = sprintf(fs_r('Added %s to bots list'),$wildcard);
			$response['fields']['botlist_placeholder'] = addslashes(fs_get_bot_list());
			$response['fields']['num_excluded'] = fs_get_num_excluded();
		}
		else
		{
			ajax_error($response, $res);
		}
	}
	else
	{
		ajax_error($response, fs_r('Empty string is not allowed'));
	}
}

function fs_ajax_removeBot(&$response)
{
	$bot_ids = $_POST['bot_ids'];
	$res = fs_remove_bots($bot_ids);
	if ($res == '')
	{
		$response['message'] = sprintf(fs_r('Removed'));
		$response['fields']['botlist_placeholder'] = addslashes(fs_get_bot_list());
		$response['fields']['num_excluded'] = fs_get_num_excluded();
	}
	else
	{
		ajax_error($response, $res);
	}

}

function fs_ajax_test_db_connection(&$response)
{
	$host 	= $_POST['host'];
	$user 	= $_POST['user'];
	$pass 	= $_POST['pass'];
	$dbname	= $_POST['dbname'];
	$table_prefix = $_POST['table_prefix'];

	$res = fs_test_db_connection($host, $user, $pass,$dbname,$table_prefix);
	$status = $res['status'];
	$response['db_status'] = $status;
	$response['styles']['advanced_feedback']['color'] = $res['color'];
	$response['fields']['advanced_feedback'] = $res['message'];
	$response['fields']['new_db_feedback'] = '';

	$response['styles']['install_tables_id']['display'] = 'none';
	$response['styles']['use_database_id']['display'] = 'none';
	$response['styles']['create_db_id']['display'] = 'none';

	switch ($status)
	{
		case 'other_db_detected':
			$response['styles']['use_database_id']['display'] = 'block';
			break;
		case 'tables_missing':
			$response['styles']['install_tables_id']['display'] = 'block';
			break;
		case'database_missing':
			$response['styles']['create_db_id']['display'] = 'block';
			break;
	}
}


function fs_ajax_useWordpressDB(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;

	if (fs_get_db_config_type() != FS_DB_CONFIG_FILE)
	{
		$response['status']='error';
		$response['message'] = fs_r('Not using configuration file');
		return;
	}

	if (!fs_in_wordpress())
	{
		ajax_error($response, fs_r('Not installed inside Wordpress'));
		return;
	}

	ob_start();
	$res = unlink(FS_ABS_PATH.'/php/fs-config.php');
	$output = ob_get_clean();

	if (!$res)
	{
		ajax_error($response, sprintf(fs_r('Failed to delete fs-config.php : %s'), $output));
	}
	else
	{
		$response['db_status'] = 'ok';
		fs_sendDBConfig($response);
		$response['styles']['switch_to_external_system']['display'] = 'none';
	}
}

function fs_ajax_attach_to_database(&$response)
{
	$host 	= $_POST['host'];
	$user 	= $_POST['user'];
	$pass 	= $_POST['pass'];
	$dbname	= $_POST['dbname'];
	$table_prefix = $_POST['table_prefix'];
	$res = fs_save_config_file($host,$user,$pass,$dbname,$table_prefix);
	if ($res != '')
	{
		ajax_error($response, $res);
		return false;
	}
	else
	{
		$response['db_status'] = 'ok';
		fs_sendDBConfig($response);
		if(fs_should_show_use_wp_button())
		{
			$response['styles']['switch_to_external_system']['display'] = 'block';
		}
		return true;
	}
}

function fs_ajax_upgrade_database(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;

	$fsdb = &fs_get_db_conn();
	$res = fs_install_impl($fsdb, true, $response);
	if ($res !== true)
	{
		ajax_error($response, fs_r('Error upgrading tables').': '.$res);
	}
}


function fs_ajax_install_db_tables(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;

	if (!fs_ajax_attach_to_database($response))
	{
		return;
	}

	# force databae connection to be re-initialized
	fs_get_db_conn(true);

	$res = fs_install();
	if ($res !== true)
	{
		ajax_error($response, fs_r('Error installing tables').': '.$res);
	}
	else
	{
		$response['db_status'] = 'ok';
		fs_sendDBConfig($response);
	}
}

function fs_ajax_create_new_database(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;

	$host 	= $_POST['host'];
	$admin_user = $_POST['admin_user'];
	$admin_pass	= $_POST['admin_pass'];
	$user 	= $_POST['user'];
	$pass 	= $_POST['pass'];
	$dbname	= $_POST['dbname'];
	$table_prefix = $_POST['table_prefix'];
	$res = fs_create_new_database($host, $admin_user, $admin_pass, $user, $pass, $dbname, $table_prefix);
	$status = $res['status'];

	$response['db_status'] = $status;
	$response['fields']['new_db_feedback'] = $res['message'];
	$response['styles']['new_db_feedback']['color'] = '';
	switch ($status)
	{
		case 'ok':
			$response['styles']['new_db_feedback']['color'] = 'blue';
			break;
		case 'error':
			$response['styles']['new_db_feedback']['color'] = 'red';
			break;
		default:
			$response['fields']['new_db_feedback'] = "Unexpected status: ".$status;
			$response['styles']['new_db_feedback']['color'] = 'red';
	}

}

function fs_sendDBConfig(&$response)
{
	fs_load_config();
	global $fs_config;
	$response['fields']['config_source'] = fs_get_config_source_desc();
	$response['fields']['text_database_host'] = $fs_config['DB_HOST'];
	$response['fields']['text_database_name'] = $fs_config['DB_NAME'];
	$response['fields']['text_database_user'] = $fs_config['DB_USER'];
	$response['fields']['text_database_pass'] = ''; // don't send password, its too risky.
	$response['fields']['text_database_prefix'] = $fs_config['DB_PREFIX'];
	// clear the fields
	$response['fields']['advanced_feedback'] = '';
	$response['fields']['new_db_feedback'] = '';
	// hide the buttons
	$response['styles']['install_tables_id']['display'] = 'none';
	$response['styles']['use_database_id']['display'] = 'none';
	$response['styles']['create_db_id']['display'] = 'none';
}

function fs_ajax_change_language(&$response)
{
	if (fs_check_is_demo($response)) return;
	$language = $_POST['language'];
	$current = fs_get_option('current_language');
	if ($current != $language)
	{
		fs_update_option('current_language', $language);
		$response['refresh'] = 'true';
	}
}

function fs_ajax_send_update(&$response)
{
	if (!isset($_POST['update'])) return;
	
	// commit pending hits if in the correct mode.
	if (FS_AUTOMATIC_COMMIT_WHEN_USER_ACCESS_STATISTICS && 
		fs_get_commit_strategy() == FS_COMMIT_AUTOMATIC)
	{
		require(FS_ABS_PATH."/php/commit-pending.php");		
	}
	
	

	$update_blocks = explode(';',$_POST['update']);
	// if we have no more blocks return.
	if (count($update_blocks) == 0) return;

	// pop the first block.
	$update = array_shift($update_blocks);

	if (count($update_blocks) > 0)
	{
		// push the remaining items to the response, so the client will be able to send antoher request with the rest.
		$response['send_request'] = "action=updateFields&update=".implode(";", $update_blocks);
	}

	$updates = explode(',',$update);
	foreach($updates as $update)
	{
		$handled = fs_do_action("ajax_send_update", array($update, &$response));
		switch ($update)
		{
			case 'popular_pages':
				$response['fields'][$update] = addslashes(fs_get_popular_pages_tree());
				break;
			case 'records_table':
				$response['fields'][$update] = fs_get_records_table();
				$response['fields']['hits_table_page_number_indicator'] = fs_get_hits_table_page_number_indicator();
				break;
			case 'countries_list':
				$response['fields'][$update] = fs_get_countries_list();
				break;
			case 'fs_recent_referers':
				$response['fields'][$update] = fs_get_recent_referers_table();
				break;
			case 'fs_browsers_tree':
				$response['fields'][$update] = fs_get_browsers_tree();
				$response['type'][$update]= 'tree';
				break;
			case 'fs_os_tree':
				$response['fields'][$update] = fs_get_os_tree();
				$response['type'][$update]= 'tree';
				break;
			case 'fs_search_terms':
				$response['fields'][$update] = fs_get_search_terms_tree();
				$response['type'][$update]= 'tree';
				break;
			case 'botlist_placeholder':
				$response['fields']['botlist_placeholder'] = addslashes(fs_get_bot_list());
				break;
			case 'num_excluded':
				$response['fields']['num_excluded'] = fs_get_num_excluded();
				break;
			case 'stats_total_count':
				$response['fields']['stats_total_count'] = fs_get_hit_count();
				break;
			case 'stats_total_unique':
				$response['fields']['stats_total_unique'] = fs_get_unique_hit_count();
				break;
			case 'stats_total_count_last_day':
				$response['fields']['stats_total_count_last_day'] = fs_get_last_day_hits();
				break;
			case 'stats_total_unique_last_day':
				$response['fields']['stats_total_unique_last_day'] = fs_get_last_day_visits();
				break;
			case 'fs_sites_table':
				$response['fields']['fs_sites_table'] = fs_get_sites_manage_table();
				break;
			case 'fs_users_table':
				$response['fields']['fs_users_table'] = fs_get_users_manage_table();
				break;
			case 'sites_filter_span':
				$response['fields']['sites_filter_span'] = fs_get_sites_list();
				break;
			case 'fs_archive_status':
				$response['fields']['fs_archive_status'] = sprintf(fs_r("%s days can be compacted, database size %s"),fs_get_num_old_days(), sprintf("%.1f MB",fs_get_database_size()/(1024*1024)));
				break;
			case 'last_day_page_views_label':
				$response['fields']['last_day_page_views_label'] = fs_get_last_day_page_views_label();
				break;				
			case 'last_day_visits_label':
				$response['fields']['last_day_visits_label'] = fs_get_last_day_visits_label();
				break;
			case 'new_firestats_version_notification':
				require_once(dirname(__FILE__).'/version-check.php');
				$response['fields']['new_firestats_version_notification'] = fs_get_latest_firestats_version_message();
				$response['styles']['new_firestats_version_notification']['display'] = "block";
				break;
			case 'new_ip2c_db_notification':
				require_once(dirname(__FILE__).'/version-check.php');
				$response['fields']['new_ip2c_db_notification'] = fs_get_latest_ip2c_db_version_message();
				$response['styles']['new_ip2c_db_notification']['display'] = "block";
				break;
		}
	}
}

function fs_ajax_update_ip_to_country(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;

	require_once(dirname(__FILE__).'/version-check.php');
	$file_type = '';
	$url = '';
	$version = '';
	$info = null;
	$error = null;
	$need_update = fs_is_ip2country_db_need_update($url,$file_type, $version, $info, $error);
	if ($need_update)
	{
		require_once(dirname(__FILE__).'/ip2country.php');
		$res = fs_update_ip2country_db($url,$file_type, $version);
		$ok = $res['status'] == 'ok';
		if ($ok)
		{
			$response['status'] = 'ok';
			$response['message'] = $res['message'];
			$response['fields']['ip2c_database_version'] = fs_get_current_ip2c_db_version();
			$response['fields']['new_ip2c_db_notification'] = '';
		}
		else
		{
			ajax_error($response, $res['message']);
		}
	}
	else
	{
		$response['status'] = 'ok';
		$response['message'] = fs_r("IP-to-country database is already up-to-date");
	}

	if (!empty($error))
	{
		$response['status'] = 'error';
		$ip2c_dir = FS_ABS_PATH.'/lib/ip2c/';
		$response['message'] = fs_r('An error has occured while trying to update the IP-to-country database')."<br/>";
		if (isset($info['ip-to-country-db']['zip_url']))
		{
			$url = $info['ip-to-country-db']['zip_url'];
			$href = sprintf("<a href='$url'>%s</a>",fs_r('file'));
			$response['message'] .=
			sprintf(fs_r('You can update the database manually by downloading this %s and extracting it into %s'), $href,$ip2c_dir);
		}
		else
		{
			$url = FS_IP2COUNTRY_DB_VER_CHECK_URL;
			$href = sprintf("<a href='$url'>%s</a>",fs_r('this'));
			$response['message'] .=
			sprintf(fs_r('You can update the database manually by opening %s and downloading the <b>zip_url</b>, and extracting it into %s'), $href,$ip2c_dir);
		}
		$response['message'] .= '</b><br/><br/>'.fs_r('Error').': '.$error;
	}
}

function fs_ajax_update_bots_list(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;

	require_once(dirname(__FILE__).'/version-check.php');
	// don't use cached version
	$force_check = true;
	$user_initiated = true;
	if (isset($_POST['user_initiated']))
	{
		$user_initiated = $_POST['user_initiated'] == 'true';
		$force_check = $user_initiated;
	}
	$url = '';
	$md5 = '';
	$error = '';
	$updated = fs_is_botlist_updated($url, $md5, $error,$force_check);
	if (!empty($error))
	{
		$response['status'] = 'error';
		$response['message'] = sprintf(fs_r("Error updating bots list: %s"),$error);
	}
	else
	{
		// if user initiated the request update regardless of current status.
		if ($user_initiated || !$updated)
		{
			// don't replace exiting bots, just add new ones.
			$remove_existing = false;
			$res = fs_botlist_import_url($url, $remove_existing);
			if ($res == '')
			{
				if ($user_initiated) $response['message'] = fs_r("Successfully updated bots list");
				fs_update_system_option('botlist_version_hash',$md5);
				fs_ajax_send_update($response);
			}
			else
			{
				ajax_error($response, sprintf(fs_r("Error updating bots list: %s"),$res));
			}
		}
	}
}

function fs_ajax_purge_excluded_hits(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;
	$res = fs_purge_excluded_entries();
	if ($res === false)
	{
		ajax_error($response, sprintf(fs_r('Error purging excluded records: %s'),fs_db_error()));
	}
	else
	{
		$response['message'] = fs_r('Purged excluded records');
		$response['fields']['num_excluded'] = fs_get_num_excluded();
	}
}

function fs_ajax_update_sites_filter(&$response)
{
	$site_id = $_POST['sites_filter'];
	if ($site_id != 'all') // all means : all the sites this user is allowed to access, so in this context it's true by definition.
	{
		$res = fs_current_user_allowed_to_access_site($site_id);
		if (is_string($res)) return ajax_error($response, $res);
		if (!$res) 
		{
			return ajax_error($response, "User is not allowed to access site $site_id");
		}
	}
	fs_update_local_option('firestats_sites_filter',$site_id);
	fs_ajax_get_all_stats($response);
}

function fs_ajax_create_new_site(&$response)
{
	if (fs_check_is_demo($response)) return;
	$new_sid = $_POST['new_sid'];
	$name = $_POST['name'];
	$type = $_POST['type'];
	$baseline_views = $_POST['baseline_views'];
	$baseline_visitors = $_POST['baseline_visitors'];
	$res = fs_create_new_site($new_sid, $name, $type, $baseline_views, $baseline_visitors);
	if ($res === true)
	{
		fs_ajax_send_update($response);
	}
	else
	{
		ajax_error($response, $res);
	}
}

function fs_ajax_update_sites_info(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;
	$new_sid = $_POST['new_sid'];
	$orig_sid = $_POST['orig_sid'];
	$name = $_POST['name'];
	$type = $_POST['type'];
	$baseline_views = $_POST['baseline_views'];
	$baseline_visitors = $_POST['baseline_visitors'];
	$res = fs_update_site_params($new_sid,$orig_sid, $name,$type, $baseline_views, $baseline_visitors);
	if ($res === true)
	{
		fs_ajax_send_update($response);
	}
	else
	{
		ajax_error($response, $res);
	}
}

function fs_ajax_delete_site(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;
	$sid = $_POST['site_id'];
	$action = $_POST['action_code'];
	$new_sid = isset($_POST['new_sid']) ? $_POST['new_sid'] : null;
	$res = fs_delete_site($sid, $action, $new_sid);
	if ($res === true)
	{
		// if the deleted site was selected in the filter, update the filter
		$current_selected = fs_get_local_option('firestats_sites_filter');
		if ($current_selected == $sid)
		{
			// reset filter to 'all'.
			fs_update_local_option('firestats_sites_filter','all');
			fs_ajax_get_all_stats($response);
		}
		// and also send whatever the client requested.
		fs_ajax_send_update($response);
	}
	else
	{
		ajax_error($response, $res);
	}
}

function fs_check_database(&$response)
{
	$fsdb = &fs_get_db_conn();
	if (!$fsdb->is_connected())
	{
		ajax_error($response, fs_r('Error connecting to database'));
		return false;
	}
	return true;
}

function fs_ajax_assert_admin(&$response)
{
	if (!fs_is_admin())
	{
		$action = $_POST['action'];
		ajax_error($response, "Action \"$action\" requires admin priveleges"); /*not translated*/
		return false;
	}
	return true;
}

function fs_check_is_demo(&$response)
{
	if (fs_is_demo())
	{
		ajax_error($response, 'This operation is not permitted in demo mode'); /*not translated*/
		return true;
	}
	return false;
}

function fs_ajax_archiveOldData(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;
	$days_remains = fs_get_num_old_days();

	$new_archive_seesion = false;
	if (!isset($_POST['num_old_days']))
	{
		$new_archive_seesion = true;
		$num_old_days = $days_remains;
	}
	else
	{
		$num_old_days = $_POST['num_old_days'];
	}

	if (is_numeric($num_old_days))
	{
		$max_days_to_archive = $_POST['max_days_to_archive'];
		$response['num_old_days'] = $num_old_days;
		// quickly return a response to the client on the fist request

		$DAY = 60 * 60 * 24;
		$archive_older_than_days = fs_get_archive_older_than();
		$archive_older_than = time() - $archive_older_than_days * $DAY;

		if (!$new_archive_seesion)
		{
			$res = fs_archive_old_data($archive_older_than, $max_days_to_archive);
		}
		else
		{
			$res = 0;
		}

		if (is_numeric($res))
		{
			if ($res == 0 && !$new_archive_seesion)
			{
				$response['done'] = 'true';
			}
			else
			{
				$response['send_request'] = "action=archiveOldData&num_old_days=$num_old_days&max_days_to_archive=$max_days_to_archive";
			}
			$days_remains -= $res;
			$done = $num_old_days - $days_remains;
			if ($num_old_days > 0)
			{
				$p = $done / $num_old_days * 100;
			}
			else
			{
				$p = "100%";
			}
				
			$response['fields']['fs_archive_status'] = sprintf(fs_r("Compacting %s days, %s done, database size is %s"), $num_old_days, sprintf("%.1f%%",$p), sprintf("%.1f MB",fs_get_database_size()/(1024*1024)));
			$response['status'] = 'ok';
				
			fs_ajax_send_update($response);
		}
		else
		{
			ajax_error($response,"Error : $res");
		}
	}
	else
	{
		ajax_error($response,"Error : $num_old_days");
	}
}


function fs_ajax_searchterms_breakdown(&$response)
{
	if (!is_var_set($response, "id")) return;
	if (!is_var_set($response, "search_term")) return;
	$id = $_POST['id'];
	$search_term = $_POST['search_term'];
	$response['fields']["$id"] = fs_get_search_term_breakdown($id,$search_term);
}

function ajax_error(&$response, $msg)
{
	$response['status'] = 'error';
	$response['message'] = $msg;
}

function fs_ajax_incremental_process(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;
	if (!is_var_set($response, "type")) return;

	$type = $_POST['type'];

	if (isset($_POST['file']))
	{
		// whitelist the allowed included files
		$allowed_files = array
		(
			'php/upgrade/upgrade_13.php',
			'php/upgrade/upgrade_12.php',
			'php/rebuild-db.php',
			'php/searchengines.php',
			'php/ip2country.php',
		);
		$file = $_POST['file'];
		if (array_search($file, $allowed_files) === false)
		{
			return ajax_error($response, "$file is not allowed when executing $type");
		}
		else
		{
			require_once(FS_ABS_PATH."/$file");
		}
	}

	if (!isset($_POST['value']))
	{
		$start = time();
		$val = 0;
		$min = 0;
		$max = fs_calculate_process_max($type);
		if (!is_numeric($max))
		{
			ajax_error($response, $max);
			return;
		}
		$performed = -1;
	}
	else
	{
		$start = $_POST['start'];
		$val = $_POST['value'];
		$min = $_POST['min'];
		$max = $_POST['max'];
		$now = time();
		$performed = fs_execute_process_step($type, $val, $max);
		if (!is_numeric($performed))
		{
			ajax_error($response, $performed);
			return;
		}
		$val += $performed;
	}
	
	$response['start'] = $start;
	$response['type'] = $type;
	$response['min'] = $min;
	$response['value'] = $val;
	$response['max'] = $max;
	if ($max > 0)
	{
		$desc = fs_get_step_description($type, $val, $max);
		$p = ($val / ($max - $min)) * 100;
		$percentage = sprintf("(<b>%.1f%%</b>)",$p);
		$descText = ($desc != null ? "$desc" : sprintf(fs_r("%d of %d"),$val,$max)). " ".$percentage;
		$response['progress_text'] = $descText;
	}

	if ($val < $max && $performed != 0)
	{
		$response['done'] = 'false';
		$response['send_request'] = "action=incrementalProcess&type=$type&max=$max&min=$min&value=$val&start=$start";
		$response['send_request'] .= (isset($_POST['file']) ? "&file=$file" : "");
	}
	else
	if ($val == $max)
	{
		$res = fs_handle_process_done($type, $response);
	}
	else
	if ($performed == 0)
	{
		ajax_error($response, "No progress in $type");
	}
	else
	{
		ajax_error($response, "Internal error 101 , VAL=$val, MAX=$max PERFORMED=$performed");
	}
}

function is_var_set(&$response, $key)
{
	if (!isset($_POST[$key]))
	{
		if ($response != null)
		{
			ajax_error($response,"$key not specified");
		}
		return false;
	}
	return true;
}

function fs_ajax_saveSentSysInfo(&$response)
{
	if (!fs_ajax_assert_admin($response)) return;
	$si = fs_get_sysinfo();
	fs_update_system_option('last_sent_sysinfo',serialize($si));
}

function fs_ajax_get_window(&$response, $type = null)
{
	if (!isset($type)) $type = $_POST['type'];
	switch($type)
	{
		case 'ask_to_send_sysinfo':
			if (!fs_ajax_assert_admin($response)) return;
			$response['width'] = '600';
			$response['height'] = '370';
			$response['url'] = fs_url('php/window-do-you-agree-to-send-sysinfo.php');
			break;
		case 'ask_for_donation':
			$response['width'] = '400';
			$response['height'] = '370';
			$response['url'] = fs_url('php/window-donation.php');
			break;
		case 'notify_about_archive':
			if (!fs_ajax_assert_admin($response)) return;
			$response['width'] = '600';
			$response['height'] = '400';
			$response['url'] = fs_url('php/window-archive-notification.php');
			break;
		default:
			return ajax_error($response, "Unknown window type '$type'");
	}

	$response['new_floating_window'] = 'true';
	if (!isset($response['top'])) $response['top'] = 'center';
	if (!isset($response['left'])) $response['left'] = 'center';
	if (!isset($response['width'])) $response['width'] = '400';
	if (!isset($response['height'])) $response['height'] = '300';
}

function fs_ajax_getNextUserMessage(&$response)
{
	require_once(FS_ABS_PATH.'/php/html-utils.php');
	require_once(FS_ABS_PATH.'/php/utils.php');

	
	// currently we don't show any user messages in demo mode.
	if (fs_is_demo()) return;

	if (fs_is_admin() && fs_get_system_option("user_agreed_to_send_system_information", '') == '')
	{
		fs_ajax_get_window($response,'ask_to_send_sysinfo');
	}
	else
	if (fs_time_to_nag())
	{
		fs_ajax_get_window($response,'ask_for_donation');
	}
	else
	if (fs_is_admin() && fs_mysql_newer_than("4.1.13") && fs_get_system_option('archive_method') == null && fs_get_num_old_days() > 0)
	{
		// if the user never selected archive method
		fs_ajax_get_window($response,'notify_about_archive');
	}
}

function fs_ajax_login(&$response)
{
	$username =  isset($_POST['username']) ? $_POST['username'] : '';
	$password =  isset($_POST['password']) ? $_POST['password'] : '';
	$cookie_pass_md5 = isset($_COOKIE['FS_LAST_PASSWORD_MD5']) ? $_COOKIE['FS_LAST_PASSWORD_MD5'] : '';
	$remember_me = $_POST['remember_me'];
	require_once(FS_ABS_PATH.'/php/auth.php');
	if ($cookie_pass_md5 !== $password) // if the cookie pass is the same as the form pass, its already md5, otherwise we convert it to md5
	{
		$password = md5($password);
	}

	$res = fs_login($username, $password, true);

	if (is_string($res))
	{
		return ajax_error($response, $res);
	}
	else
	if ($res)
	{
		global $FS_SESSION;
		fs_create_cookie($response, 'FS_SESSION_ID', $FS_SESSION['sid'], 0);
		if ($remember_me == 'on')
		{
			fs_create_cookie($response, 'FS_LAST_USERNAME', $username, 14);
			fs_create_cookie($response, 'FS_LAST_PASSWORD_MD5', $password, 14);
			fs_create_cookie($response, 'FS_REMEMBER_ME', 'on', 14);
		}
		else
		{
			// delete cookies
			fs_delete_cookie($response, 'FS_LAST_USERNAME');
			fs_delete_cookie($response, 'FS_LAST_PASSWORD_MD5');
			fs_delete_cookie($response, 'FS_REMEMBER_ME');
		}

		$response['reload'] = 'true';
	}
	else
	{
		$response['message'] = fs_r('Incorrect user-name or password');
	}
}

function fs_ajax_logout(&$response)
{
	fs_delete_cookie($response, 'FS_SESSION_ID');
	fs_delete_cookie($response, 'FS_LAST_USERNAME');
	fs_delete_cookie($response, 'FS_LAST_PASSWORD_MD5');
	fs_delete_cookie($response, 'FS_REMEMBER_ME');
	$response['refresh'] = 'true';
}

function fs_ajax_create_user(&$response)
{
	$username =  $_POST['username'];
	$pass1 =  $_POST['pass1'];
	$pass2 =  $_POST['pass2'];
	$email =  $_POST['email'];
	$security_level =  $_POST['security_level'];
	$can_access_all_sites =  $_POST['can_access_all_sites'] == 'true';
	$allowed_sites_list = $_POST['allowed_sites_list']; 
	
	if (empty($security_level)) return ajax_error($response, "Missing security level"); // not translated
	if (empty($username)) return ajax_error($response, fs_r("User name not specified"));
	if (empty($email)) return ajax_error($response, fs_r("Email not specified"));
	if (empty($pass1)) return ajax_error($response, fs_r("Password not specified"));
	if ($pass1 !== $pass2) return ajax_error($response, fs_r("Passwords did not match"));

	require_once(FS_ABS_PATH.'/php/auth.php');
	$user_id = fs_create_user($username, $email, $pass1, $security_level);
	if (!is_numeric($user_id))
	{
		return ajax_error($response, $user_id);
	}
	
	$res = fs_update_user_sites_access($user_id, $can_access_all_sites, $allowed_sites_list);
	if ($res !== true) return ajax_error($response, $res);
	
}

function fs_ajax_delete_user(&$response)
{
	$id =  $_POST['id'];
	require_once(FS_ABS_PATH.'/php/auth.php');
	$res = fs_delete_user($id);
	if ($res !== true)
	{
		return ajax_error($response, $res);
	}
}


function fs_ajax_update_user(&$response)
{
	$id =  $_POST['id'];
	$username =  $_POST['username'];
	$pass1 = !empty($_POST['pass1']) ? $_POST['pass1'] : null;
	$pass2 = !empty($_POST['pass2']) ? $_POST['pass2'] : null;
	$email = $_POST['email'];
	$security_level =  $_POST['security_level'];
	$can_access_all_sites =  $_POST['can_access_all_sites'] == 'true';
	$allowed_sites_list = $_POST['allowed_sites_list']; 
	if (empty($id)) return ajax_error($response, "Missing user id"); // not translated
	if (empty($security_level)) return ajax_error($response, "Missing security level"); // not translated
	if (empty($username)) return ajax_error($response, fs_r("User name not specified"));
	if (empty($email)) return ajax_error($response, fs_r("Email not specified"));
	if (!empty($pass1) || !empty($pass2))
	{
		if ($pass1 !== $pass2) return ajax_error($response, fs_r("Passwords did not match"));
	}

	require_once(FS_ABS_PATH.'/php/auth.php');
	$res = fs_update_user($id,$username, $email, $pass1, $security_level);
	if ($res !== true)
	{
		return ajax_error($response, $res);
	}
	
	$res = fs_update_user_sites_access($id, $can_access_all_sites, $allowed_sites_list);
	if ($res !== true) return ajax_error($response, $res);
	
	fs_ajax_send_update($response);
}

function fs_ajax_change_password(&$response)
{
	$id =  $_POST['id'];
	$username =  $_POST['username'];
	$pass1 = !empty($_POST['pass1']) ? $_POST['pass1'] : null;
	$pass2 = !empty($_POST['pass2']) ? $_POST['pass2'] : null;
	if (empty($username)) return ajax_error($response, fs_r("User name not specified"));
	if ($pass1 !== $pass2) return ajax_error($response, fs_r("Passwords did not match"));
	if (empty($pass1)) return ajax_error($response, fs_r("Empty password")); // not translated
	require_once(FS_ABS_PATH.'/php/auth.php');
	$res = fs_change_password($id,$username, $pass1);
	if ($res !== true)
	{
		return ajax_error($response, $res);
	}
	else
	{
		$base = fs_get_absolute_url(dirname(dirname($_SERVER['REQUEST_URI'])));
		$response['redirect'] = $base;
	}
}

function fs_ajax_handle_pending_maintanence(&$response)
{
	$str = fs_get_system_option('pending_maintanence', '');
	if ($str != '')
	{
		$jobs = explode(',',$str);
		if (count($jobs) > 0)
		{
			$job = array_pop($jobs);
			$s = explode(':',$job);
			$id = $s[0];
			$file = '';
			if (count($s) > 1)
			{
				$file = $s[1];
			}
			$response['execute'] = "FS.executeProcess('$id', '$file')";
			fs_update_system_option('pending_maintanence',implode(',',$jobs));
		}
	}
}

function fs_ajax_handle_add_excluded_url(&$response)
{
	$url =  $_POST['url'];
	require_once(FS_ABS_PATH.'/php/db-sql.php');
	$res = fs_add_excluded_url($url);
	if ($res !== true)
	{
		return ajax_error($response, $res);
	}
	else
	{
		$response['fields']['exclude_urls_placeholder'] = addslashes(fs_get_excluded_urls_list());
		$response['fields']['num_excluded'] = fs_get_num_excluded();
	}
}

function fs_ajax_handle_edit_excluded_url(&$response)
{
	$url =  $_POST['url'];
	$edit_id = $_POST['edit_id'];
	require_once(FS_ABS_PATH.'/php/db-sql.php');
	$res = fs_edit_excluded_url($url, $edit_id);
	if ($res !== true)
	{
		return ajax_error($response, $res);
	}
	else
	{
		$response['fields']['exclude_urls_placeholder'] = addslashes(fs_get_excluded_urls_list());
		$response['fields']['num_excluded'] = fs_get_num_excluded();
	}
}

function fs_ajax_handle_remove_excluded_url(&$response)
{
	$res = fs_remove_excluded_urls($_POST['ids']);
	if ($res === true)
	{
		$response['fields']['exclude_urls_placeholder'] = addslashes(fs_get_excluded_urls_list());
		$response['fields']['num_excluded'] = fs_get_num_excluded();
	}
	else
	{
		ajax_error($response, $res);
	}
}

function fs_ajax_set_sites_table_page_number(&$response)
{
	$page_number =  $_POST['page_number'];
	fs_update_option('current_selected_site_page',$page_number);
	$response['fields']['fs_sites_table'] = fs_get_sites_manage_table();
}

function fs_ajax_change_hits_table_page(&$response)
{
	$type = $_POST['type'];
	$cur = (int)fs_get_option("current_hits_table_page", "0");
	switch ($type)
	{
		case 'prev':
			$cur = max(0,$cur-1);
		break;
		case 'next':
			$hits = fs_get_num_hits_in_hits_table();
			if ($hits === false) return ajax_error($response, fs_db_error());
			$num_pages = ceil($hits / fs_get_num_hits_in_table());
			$cur = min($cur+1, $num_pages-1);
		break;
		case 'date':
			$cur = fs_hits_table_get_page_for_date($_POST['date']);
			if (is_string($cur)) return ajax_error($response, $cur);
		break;		
		default:
			return ajax_error($response, "unknown type $type");
	}
	
	fs_update_option("current_hits_table_page", $cur, true);
	$response['fields']['records_table'] = fs_get_records_table();
	$response['fields']['hits_table_page_number_indicator'] = fs_get_hits_table_page_number_indicator();
}

function fs_delete_cookie(&$response, $name)
{
	fs_create_cookie($response, $name, '', -1);
}

function fs_create_cookie(&$response, $name, $value, $days)
{
	if(!isset($response['cookies']))
	{
		$response['cookies'] = array();
	}

	$cookie = new stdClass();
	$cookie->name = $name;
	$cookie->value = $value;
	$cookie->days = $days;
	$cookies = &$response['cookies'];
	$cookies[] = $cookie;
}


function fs_json_encode($object)
{
	static $json;
	if (!isset($json)) 
	{
		require_once(FS_ABS_PATH . "/lib/json/JSON.php");
		$json = new fs_Services_JSON();
	}
	return $json->encode($object);
}
?>
