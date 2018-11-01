<?php
/*
Plugin Name: Lighter Menus
Plugin URI: http://www.italyisfalling.com/lighter-menus
Description: Creates Drop Down Menus for WordPress Admin Panels. Fast to load, adaptable to color schemes, comes with silk icons, a option page,  and a design that fits within the Wordpress 2.5 interface taking the less room possible.
Version: 2.7.2
Author: corpodibacco
Author URI: http://www.italyisfalling.com/coding/
WordPress Version: 2.6
*/

/*
Copyright (c) 2008
Released under the GPL license
http://www.gnu.org/licenses/gpl.txt

    This file is part of WordPress.
    WordPress is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*			IMPORTANT ACKNOWLEDGMENT:
			------------------------------------------------------------------------------------------------------------------------									
				This plugin is now basically a spin-off of "Admin Drop Down Menu", a plugin by "the wizard of" Ozh,
				which you can find here: http://planetozh.com/blog/my-projects/wordpress-admin-menu-drop-down-css/
				To Ozh obviously goes the gratitude... not only for this one, but for providing to the wordpress community really
				some of its best and most innovative plugins.
				Ozh, I didn't do this before, 'cause I'm so stupid I never realized so much of the code I was using here came 
				in fact from you. Thank you man. --corpodibacco
			------------------------------------------------------------------------------------------------------------------------		*/

//give few definitions
$dir = basename(dirname(__FILE__));
if ($dir == 'plugins') $dir = '';
else $dir = $dir . '/';	
define("LIGHTER_PATH", get_option("siteurl") . "/".PLUGINDIR."/" . $dir);

//prepare for local
$currentLocale = get_locale();
if(!empty($currentLocale)) {
	$moFile = ABSPATH . PLUGINDIR ."/" . $dir . 'lang/lighter-menus-' . $currentLocale . ".mo";
	//check if it is a window server and changes path accordingly
	if ( strpos($moFile, '\\')) $moFile = str_replace('/','\\',$moFile); //str_replace(chr(47),chr(92).chr(92),$moFile);
	if(@file_exists($moFile) && is_readable($moFile)) load_textdomain('lighter-menus', $moFile);
}

//places the menu into html tags
function lm() {
	$menu = lm_build();		
	$ladmin_menu = '</ul><ul id="lm">'; // close original <ul id="dashmenu"> and add ours
	
	foreach ($menu as $k=>$v) {
		$url 	= $v['url'];
		$name 	= $k;
		$anchor = $v['name'];
		$class	= $v['class'];

		$ladmin_menu .= "\t<li class='lm_toplevel'><a href='$url'$class><span>$anchor</span></a>";
		if (is_array($v['sub'])) {
			
			$ulclass='';
			if ($class == " class='topcurrent'") $ulclass = " class='ulcurrent'";
			$ladmin_menu .= "\n\t\t<ul$ulclass>\n";

			foreach ($v['sub'] as $subk=>$subv) {
				$suburl = $subv['url'];
				$subanchor = str_replace('"', '\"', $subv['icon'] . '' . $subv['name']);/*$subv['name'];*/
				$subclass='';
				if (array_key_exists('class',$subv)) $subclass=$subv['class'];
				$ladmin_menu .= "\t\t\t<li class='lm_sublevel'><a href='$suburl'$subclass>$subanchor</a></li>\n";
			}
			$ladmin_menu .= "\t</ul>\n";
		}
		$ladmin_menu .="\t</li>\n";
	}
	
	echo $ladmin_menu;	
} 
 
/* builds an array populated with all the infos needed for menu and submenu
it optionally does it twice to separate the sidemenus from regular. */
function lm_build () {
	global $menu, $submenu, $plugin_page, $pagenow, $parent_file;
	
	/* Most of the following garbage are bits from admin-header.php,
	 * modified to populate an array of all links to display in the menu*/	 
	$self = preg_replace('|^.*/wp-admin/|i', '', $_SERVER['PHP_SELF']);
	$self = preg_replace('|^.*/plugins/|i', '', $self);
	
	/* Make sure that "Manage" always stays the same. Stolen from Andy @ YellowSwordFish */
	$menu[5][0] = __("Write");
	$menu[5][1] = "edit_posts";
	$menu[5][2] = "post-new.php";
	$menu[10][0] = __("Manage");
	$menu[10][1] = "edit_posts";
	$menu[10][2] = "edit.php";	
	
	//get_admin_page_parent();	
	$altmenu = array();
	
	/* Step 1 : populate first level menu as per user rights */
	foreach ($menu as $key => $item)  {
	
	//select only main menu if you want to distinguish it
	if ($separatemenus = "1"){
		if ( $key > 29 && $key < 41 )continue;
	}
	
		// 0 = name, 1 = capability, 2 = file
		if ( current_user_can($item[1]) ) {
			$sys_menu_file = $item[2];
			if ( file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") )
				$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/admin.php?page={$item[2]}";			
			else
				$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/{$item[2]}";

			if (( strcmp($self, $item[2]) == 0 && empty($parent_file)) || ($parent_file && ($item[2] == $parent_file))) {
			$altmenu[$item[2]]['class'] = " class='topcurrent'";
			}else {
				//it took me a while to figure out this NOT cool way to feedback when editing existing posts or pages. --corpodibacco
				if ($item[0] == __("Dashboard")){$altmenu[$sys_menu_file]['class'] = " class='lmsidemenu'";/*$item[0] = "Dashboard |";*/}				
				elseif ( strpos($_SERVER['REQUEST_URI'], 'post.php?action') !== false && $item[2] == 'edit.php') 
				$altmenu['edit.php']['class'] = " class='topcurrent'";
				elseif ( strpos($_SERVER['REQUEST_URI'], 'page.php?action') !== false && $item[2] == 'edit.php') 
				$altmenu['edit.php']['class'] = " class='topcurrent'";				
				elseif ( strpos($_SERVER['REQUEST_URI'], 'link.php?link_id') !== false && $item[2] == 'edit.php') 
				$altmenu['edit.php']['class'] = " class='topcurrent'";	
				elseif ( strpos($_SERVER['REQUEST_URI'], 'comment.php?action') !== false && $item[2] == 'edit.php') 
				$altmenu['edit-comments.php']['class'] = " class='topcurrent'";
				elseif ( strpos($_SERVER['REQUEST_URI'], 'admin.php?page='.$item[2]) !== false) 
				$altmenu[$item[2]]['class'] = " class='topcurrent'";			
			}
			
			$altmenu[$item[2]]['name'] = $item[0];

			/* Windows installs may have backslashes instead of slashes in some paths, fix this */
			$altmenu[$item[2]]['name'] = str_replace(chr(92),chr(92).chr(92),$altmenu[$item[2]]['name']);
		}
	}
	
	/* Step 2 : populate second level menu */
	foreach ($submenu as $k=>$v) {
		foreach ($v as $item) {
			if (array_key_exists($k,$altmenu) and current_user_can($item[1])) {
				
				// What's the link ?
				$menu_hook = get_plugin_page_hook($item[2], $k);

				if (file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") || ! empty($menu_hook)) {
					$mtype = "<img src='" . LIGHTER_PATH . "images/plugin.png' height='16' width='16' alt=''/>&nbsp;";
					
					list($_plugin_page,$temp) = explode('?',$altmenu[$k]['url']);
					$link = $_plugin_page.'?page='.$item[2];
				} else {
					$icon = lm_icons($item[0]);
					$mtype = "<img src='" . LIGHTER_PATH . $icon . "' height='16' width='16' alt=''/>&nbsp;";
					$link =  $item[2];
				}
				
				/* Windows installs may put backslashes instead of slashes in paths, fix this */
				$link = str_replace(chr(92),chr(92).chr(92),$link);
				
				$altmenu[$k]['sub'][$item[2]]['url'] = $link;
				
				// Is it current page ?
				$class = '';
				if ( (isset($plugin_page) && $plugin_page == $item[2] && $pagenow == $k) || (!isset($plugin_page) && $self == $item[2] ) ) 
					$class=" class='subcurrent'";
				if ($class) {
					$altmenu[$k]['sub'][$item[2]]['class'] = $class;
					/*$altmenu[$k]['class'] = $class;*/
				}
				
				// What's its name again ?
				$altmenu[$k]['sub'][$item[2]]['name'] = $item[0];
				$altmenu[$k]['sub'][$item[2]]['icon'] = $mtype;
			}
		}
	}

	/* Step 3 : populate first level menu as per user rights for settings,plugins an users */
	if ($separatemenus = "1"){ //doing this only if you actually want to separate the two menus
		foreach ($menu as $key => $item)  {
		
		if ( $key <= 31 && $key >= 41 ) continue; // skip each menu item after 30 and before 40			
		
			// 0 = name, 1 = capability, 2 = file
			if ( current_user_can($item[1]) ) {
				$sys_menu_file = $item[2];
				if ( file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") )
					$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/admin.php?page={$item[2]}";			
				else				
					$altmenu[$item[2]]['url'] = get_settings('siteurl') . "/wp-admin/{$item[2]}";
				
				
				if (( strcmp ($self, $item[2])  == 0  && empty($parent_file) ) || ($parent_file && ($item[2] == $parent_file))) {
					$altmenu[$item[2]]['class'] = " class='topcurrent'";
				}else {
					if ($item[0] == __("Settings"))$altmenu[$sys_menu_file]['class'] = " class='lmsidemenu'";
					if (strstr($item[0], __("Plugins")))$altmenu[$sys_menu_file]['class'] = " class='lmsidemenu'";
					if ($item[0] == __("Users"))$altmenu[$sys_menu_file]['class'] = " class='lmsidemenu'";						
				}
				
				$altmenu[$item[2]]['name'] = $item[0];
				$altmenu[$item[2]]['class'];
				/* Windows installs may have backslashes instead of slashes in some paths, fix this */
				$altmenu[$item[2]]['name'] = str_replace(chr(92),chr(92).chr(92),$altmenu[$item[2]]['name']);
			}
		}
		
		/* Step 4 : populate second level menu for settings, plugins and users */
		foreach ($submenu as $k=>$v) {
			foreach ($v as $item) {
				if (array_key_exists($k,$altmenu) and current_user_can($item[1])) {
					
					// What's the link ?
					$menu_hook = get_plugin_page_hook($item[2], $k);
	
					if (file_exists(ABSPATH . "wp-content/plugins/{$item[2]}") || ! empty($menu_hook)) {
					$mtype = "<img src='" . LIGHTER_PATH . "images/plugin.png' height='16' width='16' alt=''/>&nbsp;";
						list($_plugin_page,$temp) = explode('?',$altmenu[$k]['url']);
						$link = $_plugin_page.'?page='.$item[2];
					} else {
						$icon = lm_icons($item[0]);
						$mtype = "<img src='" . LIGHTER_PATH . $icon . "' height='16' width='16' alt=''/>&nbsp;";
						$link =  $item[2];
					}
					
					/* Windows installs may put backslashes instead of slashes in paths, fix this */
					$link = str_replace(chr(92),chr(92).chr(92),$link);
					
					$altmenu[$k]['sub'][$item[2]]['url'] = $link;
					
					// Is it current page ?
					$class = '';
					if ( (isset($plugin_page) && $plugin_page == $item[2] && $pagenow == $k) || (!isset($plugin_page) && $self == $item[2] ) ) $class=" class='subcurrent'";
					if ($class) {
						$altmenu[$k]['sub'][$item[2]]['class'] = $class;
						/*$altmenu[$k]['class'] = $class;*/
					}
					
					// What's its name again ?
					$altmenu[$k]['sub'][$item[2]]['name'] = $item[0];
					$altmenu[$k]['sub'][$item[2]]['icon'] = $mtype;
				}
			}
		}
	}
	
	return ($altmenu);
}

//javascript
function lm_js($menu = '') {
	global $is_winIE, $pagenow;
	$lmoptions = get_option('lighter_options');	?>
	
<script type="text/javascript"><!--//--><![CDATA[//><!--
//preloading icons. I doubt this has any need to be. Anyone?
	<?php if ($lmoptions['display_icons']) { ?>
	Image1=new Image(16,16)
	Image1.src="<?php echo LIGHTER_PATH.'images/'; ?>information.png"			
	Image2=new Image(16,16)
	Image2.src="<?php echo LIGHTER_PATH.'images/'; ?>email_edit.png"			
	Image3=new Image(16,16)
	Image3.src="<?php echo LIGHTER_PATH.'images/'; ?>page_edit.png"			
	Image4=new Image(16,16)
	Image4.src="<?php echo LIGHTER_PATH.'images/'; ?>link_add.png"			
	Image5=new Image(16,16)
	Image5.src="<?php echo LIGHTER_PATH.'images/'; ?>folder_table.png"			
	Image6=new Image(16,16)
	Image6.src="<?php echo LIGHTER_PATH.'images/'; ?>folder_page.png"			
	Image7=new Image(16,16)
	Image7.src="<?php echo LIGHTER_PATH.'images/'; ?>application_link.png"			
	Image8=new Image(16,16)
	Image8.src="<?php echo LIGHTER_PATH.'images/'; ?>tag_blue_edit.png"			
	Image9=new Image(16,16)
	Image9.src="<?php echo LIGHTER_PATH.'images/'; ?>newspaper_link.png"			
	Image10=new Image(16,16)
	Image10.src="<?php echo LIGHTER_PATH.'images/'; ?>application_view_gallery.png"			
	Image11=new Image(16,16)
	Image11.src="<?php echo LIGHTER_PATH.'images/'; ?>layout.png"			
	Image12=new Image(16,16)
	Image12.src="<?php echo LIGHTER_PATH.'images/'; ?>brick.png"			
	Image13=new Image(16,16)
	Image13.src="<?php echo LIGHTER_PATH.'images/'; ?>layout_edit.png"			
	Image14=new Image(16,16)
	Image14.src="<?php echo LIGHTER_PATH.'images/'; ?>layout_header.png"			
	Image15=new Image(16,16)
	Image15.src="<?php echo LIGHTER_PATH.'images/'; ?>attach.png"			
	Image16=new Image(16,16)
	Image16.src="<?php echo LIGHTER_PATH.'images/'; ?>basket_edit.png"			
	Image17=new Image(16,16)
	Image17.src="<?php echo LIGHTER_PATH.'images/'; ?>comments.png"			
	Image18=new Image(16,16)
	Image18.src="<?php echo LIGHTER_PATH.'images/'; ?>comment_add.png"			
	Image19=new Image(16,16)
	Image19.src="<?php echo LIGHTER_PATH.'images/'; ?>brick_edit.png"			
	Image20=new Image(16,16)
	Image20.src="<?php echo LIGHTER_PATH.'images/'; ?>report_add.png"			
	Image21=new Image(16,16)
	Image21.src="<?php echo LIGHTER_PATH.'images/'; ?>report_go.png"			
	Image22=new Image(16,16)
	Image22.src="<?php echo LIGHTER_PATH.'images/'; ?>folder_link.png"			
	Image23=new Image(16,16)
	Image23.src="<?php echo LIGHTER_PATH.'images/'; ?>plugin_add.png"			
	Image24=new Image(16,16)
	Image24.src="<?php echo LIGHTER_PATH.'images/'; ?>plugin_edit.png"			
	Image25=new Image(16,16)
	Image25.src="<?php echo LIGHTER_PATH.'images/'; ?>user.png"			
	Image26=new Image(16,16)
	Image26.src="<?php echo LIGHTER_PATH.'images/'; ?>group_key.png"			
	Image27=new Image(16,16)
	Image27.src="<?php echo LIGHTER_PATH.'images/'; ?>application_view_list.png"			
	Image28=new Image(16,16)
	Image28.src="<?php echo LIGHTER_PATH.'images/'; ?>pencil.png"			
	Image29=new Image(16,16)
	Image29.src="<?php echo LIGHTER_PATH.'images/'; ?>zoom.png"			
	Image30=new Image(16,16)
	Image30.src="<?php echo LIGHTER_PATH.'images/'; ?>group_link.png"			
	Image31=new Image(16,16)
	Image31.src="<?php echo LIGHTER_PATH.'images/'; ?>lock.png"			
	Image32=new Image(16,16)
	Image32.src="<?php echo LIGHTER_PATH.'images/'; ?>book_link.png"			
	Image33=new Image(16,16)
	Image33.src="<?php echo LIGHTER_PATH.'images/'; ?>bullet_wrench.png"	
	Image34=new Image(16,16)
	Image34.src="<?php echo LIGHTER_PATH.'images/'; ?>group_key.png"	
	<?php } ?>
//in case two colors are too similar, alter the first one
function compare_colors (colore, sfondo) {
	if (colore != undefined) {	
		colore1 = colore.substring(4,colore.length - 1).split(', ');
		sfondo1 = sfondo.substring(4,sfondo.length - 1).split(', ');	
		colore1 = parseInt(colore1[0]) + parseInt(colore1[1]) + parseInt(colore1[2]);
		sfondo1 = parseInt(sfondo1[0]) + parseInt(sfondo1[1]) + parseInt(sfondo1[2]);		
		if ( (colore1 == sfondo1) ) {	
			var temp = 255 - ((255 - colore1) / 3);
			var colore1 = 'rgb(' + temp + ', ' + temp + ', ' + temp + ')';
			return colore1;
		} 		
		if (colore1 > sfondo1) {
			if ((colore1 - sfondo1) <= 255) {	
				var temp = 255 - ((255 - colore1) / 3);
				var colore1 = 'rgb(' + temp + ', ' + temp + ', ' + temp + ')';
				return colore1;
			} 
		}			
		if (colore1 < sfondo1) {		
			if ((sfondo1 - colore1) <= 255) {	
				var temp = 255 - ((255 - colore1) / 3);
				var colore1 = 'rgb(' + temp + ', ' + temp + ', ' + temp + ')';
				return colore1;
			} 
		}		
		return colore;
	}
}
//make color brighter or darker
function change_color(colore, valore, brighter) {
	if (colore != undefined) {
		var itwashex = false;
		if (colore.charAt(0) == '#') {	
			array = hex2num(colore)
			colore = 'rgb('+array[0]+', '+array[1]+', '+array[2]+')';
			var itwashex = true;
		}
		if (brighter == true) {
			colore = colore.substring(4,colore.length - 1).split(', ');
			colore[0] = parseInt(colore[0]) + valore; if ( isNaN(colore[0]) || (colore[0] > 255)) colore[0] = "255";
			colore[1] = parseInt(colore[1]) + valore; if ( isNaN(colore[1]) || (colore[1] > 255)) colore[1] = "255";
			colore[2] = parseInt(colore[2]) + valore; if ( isNaN(colore[2]) || (colore[2] > 255)) colore[2] = "255";
		} else {
			colore = colore.substring(4,colore.length - 1).split(', ');
			colore[0] = parseInt(colore[0]) - valore; if ( isNaN(colore[0]) || (colore[0] > 255)) colore[0] = "255";
			colore[1] = parseInt(colore[1]) - valore; if ( isNaN(colore[1]) || (colore[1] > 255)) colore[1] = "255";
			colore[2] = parseInt(colore[2]) - valore; if ( isNaN(colore[2]) || (colore[2] > 255)) colore[2] = "255";
		}
		if (itwashex == true) colore = num2hex(colore);
		else colore = 'rgb(' + colore.join(', ') + ')';	
		return colore;
	}
}
//RGB/hex conversions
function hex2num(hex) {
	if(hex.length == 4) hex = hex.substring(0,1)+hex.substring(1,4)+hex.substring(1,4);
	if(hex.charAt(0) == "#") hex = hex.slice(1); //Remove the '#' char - if there is one.
	hex = hex.toUpperCase();
	var hex_alphabets = "0123456789ABCDEF";
	var value = new Array(3);
	var k = 0;
	var int1,int2;
	for(var i=0;i<6;i+=2) {
		int1 = hex_alphabets.indexOf(hex.charAt(i));
		int2 = hex_alphabets.indexOf(hex.charAt(i+1)); 
		value[k] = (int1 * 16) + int2;
		k++;
	}
	return(value);
}
function num2hex(triplet) {
	var hex_alphabets = "0123456789ABCDEF";
	var hex = "#";
	var int1,int2;
	for(var i=0;i<3;i++) {
		int1 = triplet[i] / 16;
		int2 = triplet[i] % 16;

		hex += hex_alphabets.charAt(int1) + hex_alphabets.charAt(int2); 
	}
	return(hex);
}
// Resize menu to make sure it doesnt overlap with #user_info or blog title
function lm_resize() {
	// Reinit positions
	jQuery('#lm').css('width','');
	jQuery('#wphead').css('border-top-width', '30px');
	// Resize
	var lm_w = parseInt(jQuery('#lm').css('width').replace(/px/,''));
	var info_w = parseInt(jQuery('#user_info').css('width').replace(/px/,'')) || 130; // the " or 130" part is for when width = 'auto' (on MSIE..) to get 130 instead of NaN
	jQuery('#lm').css('width', (lm_w - info_w - 1)+'px' );
	var lm_h = parseInt(jQuery('#lm').css('height').replace(/px/,''));
	// Compare positions of first & last top level lis
	var num_li=jQuery('#lm li.lm_toplevel').length;
	var first_li = jQuery('#lm li.lm_toplevel').eq(0).offset();
	var last_li = jQuery('#lm li.lm_toplevel').eq(num_li-1).offset(); // Dunno why, but jQuery('#lm li.lm_toplevel :last') doesn't work...
	if (!lm_h) {lm_h = last_li.top + 25;}
	if ( first_li.top < last_li.top ) {
		jQuery('#wphead').css('border-top-width', (lm_h+4)+'px'); 
		/*jQuery('#lm li ul').css('top', lm_h+'px');*/	
	}
}
jQuery(document).ready(function() {
	// Remove unnecessary links in the top right corner 
	<?php if($lmoptions['reduce_userinfo']) { ?>
	var lmenu_userlinks = jQuery('#user_info p').html();
	if (lmenu_userlinks) {
	lmenu_userlinks = lmenu_userlinks.replace(/action=logout(.*?)<\/a> \| .*$/i, 'action=logout$1<\/a><\/p>' ); //this works with any local different link
		jQuery('#user_info p').html(lmenu_userlinks);
		jQuery('#user_info').css('z-index','81');
		}
	<?php } ?>	
	//Remove Howdy
	<?php if($lmoptions['remove_howdy']) { ?>
	var lmenu_userlinks = jQuery('#user_info p').html();
	var howdy = new RegExp("\!");
	if (lmenu_userlinks) {
		if (howdy.test(lmenu_userlinks)) { lmenu_userlinks = lmenu_userlinks.replace(/^(.*?)(<a.*?)(\!)(.*)$/i, '$2$4' ); 
		} else { lmenu_userlinks = lmenu_userlinks.replace(/^(.*?)(<a.*?a>)(.*?)( | .*)$/i, '$2$4' ); //this is for chinese, thanks to hit1205 
		}	
		jQuery('#user_info p').html(lmenu_userlinks);
		jQuery('#user_info').css('z-index','81');
		}
	<?php } ?>
	
	// get css values	
	//lm
	var lm_bgcolor = jQuery("#wphead").css('border-top-color');	
	if ( lm_bgcolor=='transparent') var lm_bgcolor = jQuery("body").css('background-color');		
	var lm_color = jQuery('#user_info a').css('color');
	var lm_color = change_color(lm_color,15,true); //testing this
	var lm_a_hover = change_color(lm_color,45,true);
	var lm_color = compare_colors (lm_color, lm_bgcolor);	
	//sidemenu
	var lm_a_sd =  jQuery("#user_info a").css('color');
	var lm_a_sd = change_color(lm_a_sd, 45, false);
	var lm_a_sd = compare_colors (lm_a_sd, lm_bgcolor);
	var lm_a_hoversd =  jQuery("#user_info a").css('color');	
	//current
	var lm_a_current = jQuery("#wphead h1").css('color');
	var lm_current_bgcolor = jQuery("#wphead").css('background-color');
	if ( (lm_current_bgcolor == "transparent") ) var lm_current_bgcolor = jQuery("body").css('background-color');
	var lm_a_current = compare_colors (lm_a_current, lm_current_bgcolor);
	var lm_li_currenthover = change_color(lm_a_current,81,false);
	//lmmod
	var ladminmod_bgimage =  jQuery("#adminmenu li a #awaiting-mod").css('background-image');
	var ladminmod_bgcolor =  jQuery("#adminmenu li a #awaiting-mod span").css('background-color');
	var ladminmod_color =  jQuery("#adminmenu li a #awaiting-mod span").css('color');
	var ladminmod_hovercolor = jQuery('#sidemenu a').css('border-bottom-color');	
	//submenu
	var lm_border =  jQuery("#wphead").css('background-color');
	if ( (lm_border == "transparent") ) var lm_border = jQuery("body").css('background-color');
	var lm_border = change_color(lm_border, 20, true);
	var lm_color = compare_colors (lm_color, lm_bgcolor);			
	//wphead
	var borderline = jQuery("#wphead #viewsite a").css('background-color');
	
	//apply css values	
	//#lm li a	
	jQuery('#lm li a.lm_over').css('background-color', lm_bgcolor).css('color', lm_color);
	<?php if ($is_winIE){ ?>jQuery('#lm li a').css('background-color', lm_bgcolor);<?php } ?>
	jQuery('#lm li a').css("color", lm_color);		
	jQuery('#lm li a').hover( 
		function() { jQuery(this).css('color', lm_a_hover);}, 
		function() { jQuery(this).css('color', lm_color);}
	);			
	//#lm li .topcurrent
	jQuery('#lm li .topcurrent').css("color", lm_a_current).css("background-color", lm_current_bgcolor);			
	jQuery('#lm li .topcurrent').hover( 
		function() { jQuery(this).css('color', lm_a_hover).css('background-color', lm_bgcolor);}, 
		function() { jQuery(this).css('color', lm_a_current).css('background-color', lm_current_bgcolor);}
	);
	//#lm li ul
	jQuery('#lm li ul').css("border-color", lm_border).css("background-color", lm_bgcolor);
	jQuery('#lm li ul').css("border-top-color", lm_bgcolor);	
	jQuery('#lm li ul.ulcurrent').hover( 
		function() { jQuery('#lm li .topcurrent').css('color', lm_a_hover).css('background-color', lm_bgcolor);}, 
		function() { jQuery('#lm li .topcurrent').css('color', lm_a_current).css('background-color', lm_current_bgcolor);}
	);			
	//#lm li ul li a
	jQuery('#lm li ul li a').hover( 
		function() { jQuery(this).css('color', lm_a_current).css('background-color', lm_current_bgcolor);}, 
		function() { jQuery(this).css('color', lm_color).css('background-color', lm_bgcolor);}
	);
	//#lm li ul li a.current
	jQuery('#lm li ul li a.subcurrent').css("color", lm_a_current).css("background-color", lm_current_bgcolor);			
	jQuery('#lm li ul li a.subcurrent').hover( 
		function() { jQuery(this).css('color', lm_li_currenthover).css('background-color', lm_current_bgcolor);}, 
		function() { jQuery(this).css('color', lm_a_current).css('background-color', lm_current_bgcolor);}
	);		
	//#lm .lmsidemenu
	<?php if ( $lmoptions['separate_menus'] ) { ?>
	jQuery('#lm .lmsidemenu').css("color", lm_a_sd);	
	jQuery('#lm .lmsidemenu').hover(
		function() { jQuery(this).css('color', lm_a_hoversd);},
		function() { jQuery(this).css('color', lm_a_sd);}
	);		
	<?php } ?>		
	//#awaiting-mod	
	jQuery("#lm li a #awaiting-mod").css('background-image', ladminmod_bgimage);
	jQuery('#lm li a #awaiting-mod span').css('background-color', ladminmod_bgcolor);	
	jQuery("#lm li a #awaiting-mod").css('color', ladminmod_color);
	jQuery('#lm li a #awaiting-mod').hover(
		function() { jQuery(this).css('color', ladminmod_hovercolor);}, 
		function() { jQuery(this).css('color', ladminmod_color);}
	);	
	//#update-plugins
	jQuery("#lm li a #update-plugins").css('background-image', ladminmod_bgimage);
	jQuery('#lm li a #update-plugins span').css('background-color', ladminmod_bgcolor);	
	jQuery("#lm li a #update-plugins").css('color', ladminmod_color);
	jQuery('#lm li a #update-plugins').hover(
		function() { jQuery(this).css('color', ladminmod_hovercolor);}, 
		function() { jQuery(this).css('color', ladminmod_color);}
	);	
	
	//this is crazy, man
	/*var bgimage = jQuery("#adminmenu li a #awaiting-mod").css('background-image');*/	
	<?php /*$im = imagecreatefromgif(get_option('siteurl'). '/wp-admin/images/comment-stalk-classic.gif' );	
	$start_x = 82;
	$start_y = 2;
	$color_index = imagecolorat($im, $start_x, $start_y);	
	$color_tran = imagecolorsforindex($im, $color_index);*/ ?>	
	/*jQuery("#lm li a #awaiting-mod span").css('background-color', ladminmod_bgcolor);				
	var ladminmod_hoverbgcolor = "<?php /*echo 'rgb('.$color_tran['red'].', '.$color_tran['green'].', '.$color_tran['blue'].')'*/ ?>";*/		
	/*var ladminmod_hoverbgcolor = jQuery('#sidemenu a').css('border-bottom-color'); //same as awaiting-mod hover bg	
	jQuery('#lm li a #awaiting-mod span').hover(
		function() { jQuery(this).css('background-color', ladminmod_hoverbgcolor);}, 
		function() { jQuery(this).css('background-color', ladminmod_bgcolor);}
	);*/		
	//#wphead bottom border. this imitates wordpress.com and .org
	jQuery('#wphead').css("border-bottom", "1px solid");			
	jQuery('#wphead').css("border-bottom-color", borderline);	
	// this is for compatiblity with baltic amber and other possible restyling
	// it doesn't work anymore, forget it
	/*jQuery('#wpcontent').css('border-top-width', '0px');
	jQuery('#wphead').css('border-top','');
	jQuery('#wphead').css('border-top-width', '30px');
	jQuery('#wphead').css('border-top-style', 'solid');	
	jQuery('#wphead').css('border-top-width', '30px');*/
		
	// Remove original menus (this is, actually, not needed, since the CSS should have taken care of this)
	jQuery('#sidemenu').hide();
	jQuery('#adminmenu').hide();
	<?php if ( ( $lmoptions['hide_submenu'] ) && ($pagenow != "media-upload.php") ) { ?>
	jQuery('#wpwrap #submenu').html('');
	<?php } ?>	
	// jQueryfication of the Son of Suckerfish Drop Down Menu
	// Original at: http://www.htmldog.com/articles/suckerfish/dropdowns/
	jQuery('#lm li.lm_toplevel').each(function() {
		jQuery(this).mouseover(function(){
			jQuery(this).addClass('lm_over');
			if (jQuery.browser.msie) {lm_hide_selects(true);}
		}).mouseout(function(){
			jQuery(this).removeClass('lm_over');
			if (jQuery.browser.msie) {lm_hide_selects(false);}
		});
	});	
	// Dynamically float submenu elements if there are too many
	jQuery('.lm_toplevel span').mouseover(
		function(){
			var menulength = jQuery(this).parent().parent().find('ul li').length;
			if (menulength >= parseInt(<?php echo $lmoptions['max_plugins']; ?>)) {
				jQuery(this).parent().parent().find('ul li').each(function(){
					jQuery(this).css('float', 'left');
				});
			}
		}
	);	
	// Function to hide <select> elements (display bug with MSIE)
	function lm_hide_selects(hide) {
		var hidden = (hide) ? 'hidden' : 'visible';
		jQuery('select').css('visibility',hidden);
	}	
	// Show our new menu
	jQuery('#lm').show();
	<?php if (!function_exists('wp_admin_fluency_css')) { ?>
			lm_resize();
			// Bind resize event		
			jQuery(window).resize(function(){
				lm_resize();
			});
	<?php } ?>	
	// WPMU : behavior for the "All my blogs" link
	jQuery( function($) {
		var form = $( '#all-my-blogs' ).submit( function() { document.location = form.find( 'select' ).val(); return false;} );
		var tab = $('#all-my-blogs-tab a');
		var head = $('#wphead');
		$('.blog-picker-toggle').click( function() {
			form.toggle();
			tab.toggleClass( 'current' );
			return false;
		});
	});	
})

//--><!]]></script><?php
}

//css
function lm_css() {
	global $pagenow, $is_winIE;
	$lmoptions = get_option('lighter_options');	
	if ( $lmoptions['hide_submenu'] ) $submenu = ', #wpwrap #submenu a';
	else $submenu = '';	
	if ( !$lmoptions['display_icons'] ) $icons = '#lm img {display:none}';
	else $icons = ''; ?>
    
<style type="text/css">	
#sidemenu, #adminmenu, #dashmenu<?php print $submenu; ?> {display:none;}
<?php print $icons; ?> 
#lm {
	left:0px;
	list-style-image:none;
	list-style-position:outside;
	list-style-type:none;
	margin:0pt;
	padding-left:8px;
	position:absolute;
	top:4px;
	width:95%;
	overflow:show;
	z-index:80;
	
}
#lm li {
	display:inline;
	list-style-image:none;
	list-style-position:outside;
	list-style-type:none;
	list-style: none;
	margin:0 3px;
	padding:0;
	white-space:nowrap;
	float: left;
	width: 1*;
	<?php if(!$is_winIE){ ?>margin-left:-4px;<?php } ?>
}

#lm a {
	text-decoration:none;
	line-height:200%;
	padding:0px 10px;
	display:block;
	width:1*; 
}
#lm li:hover,
#lm li.lm_over,
#lm li .topcurrent {
    -moz-border-radius-topleft: 2px;
    -moz-border-radius-topright: 2px;
    border-top-left-radius: 2px;
    border-top-right-radius: 2px;
    -webkit-border-top-right-radius: 2px;
    -webkit-border-top-left-radius: 2px;
}
#lm li ul {
	padding: 0;
	margin: 0;
	padding-bottom:5px;
	padding-top:5px;
	list-style: none;
	position: absolute;
	border:1px solid;	
    -moz-border-radius-bottomleft:4px;
    -moz-border-radius-bottomright:4px;
    border-bottom-right-radius:4px;
    border-bottom-left-radius:4px;
    -webkit-border-bottom-right-radius:4px;
    -webkit-border-bottom-left-radius:4px;		
	width: 1*;
	min-width:6em;
	left:-999em;
	list-style-position:auto;
	list-style-type:auto;
	<?php if($is_winIE){ ?>border-top:none;<?php } ?>
}
#lm li ul li {float:none;/*text-align:left;*/}
#lm li ul li a {
	line-height:195%;
}
#lm li.lm_over ul {
	left:auto;
	z-index:999999;
	top:26px;
}
#lm li a #awaiting-mod, #lm li a #update-plugins {
	position: absolute;
	margin-left: -0.2em;
	margin-top: 0.4em;
	font-size: 0.7em;
	background-repeat: no-repeat;
	background-position: 0 bottom;
	height: 1.2em;
	width: 1.6em;
}
<?php if (!$lmoptions['show_zero']) { ?>#lm li a .count-0 {display: none;}<?php } ?>
#lm li a:hover #awaiting-mod, #lm li a:hover #update-plugins {background-position: 0px bottom; /*was -80px but I have problems*/}
#lm li a #awaiting-mod span, #lm li a #update-plugins span {
	top: -0.7em;
	right: 3px;
	position: absolute;
	display: block;
	height: 1.4em;
	line-height: 1.3em;
	padding: 0 0.4em;
	-moz-border-radius: 3px;
	-khtml-border-radius: 3px;
	-webkit-border-radius: 3px;
	border-radius: 3px;
}
<?php if (!$lmoptions['display_icons']) { ?>#lm li ul li a.subcurrent:before {content: "\00BB \0020";}<?php } ?>
/* Mu Specific */
#lmmu_head {font-weight:bolder;}
#lmmu_head #all-my-blogs {
	position:relative;
	top:0px;
}

<?php if ($is_winIE) { ?>
#lm {top:5px;}
#lm li { text-align:left; margin-left:0px;}
#lm li.lm_over ul {left:inherit;top:25px;}
#lm li a #awaiting-mod {margin-top: -0.1em}
<?php } ?>

</style><?php
}

//add things to the wordpress admin header
function lm_head() {
	global $pagenow;
	if ($pagenow != "press-this.php" && $pagenow != "media-upload.php") { 
	?><!--Lighter Menus--><?php
		lm_css();
		lm_js();
	?><!--end Lighter Menus--><?php }
}

/***** Mu specific ****/
function lm_remove_blogswitch_init() {
	remove_action( '_admin_menu', 'blogswitch_init' );
	add_action( '_admin_menu', 'lm_blogswitch_init' );
}
function lm_blogswitch_init() {
	global $current_user, $current_blog;
	$blogs = get_blogs_of_user( $current_user->ID );
	if ( !$blogs )
		return;
	add_action( 'admin_menu', 'lm_blogswitch_ob_start' );
	add_action( 'dashmenu', 'blogswitch_markup' );
}

function lm_blogswitch_ob_start() {
	ob_start( 'lm_blogswitch_ob_content' );
}
function lm_blogswitch_ob_content( $content ) {
	// Menu with blog list
	$mumenu = preg_replace( '#.*%%REAL_DASH_MENU%%(.*?)%%END_REAL_DASH_MENU%%.*#s', '\\1', $content );
	$mumenu = str_replace ('<li>', '<li class="lm_sublevel">', $mumenu);
	$mumenu = preg_replace( '#</ul>.*?<form id="all-my-blogs"#s', '<li><form id="all-my-blogs"', $mumenu);
	$mumenu = str_replace ('</form>', '</form></li></ul>', $mumenu);	
	$content = preg_replace( '#%%REAL_DASH_MENU%%(.*?)%%END_REAL_DASH_MENU%%#s', '', $content );
	$content = str_replace( '<ul id="lm">', '<ul id="lm"><li class="lm_toplevel" id="lmmu_head"><a href="">My blogs</a><ul id="lmmu">'.$mumenu.'</li>', $content );	
	return $content;
}

// add the icons to the sub menu items
function lm_icons($menuitem){
	$options = get_option('lighter_options');
	$displayicons = $options['display_icons'];

	if ($displayicons == "1") {
		/*switch(substr($menuitem, 0, 21))*/
		switch($menuitem) {
		
			case __('Dashboard'):
				$i = "information.png";
				break;
				
			case __('Post'):
				$i = "email_edit.png";
				break;
			case __('Page'):
				$i = "page_edit.png";
				break;
			case __('Link'):
				$i = "link_add.png";
				break;
				
			case __('Posts'):
				$i = "folder_table.png";
				break;
			case __('Pages'):
				$i = "folder_page.png";
				break;
			case __('Links'):
				$i = "application_link.png";
				break;
			case __('Tags'):
				$i = "tag_blue_edit.png";
				break;
			case __('Link Categories'):
				$i = "newspaper_link.png";
				break;
			case __('Media Library'):
				$i = "application_view_gallery.png";
				break;
				
			case __('Themes'):
				$i = "layout.png";
				break;
			case __('Widgets'):
				$i = "brick.png";
				break;
			case __('Theme Editor'):
				$i = "layout_edit.png";
				break;
			case __('Header Image and Color'):
				$i = "layout_header.png";
				break;			
				
			case __('Uploads'):
				$i = "attach.png";
				break;
			case __('Categories'):
				$i = "basket_edit.png";
				break;
			case __('Comments'):
				$i = "comments.png";
				break;
			case substr(__('Awaiting Moderation (%s)'),0,21):
				$i = "comment_add.png";
				break;
			case __('Files'):
				$i = "brick_edit.png";
				break;
			case __('Import'):
				$i = "report_add.png";
				break;
			case __('Export'):
				$i = "report_go.png";
				break;
			case __('Import Links'):
				$i = "folder_link.png";
				break;
			case __('Plugins'):
				$i = "plugin_add.png";
				break;
			case __('Plugin Editor'):
				$i = "plugin_edit.png";
				break;
			case __('Your Profile'):
				$i = "user.png";
				break;
			case __('Authors &amp; Users'):
				$i = "group_key.png";
				break;
			case __('General'):
				$i = "application_view_list.png";
				break;
			case __('Writing'):
				$i = "pencil.png";
				break;
			case __('Reading'):
				$i = "zoom.png";
				break;
			case __('Discussion'):
				$i = "group_link.png";
				break;
			case __('Privacy'):
				$i = "lock.png";
				break;
			case __('Permalinks'):
				$i = "book_link.png";
				break;
			case __('Miscellaneous'):
				$i = "bullet_wrench.png";
				break;
		}
		if (substr($menuitem,0,7) == substr(__('Authors &amp; Users'),0,7)) 
		{
			$i = "group_key.png";
		}
		/*if ($i == "") $i =*/ 
		return 'images/'.$i;
	}
}

//creates the option page
function lm_page(){
	// Check Whether User Can Manage Options
	if(!current_user_can('manage_options'))die('Access Denied');
	$mode = trim($_GET['mode']);
	
	//handle the post event
	if(!empty($_POST['do'])) {
		switch($_POST['do']) {
			case 'Update' :
				$lmoptions = array(
				'display_icons' => $_POST['disp_ico'],
				'separate_menus' => $_POST['sep_menu'],
				'reduce_userinfo' => $_POST['reduce_userinfo'],
				'remove_howdy' => $_POST['remove_howdy'],
				'hide_submenu' => $_POST['hide_submenu'],
				'max_plugins' => $_POST['max_plugins'],
				'show_zero' => $_POST['show_zero'],
				'uninstall' => '', 
				'folder' => ''
				);
				$update_lighter_options = update_option('lighter_options', $lmoptions);		
				if ($update_lighter_options) { ?>
                <div id="message" class="updated fade"><p>
                <?php $path = get_option('siteurl'). '/wp-admin/themes.php?page=' . basename(__FILE__);
				echo str_replace("%s",$path, __('<strong>Options saved.</strong> You should probably <a href="%s">open this page</a> again.', 'lighter-menus'));
				?></p></div><?php }	
			break;
			
			case 'Deactivate' :
				$lmoptions = get_option('lighter_options');
				$lmoptions = array(
				'display_icons' => $lmoptions['display_icons'],
				'separate_menus' => $lmoptions['separate_menus'],
				'reduce_userinfo' => $lmoptions['reduce_userinfo'],
				'remove_howdy' => $lmoptions['remove_howdy'],
				'hide_submenu' => $lmoptions['hide_submenu'],
				'max_plugins' => $lmoptions['max_plugins'],
				'show_zero' => $lmoptions['show_zero'],
				'uninstall' => $_POST['remove'],
				'folder' => $_POST['folder']
				);
				$update_lighter_options = update_option('lighter_options', $lmoptions);		
				$mode = 'end-UNINSTALL';			
			break;				
		}
		
	}	
	
	switch($mode) {
		//  Deactivating
		case 'end-UNINSTALL':
				$deactivate_url = get_option("siteurl"). '/wp-admin/plugins.php?action=deactivate&plugin='.basename(dirname(__FILE__)). '/lighter-menus.php';
				if(function_exists('wp_nonce_url'))	$deactivate_url = wp_nonce_url($deactivate_url, 'deactivate-'.basename(dirname(__FILE__)). '/lighter-menus.php');	       
	 
				//feedback the deletion option
				$lmoptions = get_option('lighter_options');
				?><div class="wrap"><h2><?php echo _e('Deactivate Lighter Menus', 'lighter-menus') ?></h2>
                <p><strong><a href="<?php echo $deactivate_url ?>">
				<?php echo _e('Click Here</a> to deactivate Lighter Menus.', 'lighter-menus'); ?>
				</strong></p><?php			
				if( ($lmoptions['uninstall'] == 1) || ($lmoptions['folder'] == 1) ) echo __('Warning:', 'lighter-menus') . '<br />';
				if( $lmoptions['uninstall'] == 1 ) echo '<font color="#990000">'.__('The plugin options will be removed.', 'lighter-menus').'</font><br />';				
				if( $lmoptions['folder'] == 1) echo '<font color="#990000">'. __('The plugin folder will be removed.', 'lighter-menus').'</font><br />';								
                ?></div><?php
			break;
			
	// Main Page
	default:
	
	$lmoptions = array();
	$lmoptions = get_option('lighter_options');	
	if ( $lmoptions['display_icons'] ) $displayicons_selected = 'checked';
	if ( $lmoptions['separate_menus'] ) $separatemenus_selected = 'checked';
	if ( $lmoptions['reduce_userinfo'] ) $reduceuserinfo_selected = 'checked';
	if ( $lmoptions['remove_howdy'] ) $removehowdy_selected = 'checked';
	if ( $lmoptions['hide_submenu'] ) $showsubmenu_selected = 'checked';
	if ( $lmoptions['show_zero'] ) $showzero_selected = 'checked';
	if ( $lmoptions['uninstall'] ) $remove_lm_selected = 'checked';
	if ( $lmoptions['folder'] ) $folder_lm_selected = 'checked';		
	?>		

	<?php /*options*/ ?>
	<div class="wrap"><br/><h2>Lighter Menus - <?php echo _e('Customization Options','lighter-menus') ?></h2><br/>
	<?php echo _e('These options can be used to customize the appearance of the menus.','lighter-menus') ?><br/><br/>
    
	<form method="post" action="<?php $_SERVER['REQUEST_URI'] ?>">
	<table class="form-table">
    
    <tr valign="top"><th scope="row"><img src="<?php echo LIGHTER_PATH; ?>/images/icons.png" /></th><td>    
    <div><input type="checkbox" name="disp_ico" value="1" <?php echo $displayicons_selected ?> />
    &nbsp;<?php echo _e('Display icons in the menus.','lighter-menus') ?></div>     
    </td></tr>
    
    <tr valign="top"><th scope="row"><img src="<?php echo LIGHTER_PATH; ?>/images/submenus.png" /></th><td>
    <div><input type="checkbox" name="hide_submenu" value="1" <?php echo $showsubmenu_selected ?> />
	&nbsp;<?php echo _e('Hide the regular admin submenu.','lighter-menus') ?></div>    
    </td></tr>
    
    <tr valign="top"><th scope="row"><img src="<?php echo LIGHTER_PATH; ?>/images/menus.png" /></th><td> 
    <div><input type="checkbox" name="sep_menu" value="1" <?php echo $separatemenus_selected ?> />
	&nbsp;<?php echo _e('Show the "Settings" "Plugin" and "Users" menus in a different color and keep them to the right side if new plugin menus are added.','lighter-menus') ?></div>    
    </td></tr>
    
    <tr valign="top"><th scope="row"><img src="<?php echo LIGHTER_PATH; ?>/images/userinfo.png" /></th><td>
    <div><input type="checkbox" name="reduce_userinfo" value="1" <?php echo $reduceuserinfo_selected ?> />
    &nbsp;<?php echo _e('Remove the \'Help\', \'Forum\' and \'Turbo\' links from the user menu.','lighter-menus') ?></div>    
    </td></tr>
    
    <tr valign="top"><th scope="row"><img src="<?php echo LIGHTER_PATH; ?>/images/removehowdy.png" /></th><td>
    <div><input type="checkbox" name="remove_howdy" value="1" <?php echo $removehowdy_selected ?> />
    &nbsp;<?php echo _e('Remove the \'Howdy\' before the user menu.','lighter-menus') ?></div>    
    </td></tr>

    <tr valign="top"><th scope="row"><img src="<?php echo LIGHTER_PATH; ?>/images/showzero.png" /></th><td>
    <div><input type="checkbox" name="show_zero" value="1" <?php echo $showzero_selected ?> />
    &nbsp;<?php echo _e('Show the total comments awaiting moderation even with zero comments to moderate.','lighter-menus') ?></div>    
    </td></tr>
    
    <tr valign="top"><th scope="row"><img src="<?php echo LIGHTER_PATH; ?>/images/menulines.png" /></th><td>
    <?php $inputlines = '<input style="border:thin solid #ccc" type="text" name="max_plugins" value="'.$lmoptions['max_plugins'].'" size="2" />';
    echo str_replace ("%x",$inputlines, __('A menu with more than %x lines will not drop down in a column but float to the right.','lighter-menus')); ?>    
    </td></tr>
    
	</table>
    <input type="hidden" name="do" value="Update" />
	<div class="submit"><input type="submit" value="<?php echo _e('Update Options &raquo;', 'lighter-menus') ?>" /></div>
    </form></div><br/> 
    
    <?php /*uninstall*/ ?>
    <div class="wrap"><br/><h2>Lighter Menus - <?php echo _e('Deactivation','lighter-menus') ?></h2><br/>
    <?php echo _e('Use this to deactivate Lighter Menus and optionally remove it completely.','lighter-menus') ?><br/><br/>   
    
	<form method="post" action="<?php $_SERVER['REQUEST_URI'] ?>">
    <table class="form-table">
    <tr valign="top">
    <th scope="row"><?php _e('When deactivating', 'lighter-menus'); ?></th>
    <td>
    <input type="checkbox" name="remove" value="1" <?php echo $remove_lm_selected ?> />
    <?php echo _e('Remove the above options from the database.','lighter-menus') ?><br />
    <input type="checkbox" name="folder" value="1" <?php echo $folder_lm_selected ?> />
    <?php echo _e('Remove the plugin folder.','lighter-menus') ?>
    </td>
    </tr>
	</table>
    <input type="hidden" name="do" value="Deactivate" />
	<div class="submit"><input type="submit" value="<?php echo _e('Deactivate Lighter Menus &raquo;','lighter-menus') ?>" /></div>
    </form></div>    

    <?php /*link footer*/ ?>    
    <div style="text-align:center;" class="wrap" >
    <ul><li style="display:inline;list-style:none;">
    <a href="http://www.italyisfalling.com/lighter-menus/">
	<?php echo _e('Plugin\'s Homepage','lighter-menus') ?></a> | </li>
    <!--<li style="line-height:1em;display:inline;list-style:none;letter-spacing:0.5%;">Donate | </li>-->
    <li style="display:inline;list-style:none;"><a href="http://www.italyisfalling.com/coding"><?php echo _e('Other plugins','lighter-menus') ?></a></li>
    </ul></div></div>	
	<?php } //end switch mode
}

//add pages to wordpress theme pages
function lm_pages() {
	add_theme_page( __('Lighter Menus Options','lighter-menus'),'Lighter Menus', 9, basename(__FILE__), 'lm_page');
}

//upon activation
function lm_activation() {
	//must see if old options exist even if empty
	global $wpdb;
	$sql1 = "SELECT option_name FROM $wpdb->options WHERE option_name = 'lad_display_icons'";
	$opt1 = $wpdb->get_var($sql1);
	$sql2 = "SELECT option_name FROM $wpdb->options WHERE option_name = 'lad_separate_menus'";
	$opt2 = $wpdb->get_var($sql2);
   
	//convert pre-2.6.1 options
	if ($opt1) {
		$temp_1 = get_option('lad_display_icons');
		delete_option('lad_display_icons');
	} else $temp_1 =  1;
	
	if ($opt2) {
		$temp_2 = get_option('lad_separate_menus');
		delete_option('lad_separate_menus');
	} else $temp_2 =  1;

	//creates new array option
	$lmoptions = get_option('lighter_options');
    if( false === $lmoptions || count($lmoptions <= 4) || !is_array($lmoptions) ) {	
		$lmoptions = array();
        $lmoptions['display_icons'] = $temp_1;
        $lmoptions['separate_menus'] = $temp_2;
		$lmoptions['reduce_userinfo'] = '';
		$lmoptions['remove_howdy'] = '';
		$lmoptions['hide_submenu'] = '1';
		$lmoptions['max_plugins'] = '30';
		$lmoptions['show_zero'] = '';
		$lmoptions['uninstall'] = '';
		$lmoptions['folder'] = '';
        add_option('lighter_options', $lmoptions);
    }
}

//upon deactivation
function lm_deactivation() {
	global $wp_filesystem;
    $lmoptions = get_option('lighter_options');    

	//delete the folder
	if ($lmoptions['folder'] == 1) {
		
		//few things stolen from wp_includes/update.php
		// Is a filesystem accessor setup?
		if ( ! $wp_filesystem || !is_object($wp_filesystem) ) WP_Filesystem();	
		
		if ( ! is_object($wp_filesystem) ) return new WP_Error('fs_unavailable', __('Could not access filesystem.'));
		if ( $wp_filesystem->errors->get_error_code() ) return new WP_Error('fs_error', __('Filesystem error'), $wp_filesystem->errors);

		// Remove the existing plugin.
		$dir = realpath(dirname(__FILE__));	
		
		//check if it is a window server and changes path accordingly (so that it works on your xampp too.)
		$dir = $dir . '/';		
		if ( strpos($dir, '\\')) $dir = str_replace('/','\\',$dir);
		
		//could something like this actually happen?
		if ( substr($dir,strlen($dir)-7) == 'plugins' || substr($dir,strlen($dir)-8) == 'wp-admin' || substr($dir,strlen($dir)-11) == 'wp-includes')
		return new WP_Error('delete_failed', __('Ooops. The plugin is not in its own folder, or there is something else going on.'));
	
		//cross your fingers, it is going to delete. This actually deleted my wp-admin folder once.
		$deleted = $wp_filesystem->delete($dir, true);		
		if ( !$deleted ) return new WP_Error('delete_failed', __('Could not remove the plugin folder'));		
	}	
	 
	//delete the options
	if($lmoptions['uninstall'] == 1) delete_option('lighter_options');		
}

// excuse me, I'm hooking into Wordpress.
global $wpmu_version;
if ($wpmu_version)
	add_action( '_admin_menu', 'lm_remove_blogswitch_init', -100 );

if (is_admin()) {
	add_action('init', create_function('', 'wp_enqueue_script("jquery");')); 
}
register_activation_hook(__FILE__, 'lm_activation');
register_deactivation_hook(__FILE__, 'lm_deactivation');
add_action('dashmenu', 'lm');
add_action('admin_head', 'lm_head');
add_action('admin_menu', 'lm_pages');
?>