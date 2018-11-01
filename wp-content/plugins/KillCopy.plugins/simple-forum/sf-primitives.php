<?php
/*
Simple Forum 2.1
Base functions
*/

//== Notcie Table Handlers

function get_sfnotice($item)
{
	global $wpdb;
	
	$id=$_SERVER['REMOTE_ADDR'];
	$message = $wpdb->get_var("SELECT message FROM ".SFNOTICE." WHERE id='$id' AND item='$item'");
	return stripslashes($message);
}

function update_sfnotice($item, $message)
{
	global $wpdb;

	$message = $wpdb->escape($message);	
	$id=$_SERVER['REMOTE_ADDR'];
	
	// as usual we need to check if already there because it was orphaned...
	$check=get_sfnotice($item);
	if($check)
	{
		delete_sfnotice($item);
	}
	$wpdb->query("INSERT INTO ".SFNOTICE." (id, item, message, ndate) VALUES ('$id', '$item', '$message', now())");
	$wpdb->flush;
	
	return;
}

function delete_sfnotice($item)
{
	global $wpdb;
	
	$id=$_SERVER['REMOTE_ADDR'];
	$wpdb->query("DELETE FROM ".SFNOTICE." WHERE id='$id' AND item='$item'");
	return;
}

function  sf_clean_sfnotice()
{
	global $wpdb;
	$wpdb->query("DELETE FROM ".SFNOTICE." WHERE ndate < DATE_SUB(CURDATE(), INTERVAL 24 HOUR);");
	return;
}

//== Settings Table Handlers

function get_sfsetting($setting)
{
	global $wpdb;

	$value = $wpdb->get_var("SELECT setting_value FROM ".SFSETTINGS." WHERE setting_name = '$setting'");
// 2.1 Patch 1
//	if($value)
//	{
//		return $value;
//	} else {
//		return -1;
//	}
	if(empty($value))
	{
		return -1;
	} else {
		return $value;
	}
}

function add_sfsetting($setting_name, $setting_value = '')
{
	global $wpdb;

	$check = get_sfsetting($setting_name);
	if($check == -1)
	{
		$setting_name = $wpdb->escape($setting_name);
		$setting_value = $wpdb->escape($setting_value);
		$wpdb->query("INSERT INTO ".SFSETTINGS." (setting_name, setting_value, setting_date) VALUES ('$setting_name', '$setting_value', now())");
		$wpdb->flush;
	} else {
		update_sfsetting($setting_name, $setting_value);
	}
	return;
}

function update_sfsetting($setting_name, $setting_value) 
{
	global $wpdb;

	if (is_string($setting_value)) $setting_value = trim($setting_value);

	// If the new and old values are the same, no need to update.
	$oldvalue = get_sfsetting($setting_name);
	if ($setting_value == $oldvalue) 
	{
		return false;
	}

	if (($oldvalue == -1) || (empty($oldvalue)))
	{
		add_sfsetting($setting_name, $setting_value);
		return true;
	}

	$setting_value = $wpdb->escape($setting_value);
	$setting_name = $wpdb->escape($setting_name);
	$wpdb->query("UPDATE ".SFSETTINGS." SET setting_value = '$setting_value', setting_date = now() WHERE setting_name = '$setting_name'");
	if($wpdb->rows_affected == 1) 
	{
		return true;
	}
	return false;
}

function delete_sfsetting($setting_name) 
{
	global $wpdb;
	// Get the ID, if no ID then return
	$setting_id = $wpdb->get_var("SELECT setting_id FROM ".SFSETTINGS." WHERE setting_name = '$setting_name'");
	if (!$setting_id) return false;
	$wpdb->query("DELETE FROM ".SFSETTINGS." WHERE setting_name = '$setting_name'");
	return true;
}

function  sf_clean_settings()
{
	global $wpdb;
	$wpdb->query("DELETE FROM ".SFSETTINGS." WHERE setting_date < DATE_SUB(CURDATE(), INTERVAL 24 HOUR) AND setting_name <> 'membercount' AND setting_name <> 'maxonline';");
// 2.1. Patch 2
	$wpdb->query("DELETE FROM ".SFSETTINGS." WHERE setting_name='membercount' AND setting_value < 1;");
	return;
}

//== Search String handlers

function sf_construct_search_parameter($term, $type)
{
	$newterm = str_replace(' ', '%', $term);
	$newterm = str_replace("'", "", $newterm);
	$newterm = str_replace('"', '', $newterm);
	$newterm .= '%'.$type;
	return $newterm;
}

function sf_deconstruct_search_parameter($term)
{
	$newterm = str_replace('%', ' ', $term);
	$newterm = substr($newterm, 0, strlen($newterm)-2);
	return $newterm;
}

function sf_deconstruct_search_for_display($term)
{
	global $wpdb;
	
	if(substr($term, 0, 12) == "sf%members%1")
	{
		$items=explode('%', $term);
		$id = substr($items[3], 4, 25);

		if($id == ADMINID)
		{
			$name = ADMINNAME;
		} else {
			$name = $wpdb->get_var("SELECT user_login from ".SFUSERS." where ID = ".$id);
		}
		$newterm = "Topics in which ".$name." has posted";
	} else {
		$newterm = sf_deconstruct_search_parameter($term);
	}
	return $newterm;
}

function sf_construct_search_term($term)
{
	// get the search type from end of string
	$type = substr($term, -1, 1);
	
	// get the search terms(s)
	$term = sf_deconstruct_search_parameter($term);
	
	switch($type)
	{
		case 1:
			$searchterm = $term;
			break;
			
		case 2:
			$term = str_replace(' ', ' +', $term);
			$searchterm.= '+'.$term;
			break;
			
		case 3:
			$searchterm = '"'.$term.'"';
			break;
	}
	return $searchterm;
}

function sf_deconstruct_search_term($term)
{
	$searchterms = array();
	
	// get the search type from end of string
	$type = substr($term, -1, 1);
	
	// get the search terms(s)
	$term = sf_deconstruct_search_parameter($term);

	if($type == 3)
	{
		// return term as is in 0 array
		$searchterms[0] = $term;
	} else {
		// for type 1 or 2 send back word or array of words
		$searchterms = explode(' ', $term);
	}
	return $searchterms;
}

//== Browser check

function sf_is_safari()
{
	$pos = strpos($_SERVER['HTTP_USER_AGENT'], 'AppleWebKit');
	if($pos === false)
	{
		return false;
	} else {
		$kit = substr($_SERVER['HTTP_USER_AGENT'], $pos+12, 3);
		if($kit >= 522)
		{
			return false;
		} else {
			return true;
		}
	}
}

//== Escaping for HTML attributes

function sf_attribute_escape($text) 
{
	$safe_text = wp_specialchars($text, true);
	return apply_filters('sf_attribute_escape', $safe_text, $text);
}

//== Filter data for rss output - note directly echo's

function sf_rss_filter($text)
{
  echo convert_chars(ent2ncr($text));
}

//== Fix length of content for RSS

function sf_rss_excerpt($text)
{
	$max=get_option('sfrsswords');
	if($max == 0) return $text;
	$bits=explode(" ", $text);
	$text='';
	$end='';
	if(count($bits) < $max) 
	{
		$max=count($bits);
	} else {
		$end='...';
	}
	$text="";
	for($x=0; $x<$max; $x++)
	{
		$text.=$bits[$x].' ';
	}
	return $text.$end;
}

//== General Message Display Routine

function sf_message($message)
{
	echo '<div class="sfmessage">' . $message . '</div>'."\n";
	return;
}

//== Javascript check

function sf_js_check()
{
	return '<noscript><div class="sfmessage">'.__("This forum requires Javascript to be enabled for posting content", "sforum").'</div></noscript>'."\n";
}

//== Cookie Handling

function sf_get_cookie()
{
	$guest = array();
	if(isset($_COOKIE['guestname_'.COOKIEHASH])) $guest['name'] = $_COOKIE['guestname_'.COOKIEHASH];
	if(isset($_COOKIE['guestemail_'.COOKIEHASH])) $guest['email'] = $_COOKIE['guestemail_'.COOKIEHASH];
	if(isset($_COOKIE['sflast_'.COOKIEHASH])) $guest['last'] = $_COOKIE['sflast_'.COOKIEHASH];

	return $guest;
}

//== Spam Maths

function sf_math_spam_build()
{
	$spammath[0] = rand(1, 12);
	$spammath[1] = rand(1, 12);

	// Calculate result
	$result = $spammath[0] + $spammath[1];

	// Add name of the weblog:
	$result .= get_bloginfo('name');
	// Add date:
	$result .= date(j) . date('ny');
	// Get MD5 and reverse it
	$enc = strrev(md5($result));
	// Get only a few chars out of the string
	$enc = substr($enc, 26, 1) . substr($enc, 10, 1) . substr($enc, 23, 1) . substr($enc, 3, 1) . substr($enc, 19, 1);

	$spammath[2] = $enc;
	
	return $spammath;
}

function sf_spamcheck()
{
	$spamcheck = array();
	$spamcheck[0]=false;

	// Check dummy input field
	if(!empty($_POST['url']))
	{
		$spamcheck[0]=true;
		$spamcheck[1]= __('Form not filled by human hands!', "sforum");
		return $spamcheck;
	}

	// Check math question
	$correct = $_POST['sfvalue2'];
	$test = $_POST['sfvalue1'];
	$test = preg_replace('/[^0-9]/','',$test);

	if($test == '')
	{
		$spamcheck[0]=true;
		$spamcheck[1]= __('No answer was given to the math question', "sforum");
		return $spamcheck;
	}

	// Add name of the weblog:
	$test .= get_bloginfo('name');
	// Add date:
	$test .= date(j) . date('ny');
	// Get MD5 and reverse it
	$enc = strrev(md5($test));
	// Get only a few chars out of the string
	$enc = substr($enc, 26, 1) . substr($enc, 10, 1) . substr($enc, 23, 1) . substr($enc, 3, 1) . substr($enc, 19, 1);
	
	if($enc != $correct)
	{
		$spamcheck[0]=true;
		$spamcheck[1]= __('The answer to the math question was incorrect', "sforum");
		return $spamcheck;
	}
	return $spamcheck;
}

function sf_charset()
{
	global $wpdb;

	$charset_collate = '';

	if ( version_compare(mysql_get_server_info(), '4.1.0', '>=') ) 
	{
		if ( ! empty($wpdb->charset) )
		{
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if ( ! empty($wpdb->collate) )
		{
			$charset_collate .= " COLLATE $wpdb->collate";
		}
	}

	return $charset_collate;
}

?>