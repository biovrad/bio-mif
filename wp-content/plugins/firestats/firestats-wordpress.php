<?php
/*
 Plugin Name: FireStats
 Plugin URI: http://firestats.cc
 Description: Statistics plugin for WordPress.
 Version: 1.6.7-stable
 Author: Omry Yadan
 Author URI: http://blog.firestats.cc
 */

// this is an version of the FireStats this file is a part of.
// it's used by firestats core to detect if the correct file is installed.
// (there can in be inconsistencies in case of a satelitte installation).
define('FS_WORDPRESS_PLUGIN_VER','1.6.7-stable');

/**
 * Initialize FireStats callbacks in any case
 */
fs_initialize_fs_callbacks();


// This bit pretends to be the any file inside firestats path
// so the browser will allow the js code to send the ajax request.
if (isset($_GET['fs_javascript']))
{
	add_action('init','fs_resume_js_call');
}

function fs_resume_js_call()
{
	$path = fs_get_firestats_path();
	$file = $_GET['fs_javascript'];
	unset($_GET['fs_javascript']);
	
	$allowed_files = array
	(
		'php/ajax-handler.php',
		'php/window-donation.php',
		'css/base.css.php',
		'js/page-settings.js.php',
		'php/page-settings.php',
		'js/page-wordpress-settings.js.php',
		'php/page-wordpress-settings.php',
		'js/page-database.js.php',
		'php/page-database.php',
		'js/page-users.js.php',
		'css/page-users.css.php',
		'php/page-users.php',
		'js/page-sites.js.php',
		'css/page-sites.css.php',
		'php/page-sites.php',
		'php/page-tools.php',
		'php/window-add-excluded-url.php',
		'php/window-add-excluded-ip.php',
		'php/window-delete-site.php',
		'php/window-new-edit-site.php',
		'php/window-edit-user.php',
		'php/window-delete-user.php',
		'php/tools/system_test.php',
		'js/firestats.js.php'
	);
	
	
	$found = false;
	
	foreach ($allowed_files as $a)
	{
		if ($file == $a)
		{
			$found = true;
			break;
		}
	}
	
	if (!$found) die("invalid fs_javascript");
		
	require_once("$path/$file");
	die();
}

global $FS_CONTEXT;
$FS_CONTEXT = array();
$FS_CONTEXT['TYPE'] = 'WORDPRESS';
$FS_CONTEXT['WP_PATH'] = fs_get_wp_config_path();
$FS_CONTEXT['JAVASCRIPT_URL'] = fs_get_js_url();

/**
 * Only initialize the plugin if not indirectly invoked from ajax plugin
 * to avoid wierd order of initialization problems caused by various wordpress versions.
 */
if (!defined('FS_AJAX_HANDLER'))
{
	$is_wpmu = fs_is_wpmu();
	fs_initialize_wp_plugin($is_wpmu);
}

function fs_initialize_fs_callbacks()
{
	$FS_PATH = fs_get_firestats_path();
	if ($FS_PATH)
	{
		// if WPLANG is defined, set FireStats's default language to it.
		if(defined('WPLANG') && !defined('FS_DEFAULT_LANG')) define('FS_DEFAULT_LANG',WPLANG);
		
		require_once($FS_PATH.'/php/init.php');
		fs_add_action("db_upgraded", "fs_plugin_db_update");
		fs_add_action("authenticated", "fs_register_dashboard_widgets");
	}
}

function fs_initialize_wp_plugin($is_wpmu)
{
	if (!function_exists("add_action")) return;

	add_action('wp_head', 'fs_add_wordpress', 1);
	add_action('admin_footer', 'fs_admin_footer');
	add_action('admin_menu', 'fs_add_page');
	add_action('admin_head', 'fs_admin_head');
	add_action('widgets_init', 'fs_widget_init');
	add_action('publish_post', 'fs_update_post_title');
	add_action('edit_post', 'fs_update_post_title');
	add_action('after_plugin_row','fs_new_version_nag');
	if (!function_exists('wp_add_dashboard_widget')) add_filter('wp_dashboard_widgets', 'fs_add_dashboard_widgets');

	// wpmu
	if ($is_wpmu)
	{
		add_action('delete_blog','fs_delete_wpmu_blog');
	}

	global $wp_version;
	if (!fs_is_wpmu() && version_compare($wp_version,"2.0") == -1) // wordpress is older than 2.0
	{
		$activated = (basename($_SERVER['SCRIPT_NAME']) == 'plugins.php' && isset($_GET['activate']));
		if ($activated) fs_activate();
	}
	else
	{
		register_activation_hook(__FILE__, 'fs_activate');
	}


	if (get_option('firestats_add_comment_flag') == 'true')
	{
		add_filter('get_comment_author_link', 'fs_add_comment_flag', 100);
	}

	if (get_option('firestats_add_comment_browser_os') == 'true')
	{
		add_filter('get_comment_author_link', 'fs_add_comment_browser_os', 100);
	}

	$FS_PATH = fs_get_firestats_path();
	if ($FS_PATH) // needed when installing in satellite mode
	{
		require_once($FS_PATH.'/php/api.php');
	}

	// show footer by default
	$show_footer = get_option('firestats_show_footer');
	if ((empty($show_footer) && DEFAULT_SHOW_FIRESTATS_FOOTER) || $show_footer == "true")
	{
		add_action('wp_footer','fs_echo_footer');
	}

	$FS_PATH = fs_get_firestats_path();
	if ($FS_PATH)
	{
		// only relevant if we know FS path.
		// otherwise fs_do_action is not in the scope.
		fs_do_action("wordpress_plugin_initialized");
	}
}


function fs_activate()
{
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH)
	{
		return;
	}

	require_once($FS_PATH.'/php/db-setup.php');
	$res = fs_install();
	if ($res !== true)
	{
		$plugin_name = substr(__FILE__, strlen(ABSPATH . PLUGINDIR . '/'));
		deactivate_plugins($plugin_name);
		wp_die(sprintf("Error installing FireStats tables: %s",$res));
		return;
	}

    $res = fs_register_wordpress();
    if ($res !== true)
    {
        wp_die($res);
        return;
    }
    fs_update_post_titles();

}

function fs_update_post_title($id)
{
	$post = &get_post($id);
	if ($post->post_type == 'post' || $post->post_type == 'page')
	{
		$FS_PATH = fs_get_firestats_path();
		$site_id = get_option('firestats_site_id');
		if (!$FS_PATH)
		{
			return;
		}
		require_once($FS_PATH.'/php/db-sql.php');


		$title = get_the_title($id);
		if (empty($title))
		{
			return;
		}
		$link = get_permalink($id);
		if (empty($link))
		{
			return;
		}

		// make sure the url exists in the urls table.;
		$res = fs_insert_url($link, $site_id);

		if (!$res)
		{
			echo $res;
			return;
		}

		// replace title with current one
		fs_set_url_title($link,$title);

		// mark url as a post
		if ($post->post_type == 'post')
		{
			fs_set_url_type($link,FS_URL_TYPE_POST);
		}
	}

}

/**
 * Registers this instance of wordpress with FireStats.
 * This is requires so that if there is more than one blog/system that works with
 * the same FireStats instance it will be possible to filter the stats per site.
 */
function fs_register_wordpress()
{
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return true;
	require_once($FS_PATH.'/php/db-sql.php');


	$firestats_site_id = get_option('firestats_site_id');
	if ($firestats_site_id == null && fs_is_wpmu())
	{
		// for wpmu sites, use the blog id as the firestats site id.
		global $blog_id;
		$firestats_site_id = $blog_id;
	}

	$site_exists = fs_site_exists($firestats_site_id);
	if (is_string($site_exists)) return $site_exists;
	if (!$site_exists)
	{
		$firestats_site_id = fs_register_site($firestats_site_id);
		if (!is_numeric($firestats_site_id))
		{
			$site_info = ($site_id != null ? "(site_id=$firestats_site_id)" : "(new site)");
			return "FireStats: error registering blog ".$site_info . " : $firestats_site_id";
		}
		update_option('firestats_site_id',$firestats_site_id);
	}

	$name = get_option('blogname');
	if (empty($name))
	{
		$name = "Unamed blog (#$firestats_site_id)";
	}
	
	$type = FS_SITE_TYPE_WORDPRESS;

	$res = fs_update_site_params($firestats_site_id,$firestats_site_id, $name, $type);
	if ($res !== true)
	{
		return $res;
	}

	// update the filter to show us this blog by default after the installation
	update_option('firestats_sites_filter',$firestats_site_id);
	return true;
}



function fs_is_wpmu()
{
	return file_exists(ABSPATH."/wpmu-settings.php");
}

function fs_full_installation()
{
	return file_exists(dirname(__FILE__).'/php/db-hit.php');
}

function fs_override_base_url()
{
	if (fs_full_installation())
	{
		$site_url = get_option("siteurl");

		// make sure the url ends with /
		$last = substr($site_url, strlen( $site_url ) - 1 );
		if ($last != "/") $site_url .= "/";

		// calculate base url based on current directory.
		$base_len = strlen(ABSPATH);
		$suffix = substr(dirname(__FILE__),$base_len)."/";
		// fix windows path sperator to url path seperator.
		$suffix = str_replace("\\","/",$suffix);
		$base_url = $site_url . $suffix;
		return $base_url;
	}
	else // not full installation == satelite of a standlone firestats.
	{
		if (fs_is_wpmu())
		{
			$url = get_site_option('firestats_url');
		}
		else
		{
			$url = get_option('firestats_url');
		}
		// make sure the url ends with /
		$last = substr($url, strlen( $url ) - 1 );
		if ($last != "/") $url .= "/";
		return $url;
	}
}

function fs_get_firestats_path($reset = false)
{
	static $path;
	if (!isset($path) || $reset)
	{
		$path = fs_get_firestats_path_impl();
	}

	if (file_exists($path.'/firestats.info')) return $path;
	else return false;
}

function fs_get_firestats_path_impl()
{
	if (!fs_full_installation())
	{
		if (fs_is_wpmu())
		{
			$path = get_site_option('firestats_path');
		}
		else
		{
			$path = get_option('firestats_path');
		}
		if ($path == null || $path == '')
		{
			return false;
		}
		else
		{
			return $path;
		}
	}
	else
	{
		return dirname(__FILE__);
	}
}

# Small info on DashBoard-page
function fs_admin_footer()
{
	$admin = dirname($_SERVER['SCRIPT_FILENAME']);
	$admin = substr($admin, strrpos($admin, '/')+1);
	$query = $_SERVER["QUERY_STRING"];
	if ($admin == 'wp-admin' && basename($_SERVER['SCRIPT_FILENAME']) == 'index.php' && $query == '')
	{
		$FS_PATH = fs_get_firestats_path();
		if (!$FS_PATH) return;
		require_once($FS_PATH.'/php/auth.php');
		if (!fs_can_use()) return;

		require_once($FS_PATH.'/php/db-sql.php');
		$url = fs_get_firestats_url();
		$title = "<h3>".fs_r("FireStats"). $url."</h3><span id ='firestats_span'>".fs_r('Loading...')."</span>";
		print
			'<script language="javascript" type="text/javascript"> 
				var e = document.getElementById("zeitgeist");
				if (e)
				{
					var div = document.createElement("DIV");
					div.id = div.innerHTML = "'.$title.'";
					e.appendChild(div);
				} 
			</script> ';
		flush();

		$count = fs_get_hit_count();
		$unique = fs_get_unique_hit_count();
		$last_24h_count= fs_get_hit_count(1);
		$last_24h_unique = fs_get_unique_hit_count(1);

		echo "<!-- admin = $admin, script =  ".basename($_SERVER['SCRIPT_FILENAME'])."  -->";
		$content.= sprintf(fs_r("Total : %s page views and %s visits"),'<strong>'.$count.'</strong>','<strong>'.$unique.'</strong>').'<br/>';
		$content.= sprintf(fs_r("Last 24 hours : %s page views and %s visits"),'<strong>'.$last_24h_count.'</strong>','<strong>'.$last_24h_unique.'</strong>').'<br/>';
		print
		'<script language="javascript" type="text/javascript"> 
			var e = document.getElementById("firestats_span");
			if (e)
			{
				e.innerHTML = "'.$content.'";
			} 
			</script> ';
	}
}

function fs_get_firestats_url($txt = null)
{
	$txt = $txt ? $txt  : "&raquo;";
	if (fs_full_installation())
	{
		$plugin_dir = substr(dirname(__FILE__), strlen(ABSPATH . PLUGINDIR . '/'));
		// hack around stupid wp bug under windows
		if (fs_is_windows())
		{
			$link = "index.php?page=$plugin_dir%5C" . basename(__FILE__);

		}
		else
		{
			$link = "index.php?page=$plugin_dir/" . basename(__FILE__);
		}
		$url = "<a href='$link'>$txt</a>";
	}
	else
	{
		$file = __FILE__;
		$url = "<a href='index.php?page=$file'>$txt</a>";
	}
	return $url;
}

function fs_authenticate_wp_user()
{
	// use wordpress users only when installed in hosted mode
	if (fs_full_installation())
	{
		global $current_user;
		$path = fs_get_firestats_path();
		require_once($path.'/php/auth.php');
		$user = new stdClass();
		$user->name = $current_user->user_login;
		$user->id = $current_user->id;
		if (fs_is_wpmu())
		{
			if (is_site_admin())
			{
				$user->security_level = SEC_ADMIN;
			}
			else
			{
				$user->security_level = current_user_can('publish_posts') ? SEC_USER : SEC_NONE;
			}
		}
		else
		{
			if (current_user_can('manage_options'))
			{
				$user->security_level = SEC_ADMIN;
			}
			else
			{
				if (current_user_can('moderate_comments')) // editor
				{
					$user_level = 4;
				}
				else
				if (current_user_can('publish_posts')) // author
				{
					$user_level = 3;
				}
				else
				if (current_user_can('edit_posts')) // contributor
				{
					$user_level = 2;
				}
				else
				if (current_user_can('read')) // subscriber
				{
					$user_level = 1;
				}
				else
				{
					$user_level = 0; // a bumhug
				}

				$required = (int)fs_get_local_option('firestats_min_view_security_level',3);
				$user->security_level = $required <= $user_level ? SEC_USER : SEC_NONE;
			}
		}

		// this mark the user as a user that can access all the sites.
		// this is a little hack for WordPress in hosted installation, where users are not real FireStats users
		// and it makes no sense to add an entry in the user_sites table for each user.
		$user->check_user_sites_table = false;

		fs_start_user_session($user);
	}
	else
	{
		fs_resume_user_session();
		if (!fs_authenticated())
		{
			fs_start_user_session(null); // dummy session that can only be used to login.
		}
	}
}

function fs_page()
{
	$path = fs_get_firestats_path();
	if ($path)
	{
		if (!fs_full_installation()) // satellite installation
		{
			if (defined('DEMO'))
			{
				$user = new stdClass();
				$user->name = "Demo";
				$user->id = 1;
				$user->security_level = SEC_USER;
				$res = fs_start_user_session($user);
			}
			else
			{
				$res = fs_resume_user_session();
			}

			if ($res === true)
			{
				fs_show_embedded_page($path.'/php/tabbed-pane.php');
				return;
			}
			else
			{
				fs_show_embedded_page($path.'/login.php', true, true, true);
			}
		}
		else // full installation
		{
			if (fs_can_use() === true)
			{
				fs_show_embedded_page($path.'/php/tabbed-pane.php');
				return;
			}
			else
			{
				$msg = fs_r("You are not authorized to access FireStats");
				$msg = "<div class='error'>$msg</div>";

				fs_show_embedded_page($msg,false);
			}
		}
	}
	else
	{
		$href = sprintf("<a href='options-general.php?page=FireStats'>%s</a>",__('Options'));
		echo '<div class="error" style="margin-top:40px;margin-bottom:40px">'.sprintf(__('You need to configure FireStats in the %s menu'),$href).'</div>';
	}
}

function fs_endsWith( $str, $sub ) {
	return ( substr( $str, strlen( $str ) - strlen( $sub ) ) === $sub );
}

function fs_admin_head()
{
	$FS_PATH = fs_get_firestats_path();
	if ($FS_PATH)
	{
		if (FS_WORDPRESS_PLUGIN_VER != FS_VERSION)
		{
			echo "Version mismatch between FireStats plugin (".FS_WORDPRESS_PLUGIN_VER.") and FireStats installation (".FS_VERSION.")";
			return;
		}

		fs_authenticate_wp_user();
		if (strstr($_SERVER["QUERY_STRING"],basename(__FILE__)) !== false)
		{
			require_once($FS_PATH.'/php/html-utils.php');
			fs_output_head();
		}
	}
}

function fs_add_wordpress()
{
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return;
	if (is_404()) return; // don't log 404.
	require_once($FS_PATH.'/php/db-hit.php');
	$firestats_site_id = get_option('firestats_site_id');
	// extract user ID in a wordpress specific method.
	global $user_ID;
	get_currentuserinfo();

	$excluded_users = get_option('firestats_excluded_users');
	$excluded_user = $user_ID && $excluded_users && in_array($user_ID,explode(",",$excluded_users));
	if (!$excluded_user)
	{
		// WP likes to add slashes to the useragent, which causes problems.
		$_SERVER['HTTP_USER_AGENT'] = stripslashes($_SERVER['HTTP_USER_AGENT']); 
		$_SERVER['REQUEST_URI'] = stripslashes($_SERVER['REQUEST_URI']);
		$_SERVER['HTTP_REFERER'] = stripslashes($_SERVER['HTTP_REFERER']);
		
		fs_add_site_hit($firestats_site_id,false);
	}
}

# Add a sub-menu to the "manage"-page.
function fs_add_page()
{
	add_submenu_page('index.php', 'FireStats', 'FireStats', 1, __FILE__, 'fs_page');
	if (!fs_full_installation())
	{
		if (!fs_is_wpmu() || is_site_admin())
		{
			add_submenu_page('options-general.php', 'FireStats', 'FireStats', 8, 'FireStats', 'fs_options_page');
		}
	}
}


function fs_options_page()
{
	if (isset($_POST['action']) && $_POST['action'] == 'update')
	{
		update_option('firestats_path',$_POST['firestats_path']);
		update_option('firestats_url',$_POST['firestats_url']);
	}
	$path = get_option('firestats_path');
	$url = get_option('firestats_url');
	?>
<div class="fwrap">
<h2><?php _e('FireStats configuration')?></h2>
<form method="post"><input type="hidden" name="action" value="update" />
	<?php
	$path_good = false;
	$url_good = false;
	$path_version = '';
	$url_version = '';
	if (ini_get('safe_mode') == 1)
	{
		?>
<div class="error"><?php _e("Your PHP is configured in safe mode, FireStats may be impossible to configure in satellite mode.")?></div>
		<?php
	}

	if (!empty($path))
	{
		$len = strlen($path);
		if ($path[$len - 1] != '/' && $path[$len - 1] != '\\')
		{
			$path .= '/';
		}

		if (file_exists($path.'firestats.info'))
		{?>
<div class="updated fade"><?php
echo sprintf(__("FireStats detected at %s"),"<b>$path</b>").'<br/>';
$path_good = true;
$info = file($path.'firestats.info');
$path_version = $info[0];
?></div>
<?php
		}
		else
		{?>
<div class="error"><?php echo sprintf(__("FireStats was not found at %s"),"<b>$path</b>")?></div>
		<?php
		}
	}
	else
	{
		echo '<div class="error">'.__("Enter the directory that contains FireStats").'</div>';
	}

	if (!empty($url))
	{
		ob_start();
		$file = file($url.'/firestats.info');
		$output = ob_get_clean();
		if ($file !== false)
		{?>
<div class="updated fade"><?php
echo sprintf(__("FireStats detected at %s"),"<b>$url</b>").'<br/>';
$url_good = true;
$url_version = $file[0];
?></div>
<?php
		}
		else
		{
			if ($output != '')
			{
				$msg = sprintf(__("Error : %s"),"<b>$output</b>");
			}
			else
			{
				$msg = sprintf(__("FireStats was not found at %s"),"<b>$url</b>");
			}
			{?>
<div class="error"><?php echo $msg?></div>
			<?php
			}
		}
	}
	else
	{
		echo '<div class="error">'. __("Enter FireStats url").'</div>';
	}

	if ($path_good && $url_good)
	{
		fs_get_firestats_path(true); // reset cached path.

		$plugin_ver = "FireStats/".FS_WORDPRESS_PLUGIN_VER;
		if (trim($plugin_ver) != trim($path_version))
		{
			?>
<div class="error"><?php echo sprintf(__("Version mismatch between %s (%s) and FireStats at path (%s)"),basename(__FILE__),$plugin_ver, $path_version)?></div>
			<?php
		}
		else
		{
			$res = fs_register_wordpress();
			if ($res !== true)
			{
				echo "<div class='error'>$res</div>";
			}
			else
			{
				fs_update_post_titles();
				echo '<div class="updated fade">'.sprintf(__('Everything is okay, click %s to open %s'),'<b>'.fs_get_firestats_url('here').'</b>',"$path_version").'</div>';
			}

		}
	}
	?>
<table>
	<tr>
		<td><?php _e('FireStats path : ')?></td>
		<td><input type="text" class="code" id="firestats_path"
			name="firestats_path" size="60" value="<?php echo $path?>" /> <?php echo __('Example:')."<b>".$_SERVER["DOCUMENT_ROOT"]."firestats/</b>"?></td>
	</tr>
	<tr>
		<td><?php _e('FireStats URL : ')?></td>
		<td><input type="text" class="code" id="firestats_url"
			name="firestats_url" size="60" value="<?php echo $url?>" /> <?php _e('Example: <b>http://your_site.com/firestats</b>')?>
		</td>
	</tr>
</table>

<p class="submit"><input type="submit" name="Submit"
	value="<?php _e('Update options')?>&raquo;" /></p>
</form>
</div>
	<?php
}

function fs_widget_init()
{
	// Check for the required plugin functions. This will prevent fatal
	// errors occurring when you deactivate the dynamic-sidebar plugin.
	if ( !function_exists('register_sidebar_widget') )
		return;

	$FS_PATH = fs_get_firestats_path();
	$configured = true;
	if ($FS_PATH)
	{
		global $wp_version;
		if ($wp_version != "2.5") // 2.5 displayed actual img tag instead of image. fixed in 2.5.1
		{
			require_once($FS_PATH.'/php/utils.php');
			$img = fs_url("img/firestats-icon-small.png");
			$name = "<img alt='FireStats icon' src='$img'/>&nbsp;%s";
		}
		else
		{
			$name = "%s";
		}
	}
	else
	{
		$name = "%s (FireStats is not configured)";
		$configured  = false;
	}
	// not translated due to a bug in wordpress that prevents saving of
	// widgets with non ascii characters in their name.
	$stats_name = sprintf($name,"Statistics");
	$popular_name = sprintf($name,"Popular posts");
	$rss_name = sprintf($name,"RSS subscribers");

	// stats widget
	register_sidebar_widget($stats_name, 'fs_widget');
	if ($configured) register_widget_control($stats_name, 'fs_widget_control', 300, 100);

	// popular pages widget
	register_sidebar_widget($popular_name, 'fs_popular_pages_widget');
	if ($configured) register_widget_control($popular_name, 'fs_popular_pages_widget_control', 400, 200);

	// rss subscribers
	register_sidebar_widget($rss_name, 'fs_rss_subscribers_widget');
	if ($configured) register_widget_control($rss_name, 'fs_rss_subscribers_controll', 300, 100);
}


function fs_rss_subscribers_widget($args)
{
	// $args is an array of strings that help widgets to conform to
	// the active theme: before_widget, before_title, after_widget,
	// and after_title are the array keys. Default tags: li and h2.
	extract($args);

	// Each widget can store its own options. We keep strings here.
	$options = get_option('firestats_rss_subscribers_widget');
	$title = $options['title3'];
	$hide_logo = $options['hide_logo3'];

	// These lines generate our output. Widgets can be very complex
	// but as you can see here, they can also be very, very simple.
	echo $before_widget . $before_title . $title . $after_title;
	if (fs_plugin_installed("RSS Subscribers"))
	{
		echo fs_get_rss_subscribers_box($hide_logo);
	}
	else
	{
	    $FS_PATH = fs_get_firestats_path();
		if ($FS_PATH)
		{
		    require_once($FS_PATH.'/php/html-utils.php');
			echo fs_create_wiki_help_link("FireStatsPRO");
		}
	}
	echo $after_widget;
}

function fs_rss_subscribers_controll()
{
	// Get our options and see if we're handling a form submission.
	$options = get_option('firestats_rss_subscribers_widget');

	if (empty($options))
	{
		$options = array('title3'=>fs_r('FireStats'),'hide_logo3'=>false);
	}

	if ( $_POST['firestats-rss-subscribers-widget-submit'])
	{
		$hide_logo = $_POST['hide_logo3'] == 'on';
		// Remember to sanitize and format use input appropriately.
		$options['title3'] = strip_tags(stripslashes($_POST['firestats-title3']));
		$options['hide_logo3'] = $hide_logo;
		update_option('firestats_rss_subscribers_widget', $options);
	}
	
	// Be sure you format your options to be valid HTML attributes.
	$title = htmlspecialchars($options['title3'], ENT_QUOTES);
	$hide_logo = $options['hide_logo3'];

	// Here is our little form segment. Notice that we don't need a
	// complete form. This will be embedded into the existing form.
	?>
<table>
	<tr>
		<td><label for="firestats-title3"><?php fs_e('Title:')?></label></td>
		<td><input style="width: 200px;" id="firestats-title3"
			name="firestats-title3" type="text" value="<?php echo $title?>" /></td>
	</tr>
	<tr>
		<td colspan="2"><input id="hide_logo3" name="hide_logo3" type="checkbox"
		<?php echo $hide_logo ? "checked='checked'" : ""?> /> <label
			for="hide_logo3"><?php fs_e('Hide FireStats logo')?></label></td>
	</tr>
</table>
<input
	type="hidden" id="firestats-rss-subscribers-widget-submit"
	name="firestats-rss-subscribers-widget-submit" value="1" />
	<?php
}


function fs_widget($args)
{
	// $args is an array of strings that help widgets to conform to
	// the active theme: before_widget, before_title, after_widget,
	// and after_title are the array keys. Default tags: li and h2.
	extract($args);

	// Each widget can store its own options. We keep strings here.
	$options = get_option('widget_firestats');
	$title = $options['title'];
	$hide_logo = $options['hide_logo'];

	// These lines generate our output. Widgets can be very complex
	// but as you can see here, they can also be very, very simple.
	echo $before_widget . $before_title . $title . $after_title;
	echo fs_get_stats_box($hide_logo);
	echo $after_widget;
}

function fs_widget_control()
{
	// Get our options and see if we're handling a form submission.
	$options = get_option('widget_firestats');

	if (empty($options))
	{
		$options = array('title'=>fs_r('FireStats'), 'hide_logo'=>false);
	}

	if ( $_POST['firestats-stats-widget-submit'])
	{
		$hide_logo = $_POST['hide_logo'] == 'on';
		// Remember to sanitize and format use input appropriately.
		$options['title'] = strip_tags(stripslashes($_POST['firestats-title']));
		$options['hide_logo'] = $hide_logo;
		update_option('widget_firestats', $options);
	}

	// Be sure you format your options to be valid HTML attributes.
	$title = htmlspecialchars($options['title'], ENT_QUOTES);
	$hide_logo = $options['hide_logo'];

	// Here is our little form segment. Notice that we don't need a
	// complete form. This will be embedded into the existing form.
	?>
<table>
	<tr>
		<td><label for="firestats-title2"><?php fs_e('Title:')?></label></td>
		<td><input style="width: 200px;" id="firestats-title"
			name="firestats-title" type="text" value="<?php echo $title?>" /></td>
	</tr>
	<tr>
		<td colspan="2"><input id="hide_logo" name="hide_logo" type="checkbox"
		<?php echo $hide_logo ? "checked='checked'" : ""?> /> <label
			for="hide_logo"><?php fs_e('Hide FireStats logo')?></label></td>
	</tr>
</table>
<input
	type="hidden" id="firestats-stats-widget-submit"
	name="firestats-stats-widget-submit" value="1" />
	<?php
}

function fs_popular_pages_widget($args)
{
	$FS_PATH = fs_get_firestats_path();
	require_once($FS_PATH.'/php/html-utils.php');

	// $args is an array of strings that help widgets to conform to
	// the active theme: before_widget, before_title, after_widget,
	// and after_title are the array keys. Default tags: li and h2.
	extract($args);

	// Each widget can store its own options. We keep strings here.
	$options = get_option('firestats_popular_pages_widget');
	$title = htmlspecialchars($options['title'], ENT_QUOTES);
	$num_to_show = htmlspecialchars($options['num_to_show'], ENT_QUOTES);
	$days_ago = htmlspecialchars($options['days_ago'], ENT_QUOTES);
	$hide_logo = $options['hide_logo2'];

	echo $before_widget . $before_title . $title . $after_title;
	echo fs_get_popular_posts_list($num_to_show, $days_ago, $hide_logo);
	echo $after_widget;
}

function fs_popular_pages_widget_control()
{
	// Get our options and see if we're handling a form submission.
	$options = get_option('firestats_popular_pages_widget');
	if ( empty($options))
	{
		$options = array('title'=>fs_r("Popular posts"),
							 'num_to_show'=>10,
							 'days_ago'=>90,
							 'hide_logo2'=>false);
	}

	if ( $_POST['firestats-submit'])
	{
		$hide_logo = $_POST['hide_logo2'] == 'on';

		// Remember to sanitize and format use input appropriately.

		$options['title'] = 	  strip_tags(stripslashes(isset($_POST['firestats-title2']) ? $_POST['firestats-title2'] : $options['title']));
		$options['num_to_show'] = strip_tags(stripslashes(isset($_POST['num_to_show'])      ? $_POST['num_to_show']      : $options['num_to_show']));
		$options['days_ago'] =	  strip_tags(stripslashes(isset($_POST['days_ago2'])        ? $_POST['days_ago2']        : $options['days_ago']));
		$options['hide_logo2'] = $hide_logo;
		if (!is_numeric($options['num_to_show'])) $options['num_to_show'] = 10;
		if (!is_numeric($options['days_ago']) && !empty($options['days_ago'])) $options['days_ago'] = 90;
		update_option('firestats_popular_pages_widget', $options);
		delete_option('cached_firestats_popular_pages');
	}

	// Be sure you format your options to be valid HTML attributes.
	$title = htmlspecialchars($options['title'], ENT_QUOTES);
	$num_to_show = htmlspecialchars($options['num_to_show'], ENT_QUOTES);
	$days_ago = htmlspecialchars($options['days_ago'], ENT_QUOTES);
	$hide_logo = $options['hide_logo2'];

	// Here is our little form segment. Notice that we don't need a
	// complete form. This will be embedded into the existing form.
	?>
<table>
	<tr>
		<td><label for="firestats-title2"><?php fs_e('Title:')?></label></td>
		<td><input style="width: 200px;" id="firestats-title2"
			name="firestats-title2" type="text" value="<?php echo $title?>" /></td>
	</tr>
	<tr>
		<td><label for="num_to_show"><?php fs_e('Number to show')?></label></td>
		<td><input style="width: 200px;" id="num_to_show" name="num_to_show"
			type="text" value="<?php echo $num_to_show?>" /></td>
	</tr>
	<tr>
		<td><label for="days_ago"><?php fs_e('Days ago (Empty for all time)')?></label></td>
		<td><input style="width: 200px;" id="days_ago2" name="days_ago2"
			type="text" value="<?php echo $days_ago?>" /></td>
	</tr>
	<tr>
		<td colspan="2"><input id="hide_logo2" name="hide_logo2"
			type="checkbox" <?php echo $hide_logo ? "checked='checked'" : ""?> />
		<label for="hide_logo2"><?php fs_e('Hide FireStats logo')?></label></td>
	</tr>
</table>
<input
	type="hidden" id="firestats-submit" name="firestats-submit" value="1" />
	<?php
}

/**
 * returns a cached bit of data from the options table.
 * if the data is too old, it generates new data with the generator function
 */
function fs_get_cached_data($name, $generator, $timeout = 3600)
{
	$s = get_option($name);
	if (is_string($s))
	$cached = get_option($name);
	else
	$cached = $s;

	$stale = false;
	if (empty($cached))
	{
		$stale = true;
	}
	else
	{
		$timestamp = $cached['timestamp'];
		if (time() - $timestamp > $timeout)
		{
			$stale = true;
		}
		else
		{
			$res = $cached['data'];
		}
	}

	if ($stale)
	{
		$res = $generator();
		$cached = array('timestamp'=>time(), 'data'=>$res);
		update_option($name,serialize($cached));
	}

	return $res;
}

function fs_get_rss_subscribers_box($hide_logo = false)
{
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return "FireStats is not configured yet";
	require_once($FS_PATH.'/php/init.php');

	$powered = fs_get_powered_by('fs_powered_by');
	$cache_timeout = 10*60; // 10 minutes
	$subscribers = fs_get_cached_data('firestats_rss_subscribers', fs_get_rss_subscribers, $cache_timeout);
	$text = sprintf(fs_r("%s readers"),$subscribers);
	$res = "
<!-- You can customize the sidebox by playing with your theme css-->
<ul class='firestats_sidebox'> 
	<li>$text</li>
</ul>";
	if (!$hide_logo) $res .= $powered;

	return $res;
}


function fs_get_stats_box($hide_logo = false, $with_rss = false)
{
	$with_rss = $with_rss && fs_plugin_installed("RSS Subscribers");	
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return "FireStats is not configured yet";
	require_once($FS_PATH.'/php/db-sql.php');

	$powered = fs_get_powered_by('fs_powered_by');
	$cache_timeout = 10*60; // 10 minutes
	$count = fs_get_cached_data('cached_firestats_hit_count', fs_get_hit_count, $cache_timeout);
	$unique = fs_get_cached_data('cached_firestats_unique_hits', fs_get_unique_hit_count, $cache_timeout);
	$last_24h_count = fs_get_cached_data('cached_firestats_last_24h_count', create_function('','return fs_get_hit_count(1);'),$cache_timeout);
	$last_24h_unique = fs_get_cached_data('cached_firestats_last_24h_visits', create_function('','return fs_get_unique_hit_count(1);'),$cache_timeout);
	
	if ($with_rss)
	{
		$subscribers = fs_get_cached_data('firestats_rss_subscribers', fs_get_rss_subscribers, $cache_timeout);
	}


	$total_visits  = fs_r("Pages displayed : ")."<b>$count</b>";
	$total_uniques = fs_r("Unique visitors : ")."<b>$unique</b>";
	$visits_today  = fs_r("Pages displayed in last 24 hours : ")."<b>$last_24h_count</b>";
	$uniques_today = fs_r("Unique visitors in last 24 hours : ")."<b>$last_24h_unique</b>";
	if ($with_rss)
	{
		$rss_subscribers = fs_r("RSS subscribers")." : <b>$subscribers</b>";
	}
	

	$res = "
<!-- You can customize the sidebox by playing with your theme css-->
<ul class='firestats_sidebox'> 
	<li>$total_visits</li>
	<li>$total_uniques</li>
	<li>$visits_today</li>
	<li>$uniques_today</li>
	".($with_rss ? "<li>$rss_subscribers</li>" : "")."
</ul>";
	if (!$hide_logo) $res .= $powered;

	return $res;
}

function fs_add_comment_flag($link)
{
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return;
	require_once($FS_PATH.'/php/ip2country.php');
	require_once($FS_PATH.'/php/utils.php');
	if (fs_called_by(null, 'wp_widget_recent_comments') || fs_called_by("WP_Widget_Recent_Comments", 'widget')) return $link;
	$ip = get_comment_author_IP();
	$code = fs_ip2c($ip);
	if (!$code) return $link;
	return $link .' '. fs_get_country_flag_url($code);
}

function fs_add_comment_browser_os($link)
{
	global $comment;
	$ua = $comment->comment_agent;
	if (!$ua) return $link;
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return;
	require_once($FS_PATH.'/php/browsniff.php');
	require_once($FS_PATH.'/php/utils.php');
	if (fs_called_by(null, 'wp_widget_recent_comments') || fs_called_by("WP_Widget_Recent_Comments", 'widget')) return $link;
	return $link . ' '.fs_pri_browser_images($ua);
}

function fs_echo_footer()
{
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return;
	require_once($FS_PATH.'/php/db-sql.php');

	$stats = get_option('firestats_show_footer_stats') == 'true';
	if ($stats)
	{
		$cache_timeout = 10*60; // 10 minutes
		$count = fs_get_cached_data('cached_firestats_hit_count', fs_get_hit_count, $cache_timeout);
		$unique = fs_get_cached_data('cached_firestats_unique_hits', fs_get_unique_hit_count, $cache_timeout);
		$last_24h_count = fs_get_cached_data('cached_firestats_last_24h_count', create_function('','return fs_get_hit_count(1);'),$cache_timeout);
		$last_24h_unique = fs_get_cached_data('cached_firestats_last_24h_visits', create_function('','return fs_get_unique_hit_count(1);'),$cache_timeout);
		echo $count  .' '.fs_r('pages viewed')  . ", $last_24h_count "	. fs_r('today')."<br/>";
		echo $unique .' '.fs_r('visits') 		. ", $last_24h_unique "	. fs_r('today')."<br/>";
	}
	echo fs_get_powered_by('fs_powered_by');
}

function fs_get_powered_by($css_class)
{
	$img = fs_url("img/firestats-icon-small.png");
	$firestats_url = FS_HOMEPAGE;
	$powered = "<img alt='FireStats icon' src='$img'/><a href='$firestats_url'>&nbsp;".fs_r("Powered by FireStats").'</a>';
	return "<span class='$css_class'>$powered</span>";
}



function fs_get_wp_config_path()
{
	$base = dirname(__FILE__);
	$path = false;

	if (@file_exists(dirname(dirname($base))."/wp-config.php"))
	{
		$path = dirname(dirname($base))."/wp-config.php";
	}
	else
	if (@file_exists(dirname(dirname(dirname($base)))."/wp-config.php"))
	{
		$path = dirname(dirname(dirname($base)))."/wp-config.php";
	}
	else
	$path = false;

	if ($path != false)
	{
		$path = str_replace("\\", "/", $path);
	}
	return $path;
}


/**
 * Local option storage for wordpress, used by fs_update_local_option to update wordpress value in a generic way.
 */
function fs_update_local_option_impl($key, $value)
{
	update_option($key,$value);
}

/**
 * Local option storage for wordpress, used by fs_get_local_option to get wordpress value in a generic way.
 */
function fs_get_local_option_impl($key)
{
	return get_option($key);
}

function fs_define_system_settings_impl()
{
	global $FS_SYSTEM_SETTINGS;
	$FS_SYSTEM_SETTINGS['site_selector_mode'] = fs_full_installation()
	? FS_SITE_SELECTOR_MODE_ADMIN_ONLY
	: FS_SITE_SELECTOR_MODE_BY_USERS_SITES_TABLE;

}

/**
 * Returns a list of local (saved in WordPress) options that a non admin user may change.
 * @return array of strings
 */
function fs_local_options_allowed_for_non_admin()
{
	if (fs_is_wpmu())
	{
		// even non admin user is allowed to save those options in a wpmu blog.
		return array(
			'firestats_show_footer',
			'firestats_show_footer_stats',
			'firestats_add_comment_flag',
			'firestats_add_comment_browser_os',
			'firestats_sites_filter'
			);
	}
	else
	{
		return array();
	}
}

function fs_is_windows()
{
	if (!isset($_ENV['OS'])) return false; // assume not windows.
	if (strpos(strtolower($_ENV['OS']), "windows") === false) return false;
	return true;
}


function fs_update_post_titles()
{
	$path = fs_get_firestats_path();
	if (!$path) return "FS_PATH not defined";
	require_once($path.'/php/db-sql.php');

	global $wpdb;
	$posts = $wpdb->get_results("SELECT ID,post_title,post_type FROM $wpdb->posts WHERE post_type = 'post' OR post_type = 'page'");
	if ($posts === false)
	{
		return $wpdb->last_error;
	}

	// this is required because of a wp bug: calling get_permalink assumes pluggable was included
	// and throws an error if it's not.
	// the if is needed because plugable only exists starting from a particular version of wp.
	if (file_exists(ABSPATH . "/wp-includes/pluggable.php"))
	{
		require_once(ABSPATH . "/wp-includes/pluggable.php");
	}

	$site_id = get_option('firestats_site_id');
	$fsdb = &fs_get_db_conn();
	$urls = fs_urls_table();
	$post_type = FS_URL_TYPE_POST;
	// reset type of all urls in this site id that are marked as posts
	$fsdb->query("UPDATE `$urls` set type=NULL,title=NULL WHERE type='$post_type' AND site_id = '$site_id'");
	foreach($posts as $post)
	{
		$id = $post->ID;
		$title = $post->post_title;
		$link = get_permalink($id);
		if (empty($link)) continue;
		// make sure the url exists in the urls table.;
		$url_id = fs_get_url_id($link);
		if ($url_id == null)
		{
			$res = fs_insert_url($link, $site_id);
			if ($res !== true)
			{
				return "Error inserting url: $res";
			}
			$url_id = fs_get_url_id($link);
		}

		// replace title with current one
		$res = fs_set_url_title($link,$title);
		if ($res !== true)
		{
			return "Error handling post ($post->post_title) : $res";
		}

		// mark url as a post
		if ($post->post_type == 'post')
		{
			$res = fs_set_url_type($link,FS_URL_TYPE_POST);
			if ($res !== true)
			{
				return $res;
			}
		}		
	}

	// clear popular pages cache
	delete_option('cached_firestats_popular_pages');
	return true;
}

function fs_plugin_db_update()
{
	// database version of firestats data in this particular blog.
	// if there are several blogs on the same firestats db, each one may need to do some actions in the upgrade prorcess.
	$db_version = get_option('firestats_db_version');
	if (empty($db_version)) $db_version = 0;
	if ($db_version < 11)
	{
		if (!fs_is_wpmu())
		{
			$res = fs_update_post_titles();
			if ($res !== true)
			{
				echo $res;
				return false;
			}
		}
	}

	update_option('firestats_db_version',FS_REQUIRED_DB_VERSION);
}

function fs_get_js_url()
{
	// as if this is not complicated enough, we need to add some windows specific hacks for IE7.
	$f = "index.php?page=".__FILE__."&fs_javascript=";
	$f = str_replace("\\","%5C",$f);
	return $f;
}

function fs_new_version_nag($file)
{
	$plugin_name = substr(__FILE__, strlen(ABSPATH . PLUGINDIR . '/'));
	if ($file != $plugin_name) return;
	$FS_PATH = fs_get_firestats_path();
	if ($FS_PATH)
	{
		require_once($FS_PATH.'/php/version-check.php');
		$msg = fs_get_latest_firestats_version_message();
		if ($msg != "")
		{
			echo "<tr><td colspan='5' class='plugin-update'>";
			echo $msg;
			echo "</td></tr>";
		}
	}
}

function fs_get_popular_posts_list($num_to_show = 10, $days_ago = 90, $hide_logo = false)
{
    $FS_PATH = fs_get_firestats_path();
    if (!$FS_PATH) return;
    require_once($FS_PATH.'/php/html-utils.php');

	if ($days_ago == '') $days_ago = 'null';
	if ($num_to_show == '') $num_to_show = '10';
	$generator =  create_function('',"return fs_get_popular_pages_tree($num_to_show, $days_ago, FS_URL_TYPE_POST,false, null);");
	$res = fs_get_cached_data('cached_firestats_popular_pages', $generator, 3600);
	if (!$hide_logo)
	$res .= "<br/>".fs_get_powered_by('fs_powered_by');
	return $res;
}

function fs_delete_wpmu_blog($blog_id)
{
	$FS_PATH = fs_get_firestats_path();
	if (!$FS_PATH) return;
	require_once($FS_PATH.'/php/db-sql.php');
	fs_delete_site($blog_id,'delete',null);
	fs_log("blog $blog_id deleted");
}

function fs_register_dashboard_widgets()
{
    $fs_path = fs_get_firestats_path();
    if (!$fs_path) return;
    require_once("$fs_path/php/db-common.php");
	if (fs_db_valid() && fs_can_use())
	{
		if (function_exists('wp_add_dashboard_widget'))
		{
			wp_add_dashboard_widget( 'firestats_stats_widget', 'FireStats statistics', 'fs_dashboard_widget' );
		}
		else
		{
			wp_register_sidebar_widget('firestats_stats_widget', 'FireStats statistics', 'fs_dashboard_widgets_pre_27');
		}
	}
}

function fs_add_dashboard_widgets( $widgets )
{
	global $wp_registered_widgets;
	if ( !isset($wp_registered_widgets['firestats_stats_widget']) ) return $widgets;
	array_splice( $widgets, 0, 0, 'firestats_stats_widget' );
	return $widgets;
}

/**
 * for wp <= 2.6
 */
function fs_dashboard_widgets_pre_27($args)
{
	extract($args);
	$img = fs_url("img/firestats-icon-small.png");
	$fs =  fs_r("FireStats");
	$title = fs_get_firestats_url("<img src='$img'> $fs</img>");
	echo $before_widget . $before_title . $title . $after_title;
	echo fs_get_stats_box(true,true);
	echo $after_widget;
}

/**
 * for wp >= 2.7
 */
function fs_dashboard_widget()
{
	$img = fs_url("img/firestats-icon-small.png");
	$fs =  fs_r("FireStats");
	$title = fs_get_firestats_url("<img src='$img'> $fs</img>");
	echo  $title;
	echo fs_get_stats_box(true,true);
}

?>
