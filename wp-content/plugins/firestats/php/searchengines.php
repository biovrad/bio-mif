<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/utils.php');
require_once(FS_ABS_PATH.'/lib/utf8-encoder/utf8-encoder.php');

fs_register_incremental_process('recalculate_search_engine_terms', 'fs_recalculate_search_engine_terms_calc_max', 'fs_recalculate_search_engine_terms', null);

/**
 * returns the search engine definition.
 */
function &fs_get_search_engines($get_hash = false)
{
	/**
	 * maps id to search engine
	 */
	static $search_engine_ht;
	
	/**
	 * array
	 */
	static $search_engines_arr;
	if (!isset($search_engines_arr))
	{
		$search_engine_ht = array();
		$engines_table = array();	
		// Note: users are encouraged NOT to add new search engines to prevent 
		// conflicts with future FireStats versions.
		// if you want support for a new search engine, open a support request on the site.
		//
		// To add a new search engine, add it in both arrays.
		// If you added a new engine, please open an enhancement request
		// for it (http://firestats.cc/newticket) with the information and logo icon.
		//
		// IMPORTANT: DO NOT MODIFY THE ARRAY ORDER. element index gets into the database as an ID.
		// Add new elements at the end
		$engines = array
		(
			array("Google","google.png"),
			array("MSN","msn.png"),
			array("altavista","altavista.png"),
			array("Ask","ask.png"),
			array("Exite","exite.png"),
			array("Alexa","alexa.png"),
			array("Walla","walla.png"),
			array("Yahoo","yahoo.png"),
			array("AOL","aol.png"),
			array("Baidu","baidu.png"),
			array("Lycos","lycos.png"),
			array("HotBot",null),
			array("About","about.png"),
			array("Seznam","seznam.png"),
			array("Atlas","atlas.cz.png"),
			array("Centrum","centrum.cz.png"),
			array("Aport","aport.png"),
			array("Rambler","rambler.png"),
			array("Webalta","webalta.png"),
			array("Mail.ru","mailru.png"),
			array("Nigma","nigma.png"),
			array("Yandex","yandex.png"),
			array("Neti","neti.png"),
			array("Kvasir","kvasir.no.png"),
			array("Sesam","sesam.no.png"),
			array("ABCSøk","abcsok.no.png"),
			array("Sapo","sapo.png"),
			array("Eniro","eniro.png"),
			array("poisk.ru","poisk.png"),
			array("bing", "bing.png")
		); 
		
	
		/**
		 * search engines on the top of the list are recognized first.
		 * this is important for both correct recognition and performance.
		 */
		$engine_conf = array
		(
			// array(NAME,URL_PART,QUERY,OPTIONAL_PARSE_FUNCTION,OPTIONAL_ENCODING,OPTIONAL_ENCODING_EXTRACTOR),
			array('Google','google.com.ua','','fs_google_term_parser','cp1251'),
			array('Google','google.ru','','fs_google_term_parser','cp1251'),
			array('Google','google'		  ,'','fs_google_term_parser',null,'fs_google_encoding_extractor'),
			array('MSN','msn','q'),
			array('altavista','altavista','q'),
			array('Ask','ask.com','q'),
			array('Exite','exite','q'),
			array('Alexa','alexa','q'),
			array('Walla','search.walla.co.il','q', null, null, "fs_walla_encoding_extractor"),
			array('Yahoo','yahoo','p'),
			array('AOL','aolsearch','query'),
			array('AOL','search.aol','query'),
			array('Baidu','baidu.com','wd',null,'gb2312'),
			array('Lycos','search.lycos.com','query'),
			array('HotBot','hotbot.com','query'),
			array('About','search.about.com','terms'),
			array('MSN','live.com','q'),
			array('Seznam','seznam.cz','q'),
			array('Atlas','atlas.cz','q'),
			array('Centrum','search.centrum.cz','q'),
			array('Neti','neti.ee','query',null,'iso8859-1'),
			array('Yandex','yandex','text',null,'cp1251'),
			array('Aport','aport','r',null,'cp1251'),
			array('Rambler','rambler','words',null,'cp1251'),
			array('Webalta','webalta','q',null,'cp1251'),
			array('Mail.ru','go.mail.ru','q',null,'cp1251'),
			array('Mail.ru','search.list.mail.ru','q',null,'cp1251'),
			array('Nigma','nigma','q',null,'cp1251'),
			array('Kvasir','kvasir.no','searchExpr'),
			array('Sesam','sesam.no','q'),
			array('ABCSøk','abcsok.no','q'),
			array('Sapo','pesquisa.sapo.pt','q'),
			array('Eniro','eniro.se','search_word'),
			array('poisk.ru','poisk.ru','text', null, 'cp1251'),
			array('bing','bing.com','q'),
		);
		
		foreach($engines as $engine)
		{
			fs_create_search_engine($search_engine_ht, $engines_table, $engine[0],$engine[1]);
		}			
		
		$search_engines_arr = array();
		foreach($engine_conf as $conf)
		{
			$parse_func = isset($conf[3]) ? $conf[3] : null;
			$encoding = isset($conf[4]) ? $conf[4] : null;
			$encoding_extractor = isset($conf[5]) ? $conf[5] : null;
			fs_create_engine_conf($engines_table,$search_engines_arr,$conf[0],$conf[1],$conf[2], $parse_func, $encoding, $encoding_extractor);
		}
	}
	if ($get_hash) return $search_engine_ht;
	return $search_engines_arr;
}

/**
 * Accepts a referrer, and returns the matching search engine confuration, the referrer converted to utf-8 and the search terms.
 *
 * @param string $ref the referrer
 * @param search_engine output : $engine search engine configuration
 * @param string $terms output : search terms
 * @param boolean $convert_to_utf8 if true, search terms and output_referrer will be converted to utf8
 * @return true on success, false otherwise.
 */
function fs_process_search_engine_referrer($ref, &$engine, &$terms)
{
	$engine = $terms = null;
	
	$p = @parse_url($ref);
	if (!$p) return false;
	
	if (!isset($p['host'])) return false;
	$engine = fs_find_matching_engine($p['host']);
	if ($engine === false) return false;

	if ($engine->parse_function == null)
	{
		$vars = array();
		if (!isset($p['query'])) return false;
		parse_str($p['query'], $vars);
		$q = $engine->query;
		if (isset($vars[$q]))
		{
			$terms = $vars[$q];
		}
		else
		{
			// if there are no search terms don't record this as a search engine hit.
			// chances are its just a spam bot looking for some love.
			return false;
		}
	}
	else
	{
		$func = $engine->parse_function;
		$res = $func($ref, $engine, $terms);
		if ($res === false) return false;
	}
	
	$encoding = fs_get_referrer_encoding($ref, $engine);
	if ($encoding != null && $terms != null && !fs_is_utf8_string($terms))
	{
		$terms = fs_convert_to_utf8($encoding, $terms);
	}
			
	if (!empty($terms)) 
	{
		return true;
	}
	
	return false;
}

function fs_get_referrer_encoding($ref, $engine)
{
	if ($engine->encoding_extractor != null)
	{
		$func = $engine->encoding_extractor;
		return $func($ref, $engine);
	}
	else
	{
		return $engine->keyword_encoding;
	}
}

function fs_find_matching_engine($ref)
{
	$engines = fs_get_search_engines();
	foreach($engines as $e)
	{
		if (strpos($ref, $e->pattern) !== false) return $e;
	}
	return false;
}

function fs_create_engine_conf($engines_table, &$search_engines_arr,$name, $pattern, $query, $parse_function, $keyword_encoding, $encoding_extractor)
{
	$engine = $engines_table[$name];
	if ($engine == null) die("Unknown search engine " .$name);
	
	$conf = new stdClass();
	$conf->id = $engine->id;
	$conf->name = $engine->name;
	$conf->logo_icon = $engine->logo_icon;
	$conf->pattern = $pattern;
	$conf->query = $query;
	$conf->parse_function = $parse_function;
	$conf->keyword_encoding = $keyword_encoding;
	$conf->encoding_extractor = $encoding_extractor;
	$search_engines_arr[] = $conf;
}

function fs_create_search_engine(&$search_engine_ht,&$engines, $name, $logo_icon)
{
	static $id = 1;
	$engine = new stdClass();
	$engine->id = $id;
	$engine->name = $name;
	$engine->logo_icon = $logo_icon;
	$search_engine_ht[$engine->id] = $engine;
	$engines[$name] = $engine;
	$id++;
}

function fs_recalculate_search_engine_terms_calc_max()
{
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$count = $fsdb->get_var("SELECT COUNT(*) c FROM `$urls`");
	if ($count === null)
	{
		return fs_db_error();
	}
	else
	{
		return $count;
	}	
}

function fs_recalculate_search_engine_terms($value, $max, $chunk = 1000)
{
	require_once(FS_ABS_PATH.'/php/db-common.php');
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	if ($value == 0)
	{
		if (false === $fsdb->get_results("UPDATE `$urls` SET `search_engine_id` = NULL, `search_terms` = NULL"))
		{
			return fs_db_error();
		}
	}
	
	if (!is_numeric($value)) return "value $value must be numeric";
	
	$res = $fsdb->get_results("SELECT id,url from $urls LIMIT $chunk OFFSET $value");
	if ($res === false)
	{
		return fs_db_error();
	}
	
	$total = count($res);
	if ($total > 0)
	{
		foreach($res as $r)
		{
			$id = $r->id;
			$ref = $r->url;
			$engine = null;
			
			$res = fs_process_search_engine_referrer($ref, $engine, $terms);
			
			if ($terms !== false && $terms != '')
			{
				$terms = $fsdb->escape($terms);
				$r2 = $fsdb->query("UPDATE `$urls` SET `search_engine_id`='$engine->id', `search_terms` = $terms WHERE `id` = '$id'");
				if ($r2 === false)
				{
					return fs_db_error();
				}
			}
		}
	}

	return $total;	
}


/**
 * Parser specific to google images urls
 */
function fs_google_images_parser($ref, $engine, &$terms)
{
	$p = @parse_url($ref);
	$vars = array();
	parse_str($p['query'], $vars);
	if (isset($vars['prev']))
	{
		$prev = $vars['prev'];
		$p = @parse_url($prev);
		if (isset($p['query']))
		{
			parse_str($p['query'], $vars);
			if (isset($vars['q']))
			{
				$terms = $vars['q'];
				return true;
			}
		}
	}
	
	return false;
}

function fs_google_encoding_extractor($ref, $engine)
{
	$p = @parse_url($ref);
	$vars = array();
	if (!isset($p['query'])) return null;
	parse_str($p['query'], $vars);
	if (!isset($vars['ie'])) return null;
	$e = $vars['ie'];
	if ($e == 'windows-1251') return 'cp1251';
	if ($e == 'windows-1255') return 'cp1255';
	return null;
}

function fs_google_term_parser($ref, $engine, &$terms)
{
	// This function is far from perfect.
	// a perfect function is way to hard to implement and does not worth the effort.
	
	// == Google query parameters ==
	// q=				all these words (AND, implicit)
	// as_q=			all these words (AND, implicit)
	// as_epq=			advanced search "this exact wording or phrase" (quoted)
	// as_oq=			one of those words (OR separated)
	// as_eq=			Except qurey, prepand minus.
	// hl=en			search language domain hl=en will search english sites.
	// num=10			number to show in result page
	// lr=				unknown
	// as_filetype		unknown
	// ft=i				unknown
	// as_sitesearch=SITE	site search, site:SITE
	// as_qdr=all
	// as_rights=
	// as_occt=any
	// cr=&as_nlo
	// as_nhi=
	// safe=images
	// as_epq	: 
	// as_q		: advanced search "all these words"
	// as_oq	: advanced search "one or more of these words"
	$terms = "";
	$up = @parse_url($ref);
	if (isset($up['path']) && $up['path'] == '/imgres')
	{
		return fs_google_images_parser($ref, $engine, $terms);
	}
	
	if (!$up || !isset($up['query'])) return false;
	$p = array();
	parse_str($up['query'], $p);
	$t = '';
	if(!empty($p['q'])) 	$t = fs_append_str($t, $p['q']);
	if(!empty($p['as_q']))	$t = fs_append_str($t, str_replace("+"," ",$p['as_q']));
	if(!empty($p['as_epq']))$t = fs_append_str($t, '"'.$p['as_epq'].'"');
	if(!empty($p['as_oq']))	
	{
		// harder to implement, and almost never used. so fuck it.
		// $t = fs_append_str($t, fs_implode(" OR ", explode("+",str_replace(" ","+",$p['as_oq']))));
		$t = stripcslashes($p['as_oq']);
	}
	if(!empty($p['as_eq']))	$t = "-".fs_append_str($t, fs_implode(" -", explode("+",str_replace(" ","+",$p['as_eq']))));
	$terms = $t;
	return true;
}

/**
 * Extract the encoding of a search.walla.co.il url
 */
function fs_walla_encoding_extractor($ref, $engine)
{
	$p = @parse_url($ref);
	$vars = array();
	if (!isset($p['query'])) return null;
	parse_str($p['query'], $vars);
	if (!isset($vars['e'])) return null;
	$e = $vars['e'];
	if ($e == 'hew') return 'cp1255';
	if ($e == 'utf') return null; // utf8 is the default FireStats encoding, nothing to do.
	return null;
}

?>
