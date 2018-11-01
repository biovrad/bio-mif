<?php
// =====================================
// === Start of params bugs handling ===

/**
 * If you have the Joomla 1.5 params bug (was observed when running on PHP4), 
 * you need to edit the followning values:
 */
// firestats dir, change null to '/www/firestats' or something similar (WITH THE QUOTES!).
define('FIRESTATS_PATH', null);

// firestats site_id (see the sites tab in FireStats) 
define('FIRESTATS_SITE_ID', 1);

// should admins be excluded from the statistics?
define('FIRESTATS_EXCLUDE_ADMINS', true);

// === End of params bugs handling ===
// =====================================


/**
 * FireStats integration plugin for Joomla 1.5
 * 
 * Author: Omry Yadan (omry@yadan.net)
 * Author: OneMarko(onemarko@gmail.com)
 */

// No direct access
defined('_JEXEC') or die('Restricted access');

// Import library
jimport('joomla.plugin.plugin');

class  plgSystemFirestats_Joomla extends JPlugin 
{   
	function plgSystemFirestats_Joomla(& $subject, $config)
    {
        parent::__construct($subject, $config);
    }
    
	function onAfterInitialise() 
	{
		$default_firestats_path = FIRESTATS_PATH; 
		$default_site_id = FIRESTATS_SITE_ID;
		$default_exclude_admins = FIRESTATS_EXCLUDE_ADMINS;
		
		$is_admin_page = strstr($_SERVER['REQUEST_URI'],"administrator/index.php") != false;

		$fs_folder	= $this->param("firestats_path", $default_firestats_path);
		
		if (strpos($fs_folder,"..") !== false) 
		{
			JError::raiseWarning(0,".. is not allowed in firestats_path");
			return;
		}
		
		$fs_folder	= $this->path($fs_folder);
		
		$fs_site_id	= intVal($this->param("firestats_site_id",$default_site_id));
		if ($is_admin_page)
		{
			if ($this->params == null && $default_firestats_path == null)
			{
				JError::raiseWarning(0,"params bug detected (appears when running Joomla on PHP 4), You will need to edit <b>".__FILE__."</b> to set the required parameters");
				return;
			}
			// validate parameters
			if (file_exists($fs_folder."/php/db-hit.php"))
			{
				include_once($fs_folder."/php/db-hit.php");
				include_once($fs_folder."/php/db-sql.php");

				if (!fs_site_exists($fs_site_id)) 
				{
					JError::raiseWarning(0, "Site with id '$fs_site_id' was not found in FireStats, check FireStats sites management tab");
				}
			}
			else
			{
				JError::raiseWarning(1, "FireStats not found at <b>'$fs_folder'</b>");
			}
		}
		else
		{
			$user =& JFactory::getUser();
			$is_admin = $user->usertype == "Super Administrator" || $user->usertype == "Administrator";
			$exclude_admin = $this->param("exclude_administrators_from_stats",$default_exclude_admins) ? true : false;
			if (!($is_admin && $exclude_admin))
			{
				// record hit
				if (file_exists($fs_folder."/php/db-hit.php"))
				{
					include_once($fs_folder."/php/db-hit.php");
					fs_add_site_hit($fs_site_id,false);
				}
			}
		}
		
	}
	
	function path($path) 
	{
		$search = array ("%joomla_root%","%server_root%");
		$replace = array(JPATH_ROOT,$_SERVER["DOCUMENT_ROOT"]);
		return str_replace($search, $replace, $path);
	}

	function param($key, $default)
	{
		return $this->params != null ? $this->params->get($key) : $default;
	}
}
?>
