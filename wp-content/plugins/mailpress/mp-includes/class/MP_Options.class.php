<?php
abstract class MP_Options
{
	function __construct()
	{
		// Load abstract class if any
		if (isset($this->abstract) && !is_array($this->abstract)) $this->abstract = array($this->abstract);
		if (isset($this->abstract)) foreach($this->abstract as $abstract) MailPress::load_options($abstract);

   		// Load all options so that they can do what they have to do.
		$root = MP_ABSPATH . 'mp-includes/class/options/' . $this->path;
		$dir  = @opendir($root);
		if ($dir) while ( ($file = readdir($dir)) !== false ) if ($file[0] != '.') $this->load($root, $file);
		@closedir($dir);
	}

	function load($root, $file)
	{
		if (isset($this->deep))
		{
			if (is_dir("$root/$file"))
			{
				$root .= "/$file";
				$dir  = @opendir($root);
				if ($dir) while (($file = readdir($dir)) !== false) if ($file[0] != '.') $this->load_file("$root/$file");
				@closedir($dir);
				return;
			}
		}
		elseif ( isset($this->includes) && !isset($this->includes[substr($file, 0, -4)]) ) return;

		$this->load_file("$root/$file");
	}

	function load_file($file)
	{
		if (substr($file, -4) != '.php') return;
//if (defined('MP_DEBUG_LOG')) { global $mp_debug_log; if (isset($mp_debug_log)) $mp_debug_log->log(" ¤¤ loading " . basename($file) . " ..." ); }
		require_once($file);
//if (defined('MP_DEBUG_LOG')) { global $mp_debug_log; if (isset($mp_debug_log)) $mp_debug_log->log(" ¤¤¤ loaded " . basename($file) ); }
	}
}