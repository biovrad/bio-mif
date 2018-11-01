<?php

init_plugins();

function fs_add_action($action_type, $callback)
{
	$actions = &fs_get_actions();
	if (isset($actions[$action_type]))
	{
		$list = &$actions[$action_type];
	}
	else
	{
		$list = array();
	}
	$data['context'] = fs_pcontext();
	$data['callback'] = $callback;
	$list[] = $data;
	$actions[$action_type] = $list;
}


function fs_do_action($type, $args = null)
{
	$handled = false;
	$actions = &fs_get_actions();
	if (isset($actions[$type]))
	{
		$list = $actions[$type];
		foreach($list as $data)
		{
			$plugin_id = $data['context'];
			fs_push_pcontext($plugin_id);
			$callback = $data['callback'];
			if ($args != null)
			{
				call_user_func_array($callback,$args);
			}
			else
			{
				$callback();
			}
			fs_pop_pcontext();
			$handled  = true;
		}
	}
	
	return $handled;
}

function &fs_get_actions()
{
	static $actions;
	if (!isset($actions)) $actions = array();
	return $actions;
}

function &fs_get_plugins()
{
	static $plugins;
	if (!isset($plugins)) $plugins = array();
	return $plugins;
}

function &fs_get_context_stack()
{
	static $context_stack;
	if (!isset($context_stack)) $context_stack = array();
	return $context_stack;
}

function init_plugins()
{
	$dir = FS_ABS_PATH."/plugins";
	if (@file_exists($dir))
	{
		$dh  = opendir($dir);
		$list = array();
		while (false !== ($filename = readdir($dh)))
		{
			if ($filename == "." || $filename == "..") continue;
			if (is_dir($dir."/".$filename))
			{
				$dh2  = opendir($dir."/".$filename);
				while (false !== ($filename2 = readdir($dh2)))
				{
					if ($filename2 == "." || $filename2 == "..") continue;
					fs_load_plugin($filename."/".$filename2);
				}
			}
			else
			{
				fs_load_plugin($filename);
			}
		}
	}
}

function fs_push_pcontext($context)
{
	$context_stack = fs_get_context_stack();
	array_push($context_stack,$context);
}

function fs_pop_pcontext()
{
	$context_stack = fs_get_context_stack();
	return array_pop($context_stack);
}

function fs_pcontext()
{
	$context_stack = fs_get_context_stack();
	$len = count($context_stack);
	return $len > 0 ? $context_stack[$len-1] : null;
}

function fs_dump_actions()
{
	$actions = &fs_get_actions();
	echo "<pre>".var_export($actions,true)."</pre>";
}


function fs_plugin_installed($name)
{
	$plugins = &fs_get_plugins();
	foreach($plugins as $plugin)
	{
		if (strtolower($plugin->plugin_name) == strtolower($name)) return true;
	}
	return false;
}

function fs_load_plugin($filename)
{
	if (!fs_ends_with(strtolower($filename), ".php")) return;
	$r = sscanf($filename,"%s.php");
	$full_path = FS_ABS_PATH ."/plugins/". $filename;
	$fp = fopen($full_path, "r");
	if ($fp == false) return;
	$plugin = new stdClass();
	while(true)
	{
		$s = fgets($fp);
		if ($s === false) break;
		if ($s == "<?php") continue;
		if ($s == "/*") continue;
		if ($s == "*/") break;
		$i = strpos($s, ":");
		if ($i != false)
		{
			$key = strtolower(trim(substr($s, 0, $i)));
			$key = str_replace(' ', '_', $key);
			$value = substr($s, $i+1);
			$plugin->$key = trim($value);
		}
	}
	
	fclose($fp);
	if (!isset($plugin->plugin_name)) return; // a plugin without a name is not a plugin! 
	$plugin->id = $r[0];
	$plugins = &fs_get_plugins();
	$plugins[] = $plugin;
	
	// set current plugin id.
	//$plugin_id = $r[0];
	fs_push_pcontext($plugin->id);
	// initialize plugin
	include($full_path);
	fs_pop_pcontext();
}
?>
