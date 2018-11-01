<?php
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/db-common.php');

/**
 * Checks if the there is a session cookie, and if so resume the session 
 */
function fs_resume_user_session()
{
	require_once(FS_ABS_PATH.'/php/session.php');
	$res = fs_resume_existing_session();
	if ($res !== true) 
	{
		return $res;
	}
	
	$authenticated = fs_current_user_id() !== false;
	if ($authenticated)
	{
		// raise authenticated event.
		// some initialization code may only happen after the user is authenticated.
		fs_do_action("authenticated");
		
	}
	return $authenticated;
}


function fs_current_user_id()
{
	$user = fs_get_current_user();
	if ($user === null || (isset($user->dummy) && $user->dummy)) return false;
	return $user->id;
}
/**
 * Checks if there is an authenticated user
 */
function fs_authenticated()
{
	return fs_get_current_user() !== null ;
}

function fs_ensure_authenticated()
{
	if (!fs_authenticated()) die("Authentication failed");
}


function fs_get_current_user()
{
	global $FS_SESSION;
	if(empty($FS_SESSION)) return null;
	if(!empty($FS_SESSION['user'])) return $FS_SESSION['user'];
	return null;
}

function fs_is_user()
{
	return fs_check_sec_level(SEC_USER);
}

function fs_is_admin()
{
	return fs_check_sec_level(SEC_ADMIN);
}

function fs_can_use()
{
	return fs_is_user() || fs_is_admin();
}

function fs_check_sec_level($sec_level)
{
	global $FS_SESSION;
	if(empty($FS_SESSION)) return false;
	if(empty($FS_SESSION['user'])) return false;
	$user = $FS_SESSION['user'];
	return $user->security_level == $sec_level;
}

/**
 * Attempts to login the user
 * on success, creates a new session for the user and returns true.
 * on failure, return false. 
 */
function fs_login($username, $password, $pass_is_md5 = false)
{
	$fsdb = &fs_get_db_conn();
	if (!$fsdb->is_connected())
	{
		global $fs_config;
		return sprintf(fs_r("Error connecting to database (%s)"), $fs_config['DB_HOST']);
	}
	
	$username = $fsdb->escape($username);
	$password = $fsdb->escape($password);
	$users = fs_users_table();
	if ($pass_is_md5)
	{
		$pass = "$password";	
	}
	else
	{
		$pass = "MD5($password)";
	}
	
	$user = $fsdb->get_row("SELECT `id`,`username`,`email`,`security_level`  FROM `$users` WHERE `username` = $username AND `password` = $pass");
	if ($user === false)
	{
		return fs_db_error();
	}
	else
	if ($user !== null)
	{
		// this is used to indicate the user logged in (and was not invented by some plugin). only logged in users can logout.
		$user->logged_in = true;
		$res = fs_start_user_session($user);
		if ($res === false) return false;
		return true;
	}
	else
	{
		return false;
	}
}

/**
 * Authenticate the current user as an admin.
 * this should only be used if there is currenty no admin in the database.
 */
function fs_dummy_auth()
{
	if (!fs_no_admin()) 
	{
		echo "Admin is already defined in the database";
		return;
	}
	
	$user = new stdClass();
	$user->dummy = true;
	$user->name = "Dummy admin";
	$user->security_level = SEC_ADMIN;
	$res = fs_start_user_session($user);
	if ($res) fs_store_session();
	if ($res === false) return false;
}

function fs_start_user_session($user)
{
	require_once(FS_ABS_PATH.'/php/session.php');
	$ok = fs_session_start(null, true);
	if ($ok !== true)
	{
		$msg = "Error starting session";
		if (is_string($ok)) $msg .= " :$ok";
		$msg .= "<br/>"; 
		echo $msg;
		return false;
	}
	
	global $FS_SESSION;
	$FS_SESSION['user'] = $user;
	fs_store_session();

	// user is null for dummy sessions (may be needed before login)
	if ($user != null)
	{
		// raise authenticated event.
		// some initialization code may only happen after the user is authenticated.
		fs_do_action("authenticated");
	}
	return true;
}

/**
 * returns true if there are no admin users in the users table.
 * this is an indication that an admin user need to be created.
 */
function fs_no_admin()
{
	$fsdb = &fs_get_db_conn();
	if (!$fsdb->is_connected()) 
	{
		// if database is not connected, we have no admin, right?
		return true;
	}
	$users = fs_users_table();
	$c = $fsdb->get_var("SELECT COUNT(`id`) FROM `$users` WHERE `security_level` = '1'");
	return (int)$c === 0;
}

function fs_create_user($username, $email, $password, $security_level)
{
	if (!fs_is_admin()) return "Access denied : fs_create_user";
	if (!fs_validate_email_address($email)) return fs_r("Invalid email address");
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$username = $fsdb->escape($username);
	$email = $fsdb->escape($email);
	$password = $fsdb->escape($password);
	$security_level = $fsdb->escape($security_level);
	
	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `username` = $username");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this name already exists");
	
	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `email` = $email");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this email address already exists");

	$sql = "INSERT INTO `$users` (`id` ,`username` ,`password` ,`email` ,`security_level`)VALUES (NULL , $username, MD5($password), $email, $security_level)";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}

	$r = $fsdb->get_var("SELECT id FROM `$users` WHERE `username` = $username");
	if ($r === false)
	{
		return fs_db_error();
	}
	return $r;
}

function fs_update_user($id,$username, $email, $password, $security_level)
{
	if (!fs_is_admin()) return "Access denied : fs_update_user";
	if (!fs_validate_email_address($email)) return fs_r("Invalid email address");
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$id = $fsdb->escape($id);
	$username = $fsdb->escape($username);
	$email = $fsdb->escape($email);
	$security_level = $fsdb->escape($security_level);
	
	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `username` = $username AND `id` != $id");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this name already exists");

	$r = $fsdb->get_var("SELECT COUNT(*) FROM `$users` WHERE `email` = $email AND `id` != $id");
	if ($r === false) return fs_db_error();
	if ((int)$r > 0) return fs_r("A user with this email address already exists");
	
	if (empty($password))
	{
		$sql = "UPDATE `$users` set `username`=$username,`email`=$email ,`security_level`=$security_level WHERE `id` = $id";
	}
	else
	{
		$password = $fsdb->escape($password);
		$sql = "UPDATE `$users` set `username`=$username,`password`=MD5($password),`email`=$email ,`security_level`=$security_level WHERE `id` = $id";
	}
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}

	return true;
}

function fs_change_password($id, $username, $password)
{
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$id = $fsdb->escape($id);
	$username = $fsdb->escape($username);
	$password = $fsdb->escape($password);
	$user = $fsdb->get_row("SELECT `id`,`username`,`email`,`security_level`  FROM `$users` WHERE `username` = $username AND `id` = $id");
	if ($user === false)
	{
		return fs_db_error();
	}
	else
	if ($user === null)
	{
		return "fs_change_password: Unknown user"; // not translated
	}
	else
	{
		$allowed = fs_is_admin() || $user->id == fs_current_user_id();
		if (!$allowed)
		{
			return "Access denied: fs_change_password"; // not translated
		}
		else
		{
			$sql = "UPDATE `$users` set `password`=MD5($password) WHERE `username` = $username AND `id` = $id";
			$r = $fsdb->query($sql);
			if ($r === false)
			{
				return fs_db_error();
			}
			return true;
		}
	}
	
}

function fs_get_user_by_username_and_email($username, $email)
{
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$email = $fsdb->escape($email);
	$username = $fsdb->escape($username);
	$sql = "SELECT `id`,`username`,`email`,`security_level` FROM `$users` WHERE `username` = $username AND `email` = $email";
	$u = $fsdb->get_row($sql);
	if ($u === false) return fs_db_error();
	return $u;
}

function fs_delete_user($id)
{
	if (!fs_is_admin()) return "Access denied : fs_delete_user";
	$fsdb = &fs_get_db_conn();
	$users = fs_users_table();
	$id = $fsdb->escape($id);
	$sql = "DELETE FROM `$users` WHERE `id`=$id";
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}

	$user_sites = fs_user_sites_table();
	$res = $fsdb->query("DELETE FROM `$user_sites` WHERE `user_id` = $id");
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}
	
	$options_table = fs_options_table();
	$res = $fsdb->query("DELETE FROM `$options_table` WHERE `user_id` = $id");
	$r = $fsdb->query($sql);
	if ($r === false)
	{
		return fs_db_error();
	}
	return true;
}

/**
 * Allows the user with user_id to access statistics for the site with site_id.
 * note that a user may have access to many sites.
 * to allow a user to access all sites, assign him the site_id -1.
 *
 * @param int $user_id the user id
 * @param int $site_id the site id, -1 for all-sites mapping.
 * @return true on success, or error message on failure.
 */
function fs_add_site_to_user($user_id, $site_id)
{
	if (!fs_is_admin()) return "Access denied : fs_allow_user_to_access_site";
	$fsdb = &fs_get_db_conn();
	$user_id = $fsdb->escape($user_id);
	$site_id = $fsdb->escape($site_id);
	$user_sites = fs_user_sites_table();
	$res = $fsdb->query("REPLACE INTO `$user_sites` (`user_id`,`site_id`) VALUES ($user_id,$site_id)");
	if ($res === false) return fs_db_error();
	else return true;
}

/**
 * Remove the mapping between the user_id and the site_id.
 * this causes the user to no longer have access to the site statistics.
 * 
 * if -1 is passed, the only the mapping (user_id,-1) is deleted, the rest of
 * (user_id,site_id) mappins remains intact.
 *
 * @param int $user_id the user id
 * @param int $site_id the site id, -1 for all-sites mapping.
 * @return true on success, or error message on failure.
 */
function fs_delete_site_from_user($user_id, $site_id)
{
	if (!fs_is_admin()) return "Access denied : fs_delete_site_from_user";
	$fsdb = &fs_get_db_conn();
	$user_id = $fsdb->escape($user_id);
	$site_id = $fsdb->escape($site_id);
	$user_sites = fs_user_sites_table();
	$res = $fsdb->query("DELETE FROM `$user_sites` WHERE `user_id` = $user_id AND `site_id` = $site_id");
	if ($res === false) return fs_db_error();
	else return true;
}

/**
 * returns an array containing the site ids this user is allowed to access.
 *
 * @param $user_id
 */
function fs_get_user_sites_array($user_id, $include_all_sites_row = true)
{
	$fsdb = &fs_get_db_conn();
	$user_id = $fsdb->escape($user_id);
	$user_sites = fs_user_sites_table();
	$sql = "SELECT site_id FROM `$user_sites` WHERE `user_id` = $user_id".
			(!$include_all_sites_row ? " AND site_id != -1" : "").
			" ORDER BY site_id";
	$res = $fsdb->get_col($sql);
	if ($res === false) return fs_db_error();
	return $res;
}

/**
 * returns a comma separated list of sites the user with the specified user_id
 * is allowed to access
 *
 * @param int $user_id
 * @return comma spearated list
 */

function fs_get_user_sites_list($user_id, $include_all_sites_row = true)
{
	$user_sites = fs_get_user_sites_array($user_id, $include_all_sites_row);
	if (count($user_sites) == 0) return '';
	return fs_implode(",", $user_sites);
}

/**
 * returns true if the user is allowed to access the specified site or false otherwise.
 * a user can access a site if the row (user_id,site_id) is in the 
 * user_sites table, or if the row (user_id,-1) is in the user sites table.
 *
 * @param int $user_id
 * @param int $site_id
 * @return unknown
 */
function fs_user_allowed_to_access_site($user, $site_id)
{
	if (isset($user->check_user_sites_table) && !$user->check_user_sites_table) return true;
	
	$user_id = $user->id;
	static $user_can_access_site_cache;
	if (!isset($user_can_access_site_cache))
	{
		$user_can_access_site_cache = array();
	}
	$cache_key = $user_id."__".$site_id;
	if (isset($user_can_access_site_cache[$cache_key])) return $user_can_access_site_cache[$cache_key];
	
	$fsdb = &fs_get_db_conn();
	$user_id = $fsdb->escape($user_id);
	$site_id = $fsdb->escape($site_id);
	$user_sites = fs_user_sites_table();
	$res = $fsdb->get_var("SELECT count(*) FROM `$user_sites` WHERE `user_id` = $user_id AND (`site_id` = $site_id OR `site_id` = -1)");
	if ($res === false) return fs_db_error();
	$user_can_access_site_cache[$cache_key] = (int)$res > 0;
	return $user_can_access_site_cache[$cache_key];
}

function fs_current_user_allowed_to_access_site($site_id)
{
	$user = fs_get_current_user();
	return fs_user_allowed_to_access_site($user, $site_id);
}

function fs_update_allowed_sites_list($user_id, $allowed_sites_list)
{
	$list = explode(",",$allowed_sites_list);
	$newlist = array();
	foreach($list as $site_id)
	{
		if ($site_id == '') continue;
		if (!is_numeric($site_id)) return sprintf(fs_r("Invalid site id: %s"), $site_id);
		$newlist[] = $site_id;
	}
	$user_sites = fs_user_sites_table();
	$fsdb = &fs_get_db_conn();
	$user_id = $fsdb->escape($user_id);
	$fsdb->query("START TRANSACTION");
	if (false === $fsdb->query("DELETE FROM `$user_sites` WHERE user_id = $user_id AND site_id != -1")) 
	{
		return fs_db_error(true);
	}
	
	if (count($newlist) > 0)
	{
		$sql = "REPLACE INTO `$user_sites` (`user_id`,`site_id`) VALUES";
		$context = array();
		$context["fsdb"] = $fsdb;
		$context["user_id"] = $user_id;	
		$func = create_function('$context,$site_id','extract($context);$site_id = $fsdb->escape($site_id);return "($user_id,$site_id)";');	
		$values = fs_implode(",", $newlist, $func, $context);
		if (false === $fsdb->query($sql . $values)) 
		{
			return fs_db_error(true);
		}
	}
	
	$fsdb->query("COMMIT");
	return true;
}

function fs_update_user_sites_access($user_id, $can_access_all_sites, $allowed_sites_list)
{
	if ($can_access_all_sites)
	{
		fs_add_site_to_user($user_id, -1);
	}
	else
	{
		fs_delete_site_from_user($user_id, -1);
		$res = fs_update_allowed_sites_list($user_id, $allowed_sites_list);
		if ($res !== true) $res;
	}
	return true;
}
?>
