<?php
/*
Plugin Name: TAC (Theme Authenticity Checker)
Plugin URI: http://builtbackwards.com/projects/tac/
Description: TAC scans all of your theme files for potentially malicious and unwanted code.
Author: builtBackwards
Version: 1.3
Author URI: http://builtbackwards.com/
*/

/*  Copyright 2008  builtBackwards (William Langford and Sam Leavens) - (email : contact@builtbackwards.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Check the theme
function tac_check_theme($template_files, $theme_title) {
	foreach ($template_files as $tfile)
	{	
		/*
		 * Check for base64 Encoding
		 * Here we check every line of the file for base64 functions.
		 * 
		 */
			
		$lines = file($tfile, FILE_IGNORE_NEW_LINES); // Read the theme file into an array

		$line_index = 0;
		$is_first = true;
		foreach($lines as $this_line)
		{
			if (stristr ($this_line, "base64")) // Check for any base64 functions
			{
				if ($is_first) {
						$the_result .= tac_make_edit_link($tfile, $theme_title); 
						$is_first = false;
					}
				$the_result .= "<div class=\"tac-bad\"><strong>Line " . ($line_index+1) . ":</strong> \"" . trim(htmlspecialchars(substr(stristr($this_line, "base64"), 0, 45))) . "...\"</div>";
			}
			$line_index++;
		}
		
		/*
		 * Check for Static Links
		 * Here we utilize a regex to find HTML static links in the file.
		 * 
		 */

		$file_string = file_get_contents($tfile);

		$url_re='([[:alnum:]\-\.])+(\\.)([[:alnum:]]){2,4}([[:blank:][:alnum:]\/\+\=\%\&\_\\\.\~\?\-]*)';
		$title_re='[[:blank:][:alnum:][:punct:]]*';	// 0 or more: any num, letter(upper/lower) or any punc symbol
		$space_re='(\\s*)'; 
		
		if (preg_match_all ("/(<a)(\\s+)(href".$space_re."=".$space_re."\"".$space_re."((http|https|ftp):\\/\\/)?)".$url_re."(\"".$space_re.$title_re.$space_re.">)".$title_re."(<\\/a>)/is", $file_string, $out, PREG_SET_ORDER))
		{
			$static_urls .= tac_make_edit_link($tfile, $theme_title); 
						  
			foreach( $out as $key ) {
				$static_urls .= "<div class=\"tac-ehh\">";
				$static_urls .= htmlspecialchars($key[0]);
				$static_urls .= "</div>";
			}			  
		}  
	} // End for each file in template loop
	
	// Assemble the HTML results for the completed scan of the current theme
	if (!isset($the_result) && !isset($static_urls)) {
		return "<div class=\"tac-good-notice\">Theme Ok!</div>";
	} else {
		if(isset($the_result)) {
			$final_string = "<div class=\"tac-bad-notice\">Encrypted Code Found!</div>".$the_result."";
		} else {
			$final_string = "<div class=\"tac-good-notice\">Theme Ok!</div>";
		}
		if(isset($static_urls)) {
			$final_string .= "<div class=\"tac-ehh-notice\">Check these static link(s)...</div>".$static_urls;
		}		
		return $final_string;
	}
}


function tac_make_edit_link($tfile, $theme_title) {
	// Assemble the HTML links for editing files with the built-in WP theme editor
	
	if ($GLOBALS['wp_version'] >= "2.6") {
		return "<div class=\"file-path\"><a href=\"theme-editor.php?file=/" . substr(stristr($tfile, "themes"), 0) . "&amp;theme=" . urlencode($theme_title) ."\">" . substr(stristr($tfile, "wp-content"), 0) ."</a></div>";
	} else {
		return "<div class=\"file-path\"><a href=\"theme-editor.php?file=" . substr(stristr($tfile, "wp-content"), 0) . "&amp;theme=" . urlencode($theme_title) ."\">" . substr(stristr($tfile, "wp-content"), 0) ."</a></div>";
	}
	
}

function tac_get_template_files($template) {
	// Scan through the template directory and add all php files to an array
	
	$theme_root = get_theme_root();
	
	$template_files = array();
	$template_dir = @ dir("$theme_root/$template");
	if ( $template_dir ) {
		while(($file = $template_dir->read()) !== false) {
			if ( !preg_match('|^\.+$|', $file) && preg_match('|\.php$|', $file) )
				$template_files[] = "$theme_root/$template/$file";
		}
	}

	return $template_files;
}

function tac_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('themes.php',__('TAC'), __('TAC'), '10', 'tac.php', 'tac');
	}

function tac_init() {
	add_action('admin_menu', 'tac_page');
}

add_action('init', 'tac_init');

function tac() {
	?>
<div class="wrap">
    <h2>
        <?php _e('TAC (Theme Authenticity Checker)'); ?>
    </h2>
    <div class="pinfo">
        TAC checks themes for malicious and potentially unwanted code.<br />For more info please go to the plugin page: <a href="http://builtbackwards.com/projects/tac/">http://builtbackwards.com/projects/tac/</a>
    </div>
    <?php
	$themes = get_themes();
	$theme_names = array_keys($themes);
	natcasesort($theme_names);
	foreach ($theme_names as $theme_name) {
		$template_files = tac_get_template_files($themes[$theme_name]['Template']);
		$title = $themes[$theme_name]['Title'];
		$version = $themes[$theme_name]['Version'];
		$author = $themes[$theme_name]['Author'];
		$screenshot = $themes[$theme_name]['Screenshot'];
		$stylesheet_dir = $themes[$theme_name]['Stylesheet Dir'];
	?>
    <div id="tacthemes">
        <?php if ( $screenshot ) : ?>
        <img src="<?php echo get_option('siteurl') . '/wp-content' . str_replace('wp-content', '', $stylesheet_dir) . '/' . $screenshot; ?>" alt="" />
		<?php else : ?>
		<div class="tacnoimg">No Screenshot Found</div>
        <?php endif; 
		?>
		<div class="tacresults">
	        <h3>
	            <?php echo "$title $version by $author"; ?>
	        </h3>
	        <?php echo tac_check_theme($template_files, $title); ?>
		</div>
    </div>
    <?php
	}
	echo '</div>';
}

// CSS to format results of themes check
function tac_css() {
echo '
<style type="text/css">
<!--
.tac-bad, .tac-ehh {
	border: 1px inset #000;
    width: 90%;
    margin-left: 10px;
    font-family: "Courier New", Courier, monospace;
    padding: 5px;	
	margin-bottom: 10px;
}

.tac-bad {
	background: #FFC0CB;
}

.tac-ehh {
    background: #FFFEEB;    
}

.tac-good-notice {
    width: 90px;
    background: #3fc33f;
    font-size: 120%;
    margin: 20px 10px 0px 0px;
    padding: 10px;
	border: 1px solid #000;
}

.tac-bad-notice {
    width: 185px;
    background: #FFC0CB;
    font-size: 120%;
    margin: 20px 10px 0px 0px;
    padding: 10px;
	border: 1px solid #000;
}

.tac-ehh-notice {
    width: 215px;
    background: #FFFEEB;
    font-size: 120%;
    margin: 20px 10px 0px 0px;
    padding: 10px;
	border: 1px solid #ccc;
}

.file-path {
	color: #666666;
	text-align: right;
	width: 92%;
	font-size: 12px;
	padding-top: 5px;
}

.file-path a {
	text-decoration: none;
}

.pinfo {
    background: #DCDCDC;
    margin: 5px;
    padding: 5px;
	margin-bottom: 40px;
}

#tacthemes {
    padding-bottom: 20px;
    border-bottom: 1px solid #ccc;
    margin: 10px;
}

#tacthemes img, .tacnoimg {
    float: left;
    width: 100px;
    height: 75px;
    border: 1px solid #000;
    margin: 10px 0px 10px 10px;
	text-align: center;
	font-size: 16px;
	color: #DCDCDC;
}

.tacresults {
	margin-left: 130px;
}
-->
</style>
';
	}

add_action('admin_head', 'tac_css');
?>
