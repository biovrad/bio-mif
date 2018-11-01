<?php
/*
Plugin Name: Xchange drone
Plugin URI: http://blogdr.ru
Description: SEO плагин для обмена реф-ссылками с другими блогами. Код для вывода блока ссылок <code>&lt;?php xchange_drone(); ?&gt;</code> необходимо вставить в шаблон отображения поста. Подробная инструкция: <a href="http://blogdr.ru/">SEO xchange drone</a>.
Version: 1.0
Author: Dr.ONE
Author URI: http://blogdr.ru
*/

//db setup
function xchange_install () {
	//get your personal very secret site-key
	add_option("xchange_site_key", '');
	$site=urlencode(get_option('siteurl'));
	$site_key = file("http://blogdr.ru/x/register.php?site=$site");
	update_option("xchange_site_key", trim($site_key[0]));
}
register_activation_hook(__FILE__,'xchange_install');

function xchangeDroneRef() {
	$Ref=  isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
	if (empty($Ref)) return;
	$UrlArray = parse_url($Ref);
	if ( $UrlArray['scheme'] !== 'http' )  return;
	$RefHost = $UrlArray['host'];
	$IsGoogle = strpos($RefHost, '.google.');
	if (($RefHost == 'search.msn.com')  || is_int($IsGoogle)) {
		parse_str($UrlArray['query']);
		$KeyWords=$q;
		if (isset($ie) && ($ie == 'windows-1251')) {
			$KeyWords= @iconw("windows-1251", "utf-8", $KeyWords);
		}
	} elseif ($RefHost == 'www.rambler.ru') {
		parse_str($UrlArray['query']);
		$KeyWords= @iconw("windows-1251", "utf-8", $words);
	} elseif ($RefHost == 'www.yandex.ru' || $RefHost == 'yandex.ru') {
		parse_str($UrlArray['query']);
		$KeyWords = $text;
		$c = substr_count($KeyWords, chr(208));
		if ($c < 3) {
			$KeyWords= @iconw("windows-1251", "utf-8", $words);
		}
	} elseif ($RefHost == 'blogs.yandex.ru') {
		parse_str($UrlArray['query']);
		$KeyWords = $text;
		$c = substr_count($KeyWords, chr(208));
	}
	$KeyWords = trim($KeyWords);
	if (empty($KeyWords)) return;
	return $KeyWords;
}


function GetXchangeLinks() {
	$Sign = '<p>Меня читают</p>';
	$secret_key=get_option("xchange_site_key");
	$page=get_the_ID();
	$myLinks = file("http://blogdr.ru/x/get.php?site=$secret_key&page=$page");
	if (sizeof($myLinks)<=0) return '';
	foreach($myLinks as $row=>$value) {
		
		$b=split("\t",$value);
		
		$Sign .= "<li>";
		$Sign .= '<a href="'.$b[0].'">'.$b[1].'</a>';
		$Sign .="\n</li>";
	}
	$Sign .="\n</ul><P></P>";
	return $Sign;
}

function xchange_drone() {
	echo GetXchangeLinks();
	return;
}



function xchange_drone_saveref() {

	$keywords = xchangeDroneRef();

	if ($keywords!='') {
		$secret_key=get_option("xchange_site_key");
		$secret_keywords=urlencode($keywords);
		$secret_page = urlencode(get_page_link(get_the_ID()));
		file("http://blogdr.ru/x/save.php?site=$secret_key&keywords=$secret_keywords&page=$secret_page");
	}
}
add_action('shutdown', 'xchange_drone_saveref');

?>