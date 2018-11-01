<?php

require_once(dirname(__FILE__).'/constants.php');
require_once(dirname(__FILE__).'/errors.php');

if (function_exists('strripos'))
{
	function fs_ends_with( $str, $sub )
	{
		$l1 = strlen($str);
		$l2 = strlen($sub);
		if ($l2 > $l1) return false;
	    $res = strripos($str,$sub,$l1 - $l2);
	    return $res !== false;
	}
}
else
{
	function fs_ends_with( $str, $sub )
	{
		return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
	}
}


function fs_starts_with($haystack, $needle)
{
    // Recommended version, using strpos
    return strpos($haystack, $needle) === 0;
}


// echo translated text
function fs_e($txt)
{
	global $fs_gettext;
	if (isset($fs_gettext))
	echo $fs_gettext->get($txt);
	else echo $txt;
}

// return translated text
function fs_r($txt)
{
	global $fs_gettext;
	if (isset($fs_gettext)) return $fs_gettext->get($txt);
	else return $txt;
}

function fs_url($file)
{
	global $fs_base_url;
	
	if (!isset($fs_base_url))
	{
		if (function_exists('fs_override_base_url'))
		{
			$fs_base_url = fs_override_base_url();
		}
		else
		{
			$fs_base_url = "";
		}
	}
	
	return $fs_base_url.$file;
}

/**
 * returns a URL which javascript can connect to to accesss FireStats resources.
 * browser security prevents JavaScript from accessing hosts other than the one 
 * it was downloaded from.
 */
function fs_js_url($file, $suffix = "")
{
	// This is a work around browsers restricting javascript from accessing different hosts.
	// in wordpress, the Ajax url may be on a different host than the url of the blog.
	// so the browsers prevent javascript from accessing the ajax handler.
	// what happens here is that we redirect the ajax call through the origin page
	global $FS_CONTEXT;
	if (isset($FS_CONTEXT['JAVASCRIPT_URL']))
	{
		return $FS_CONTEXT['JAVASCRIPT_URL'].$file."&".fs_get_request_suffix($suffix,false);
	}
	else
	{
		return fs_url($file).fs_get_request_suffix($suffix);
	}
}

function fs_get_request_suffix($append = "", $prepand_with_qm = true)
{
	require_once(dirname(__FILE__).'/session.php');
	$t = $prepand_with_qm ? '?' : '';
	$t .= 'sid='.fs_get_session_id();
	if ($append)
	{
		$t .= "&$append";
	}
	return $t;
}

function fs_get_whois_providers()
{
	static $whois_providers;
	if (!isset($whois_providers))
	{
		$providers = file(FS_ABS_PATH.'/php/whois.txt');
		foreach($providers as $line)
		{
			$r = sscanf($line,"%s %s");
			$whois_providers[$r[0]] = $r[1];
		}
	}
	return $whois_providers;
}


/*
 Function to replace PHP's parse_ini_file() with much fewer restritions, and
 a matching function to write to a .INI file, both of which are binary safe.

 Version 1.0

 Copyright (C) 2005 Justin Frim <phpcoder@cyberpimp.pimpdomain.com>

 Sections can use any character excluding ASCII control characters and ASCII
 DEL.  (You may even use [ and ] characters as literals!)

 Keys can use any character excluding ASCII control characters, ASCII DEL,
 ASCII equals sign (=), and not start with the user-defined comment
 character.

 Values are binary safe (encoded with C-style backslash escape codes) and may
 be enclosed by double-quotes (to retain leading & trailing spaces).

 User-defined comment character can be any non-white-space ASCII character
 excluding ASCII opening bracket ([).

 readINIfile() is case-insensitive when reading sections and keys, returning
 an array with lower-case keys.
 writeINIfile() writes sections and keys with first character capitalization.
 Invalid characters are converted to ASCII dash / hyphen (-).  Values are
 always enclosed by double-quotes.

 writeINIfile() also provides a method to automatically prepend a comment
 header from ASCII text with line breaks, regardless of whether CRLF, LFCR,
 CR, or just LF line break sequences are used!  (All line breaks are
 translated to CRLF)
 */

function fs_readINIfile ($filename, $commentchar)
{
	return fs_readInitArray(file($filename),$commentchar);
}

function fs_readINIArray ($array1, $commentchar = '#')
{
	$section = '';
	foreach ($array1 as $filedata)
	{
		$dataline = trim($filedata);
		$firstchar = substr($dataline, 0, 1);
		if ($firstchar!=$commentchar && $dataline!='')
		{
			//It's an entry (not a comment and not a blank line)
			if ($firstchar == '[' && substr($dataline, -1, 1) == ']')
			{
				//It's a section
				$section = strtolower(substr($dataline, 1, -1));
			}
			else
			{
				//It's a key...
				$delimiter = strpos($dataline, '=');
				if ($delimiter > 0)
				{
					//...with a value
					$key = strtolower(trim(substr($dataline, 0, $delimiter)));
					$value = trim(substr($dataline, $delimiter + 1));
					if (substr($value, 1, 1) == '"' && substr($value, -1, 1) == '"')
					{
						$value = substr($value, 1, -1);
					}
					$array2[$section][$key] = stripcslashes($value);
				}
				else
				{
					//...without a value
					$array2[$section][strtolower(trim($dataline))]='';
				}
			}
		}else
		{
			//It's a comment or blank line.  Ignore.
		}
	}
	return $array2;
}

function fs_writeINIfile ($filename, $array1, $commentchar, $commenttext) {
	$handle = fopen($filename, 'wb');
	if ($commenttext!='') {
		$comtext = $commentchar.
		str_replace($commentchar, "\r\n".$commentchar,
		str_replace ("\r", $commentchar,
		str_replace("\n", $commentchar,
		str_replace("\n\r", $commentchar,
		str_replace("\r\n", $commentchar, $commenttext)
		)
		)
		)
		)
		;
		if (substr($comtext, -1, 1)==$commentchar && substr($comtext, -1, 1)!=$commentchar) {
			$comtext = substr($comtext, 0, -1);
		}
		fwrite ($handle, $comtext."\r\n");
	}
	foreach ($array1 as $sections => $items) {
		//Write the section
		if (isset($section)) { fwrite ($handle, "\r\n"); }
		//$section = ucfirst(preg_replace('/[\0-\37]|[\177-\377]/', "-", $sections));
		$section = ucfirst(preg_replace('/[\0-\37]|\177/', "-", $sections));
		fwrite ($handle, "[".$section."]\r\n");
		foreach ($items as $keys => $values) {
			//Write the key/value pairs
			//$key = ucfirst(preg_replace('/[\0-\37]|=|[\177-\377]/', "-", $keys));
			$key = ucfirst(preg_replace('/[\0-\37]|=|\177/', "-", $keys));
			if (substr($key, 0, 1)==$commentchar) { $key = '-'.substr($key, 1); }
			$value = ucfirst(addcslashes($values,''));
			fwrite ($handle, '    '.$key.' = "'.$value."\"\r\n");
		}
	}
	fclose($handle);
}

/**
 Compare versions like 0.1.2[-beta]
 where -beta is optional.

 return 0 if ver1 = ver2
 -1 if ver1 < ver2
 1 if ver1 > ver2
 */
function ver_comp($ver1, $ver2, $ignore_suffix = false)
{
	$r1 = sscanf($ver1,"%d.%d.%d-%s");
	$r2 = sscanf($ver2,"%d.%d.%d-%s");
	if ($r1[0] == $r2[0])
	{
		if ($r1[1] == $r2[1])
		{
			if ($r1[2] == $r2[2])
			{
				if ($ignore_suffix) return 0;
				if ($r1[3] == $r2[3]) return 0;
				if ($r1[3] == null) return 1;
				if ($r2[3] == null) return -1;
				return strcmp($r1[3],$r2[3]);
			}
			else
			{
				return $r1[2] - $r2[2] < 0 ? -1 : 1;
			}
		}
		else
		{
			return $r1[1] - $r2[1] < 0 ? -1 : 1;
		}
	}
	else
	{
		return $r1[0] - $r2[0] < 0 ? -1 : 1;
	}
}

function ver_suffix($version)
{
	$r = sscanf($version,"%d.%d.%d-%s");
	return count($r) == 4 ? $r[3] : false;
}

/**
 * accepts an ipv4 or ipv6 address and return an array with two hex string elements:
 * left 64bit and right 64bit.
 * for ipv4 addresses, the left 64bit is always 0
 */
function fs_ip2hex($ip_str)
{
	$ip_str = trim($ip_str);
	$ipv = fs_ip_version($ip_str);
	if ($ipv == 4)
	{
		$r = sscanf($ip_str,"%d.%d.%d.%d");
		$nib1 = "0";
		$nib2 = "ffff".sprintf('%02s%02s%02s%02s', dechex($r[0]),dechex($r[1]),dechex($r[2]),dechex($r[3]));
		return array($nib1,$nib2);
	}
	else
	if ($ipv == 6)
	{
	    if (substr_count($ip_str, '::'))
	    {
	        $ip_str = str_replace('::', str_repeat(':0000', 8 - substr_count($ip_str, ':')) . ':', $ip_str) ;
	    }
	
	    $ip = explode(':', $ip_str);
	
	    $r_ip = '' ;
	    foreach ($ip as $v)
	    {
	        $r_ip .= str_pad($v, 4, 0, STR_PAD_LEFT);
	    }

	    $nib1 = str_pad($ip[0], 4, 0, STR_PAD_LEFT).
	    		str_pad($ip[1], 4, 0, STR_PAD_LEFT).
	    		str_pad($ip[2], 4, 0, STR_PAD_LEFT).
	    		str_pad($ip[3], 4, 0, STR_PAD_LEFT);
	    $nib2 = str_pad($ip[4], 4, 0, STR_PAD_LEFT).
	    		str_pad($ip[5], 4, 0, STR_PAD_LEFT).
	    		str_pad($ip[6], 4, 0, STR_PAD_LEFT).
	    		str_pad($ip[7], 4, 0, STR_PAD_LEFT);
		return array($nib1,$nib2);
	}
	else return false; 
}

function fs_ip_version($ip)
{
	if (fs_is_ipv4_address($ip)) return 4;
	if (fs_is_ipv6_address($ip)) return 6;
	return 0;
}

function fs_is_ipv4_address($ip)
{
	$res = ip2long($ip);
	return $res !== false && $res != -1;
}

function fs_is_ipv6_address($ip)
{
	$c = substr_count($ip, '::');
	if ($c > 1) return false;
    if ($c == 1)
    {
        $ip = str_replace('::', str_repeat(':0000', 8 - substr_count($ip, ':')) . ':', $ip) ;
        if (strpos($ip,":") == 0)
        {
        	$ip = "0000".$ip;
        }
    }

    $ip = explode(':', $ip);
    if (count($ip) != 8) return false;
    foreach ($ip as $v)
    {
		if (!is_numeric("0x$v")) return false;
		$n = (int)$v;
		if ($n < 0x0000 || $n >= 0xffff) return false;
        
    }
    return true;
}

/**
 * Accepts a pair of 64bit hex numbers and format them as an ipv4 or ipv6 IP address.
 */
function fs_ip_to_string($ip1, $ip2)
{
	if ($ip1 == null || $ip2 == null) return null;
	$ip1 = str_pad($ip1,16,"0",STR_PAD_LEFT);
	$ip2 = str_pad($ip2,16,"0",STR_PAD_LEFT);
	if ($ip1 == 0 && stristr($ip2,"0000ffff") !== false) // mapped ipv4
	{
		$bytes = pack("H8",substr($ip2, 8));
		return fs_ip_bin_to_string($bytes);
	}
	else
	{
		return fs_ip_bin_to_string(pack("H32",$ip1.$ip2));
	}
}

/**
 * Accepts a binary string with an ip address, and return a textual representation.
 */
function fs_ip_bin_to_string($bytes) 
{
	$len = strlen($bytes);
	if ($len == 4) 
	{
		list(,$ip)=unpack('N',$bytes);
		return long2ip($ip);
	} 
	else
	if ($len == 16)
	{
		$res = '';
		$skiped = false;
		for($i = 0;$i<8;$i++)
		{
			$b1 = $bytes[$i*2+0];
			$b2 = $bytes[$i*2+1];
			$h1 = bin2hex($b1);
			$h2 = bin2hex($b2);
			if ($h1 == "0" && $h2 == 0) 
			{
				if (!$skiped)
				{
					$res .= ":";
				}
				$skiped = true;
				continue;
			}
			
			if ($i != 0)
			{
				$res .= ":";
			}
			$res .= "$h1$h2";
		}
		return $res;
	}
	else
	{
		return "Incorect IP byte count : " . count($bytes);
	}
}

function fs_create_http_conn($url)
{
	require_once(FS_ABS_PATH.'/lib/http/http.php');
	@set_time_limit(0);
	$http=new fs_http_class;
	$http->timeout=10;
	$http->data_timeout=15;
	$http->user_agent= 'FireStats/'.FS_VERSION.' ('.FS_HOMEPAGE.')';
	$http->follow_redirect=1;
	$http->redirection_limit=5;
	$arguments = "";
	$error = $http->GetRequestArguments($url,$arguments);
	return array('status'=>(empty($error)?"ok" : $error ),"http"=>$http, "args"=>$arguments);
}

function fs_fetch_http_file($url, &$error)
{
	$res = fs_create_http_conn($url);
	if ($res['status'] != 'ok')
	{
		$error = $res['status'];
		return null;
	}
	else
	{

		$http = $res['http'];
		$args = $res['args'];
		$error=$http->Open($args);
		if (!empty($error))
		{
			return false;
		}

		$error = $http->SendRequest($args);
		if (!empty($error))
		{
			return false;
		}
		
		$http->ReadReplyHeadersResponse($headers);
		if ($http->response_status != '200')
		{
			$error = sprintf(fs_r("Server returned error %s for %s"),"<b>$http->response_status</b>", "<b>$url</b>");
			return false;
		}

		$content = '';
		for(;;)
		{
			$body = "";
			$error=$http->ReadReplyBody($body,1000);
			if($error!="" || strlen($body)==0)
			break;
			$content .= $body;
		}
		return $content;
	}
}

function fs_time_to_nag()
{
	/**
	 * if donation status is not no or donated
	 * if last nag time > now - 32 days
	 * nag
 	 */

	$status = fs_get_option('donation_status');
	$last_nag_time = fs_get_option('last_nag_time');
	if (!$last_nag_time)
	{
		$last_nag_time = fs_get_option('first_login');
	}
	
	if ($status != 'no' && $status != 'donated')
	{
		return time() - $last_nag_time > 60*60*24*32;
	}

	return false;
}

function fs_authenticate()
{
	global $FS_SESSION;
	return (isset($FS_SESSION['authenticated']) && $FS_SESSION['authenticated']);
}

function fs_get_relative_url($url)
{
	$text = $url;
	if ($text == "") return $text;
    $p = @parse_url($url);
    if ($p != false)
    {
        if (isset($p['scheme'])) // absolute
        {
            if (isset($p['host']) && $p['host'] == $_SERVER['SERVER_NAME'])
            {
                if (isset($p['path']))		$text = $p['path'];
                if (isset($p['query'])) 	$text .= "?".$p['query'];
                if (isset($p['fragment'])) 	$text .= "#".$p['fragment'];
            }
        }
    }
    return $text;
}

function fs_get_absolute_url($path, $base = null)
{
	$result = $path;
	if ($result == "") return $result;
	$p = @parse_url($path);
	if ($p === false) return $path;

	if (!isset($p['scheme'])) // relative
	{
		// make sure path starts with /
		if (strlen($path) == 0 ||  substr($path, 0, 1) != "/") $path = "/".$path;
		
		if ($base)
		{
			$b = @parse_url($base);
			if (isset($b['scheme']))
			{
				$scheme = $b['scheme'];
				$host   = $b['host'];
				$port   = isset($b['port']) ? $b['port'] : '80';
			}
		}
		
		if (!isset($scheme)) // base is not defined or relative, use SERVER as base.
		{
			$host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : "";
			$port = isset($_SERVER['SERVER_PORT']) ? $_SERVER['SERVER_PORT']  : "80";
			if ( !isset($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) != 'on' )
			{
				$scheme = "http";
			}
			else
			{
				$scheme = "https";
			}
		}
		
		$portstr = $port == "80" ? "" : ":".$port;
		$result = $scheme."://".$host.$portstr.$path;
	}
	return $result;
}

function fs_mkPair($d,$msg)
{
	$s = new stdClass();
	$s->d = $d;
	$s->msg = $msg;
	return $s;
}

/**
 * System information includes:
 * Unique firestats id
 * FireStats version
 * Installation time
 * PHP version
 * MySQL version
 * Server software (apache? IIS? which version?)
 * Memory limit
 * Number of sites monitored
 * Number of sites monitored from each type (how many wordpress blogs, how many drupals etc).
 */
function fs_get_sysinfo()
{
	require_once(dirname(__FILE__).'/db-common.php');
	$s = array();
	$s["FIRESTATS_ID"] = fs_get_system_option('firestats_id');
	$s["FIRESTATS_VERSION"] = FS_VERSION;
	$s["INSTALLATION_TIME"] = fs_get_system_option('first_run_time');
	
	$s["PHP_VERSION"] = phpversion();
	$s["MYSQL_VERSION"] = fs_mysql_version();
	$s["SERVER_SOFTWARE"] = $_SERVER["SERVER_SOFTWARE"];
	$s["MEMORY_LIMIT"] = ini_get('memory_limit');
	
	$sites_table = fs_sites_table();
	$sql = "SELECT type,COUNT(type) c from $sites_table GROUP BY type";
	$fsdb = &fs_get_db_conn();
	$res = $fsdb->get_results($sql);
	if ($res === false) return $s;
	$total = 0;
	if (count($res) > 0)
	{
		foreach($res as $r)
		{
			$s["NUM_SITES_$r->type"] = $r->c;
			$total += $r->c;
		}
	}
	$s["NUM_SITES"] = $total;
	
	return $s;
}

function fs_last_sent_info_outdated()
{
	$last_sysinfo_ser = fs_get_system_option('last_sent_sysinfo');
	if ($last_sysinfo_ser)
	{
		$current_sysinfo = fs_get_sysinfo();
		$last_sysinfo = unserialize($last_sysinfo_ser);
		foreach ($last_sysinfo as $k => $v)
		{
			if (isset($current_sysinfo[$k]) && $current_sysinfo[$k] != $last_sysinfo[$k])
			{
				return true;
			}
		}
		return false;
	}
	return true;
}

function fs_unlink($path,$match,$recursive = false)
{
	$dirs = glob($path."*");
	$files=glob($path.$match);
	foreach($files as $file)
	{
		if(is_file($file))
		{
			unlink($file);
		}
	}
	
	foreach($dirs as $dir)
	{
		if($recursive && is_dir($dir))
		{
			$dir=basename($dir)."/";
			fs_unlink($path.$dir,$match);
		}
	}
}

function fs_validate_email_address($mail) 
{
	$user = '[a-zA-Z0-9_\-\.\+\^!#\$%&*+\/\=\?\`\|\{\}~\']+';
	$domain = '(?:(?:[a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.?)+';
	$ipv4 = '[0-9]{1,3}(\.[0-9]{1,3}){3}';
	$ipv6 = '[0-9a-fA-F]{1,4}(\:[0-9a-fA-F]{1,4}){7}';
	return preg_match("/^$user@($domain|(\[($ipv4|$ipv6)\]))$/", $mail);
}

function fs_microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

function fs_array_remove(&$a_Input, $m_SearchValue)
{
	$a_Keys = array_keys($a_Input, $m_SearchValue);
	foreach($a_Keys as $s_Key) 
	{
		unset($a_Input[$s_Key]);
	
	}
	return $a_Input;
}

function &fs_get_incremental_processes()
{
	static $ips;
	if (!isset($ips))
	{
		$ips = array();	
	}
	return $ips;
}

/**
 * Registers an incremental process to be executed as a sequence of steps.
 * We register two functions that will help us execute the process:
 * 1. max_calc()
 * max_calc calculates the number of steps for the whole process, and accept no arguments.
 * on error it returns a string with the error message, on success it returns an int with the number of steps.
 * 
 * 2. step_exec($value, $max)
 * step_exec executes a step in the process.
 * returns the number of actual steps executes, or error message string on error.
 * 
 * 3. step_desc($value, $max)
 * returns the desciption test for the specifeid step.
 *
 * @param $id process ID (unique name for the process)
 * @param $max_calc a function that calculates the number of steps in the process
 * @param $step_exec a function that exectures a step in the process
 * @param $step_desc a function that returns the description of a step
 * @param unknown_type $includes an array containing a list of files to include before calling max_calc of step_exec.
 */
function fs_register_incremental_process($id, $max_calc, $step_exec, $step_desc = null, $includes = array(), $done_callback = null)
{
	$ips = &fs_get_incremental_processes();
	$ip = new stdClass();
	$ip->max_calc = $max_calc;
	$ip->step_exec = $step_exec;
	$ip->step_desc = $step_desc;
	$ip->includes = $includes;
	$ip->done_callback = $done_callback;
	$ips[$id] = $ip;
}

function fs_calculate_process_max($id)
{
	$res = fs_setup_process_action($id, "fs_calculate_process_max");
	if ($res !== true) return $res;
	$ips = &fs_get_incremental_processes();
	$ip = $ips[$id];
	$max_calc = $ip->max_calc;
	if (!function_exists($max_calc)) return "function does not exist : $max_calc";
	return $max_calc();
}

function fs_execute_process_step($id, $value, $max)
{
	$res = fs_setup_process_action($id, "fs_execute_process_step");
	if ($res !== true) return $res;
	
	$ips = fs_get_incremental_processes();
	$ip = $ips[$id];
	$step_exec = $ip->step_exec;
	if (!function_exists($step_exec)) return "function does not exist : $step_exec";
	return $step_exec($value, $max);
}

function fs_handle_process_done($id, &$response)
{
	$res = fs_setup_process_action($id, "handle_done");
	if ($res !== true) return $res;
	$ips = &fs_get_incremental_processes();
	$ip = $ips[$id];
	$done_callback = $ip->done_callback;
	if ($done_callback != null)
	{
		return $done_callback($response);
	}
	else
	{
		$start = $response['start'];
		$response['done'] = 'true';
		$response['progress_text'] = sprintf(fs_r('Done, took %s seconds'),(time() - $start));
	}
}


function fs_get_step_description($id, $value, $max)
{
	$res = fs_setup_process_action($id, "step desc");
	if ($res !== true) return $res;
	
	$ips = &fs_get_incremental_processes();
	$ip = $ips[$id];
	$step_desc = $ip->step_desc;
	if ($step_desc != null)
	{
		if (!function_exists($step_desc)) return "function does not exist : $step_desc";
		return $step_desc($value, $max);
	}
	return null;
}

function fs_setup_process_action($id, $name)
{
	$ips = &fs_get_incremental_processes();
	if (!isset($ips[$id])) return "Unknown process id : $id ($name)";
	$ip = $ips[$id];
	$includes = $ip->includes;
	foreach($includes as $include)
	{
		require_once($include);
	}
	return true;
}

function fs_add_pending_maintanence_job($id, $file)
{
	$jobs = fs_get_system_option('pending_maintanence', '');
	if ($jobs == '') $jobs = "$id:$file";
	else $jobs .= ",$id:$file";
	// remove duplicate jobs
	$jobs = implode(',',array_unique(explode(',',$jobs)));
	fs_update_system_option('pending_maintanence',$jobs);
}

function fs_println($line = "")
{
	if (php_sapi_name() == "cli")
	{
		echo "$line\n";
	}
	else
	{
		echo "$line</br>";
		flush();
	}
}

/**
 * Similar to php standard implode function, except it can accept an optional string_convertor function that 
 * converts objects in $arr to strings.
 *
 * @param String $glue
 * @param an array of objects $arr
 * @param function $string_converter if the array contains object, this function can be used to convert them to a string form.
 * @return unknown
 */
function fs_implode($glue, $arr, $string_converter = null, $context = null)
{
	if (!is_array($arr)) return "Error, not an array : $arr";
	$res = '';
	if (count($arr) > 0)
	{
		if ($string_converter == null)
		{
			foreach ($arr as $a)
			{
				if ($res != '') $res .= $glue;
				$res .= "$a";
			}
		}
		else
		{
			foreach ($arr as $a)
			{
				if ($res != '') $res .= $glue;
				$res .= $string_converter($context, $a);
			}
		}
	}
	return $res;
}

function fs_time_since($ts)
{
    $ts=time() - $ts;
    if ($ts < 60)
        // <1 minute
        return sprintf(fs_r("%d seconds ago"),$ts);
	elseif ($ts>60 && $ts < 120)
		// 1 minute <= $ts < 2 minutes
		return sprintf(fs_r("One minute ago"),floor($ts/60));        
    elseif ($ts<60*60)
        // <1 hour
        return sprintf(fs_r("%d minutes ago"),floor($ts/60));
    elseif ($ts<60*60*2)
        // <2 hour
        return fs_r("one hour ago");
    elseif ($ts<60*60*24)
        // <24 hours = 1 day
        return sprintf(fs_r("%d hours ago"),floor($ts/(60*60)));
    elseif ($ts<60*60*24*2)
        // <2 days
        return fs_r("One day ago");
    elseif ($ts<60*60*24*7)
        // <7 days = 1 week
        return sprintf(fs_r("%d days ago"),floor($ts/(60*60*24)));
    elseif ($ts<60*60*24*10)
        // <7 days = 1 week
        return sprintf(fs_r("One week ago"),floor($ts/(60*60*24)));
   elseif ($ts<60*60*24*30.5)
        // <30.5 days ~  1 month
        return sprintf(fs_r("%d weeks ago"),floor($ts/(60*60*24*7)));
    elseif ($ts<60*60*24*365)
        // <365 days = 1 year
        return sprintf(fs_r("%d months ago"),($ts/(60*60*24*30.5)));
    elseif ($ts<60*60*24*365*1.3)
        // <365 days = 1 year
        return sprintf(fs_r("One year ago"),($ts/(60*60*24*30.5)));
    else
        // more than 1 year
        return sprintf(fs_r("%d years ago"),floor($ts/(60*60*24*7*365)));
};

function fs_link($url, $text, $external = false, $target = null)
{
	if (!$external)
	{
		$url = fs_url($url);
	}
	
	if ($target != null)
	{
		return "<a target='$target' href='$url'>$text</a>";
	}
	else
	{
		return "<a href='$url'>$text</a>";
	}
}

/**
 * Output messages info to the log file.
 */
function fs_log($msg)
{
	if (!fs_log_active()) return;
    require_once('session.php');
    fs_initialize_session_dir();
    global $FS_TEMP_DIR;
    if (isset($FS_TEMP_DIR))
    {
        error_log("$msg\n", 3, $FS_TEMP_DIR."fs.log");
    }
}

function fs_log_active()
{
	return (defined('FS_LOGGING') && FS_LOGGING);
}

function fs_append_str($base, $append, $sep = " ")
{
	if (empty($append)) return $base;
	if ($base == "") return $append;
	else return $base . $sep . $append;
}

////////////////////////////////////////////////////////
// Function:         dump
// Inspired from:     PHP.net Contributions
// Description: Helps with php debugging

function fs_dump(&$var, $info = FALSE)
{
    $scope = false;
    $prefix = 'unique';
    $suffix = 'value';
 
    if($scope) $vals = $scope;
    else $vals = $GLOBALS;

    $old = $var;
    $var = $new = $prefix.rand().$suffix; $vname = FALSE;
    foreach($vals as $key => $val) if($val === $new) $vname = $key;
    $var = $old;

    echo "<pre style='margin: 0px 0px 10px 0px; display: block; background: white; color: black; font-family: Verdana; border: 1px solid #cccccc; padding: 5px; font-size: 10px; line-height: 13px;'>";
    if($info != FALSE) echo "<b style='color: red;'>$info:</b><br>";
    fs_do_dump($var, '$'.$vname);
    echo "</pre>";
}

////////////////////////////////////////////////////////
// Function:         do_dump
// Inspired from:     PHP.net Contributions
// Description: Better GI than print_r or var_dump

function fs_do_dump(&$var, $var_name = NULL, $indent = NULL, $reference = NULL)
{
    $do_dump_indent = "<span style='color:#eeeeee;'>|</span> &nbsp;&nbsp; ";
    $reference = $reference.$var_name;
    $keyvar = 'the_do_dump_recursion_protection_scheme'; $keyname = 'referenced_object_name';

    if (is_array($var) && isset($var[$keyvar]))
    {
        $real_var = &$var[$keyvar];
        $real_name = &$var[$keyname];
        $type = ucfirst(gettype($real_var));
        echo "$indent$var_name <span style='color:#a2a2a2'>$type</span> = <span style='color:#e87800;'>&amp;$real_name</span><br>";
    }
    else
    {
        $var = array($keyvar => $var, $keyname => $reference);
        $avar = &$var[$keyvar];
   
        $type = ucfirst(gettype($avar));
        if($type == "String") $type_color = "<span style='color:green'>";
        elseif($type == "Integer") $type_color = "<span style='color:red'>";
        elseif($type == "Double"){ $type_color = "<span style='color:#0099c5'>"; $type = "Float"; }
        elseif($type == "Boolean") $type_color = "<span style='color:#92008d'>";
        elseif($type == "NULL") $type_color = "<span style='color:black'>";
   
        if(is_array($avar))
        {
            $count = count($avar);
            echo "$indent" . ($var_name ? "$var_name => ":"") . "<span style='color:#a2a2a2'>$type ($count)</span><br>$indent(<br>";
            $keys = array_keys($avar);
            foreach($keys as $name)
            {
                $value = &$avar[$name];
                fs_do_dump($value, "['$name']", $indent.$do_dump_indent, $reference);
            }
            echo "$indent)<br>";
        }
        elseif(is_object($avar))
        {
            echo "$indent$var_name <span style='color:#a2a2a2'>$type</span><br>$indent(<br>";
            foreach($avar as $name=>$value) fs_do_dump($value, "$name", $indent.$do_dump_indent, $reference);
            echo "$indent)<br>";
        }
        elseif(is_int($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
        elseif(is_string($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color\"$avar\"</span><br>";
        elseif(is_float($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color$avar</span><br>";
        elseif(is_bool($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $type_color".($avar == 1 ? "TRUE":"FALSE")."</span><br>";
        elseif(is_null($avar)) echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> {$type_color}NULL</span><br>";
        else echo "$indent$var_name = <span style='color:#a2a2a2'>$type(".strlen($avar).")</span> $avar<br>";

        $var = $var[$keyvar];
    }
}

/**
 * returns true if the specified function is one of the functions on the stack calling this function.
 * this is rather slow, so use with caution.
 */
function fs_called_by($class, $function)
{
	$bt = debug_backtrace(); 
	for($i = count($bt)-1;$i >= 0;$i--)
	{
		$frame = $bt[$i];
		if ($frame['function'] == $function && $frame['class'] == $class) return true;
	}
	return false;
}
?>
