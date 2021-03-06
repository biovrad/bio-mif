<?php
require_once(FS_ABS_PATH.'/php/browsniff.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
require_once(FS_ABS_PATH.'/php/fs-gettext.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
require_once(FS_ABS_PATH.'/lib/utf8-encoder/utf8-encoder.php');

function fs_create_anchor($anchor)
{
	$link = fs_r("Link here");
	echo "<a class='anchor' name='$anchor' href='#$anchor' title='$link'></a>";
}

function fs_create_wiki_help_link($page_name, $width = 600, $height=600)
{
	echo fs_get_wiki_help_link($page_name, $width, $height);
}

function fs_get_wiki_help_link($page_name, $width = 600, $height=600)
{
	$img = fs_url("img/help.blue.png");
	$page = FS_WIKI.$page_name;
	$open = sprintf("window.open ('%s', 'newwindow','height='+%s+',width='+%s+',toolbar=no,menubar=no,location=no, directories=no, status=no,resizable=yes,scrollbars=yes');",$page, $width,$height);
	return "<input type='image' title='$page' class='img_btn' src=\"$img\" onclick=\"$open\"/>";
}


function fs_create_help_button($tooltip, $javascript, $width = 600, $height=600)
{
	$img = fs_url("img/help.blue.png");
	echo "<input type='image' title='$tooltip' class='img_btn' src=\"$img\" onclick=\"$javascript\"/>";
}

function fs_create_checkbox($data_id, $text, $default = 'false', $system_option = false)
{
	echo fs_get_checkbox($data_id, $text, $default, $system_option);
}

function fs_get_checkbox($data_id, $text, $default = 'false', $system_option = false)
{
	$current_value = $system_option ? fs_get_system_option($data_id, $default) : fs_get_option($data_id, $default);
	$current_value = $current_value == 'true' ? "checked=\"checked\"" : "";
	$save = $system_option ? "saveSystemOption" : "saveOption";
	return "<input type='checkbox'
			onclick='$save(\"$data_id\",\"$data_id\",\"boolean\")' 
			id='$data_id' $current_value/><label for='$data_id'>$text</label>";
}

function fs_create_textfield($id, $update, $type, $initial_value = "", $size = 4, $localOption = false)
{
	echo fs_get_textfield($id, $update, $type, $initial_value, $size, $localOption);
}

function fs_get_num_textfield($id, $update, $initial_value = "", $size = 4, $localOption = false)
{
	return fs_get_textfield($id, $update, 'positive_integer', $initial_value, $size, $localOption);
}

function fs_get_textfield($id, $update, $type, $initial_value = "", $size = 4, $localOption = false)
{
	$q = "\\\""; // inner inner quote
	if ($localOption)
	{
		$save = "\"saveLocalOption($q$id$q,$q$id$q,$q$type$q,$q$update$q)\"";
	}
	else
	{
		$save = "\"saveOption($q$id$q,$q$id$q,$q$type$q,$q$update$q)\"";
	}
	return "<input type='text' onkeypress='return FS.trapEnter(event, $save);' size='$size' id='$id' value='$initial_value'/>";
}

function fs_get_date_selector_button($id, $select_js_callback = null)
{
	$img =  fs_url("img/date.png");
	return "<input id='$id' type='image' class='img_btn' src='$img'/>".fs_get_calendar_setup($id, $select_js_callback);
}

function fs_get_date_selector_text($id, $select_js_callback = null)
{
	$value = fs_get_option($id, '');
	return "<input id='$id' type='text' readonly='readonly' value='$value' class='calendar_input'/>".fs_get_calendar_setup($id, $select_js_callback);
}

function fs_get_calendar_setup($id, $select_js_callback = null)
{
	$value = fs_get_option($id, "");
	return "<script type='text/javascript'>
	Calendar.setup(
	{
		inputField  : '$id', 				// ID of the input field
		ifFormat    : '%d/%m/%Y',    		// the date format
		button      : '$id'  				// ID of the button
		".($select_js_callback ? ",onSelect : $select_js_callback" : "")."
	})
</script>";
}

function fs_create_date_selector_text($id, $select_js_callback = null)
{
	echo fs_get_date_selector_text($id, $select_js_callback);
}

function fs_create_dropbox($items, $selected, $key, $onchange, $disabled = false)
{
	$dis = $disabled ? "disabled='disabled'" : "";
	$res = "<select id='$key' onchange=\"$onchange\" $dis>";
	foreach($items as $d)
	{
		$name = $d->msg;
		$value = $d->d;
		$res .= "<option value='$value'".($value == $selected ? " selected='selected'" : "").">$name</option>";
	}
	$res .= "</select>";
	return $res;
}

function fs_cfg_button($toggle_id)
{
	?>
<input
	type='image' class="img_btn config_img"
	src='<?php echo fs_url("img/configure.png")?>'
	onclick="toggle_div_visibility('<?php echo $toggle_id?>')" />
	<?php
}

function fs_get_whois_options()
{
	$selected = fs_get_option('whois_provider','');
	$res = "";
	$providers = fs_get_whois_providers();
	foreach($providers as $p=>$v)
	{
		$res .= "<option ".($p == $selected ? "selected=\"selected\" " : "")."value='$p'>$p</option>";
	}
	return $res;
}

function fs_get_users_manage_table()
{
	$users = fs_get_users();
	$res = "<table>";
	$nameH = fs_r('Name');
	$emailH = fs_r("Email");
	$sec = fs_r("Security level");
	$res .= "<tr>
		<th style='width:30%'>$nameH</th>
		<th style='width:30%'>$emailH</th>
		<th style='width:20%'>$sec</th>
		<th style='width:20%'></th>
		</tr>";
	if ($users === false)
	{
		echo fs_db_error();
	}
	else
	if (count($users) > 0)
	{
		foreach($users as $user)
		{
			$id = $user->id;
			$name = $user->username;
			$email = $user->email;
			$sec = $user->security_level;
			if ($sec == SEC_ADMIN)
			{
				$secStr = fs_r("Admin");
			}
			else
			if ($sec == SEC_USER)
			{
				$secStr = fs_r("User");
			}
			else
			{
				$secStr = fs_r("Unknown");
			}
			
			$text = "<tr id='user_row_$id'>
				<td title='$id' id='user_name_$id'>$name</td>
				<td id='user_email_$id'>$email</td>
				<td id='user_sec_$id'>$secStr</td>
				<td>
				<input type='image' class='img_btn' src='".fs_url("img/edit.png")."' onclick='FS.editUserDialog(\"$id\")'/>
				<input type='image' class='img_btn' src='".fs_url("img/delete.png")."' onclick='FS.deleteUserDialog(\"$id\")'/>
				</td>
				</tr>";
			$res .= "$text\n";
		}
	}
	
	$add_new = fs_r('Add a new user');
	$res .= "<tr><td colspan='4'>
		<input type='image' class='img_btn' src='".fs_url("img/add.png")."' onclick='FS.newUserDialog()'/>
			$add_new
		</td></tr></table>";
	
	return $res;	
}

function fs_get_sites_manage_table()
{
	$sites_table_name_filter = fs_get_option("sites_table_name_filter", "");
	$page_dropdown = fs_r("Page") . " : " . fs_create_sites_page_dropdown();
	$page_number = fs_get_option("current_selected_site_page", "0");
	$sites = fs_get_sites_page($page_number, $sites_table_name_filter);
	
	if ($sites === false) return fs_db_error();
	$res = "<table>";
	$idH = fs_r('ID');
	$nameH = fs_r('Name');
	$typeH = fs_r("Type");
	$views = fs_r("Page views");
	$res .= "<tr>
		<th style='width:30px'>$idH</th>
		<th style='width:200px'>$nameH</th>
		<th style='width:160px'>$typeH</th>
		<th style='width:60px'>$views</th>
		<th style='width:80px'>$page_dropdown</th>
		</tr>";
	if (count($sites) > 0)
	{
		foreach($sites as $site)
		{
			$id = $site->id;
			$hits = fs_get_hit_count(null, $id);
			$name = $site->name;
			$type = $site->type;
			$typeStr = fs_get_site_type_str($type);
			$tip = fs_r('How to integrate with this site');
			$text = "<tr id='site_row_$id'>
				<td id='site_id_$id'>$id</td>
				<td id='site_name_$id'>$name</td>
				<td id='site_type_$id'>$typeStr</td>
				<td id='site_hits_$id'>$hits</td>
				<td>
				<input type='image' class='img_btn' src='".fs_url("img/edit.png")."' onclick='FS.editSiteDialog(\"$id\")'/>
				<input type='image' class='img_btn' src='".fs_url("img/delete.png")."' onclick='FS.deleteSiteDialog(\"$id\")'/>
				<input type='image' class='img_btn' title='$tip' src='".fs_url("img/help.blue.png")."' onclick='FS.activationHelp($type,$id)'/>
				</td>
				</tr>";
			$res .= "$text\n";
		}
	}

	$orphans = fs_get_orphan_site_ids();
	if ($orphans === false)
	{
		$res .= fs_db_error();
	}
	else
	{
		if (count($orphans) > 0)
		{
			foreach($orphans as $site)
			{
				$id = $site->id;
				$hits = fs_get_hit_count(null, $id);
				$name = fs_r("Orphaned hits");
				$text = "<tr id='site_row_$id'>
					<td id='site_id_$id'>$id</td>
					<td id='site_name_$id'>$name</td>
					<td id='site_type_$id'></td>
					<td id='site_hits_$id'>$hits</td>
					<td></td>
					</tr>";
				$res .= "$text\n";
			}
		}
	}
	
	$num_sites = fs_get_num_sites($sites_table_name_filter);
	
	$num_pages = ceil($num_sites / SITES_TAB_MAX_SITES_PER_PAGE);
	$next_page_num = $page_number + 1;
	$prev_page_num = $page_number - 1;
	$page_str = $num_pages > 0 ? sprintf(fs_r("Page %s of %s"), $page_number+1, $num_pages) : "";
	$prev_style = $prev_page_num < 0  		   ? "style='display:none'" : "style=''";
	$next_style = $next_page_num >=$num_pages ? "style='display:none'" : "style=''"; 
	$prev = "<input id='prev_sites_page' $prev_style type='image' class='img_btn' src='".fs_url("img/prev.png")."' onclick='FS.setSitesTablePageNumber($prev_page_num)'/>";
	$next = "<input id='prev_sites_page' $next_style type='image' class='img_btn' src='".fs_url("img/next.png")."' onclick='FS.setSitesTablePageNumber($next_page_num)'/>";
	
	$res .=  '<tr><td colspan="2">
		<input id="new_site_button" type="image" class="img_btn" src="'.fs_url("img/add.png").'"onclick="FS.newSiteDialog()"/>  
		<label for="new_site_button">'.fs_r('Add a new site').'</label>
	</td>
	<td>'.
		$page_str
	.'</td>
	<td'.
	$prev 
	.'</td>
	<td'.
	$next 
	.'</td>
	</tr>';
	$res .= "</table>";




	return $res;

}

function fs_create_sites_page_dropdown()
{
	$selected = fs_get_option("current_selected_site_page", "0");
	$sites_table_name_filter = fs_get_option("sites_table_name_filter", "");
	$num_sites = fs_get_num_sites($sites_table_name_filter);
	$num_pages = ceil($num_sites / SITES_TAB_MAX_SITES_PER_PAGE);
	if ($selected >= $num_pages)
	{
		fs_update_option("current_selected_site_page", 0);
		$selected = 0;
	}
	$arr = array();
	for($i = 0;$i < $num_pages;$i++)
	{
		$start = $i * SITES_TAB_MAX_SITES_PER_PAGE + 1;
		if ($start > $num_sites) break;
		$end = min( ($i+1) * SITES_TAB_MAX_SITES_PER_PAGE, $num_sites);
		$arr[] = fs_mkPair($i, $i+1);
	}
	$onchange = 'FS.setSitesTablePageNumber($F(\'current_selected_site_page_dropbox\'))';
	return fs_create_dropbox($arr,$selected,'current_selected_site_page_dropbox',$onchange);	
}

function fs_get_site_type_options($selected = null)
{
	$a = array();
	$a[] = FS_SITE_TYPE_GENERIC;
	$a[] = FS_SITE_TYPE_HTML;
	$a[] = FS_SITE_TYPE_WORDPRESS;
	$a[] = FS_SITE_TYPE_DJANGO;
	$a[] = FS_SITE_TYPE_DRUPAL;
	$a[] = FS_SITE_TYPE_GALLERY2;
	$a[] = FS_SITE_TYPE_GREGARIUS;
	$a[] = FS_SITE_TYPE_JOOMLA;
	$a[] = FS_SITE_TYPE_MEDIAWIKI;
	$a[] = FS_SITE_TYPE_TRAC;
	$res = '';
	foreach($a as $v)
	{
		$str = fs_get_site_type_str($v);
		$res .= "<option value='$v' ".($v == $selected ? 'selected="selected"' : '' ).">$str</option>";
	}

	return $res;
}

function fs_get_site_type_str($type)
{
	switch($type)
	{
		case FS_SITE_TYPE_GENERIC:
			return fs_r("Generic PHP site");
		case FS_SITE_TYPE_WORDPRESS:
			return fs_r("Wordpress");
		case FS_SITE_TYPE_DJANGO:
			return fs_r("Django");
		case FS_SITE_TYPE_DRUPAL:
			return fs_r("Drupal");
		case FS_SITE_TYPE_GREGARIUS:
			return fs_r("Gregarius");
		case FS_SITE_TYPE_JOOMLA:
			return fs_r("Joomla");
		case FS_SITE_TYPE_MEDIAWIKI:
			return fs_r("MediaWiki");
		case FS_SITE_TYPE_TRAC:
			return fs_r("Trac");
		case FS_SITE_TYPE_GALLERY2:
			return fs_r("Gallery2");
		case FS_SITE_TYPE_HTML:
			return fs_r("Generic HTML site");
	}
	return fs_r("Unknown");
}

function fs_get_timezone_list()
{
	$zones = file(FS_ABS_PATH.'/php/timezones.txt');
	$current_tz = fs_get_option('firestats_user_timezone','system');
	$res = '';
	foreach($zones as $zone)
	{
		$zone = trim($zone);
		if ($zone[0] == '#') continue;
		if ($current_tz != $zone)
		{
			$res .= "<option value='$zone'>$zone</option>\n";
		}
		else
		{
			$res .= "<option selected=\"selected\" value='$zone'>$zone</option>\n";
		}
	}
	return $res;
}

function fs_get_languages_list()
{
	// an array of files to ignore.
	// in case of a mistake in the locale name, after fixing the file name upgrading users will have
	// duplicate files.
	// files in the following array are excluded.
	$ignore = array("firestats-sw_SE.po");
	
	$current_lang = fs_get_option('current_language');
	$dir = FS_ABS_PATH.'/i18n';
	$dh  = opendir($dir);
	$res = '<option'.($current_lang == '' ? " selected=\"selected\"" : "").' value="en_US">English</option>';

	$list = array();
	while (false !== ($filename = readdir($dh)))
	{
		if (fs_ends_with($filename, '.po') && !in_array($filename, $ignore))		
		{
			$r = sscanf($filename,"firestats-%s");
			if (isset($r[0]))
			{
				$code = $r[0];
				$len = strlen($code);
				$code = substr($code, 0, $len - 3);
				$name = fs_get_lang_name($dir.'/'.$filename);
				$d = new stdClass;
				$d->valid = true;
				$d->code = $code;
				$d->lname = $name;
				$list[] = $d;
			}
			else
			{
				$d = new stdClass;
				$d->valid = false;
				$d->fname = $filename;
				$list[] = $d;
			}
		}
	}

	$foo = create_function('$a, $b',
	'
		if ($a->valid && $b->valid)
		{
			return strcmp($a->lname,$b->lname);
		}
		else
		{
			if (!$a->valid && !$b->valid) return 0;
			if ($a->valid && !$b->valid) return -1;
			if (!$a->valid && $b->valid) return 1;
			return 0;
		}
	');
	uasort($list,$foo);
	foreach($list as $lang)
	{
		if ($lang->valid)
		{
			$code = $lang->code;
			$name = $lang->lname;
			$res .= "<option value='$code'".($current_lang == $code ? " selected='selected'" : "").">$name</option>";
		}
		else
		{
			$filename = $lang->fname;
			$res .= "<option>".fs_r('Invalid').": $filename"."</option>";
		}
	}

	return $res;
}

function fs_get_sites_list()
{
	$user_id = fs_current_user_id();
	if ($user_id === false) return "ERROR : unknown user_id";

	$selector_mode = fs_site_selector_mode();
	
	$user_sites = fs_get_user_sites_array($user_id);
	if ($selector_mode == FS_SITE_SELECTOR_MODE_BY_USERS_SITES_TABLE)
	{
		if (count($user_sites) == 0)
		{
			return fs_r("You are not allowed to view the statistics of any site, please contact your system administrator");
		}
		else
		{
			if ($user_sites[0] == -1) // special case, allowed to access all sites.
			{
				$site_ids = null;		
			}
			else
			{
				$site_ids = fs_implode(",", $user_sites);
			}
		}
	}
	else
	if ($selector_mode == FS_SITE_SELECTOR_MODE_ADMIN_ONLY)
	{
		if (fs_is_admin()) 
		{
			$site_ids = null; // meaning all sites
		}
		else
		{
			return ""; // don't even show the selector
		}
	}
	$sites= fs_get_sites($site_ids);
	if (count($sites) < 2) return '';

	$all = fs_r("All");
	$str = fs_r('Show statistics from')."<select id='sites_filter' onchange='FS.updateSitesFilter()'>";
	$str .= "<option value='all'>$all</option>";
	$selected_site = fs_get_local_option('firestats_sites_filter');

	foreach($sites as $site)
	{
		$selected = "";
		$id = $site->id;
		$name = $site->name;
		if ($id == $selected_site) $selected = "selected='selected'";
		$str .= "<option $selected value='$id'>$name</option>";
	}
	$str .= "</select>";
	return $str;
}

// replace special url character with xml friendly escape codes.
function fs_xmlentities ( $string )
{
	return str_replace ( array ( '&', '"', "'", '<', '>'),
	array ( '&amp;' , '&quot;', '&apos;' , '&lt;' , '&gt;'),
	$string );
}

function fs_format_link($url, $link_text = null, $max_length = null, $break_at = 30, $tooltip = null)
{
	if ($url == "unknown")
	{
		return fs_r('unknown');
	}
	else
	{
		// if the url is relative, make it absoulte.
		$full_url = fs_get_absolute_url($url);
		$relative = fs_get_relative_url($full_url);
		if ($link_text == null)
		{
			$link_text = $relative;
		}
		if (!fs_is_utf8_string($link_text))
			$link_text = urlencode($link_text);

		// for the display, use the relative and line splited version.
		$text = fs_prepare_string($link_text, $break_at, "<br/>",$max_length);
		// ' tends to mess up the url, encode it. (not using full urlencode because this really makes a mess in this case).
		$url = str_replace (array ( '\''),array ( '%27'),$full_url);
		if (!$tooltip)
		{
			return "<a target='_blank' href='$url'>$text</a>";
		}
		else
		{
			return "<a target='_blank' title='$tooltip' href='$url'>$text</a>";
		}
	}
}

function fs_get_referer_link($entry)
{
	$url = $entry->referer;
	if ($entry->search_terms != null)
	{
		require_once(FS_ABS_PATH.'/php/searchengines.php');
		$imgtag = fs_get_search_engine_img_tag(fs_get_search_engines(true),$entry->search_engine_id);

		$st = $entry->search_terms; 
		if (!fs_is_utf8_string($st))
			$st = urlencode($st);

		return "<a target='_blank' href='$url'>$imgtag $st</a>";
	}
	else
	{
		$text = $entry->referrer_title;
		return fs_format_link($url, $text, 150, 30);
	}
}

function fs_get_url_link($entry)
{
	return fs_format_link($entry->url, $entry->url_title, 150);
}

function fs_get_current_whois_provider_url()
{
	$name = fs_get_option('whois_provider','ARIN');
	$providers = fs_get_whois_providers();
	return isset($providers[$name]) ? $providers[$name] : '';
}

function fs_get_whois_link($entry)
{
	$ip = fs_ip_to_string($entry->ip_int1, $entry->ip_int2);
	// if provider is not specified just return the ip address as is.
	$whois = fs_get_current_whois_provider_url();
	if (empty($whois)) return $ip;
	$url = sprintf("$whois",$ip);
	return "<a target='_blank' href='$url'>$ip</a>";
}

function fs_get_records_table()
{
	require_once(dirname(__FILE__).'/ip2country.php');
	$res = "";
	$entries = fs_getentries();
	if ($entries === false)
	{
		return fs_db_error();
	}
	else
	if ($entries)
	{

		$i = 0;
		$res =
		'<table>
	<thead>
		<tr>
			<td class="records_table_row2">'.fs_r('IP')   	 	.'</td>
			<td class="records_table_row3">'.fs_r('TimeStamp')	.'</td>
			<td class="records_table_row4">'.fs_r('URL')      	.'</td>
			<td class="records_table_row5">'.fs_r('Referrer')	.'</td>
			<td class="records_table_row6">'.fs_r('Image')    	.'</td>
			<td class="records_table_row7">'.fs_r('UserAgent')	.'</td>
		</tr>
	</thead>
	<tbody>';

		foreach ($entries as $entry)
		{
			$i++;
			$res .=
			'<tr'.($i%2 ? ' class="table_alternate_bg"' : "").'>
			<td class="records_table_row2">'.fs_get_whois_link($entry).'</td>
			<td class="records_table_row3">'.$entry->time.'</td>
			<td class="records_table_row4">'.fs_get_url_link($entry).'</td>
			<td class="records_table_row5">'.fs_get_referer_link($entry).'</td>
			<td class="records_table_row6">'.fs_pri_browser_images($entry->useragent).fs_get_country_flag_url($entry->country_code, true).'</td>
			<td class="records_table_row7">'.fs_prepare_string($entry->useragent, 50).'</td>
		</tr>';
		} // for loop
	}
	else
	{
		$res .= fs_r('No data yet, go get some hits');
	}

	$res .=
	'	</tbody>
</table>';
	return $res;
}

function fs_wordwrap($text,$break_at,$sep)
{
	if ($break_at <= 0) die("none positive break point");
	$nbytes = strlen($text);
	$x = 0;
	$char_num = 0;
	$res = "";
	while($x < $nbytes)
	{
		$w = fs_next_utf8_char_width($text,$x);
		if ($w == -1) $w = 1;
		$res .= substr($text, $x, $w);
		$x += $w;
		$char_num++;
		if ($char_num == $break_at) 
		{
			$res .= $sep;
			$char_num = 0;
		}
	}
	return $res;
}

function fs_prepare_string($text, $break_at = null, $newline = "<br/>", $max_length = null)
{
	$dec = urldecode($text);
	if (fs_is_utf8_string($dec))
		$text = $dec;
	
	$break = $break_at != null;
	if ($break)
	{
		// since encode will encode our line breaks if we insert it now
		// we are doing a little trick here:
		// first put a place holder for the line break
		$text = fs_wordwrap($text, $break_at,"{_SEP_}");
	}

	if ($max_length != null)
	{
		if (strlen($text) > $max_length)
		{
			$text = substr($text,0, $max_length);
			$text .= "...";
		}
	}

	// fix up any magic characters in the url.
	$text = str_replace (array ( '<', '>'),array ( '&lt;' , '&gt;'),$text);
	if ($break)
	{
		// now we can replace the \255 by a line break.
		$text = str_replace(array("{_SEP_}"),array($newline),$text);
	}
	return $text;
}

function fs_get_browsers_tree($days_ago = NULL)
{
	if (!$days_ago) $days_ago = fs_browsers_tree_days_ago();
	return fs_get_stats_tree(fs_get_browser_statistics($days_ago),'browsers_tree_id');
}

function fs_get_os_tree($days_ago = NULL)
{
	if (!$days_ago) $days_ago = fs_os_tree_days_ago();
	return fs_get_stats_tree(fs_get_os_statistics($days_ago),'os_tree_id');
}

function fs_get_stats_tree($stats, $id)
{
	if ($stats === false)
	{
		return fs_db_error();
	}
	if (is_string($stats))
	{
		return "<div id='$id'>$stats</div>";
	}
	$stats_data = $stats;

	$res = "<div id='$id'>";
	if (!$stats_data) // no data yet
	{
		$res .= fs_r('No data yet, go get some hits');
	}
	else
	{
		foreach ($stats_data as $code => $stats)
		{
			$img=isset($stats['image']) ? $stats['image'] : "";
			$name=$stats['name'];
			$count=$stats['count'];

			$browser_percent=sprintf("%.1f",$stats['percent']);
			$res .= "<ul class=\"mktree\">";
			$res .= "<li>$img $name <b>$browser_percent%</b>";
			$res .= "<ul>";
			$sublist = $stats['sublist'];
			if ($sublist == null) continue;
			foreach($sublist as $ver => $vstats)
			{
				if ($code == 'others')
				{
					$others = $vstats['sublist'];
					foreach($others as $okey => $other)
					{
						//var_dump($other);
						$img=isset($other['image']) ? $other['image'] : "";
						$name=$other['name'];
						$ua = fs_prepare_string($other['useragent']);
						$version_percent=sprintf("%.1f",$other['percent']);
						$res .= "<li>$img $name $okey <b>$version_percent%</b>";
						$res .="<ul><li>$ua</li></ul>";
						$res .= "</li>\n";
					}
				}
				else
				{
					$ua = fs_prepare_string($vstats['useragent']);
					$version_percent=sprintf("%.1f",$vstats['percent']);
					$res .= "<li>$img $name $ver <b>$version_percent%</b>";
					$res .="<ul><li>$ua</li></ul>";
					$res .= "</li>\n";
				}
			}
			$res .= "</ul>";
			$res .= "</li>";
			$res .= "</ul>\n";
		}
	}

	$res .= "</div>";

	return $res;
}

function fs_get_excluded_ips_list()
{
	$list = fs_get_excluded_ips();
	if ($list === false) return fs_db_error();
	$c = count($list);
	$res = "<select multiple class='full_width' size='10' id='exclude_ip_table' ".($c == 0 ? "disabled=\"disabled\"" : "")." >";
	if ($c == 0)
	{
		$res .= "	<option>".fs_r('Empty')."</option>";
	}
	else
	{
		foreach ($list as $row)
		{
			$start_ip = fs_ip_to_string($row->start_ip1,$row->start_ip2);
			$end_ip = fs_ip_to_string($row->end_ip1,$row->end_ip2);
			if (!strcmp($start_ip,$end_ip))
			{
				$ip = $start_ip;
			}
			else
			{
				$ip = "$start_ip - $end_ip";
			}
			$res .="	<option value='$row->id'>$ip</option>\n";
			
		}
	}
	$res .= "</select>\n";
	return $res;
}

function fs_get_excluded_urls_list()
{
	$list = fs_get_excluded_urls();
	if ($list === false) return fs_db_error();
	$c = count($list);
	$res = "<select class='full_width' multiple size='10' id='exclude_urls_table' ".($c == 0 ? "disabled=\"disabled\"" : "")." >";
	if ($c == 0)
	{
		$res .= "	<option>".fs_r('Empty')."</option>";
	}
	else
	{
		foreach ($list as $row)
		{
			$res .="<option value='$row->id'>$row->url_pattern</option>\n";
		}
	}
	$res .= "</select>\n";
	return $res;
}

function fs_get_excluded_users_list()
{
	$users = fs_wp_get_users();
	if ($users === false) return fs_db_error();
	$excluded_users=explode(",",fs_get_local_option('firestats_excluded_users',''));
	$c = count($users);
	$res = '';
	if ($c == 0)
	{
		$res .= fs_r('Empty');
	}
	else
	{
		foreach($users as $u)
		{
			$user_id = $u['id'];
			$user_name = $u['name'];
			$in = in_array($u['id'],$excluded_users);
			$checked = $in ? "checked='checked'"  : "";
			$res .= "<input type='checkbox' onclick='updateExcludedUsers(this,$user_id)' id='wordpress_user_$user_id' $checked/><label for='wordpress_user_$user_id'>$user_name</label><br/>";
		}
	}
	return $res;

}

function fs_get_bot_list()
{
	$list = fs_get_bots();
	if ($list === false) return fs_db_error();
	$c = count($list);
	$res = "<select multiple class='full_width' size='10' id='botlist' ".($c == 0 ? "disabled='disabled'" : "")." >";
	if ($c == 0)
	{
		$res .= '<option>'.fs_r('Empty').'</option>';
	}
	else
	{
		foreach ($list as $row)
		{
			$res .= "<option value='".$row['id']."'>".$row['wildcard']."</option>";
		}
	}
	$res .= "</select>";
	return $res;
}

function fs_get_search_engine_img_tag(&$engines_ht,$engine_id)
{
	if (isset($engines_ht[$engine_id]))
	{
		$engine = $engines_ht[$engine_id];
		$name = $engine->name;
		$img = fs_url("img/engines/".$engine->logo_icon);
		return "<img src='$img' alt='$name image' title='$name' width='16' height='16'/>";
	}
	else return '';
}

function fs_get_search_term_breakdown($id,$search_term)
{
	$max_num = fs_get_max_search_terms();
	$search_terms_date_type = fs_get_option("search_terms_date_type",90);
	
	if (is_numeric($search_terms_date_type))
	{
		$terms = fs_get_recent_search_terms($max_num, $search_terms_date_type,$search_term);
	}
	else
	if ($search_terms_date_type == 'ever')
	{
		$terms = fs_get_recent_search_terms($max_num, null,$search_term);
	}
	else
	if ($search_terms_date_type == 'time_range')
	{
		$start_date = fs_format_sql_date("_search_terms_start");
		if (!$start_date) return "missing search_terms_start";
		$end_date = fs_format_sql_date("search_terms_end");
		if (!$end_date) return "missing search_terms_end";
		$terms = fs_get_recent_search_terms_range($max_num, true, strtotime($start_date),strtotime($end_date),true,ORDER_BY_HIGH_COUNT_FIRST,$search_term);
	}
	else
	{
		return "ERROR: Unexpected search_terms_date_type";
	}

	$id = "search_term_$search_term";
	$res = "\n";

	if ($terms !== FALSE && count($terms) > 0)
	{
		require_once(FS_ABS_PATH.'/php/searchengines.php');
		$engines_ht = fs_get_search_engines(true);
		foreach ($terms as $line)
		{
			$imgtag = fs_get_search_engine_img_tag($engines_ht,$line->search_engine_id);
			$terms = $line->search_terms;
			if (!fs_is_utf8_string($terms))
				$terms = urlencode($terms);
			$terms = fs_format_link($line->referer, $terms, null, null);
			$search_terms = "<span>$terms</span> <span>(<b>&lrm;$line->c</b>)</span>";
			$res .= "<li class='liBullet'><span class='bullet'/>$imgtag $search_terms</li>";
		}
	}
	else
	{
		if ($terms === FALSE)
		{
			$res .= fs_db_error();
		}
		else
		{
			$res .= fs_r('No data yet, go get some hits');
		}
	}
	return $res."\n";
}

function fs_get_search_terms_tree()
{
	$error = '';
	$max_num = fs_get_max_search_terms();
	$search_terms_date_type = fs_get_option("search_terms_date_type",90);
	if (is_numeric($search_terms_date_type))
	{
		$terms = fs_get_recent_search_terms($max_num, $search_terms_date_type);
	}
	else
	if ($search_terms_date_type == 'ever')
	{
		$terms = fs_get_recent_search_terms($max_num, null);
	}
	else
	if ($search_terms_date_type == 'time_range')
	{
		$start_date = fs_format_sql_date("search_terms_start");
		$end_date = fs_format_sql_date("search_terms_end");
		if (!$end_date || !$start_date)
		{
			if (!$start_date)
			{
				$error = fs_r("Start date is missing");
			}
			else
			if (!$end_date)
			{
				$error = fs_r("End date is missing");
			}
		}
		else
		{
			$terms = fs_get_recent_search_terms_range($max_num, true, strtotime($start_date),strtotime($end_date), true);
		}
	}
	else
	{
		$error = "ERROR: Unexpected search_terms_date_type";
	}

	$res ="<div id='search_terms_tree_id'>\n";

	if ($error == '' && (isset($terms) && $terms !== false) && count($terms) > 0)
	{
		require_once(FS_ABS_PATH.'/php/searchengines.php');
		$engines_ht = fs_get_search_engines(true);

		$please_wait = fs_r("Please wait...");
		$res .= "<ul class='mktree'>\n";
		foreach ($terms as $line)
		{
			$imgtag = fs_get_search_engine_img_tag($engines_ht,$line->search_engine_id);
			$terms = fs_format_link($line->referer, $line->search_terms, null, null);
			$search_terms = "$imgtag <span>$terms</span> <span>(<b>&lrm;$line->c</b>)</span>";

			if ($line->num_engines > 1)
			{
				$id = "search_term_$line->search_terms";
				$pid = "parent_search_term_$line->search_terms";
				$res .= "<li id='$pid' onclick='FS.fs_get_search_terms_engines_breakdown(\"$id\")'>$search_terms
							<ul id='$id'>
								<li>$please_wait</li>
							</ul>
						</li>\n";
			}
			else
			{
				$res .= "<li>$search_terms</li>\n";
			}
		}
		$res .= "</ul>\n";
	}
	else
	{
		if ($error != '')
		{
			$res .= $error;
		}
		else
		if ($terms === FALSE)
		{
			$res .= fs_db_error();
		}
		else
		{
			$res .= fs_r('No data yet, go get some hits');
		}
	}
	$res .= "</div>\n";
	return $res;
}

function fs_get_recent_referers_table($max_num = null, $days_ago = null, $site_id = true, $order_by = null)
{
	if (!$max_num)
	{
		$max_num = fs_get_max_referers_num();
	}
	if (!$days_ago)
	{
		$days_ago = fs_get_recent_referers_days_ago();
	}
	
	if (!$order_by)
	{
		$order_by = fs_get_option("recent_referrers_order_by", ORDER_BY_FIRST_SEEN);
	}
	
	$show_days_ago = $order_by == ORDER_BY_FIRST_SEEN;
	
	$refs = fs_get_recent_referers($max_num, $days_ago, $site_id, $order_by);
	if($refs === false)
	{
		return fs_db_error();
	}
	
	$res = "<table>";
	
	if (!$refs) // no data yet
	{
		$res .= "<tr><td>".fs_r('No data yet, go get some hits')."</td></tr>";
	}
	else
	{
		$last_days_ago = null;
		$row_count = 0;
		$now = time();
		foreach($refs as $r)
		{
			$count = $r->refcount;
			$add_time = $r->add_time;
			
			$url = $r->url;
			$dec = urldecode($url);
			if (fs_is_utf8_string($dec))
				$url = $dec;			
			
			$url = fs_xmlentities($url);
			$page_title = $r->title;
			if (!empty($page_title))
			{
				$line_source = $page_title;
			}
			else
			{
				$line_source = $url;
			}
			$line = substr($line_source, 0,80);
			if(strlen($line_source) != strlen($line)) $line .= "...";
			$title = sprintf(fs_r('%d hits from %s'),$count,$url);
			
			if (!$show_days_ago)
			{
				$res .= "<tr>";
				$res .= "<td>";
				$res .= "<a href='$url' title='$title' target='_blank'>$line <b dir='ltr'>($count)</b></a>\n";
				$res .= "</td>";
				$res .= "</tr>";
			}
			else
			{
				$hit_days_ago = floor(($now - $add_time)/(60*60*24));
				if ($last_days_ago == null) 
				{
					$last_days_ago = $hit_days_ago;
					$days_count = 0; 
				}
				else
				if ($last_days_ago != $hit_days_ago)
				{
					$last_days_ago = $hit_days_ago;
					$days_count++;
				}
				
				$alternate = $days_count % 2 == 1;
				
				$time_since = fs_time_since($add_time);
				$res .= '<tr'.($alternate ? ' class="table_alternate_bg"' : "").'>';
				$res .= "<td>";
				$res .= "<a href='$url' title='$title' target='_blank'>$line</a>\n";
				$res .= "</td>";
				$res .= "<td>";
				$res .= "$time_since\n";
				$res .= "</td>";
				$res .= "</tr>";
			}
		}
	}
	
	$res .= "</table>";
	return $res;
}

function fs_get_popular_pages_tree($max_num = null, $days_ago = null, $type = null, $show_count = true, $target = '_blank')
{
	if (!$max_num)
	{
		$max_num = fs_get_max_popular_num();
	}

	if (!$days_ago)
	{
		$days_ago = fs_get_recent_popular_pages_days_ago();
	}

	$urls = fs_get_popular_pages($max_num, $days_ago, true, $type);
	if($urls === false) return fs_db_error();
	
	$res = '';
	if (!$urls) // no data yet
	{
		$res .= fs_r('No data yet, go get some hits');
	}
	else
	{
		$res .= "	<ul>";
		foreach($urls as $r)
		{
			$url = $r->url;
			$count = $r->c;
			$rr = array();
			fs_ensure_initialized($rr[$url]['count']);
			$dec = urldecode($url);
			if (!fs_is_utf8_string($dec))
				$dec = $url;
			$url = fs_xmlentities($dec);
			$text = isset($r->title) ? $r->title : $url;
			$url_text = substr($text,0,80);
			if(strlen($url_text) != strlen($text)) $url_text .= "...";
			$count_text = $show_count ? " (<b>&lrm;$count</b>)" : "";
			$target_str = $target != null ? "target='$target'" : "";
			$res .= "
		<li>
			<a $target_str title='$text' href='$url'>$url_text</a>$count_text
		</li>\n";
		}
		$res .=
		"	</ul>\n";
	}
	return $res;
}

function fs_get_country_codes_percentage($num_limit, $days_ago)
{
	require_once(dirname(__FILE__).'/ip2country.php');
	$codes = fs_get_country_codes($days_ago);
	if ($codes === false) return false;
	if (count($codes) == 0) return array();

	$total = 0;
	foreach ($codes as $code)
	{
		$total += $code->c;
	}

	$t = 0;
	$res = array();
	$tp = 0;
	foreach ($codes as $code)
	{
		if ($t == $num_limit) break;
		$t++;
		$percentage = $code->c / (float)$total * 100;
		$code->percentage = $percentage;
		$intcode = $code->country_code;
		$code->name = fs_get_country_name($intcode, true);
		$code->img = fs_get_country_flag_url($intcode, true);
		$res[] = $code;
		$tp += $percentage;
	}

	if ($tp < 100)
	{
		$last = new stdClass;
		$last->percentage = 100 - $tp;
		$last->name = fs_r('Others');
		$last->img = fs_get_flag_img_tag($last->name, fs_url("img/others.png"));
		$res[] = $last;
	}

	return $res;
}

function fs_get_countries_list()
{
	$countries = fs_get_country_codes_percentage(fs_get_max_countries_num(), fs_countries_list_days_ago());
	if($countries === false) return fs_db_error();
	$res = '';

	if (count($countries) == 0 )
	{
		$res .= fs_r('No data yet, go get some hits');
	}
	else
	{
		$res = "<ul>";
		foreach($countries as $country)
		{
			$name = $country->name;
			$flag = $country->img;
			$percentage = sprintf("%.2F", $country->percentage);
			$res .= "<li>$flag $name <b>$percentage%</b></li>\n";
		}
		$res .= "</ul>";
	}
	return $res;
}

function fs_format_sql_date($option_key)
{
	$t = fs_get_option($option_key);
	if (empty($t)) return false;
	$s = sscanf($t,"%d/%d/%d");
	return $s[2].'-'.$s[1].'-'.$s[0];
}

function fs_output_sysinfo_information()
{
	$sysinfo = fs_r('System information');
	$you_can_help = fs_r('You can help by sending anonymous system information that will help make better decisions about new features').".";
	$line1 = fs_r('The information will be sent anonymously, but a unique identifier will be sent to prevent duplicate entries from the same FireStats');
	echo "<h3>$sysinfo</h3>$you_can_help<br/>$line1.";
}

/**
 * if user agrees to send system information and the last sent info is outdated outputs a bunch of stuff that sends sysinfo without interrupting
 */
function fs_output_send_info_form()
{
    if (fs_is_admin() && fs_get_system_option("user_agreed_to_send_system_information") == 'true' && fs_last_sent_info_outdated())
    {?>
        <iframe id="hidden_frame" name="hidden_frame" style="width:0px; height:0px; border: 0px" src="about:blank"></iframe>
        <form name="send_info_form" target="hidden_frame" method="post" action="<?php echo FS_SYSINFO_URL?>">
            <?php
                $sysinfo = fs_get_sysinfo();
                foreach($sysinfo as $k=>$v)
                {
                    ?>
                        <input type="hidden" name="<?php echo $k?>" value="<?php echo $v?>"></input>
                    <?php
                }
            ?>
        </form>
        <script type='text/javascript'>
        sendSilentRequest('action=saveSentSysInfo');
        document.forms['send_info_form'].submit();
        </script>
    <?php
    }
}

function fs_show_page($page, $is_file = true, $add_firestats_js = true, $hide_support_fs = false)
{
	header("Content-Type: text/html; charset=utf-8");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link rel="icon" href="img/firestats-icon-small.png" type="image/png"></link>
<title><?php fs_e('FireStats');?></title>
	<?php fs_output_head()?>
</head>
<body>
<div id="firestats">
<div class="fs_body width_margin <?php echo fs_lang_dir()?>">
	<?php
	if ($hide_support_fs) 
	{
		global $fs_hide_support_button; 
		$fs_hide_support_button = true;
	}
	require(FS_ABS_PATH."/php/header.php");
	echo "\n";
	if ($is_file)
	{
		echo "<!-- PAGE : $page -->\n";
		require($page);
	}
	else
	{
		echo $page;
	}
	echo "\n";
	require(FS_ABS_PATH."/php/footer.php");
	?>
</div> <!-- fs_body -->
</div><!-- firestats -->
</body>
</html>
	<?php
}

function fs_show_embedded_page($page, $is_file = true, $add_firestats_js = true, $hide_support_fs = false)
{
?>
<div id="firestats">
<div class="fs_body width_margin <?php echo fs_lang_dir()?>">
<?php
	
	if ($hide_support_fs) 
	{
		global $fs_hide_support_button; 
		$fs_hide_support_button = true;
	}
	require(FS_ABS_PATH."/php/header.php");
	echo "\n";
	if ($is_file)
	{
		echo "<!-- PAGE : $page -->\n";
		require($page);
	}
	else
	{
		echo $page;
	}
	echo "\n";
	require(FS_ABS_PATH."/php/footer.php");
?>
</div> <!-- fs_body -->
</div><!-- firestats -->
<?php
}


function fs_output_head()
{?>
<link rel="stylesheet" href="<?php echo fs_url('css/base.css.php');?>" type="text/css"/>
<link rel="stylesheet" href="<?php echo fs_url('css/mktree.css.php');?>" type="text/css" />
<link rel="stylesheet" href="<?php echo fs_url('lib/jscalendar-1.0/skins/aqua/theme.css');?>" type="text/css" />
<link rel="stylesheet" href="<?php echo fs_url('lib/dhtml-windows/css/floating-window.css');?>" media="screen" type="text/css"/>

<?php // AND HOW CAN WE GO WITHOUT SOME I.E SPECIFIC HACKS?! ?>
<!--[if lt IE 7]>
		<link rel="stylesheet" href="<?php echo fs_url('css/ie6-hacks.css');?>" type="text/css" />
	<![endif]-->
<!--[if IE]>
	<link rel="stylesheet" href="<?php echo fs_url('css/ie-hacks.css');?>" type="text/css" />
<![endif]-->

<script type="text/javascript">
<!--
	<?php // Configure window buttons ?>
	var conf = new Object();
	conf.img_top_left = '<?php echo fs_url('lib/dhtml-windows/images/top_left.gif')?>';
	conf.img_top_center = '<?php echo fs_url('lib/dhtml-windows/images/top_center.gif')?>';
	conf.img_minimize = '<?php echo fs_url('lib/dhtml-windows/images/minimize.gif')?>';
	conf.img_close = '<?php echo fs_url('lib/dhtml-windows/images/close.gif')?>';
	conf.img_top_right = '<?php echo fs_url('lib/dhtml-windows/images/top_right.gif')?>';
	conf.img_buttom_right = '<?php echo fs_url('lib/dhtml-windows/images/bottom_right.gif')?>';
	conf.root = 'firestats';
//-->
</script>

<script type="text/javascript" src='<?php echo fs_url('lib/dhtml-windows/js/ajax.js')?>'></script>
<script type="text/javascript" src='<?php echo fs_url('lib/dhtml-windows/js/floating-window.js')?>'></script>
<script type="text/javascript" src='<?php echo fs_url('js/prototype.js')?>'></script> 
<script type="text/javascript" src='<?php echo fs_url('js/firestats.js.php').fs_get_request_suffix()?>'></script>
<script type="text/javascript" src='<?php echo fs_url('js/mktree.js')?>'></script>
<script type="text/javascript" src="<?php echo fs_url('lib/jscalendar-1.0/calendar_stripped.js')?>"></script>
<script type="text/javascript" src="<?php echo fs_url('lib/jscalendar-1.0/lang/calendar-en.js')?>"></script>
<script type="text/javascript" src="<?php echo fs_url('lib/jscalendar-1.0/calendar-setup_stripped.js')?>"></script>
<?php	
}

function fs_get_last_day_page_views_label() 
{
	$mode = fs_get_option("last_day_stats_mode","24h");
	switch ($mode)
	{
		case "24h": return fs_r('Page views in last 24 hours');break;
		case "midnight": return fs_r('Page views since midnight');break;
		default: return 'Error';break;
	}
}


function fs_get_last_day_visits_label() 
{
	$mode = fs_get_option("last_day_stats_mode","24h");
	switch ($mode)
	{
		case "24h": return fs_r('Visits in last 24 hours');break;
		case "midnight": return fs_r('Visits since midnight');break;
		default: return 'Error';break;
	}
}


function fs_get_hits_table_page_number_indicator()
{
	$hits = fs_get_num_hits_in_hits_table();
	if ($hits === false) return fs_db_error();
	$num_pages = ceil($hits / fs_get_num_hits_in_table());
	$page_num = (int)fs_get_option("current_hits_table_page", "0") + 1;
	return sprintf(fs_r("Page %s of %s (%s hits)"), $page_num, $num_pages, $hits);
}
?>
