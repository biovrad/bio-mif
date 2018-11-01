<?php
/*
Plugin Name: Simple Forum
Version: 2.1
Plugin URI: http://www.stuff.yellowswordfish.com/simple-forum/
Description: Простой форум на странице вашего блога. Русская версия от <a href=http://lecactus.ru>Lecactus</a>
Author: Andy Staines
Author URI: http://www.yellowswordfish.com

*/

/*  Copyright 2006/2007  Andy Staines

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    For a copy of the GNU General Public License, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

//== required Simple Forum code files
include_once('sf-includes.php');
include_once('sf-primitives.php');
include_once('sf-upgrade.php');
include_once('sf-support.php');
include_once('sf-pagecomponents.php');
include_once('sf-page.php');
include_once('sf-forms.php');
include_once('sf-avatars.php');
include_once('sf-tags.php');
include_once('sf-registration.php');
include_once('sf-hook-template.php');
include_once('sf-filters.php');
include_once('sf-links.php');

//== WP hooks
add_action('activate_simple-forum/sf-control.php', 'sf_setup_data');
add_action('deactivate_simple-forum/sf-control.php', 'sf_remove_data');
add_action('init', 'sf_localisation');
add_filter('wp_head', 'sf_setup_header');
add_filter('the_content', 'sf_setup_forum');
add_filter('the_title', 'sf_setup_page_title');
add_filter('wp_title', 'sf_setup_browser_title');
add_action('activity_box_end', 'sf_announce', 1);
add_action('admin_menu', 'sf_admin_menu');
add_action('admin_head', 'sf_admin_css', 1);
add_action('template_redirect', 'sf_feed');

//== Content Filters
add_filter('sf_save_post_content', 'balanceTags', 30);

add_filter('sf_save_topic_title', 'balanceTags', 30);

add_filter('sf_save_post_name', 'strip_tags');
add_filter('sf_save_post_name', 'trim');
add_filter('sf_save_post_name', 'wp_specialchars', 30);
add_filter('sf_save_post_name', 'wp_filter_kses');

add_filter('sf_save_post_email', 'trim');
add_filter('sf_save_post_email', 'sanitize_email');
add_filter('sf_save_post_email', 'wp_filter_kses');

add_filter('sf_show_post_content', 'wptexturize');
add_filter('sf_show_post_content', 'convert_chars');
add_filter('sf_show_post_content', 'make_clickable');
add_filter('sf_show_post_content', 'wpautop', 30);

add_filter('sf_show_post_name', 'wptexturize');
add_filter('sf_show_post_name', 'convert_chars');
add_filter('sf_show_post_name', 'wp_specialchars');


//== MAIN FORUM SETUP PROCESSOR ===========================================

function sf_setup_forum($content)
{
	global $is_forum, $icons, $moderators, $user_ID;

// ---------------------------

// ---------------------------

	get_currentuserinfo();

	if($is_forum)
	{
		sf_setup_data();
		sf_clean_settings();
		sf_clean_sfnotice();

		//== Load icon List
		$icons = get_option('sfshowicon');
		$list = explode('@', $icons);

		$icons = array();
		foreach($list as $i)
		{
			$temp=explode(';', $i);
			$icons[$temp[0]] = $temp[1];
		}

		//== Load Moderators List
		$moderators = explode(';',get_option('sfmodusers'));

		$paramtype='';
		$paramvalue='';

		//== BRANCH TO CORRECT POCESSING

		// Login/register/password etc.
		if(isset($_GET['action']))
		{
			$message = get_sfnotice('sfmessage');
			if(!empty($message))
			{
				sf_message($message);
				delete_sfnotice('sfmessage');
			}

			if(sf_login() == true)
			{
				// true means a form was displayed
				return;
			}
		}

		// edit post from manage
		if (isset($_POST['editpost'])) sf_save_edited_post();

		// edit topic from manage
		if (isset($_POST['edittopic'])) sf_save_edited_topic();

		// Maybe a profile edit?
		if (isset($_GET['profile'])) return sf_profile();

		// maybe a profile save
		if (isset($_POST['subprofile'])) sf_save_profile();

		// maybe show extended profile details
		if (isset($_POST['profileext'])) return sf_view_profile($_POST['profileext']);

		// subscription manage request
		if (isset($_POST['mansubs'])) return sf_subscription_form();

		// save subscriptions
		if (isset($_POST['uptopsubs'])) sf_update_subscriptions($user_ID);

		// How abut a search
		if (isset($_GET['search']))
		{
			$paramvalue=$_GET['value'];
			if($_GET['forum'] == 'all')
			{
				$paramtype = 'SA';
			} else {
				$paramtype = 'S';
			}
		}

		if (isset($_POST['icontoggle'])) sf_icon_toggle();

		// manage topic admin icons
		if (isset($_POST['locktopic'])) sf_lock_topic_toggle($_POST['locktopic']);
		if (isset($_POST['pintopic'])) sf_pin_topic_toggle($_POST['pintopic']);
		if (isset($_POST['sorttopic'])) sf_sort_topic_toggle($_POST['sorttopic']);
		if (isset($_POST['killtopic'])) sf_delete_topic($_POST['killtopic']);
		if (isset($_POST['movetopic'])) return sf_move_topic_form($_POST['movetopic'], $_POST['forum'], $_POST['page']);
		if (isset($_POST['linkbreak'])) sf_break_post_link($_POST['linkbreak'], $_POST['blogpost']);

		if (isset($_POST['maketopicmove'])) sf_move_topic();

		// manage post admin icons
		if (isset($_POST['approvepost'])) sf_approve_post($_POST['approvepost']);
		if (isset($_POST['pinpost'])) sf_pin_post_toggle($_POST['pinpost']);
		if (isset($_POST['killpost'])) sf_delete_post($_POST['killpost'], $_POST['killposttopic']);

		// maybe a subscription call?
		if (isset($_GET['subscribe'])) sf_save_subscription(intval($_GET['topic']), $user_ID, true);

		// is it a call to remove un read post list?
		if (isset($_POST['doqueue'])) sf_remove_waiting_queue($user_ID);

		// Now display forum page
		$content = sf_hook_pre_content() . $content . sf_hook_post_content();
		$content.= sf_js_check();
		$content.= sf_render_page($paramtype, $paramvalue);
	}
	return $content;
}

// == FILTERS ======================================================================

function sf_localisation()
{
	// i18n support
	load_plugin_textdomain('sforum', '/wp-content/plugins/simple-forum/languages');
	return;
}

function sf_setup_header()
{
	global $wp_query, $is_forum;

	if(( is_page() ) && ( $wp_query->post->ID == get_option('sfpage')) )
	{
		// if page is password protected, ensure it matches before starting
		if (!empty($wp_query->post->post_password))
		{
			if ($_COOKIE['wp-postpass_'.COOKIEHASH] != $wp_query->post->post_password)
			{
				return;
			}
		}

		$is_forum = true;
		$lang=get_option('sflang');

		echo '<link rel="stylesheet" type="text/css" href="' . SFSKINCSS .'" />' . "\n";
		echo '<script type="text/javascript" src="'.SFJSCRIPT.'sf.js"></script>' . "\n";
		if(get_option('sfquicktags') == false)
		{
			if(sf_is_safari() == false)
			{
 				echo '<script type="text/javascript" src="'.SFRTE.'tiny_mce.js"></script>'."\n";
echo <<< theEnd
				<script type="text/javascript">
				tinyMCE.init({
					mode : "exact",
					elements : "newtopicpost, editpostcontent",
					theme : "advanced",
					theme_advanced_resizing : true,
					language : "$lang",
					auto_reset_designmode : true,
					width : "100%",
					height: "300",
					extended_valid_elements: "code",
                    apply_source_formatting : true,
                    entity_encoding : "named",
					invalid_elements: "script,object,applet,iframe, h1, h2, h3, h4, h5 h6",
					plugins : "inlinepopups, preview, emotions, spellchecker, media, dd",
					theme_advanced_toolbar_location : "top",
					theme_advanced_statusbar_location: "bottom",
					theme_advanced_toolbar_align : "left",
					theme_advanced_buttons1 : "bold, italic, underline, separator, bullist, numlist, separator, dd_code, separator, outdent, indent, separator, link, unlink, separator, image, undo, redo, separator",
					theme_advanced_buttons2 : "",
					theme_advanced_buttons3 : "",
					theme_advanced_buttons1_add : "emotions, separator, preview, code, separator, spellchecker, media"
				});
				</script>\n
theEnd;
			}
		}
	} else {
		$is_forum = false;

		if(get_option('sfannounceauto'))
		{
			echo '<script type="text/javascript" src="'.SFJSCRIPT.'sf.js"></script>' . "\n";
		}
	}
}

function sf_feed()
{
	if(isset($_GET['xfeed']))
	{
		include ABSPATH . 'wp-content/plugins/simple-forum/sf-feeds.php';
		exit;
	}
}

function sf_setup_page_title($title)
{
	return sf_setup_title($title, '<br />', 'page');
}

function sf_setup_browser_title($title)
{
	return sf_setup_title($title, ' &raquo; ', 'browser');
}

function sf_setup_title($title, $sep, $source)
{
	global $wp_query;

	if(get_option('sftitle'))
	{
		if(( is_page() ) && ( $wp_query->post->ID == get_option('sfpage')) )
		{
			// workaround for WP's title filter bug
			if(($title == $wp_query->post->post_title) || ($source == 'browser'))
			{
				if(isset($_GET['topic']))
				{
					$title.=$sep.sf_get_topic_name(intval($_GET['topic']));
					return $title;
				}
				if(isset($_GET['forum']))
				{
					if($_GET['forum'] != 'all')
					{
						$title.=$sep.sf_get_forum_name(intval($_GET['forum']));
					}
					return $title;
				}
			}
		}

	}
	return $title;
}

//== CREATE ADMIN MENU & CSS =================================

function sf_admin_menu()
{
	add_submenu_page('edit.php', __('Simple Forum', 'sforum'), __('Simple Forum', 'sforum'), 8, 'simple-forum/sf-admin.php');
}

function sf_admin_css()
{
	if((isset($_GET['page'])) && (strpos($_GET['page'], 'simple-forum'))!==false)
	{ ?>
		<link rel="stylesheet" type="text/css" href="<?php echo(SFADMINURL);?>sf-admin.css" />
		<script type="text/javascript">
			var tabberOptions = {manualStartup:true};
			document.write('<style type="text/css">.tabber{display:none;}</style>');
		</script>
		<script type="text/javascript" src="<?php echo(SFADMINURL);?>tabber.js"></script>
		<script type="text/javascript">
			addLoadEvent(tabberAutomaticOnLoad);
		</script>
	<?php }
	return;
}

//== CREATE ANNOUNCEMENT IN DASHBOARD ==================

function sf_announce()
{
	$out = '<h3>'.__("Forums", "sforum").'</h3>';
	$out.="<p>";
	$unreads = sf_get_unread_forums();
	if($unreads)
	{
		foreach($unreads as $unread)
		{
			if($unread->post_count == 1)
			{
				$mess = sprintf(__("There is %s new post", "sforum"), $unread->post_count);
			} else {
				$mess = sprintf(__("There are %s new posts", "sforum"), $unread->post_count);
			}
			$out.= $mess.__(" in the forum topic ", "sforum").sf_get_topic_url_dashboard($unread->forum_id, $unread->topic_id)."<br />";
		}
	} else {
		$out.= __("There are no new forum posts", "sforum")."<br />";
	}
	$waiting = sf_get_awaiting_approval();
	if($waiting == 1)
	{
		$out.= __("There is 1 post awaiting approval", "sforum")."<br />";
	}
	if($waiting > 1)
	{
		$out.= sprintf(__("There are %s posts awaiting approval", "sforum"), $mod)."<br />";
	}
	$out.="</p>";
	echo($out);

	return;
}

?>