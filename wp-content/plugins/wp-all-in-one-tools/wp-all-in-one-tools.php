<?php
/*
Plugin Name: WP All-in-One tools
Plugin URI: http://www.infine.ru/support/wp_plugins/wp-all-in-one-tools.htm
Description: Collection of more usefull plugins and functions for WordPress (secure, feed, patches etc.)
Author: Nikolay aka 'cmepthuk'
Version: 0.3.1
License: GPL
Author URI: http://infine.ru/
*/

define('AIO_VERSION', '0.3.1');

### Die if direct call plugin file
if (!defined('ABSPATH')) die('Something wrong? O_o');

### Use WordPress 2.6 Constants
if (!defined('WP_CONTENT_DIR')) {
	define( 'WP_CONTENT_DIR', ABSPATH.'wp-content');
}
if (!defined('WP_CONTENT_URL')) {
	define('WP_CONTENT_URL', get_option('siteurl').'/wp-content');
}
if (!defined('WP_PLUGIN_DIR')) {
	define('WP_PLUGIN_DIR', WP_CONTENT_DIR.'/plugins');
}
if (!defined('WP_PLUGIN_URL')) {
	define('WP_PLUGIN_URL', WP_CONTENT_URL.'/plugins');
}

if (!defined('AIO_IS_2_5')) {
  global $wp_version;
  static $wp_exploded_version = array();
  $wp_exploded_version = explode('.', $wp_version);
  if($wp_exploded_version[0] == 2 && $wp_exploded_version[1] == 5)
    define('AIO_IS_2_5', 'true');
}

### Detect and store 'true' slash, and declare path to plugin dir and filename
$aio_slash = eregi('WIN',PHP_OS) ? '\\' : '/';
$break = explode($aio_slash, __FILE__);
define('AIO_FOLDER_NAME', $break[count($break) - 2]);
define('AIO_FILE_NAME',   $break[count($break) - 1]);

### Declare pathes
define('AIO_FOLDER_DIR', WP_PLUGIN_DIR.'/'.AIO_FOLDER_NAME);
define('AIO_FOLDER_URL', WP_PLUGIN_URL.'/'.AIO_FOLDER_NAME);
define('AIO_IMAGES_URL', WP_PLUGIN_URL.'/'.AIO_FOLDER_NAME.'/images/admin/');
define('AIO_PLUGINS_DIR',AIO_FOLDER_DIR.'/plugins/');

### Create Text Domain For Translations
add_action('init', 'aio_textdomain');
function aio_textdomain() {
	if (!function_exists('wp_print_styles')) {
		load_plugin_textdomain('wp-all-in-one-tools', 'wp-content/plugins/'.AIO_FOLDER_NAME);
	} else {
		load_plugin_textdomain('wp-all-in-one-tools', false, AIO_FOLDER_NAME);
	}
}

### Declare option name
define('AIO_OPTIONS', 'aio-active-plugins');
define('AIO_PLIGINS_BR', '|');

### Global arrays
$aio_messages = array();
$aio_act = array(activate =>   array(act => 'activate',   hint => __('Activate plugin', 'wp-all-in-one-tools')),
                 deactivate => array(act => 'deactivate', hint => __('Deactivate plugin', 'wp-all-in-one-tools')));
$aio_plugins = array('replace-wp-version' => array(
                                             cell_function => 'fb_replace_wp_version',
                                             type => 'plugin',
                                             uniqId => 'replace-wp-version'
                                             ),
                     'secret-key-edit' =>    array(
                                             cell_function => 'utopia39_addSecretKey',
                                             type => 'function',
                                             uniqId => 'secret-key-edit'
                                             ),
                     'iodized-salt' =>       array(
                                             cell_function => 'Iodized_Salt',
                                             type => 'plugin',
                                             uniqId => 'iodized-salt'
                                             ),
                     'disable-core-update' =>array(
                                             type => 'plugin',
                                             uniqId => 'disable-core-update'
                                             ),
                     'disable-plugin-update'=>array(
                                             type => 'plugin',
                                             uniqId => 'disable-plugin-update'
                                             ),
                     'allow-numeric-stubs' =>array(
                                             cell_function => 'allow_numeric_stubs',
                                             type => 'plugin',
                                             uniqId => 'allow-numeric-stubs'
                                             ),
                     'image-upload-2-5-fix'=>array(
                                             cell_function => 'iuehf_activate',
                                             type => 'plugin',
                                             uniqId => 'image-upload-2-5-fix'
                                             ),
                     'show-me-options'    => array(
                                             cell_class => 'ShowMeOptions',
                                             type => 'plugin',
                                             uniqId => 'show-me-options'
                                             ),
                     'minimum-comment-length' => array(     
                                             cell_function => 'check_comment_length',
                                             type => 'plugin',
                                             uniqId => 'minimum-comment-length'
                                             ),
                     'feed-copyright'         => array(
                                             type => 'plugin',
                                             cell_function => 'addCopyrightToFeed',
                                             optionName => 'aio_feed_copyright',
                                             uniqId => 'feed-copyright'
                                             ),
                    );

function aio_get_active_plugins() {
  return array_unique(explode(AIO_PLIGINS_BR, get_option(AIO_OPTIONS)));
}

### Initialization function
function aio_init() {
  if(get_option(AIO_OPTIONS) == '') {
    # add default value
    add_option('aio-active-plugins', '', 'Option for plugin WP All-in-One tools', 'yes');
  }
}

### Function get array, and return string of array units, ex: val1|val2|val3
function plugins_list_to_str($plugins = array()) {
  static $string = '', $i = 0;
  if(!empty($plugins)) {
    foreach($plugins as $plugin) {
      if($plugin !== '') {
      ### Add break char only in middle
        if(count($plugins) == 1) {
          $string .= $plugin;
        } else {
          $string .= ($i !== count($plugins) - 1) ? $plugin.AIO_PLIGINS_BR : $plugin;
        } $i++;
      }
    }
    return $string;
  }
}

### Function return true if version valid
function aio_is_valid_wp_version($need_version, $check_with = '') {
  if(is_string($need_version) && is_string($check_with)) {
    if($check_with == '') {
      global $wp_version;
      $wp_version  = $check_with;
    }
    #print('<strong>[need version = '.$need_version.' / check with = '.$check_with.']</strong> ');
    return version_compare($need_version, $check_with, ">=");
  }
}

### Function use POST values, and update option in needed
function aio_update_option() {
  global $aio_act, $aio_messages, $aio_plugins, $aio_messages;
  static $active_plugins = array();
  $active_plugins = aio_get_active_plugins();
  if(isset($_POST['act'])){
    if($_POST['act'] == $aio_act[activate][act]) {
      if(!in_array($_POST['plugin_name'], $active_plugins)) {
        ### Store new activated plugin
        //print_r($active_plugins);
        switch($aio_plugins[$_POST['plugin_name']][type]) {
          case 'function':
            call_user_func($aio_plugins[$_POST['plugin_name']][cell_function]);
            array_push($aio_messages, __('Plugin <strong>', 'wp-all-in-one-tools').$_POST['plugin_full_name'].__('</strong> maked callback function, usually need refresh page', 'wp-all-in-one-tools'));
            break;
          case 'plugin':
            array_push($active_plugins, $_POST['plugin_name']);
            array_push($aio_messages, __('Plugin <strong>', 'wp-all-in-one-tools').$_POST['plugin_full_name'].__('</strong> activated', 'wp-all-in-one-tools'));
            break;
          default:
            return false; # d(X_x)b
            break;
        }
        //print_r($active_plugins);
      }
    } elseif($_POST['act'] == $aio_act[deactivate][act])
      if(in_array($_POST['plugin_name'], $active_plugins)) {
        ### Find in activated plugins array index of deactivated (by POST value), and remove it
        unset($active_plugins[array_search($_POST['plugin_name'], $active_plugins)]);
        array_push($aio_messages, __('Plugin <strong>', 'wp-all-in-one-tools').$_POST['plugin_full_name'].__('</strong> deactivated', 'wp-all-in-one-tools'));
      }
    update_option(AIO_OPTIONS, plugins_list_to_str(array_unique($active_plugins)));
  }
}

                            ####################
                                 aio_init();     ### Init plugin, if need - write default value
                            aio_update_option(); ### Store values if needed
                            ####################

################################################################################
### Admin page #################################################################
function aio_admin_header() { ?>
  <style type="text/css" media="all">
    /* by WP All-in-One tools plugin */
    h2.aio_pluginname {
      font-size: 19px !important;
      font-family: Tahoma, Verdana !important;
      color: #444 !important;
      margin: 0px !important;
      padding: 0px !important;
      border: 0px !important;
      /*font-weight: bold !important;*/
    }
    h2.aio_pluginname > small {
      font-size: 12px;
      font-weight: normal !important;
      padding-left: 28px;
      background: #fff url('<?php print(AIO_IMAGES_URL); ?>home.png') 14px center no-repeat;
    }
    form > p.descr, div.descr, div.opt {
      padding: 0 0 0 25px;
      margin: 1px 0 15px 0;
    }
    form > p.descr {
      color: #666;
    }
    .aio_section_head {
      background-color: #e4f2fd;
      border: #c6d9e9 solid 1px;
      color: #464646;
      text-align: right;
      position: relative;
      width: 600px;
      left: -400px;
      padding: 6px;
      margin: 10px 0 15px 0;
    }
    .aio_footer {
      text-align: center;
      font-size: 9px;
      color: #c3c3c3;
    }
    .aio_footer a {
      color: #aaa;
    }

    .aio_on, .aio_off, .aio_refresh {
      padding: 3px 0 0 3px !important;
      margin: 0px;
    }
    .aio_table {
      width: 100%;
    }
    .aio_table td {
      background-color: #eaf3fa;
      padding: 5px;
      border: 2px solid #eff3fa;
    }
    .aio_table * > label {
      font-weight: bold;
    }
    .aio_table * > input, .aio_table * > textarea {
      border: 1px solid #c6d9e9;
    }
    .aio_submit {
      font-size: 0.9em;
      background-color: #e5e5e5;
      padding: 5px;
      color: #246;
      border: 1px solid #80b5d0;
      margin: 10px 0 10px 0 !important;
    }
    .aio_submit:hover {
      color: #d54e21;
      cursor: pointer;
      border-color: #535353;
    }
  </style>
<?php }

if(strpos($_SERVER['REQUEST_URI'], AIO_FILE_NAME))
  add_action('admin_head', 'aio_admin_header');

function aio_admin_page() {
  function get_plugin_form_info($plugin_name, $plugins_list) {
    global $aio_plugins, $aio_act;
    static $act_class = array(on => 'aio_on', off => 'aio_off'),
           $result = array(active => false,
                           name => '',
                           act => '',
                           css_class => '',
                           btn_title => '');
    $result[active] =    in_array($plugin_name, $plugins_list);
    $result[name] =      $aio_plugins[$plugin_name][uniqId];
    $result[type] =      $aio_plugins[$plugin_name][type];
    $result[act] =       $result[active] ? $aio_act[deactivate][act] : $aio_act[activate][act];
    $result[cssclass] =  $result[active] ? $act_class[on] : $act_class[off];
    switch ($result[type]) {
      case 'plugin':
        $result[image_url] = $result[active] ? AIO_IMAGES_URL.'power_on.png' : AIO_IMAGES_URL.'power_off.png';
        break;
      case 'function':
        $result[image_url] = AIO_IMAGES_URL.'refresh.png';
        break;
    }

    $result[btn_title] = $result[active] ? $aio_act[deactivate][hint] : $aio_act[activate][hint];
    return $result;
  }

  function print_plugin_form($plugin = array(uniqId, full_name, uri => 'http://wordpress.org/extend/plugins/', description, close_form => true, options_div => false), $active_plugins) {
    static $plugin_form;
    $plugin_form = get_plugin_form_info($plugin[uniqId], $active_plugins);
    print("
<form method=\"post\" action=\"\">
  <h2 class=\"aio_pluginname\">
    <input type=\"hidden\" name=\"plugin_name\" value=\"".$plugin_form[name]."\" />
    <input type=\"hidden\" name=\"plugin_full_name\" value=\"".$plugin[full_name]."\" />
    <input type=\"hidden\" name=\"act\" value=\"".$plugin_form[act]."\" />
    <input type=\"image\"  id=\"".$plugin_form[name]."\" class=\"".$plugin_form[cssclass]."\" src=\"".$plugin_form[image_url]."\" value=\"\" title=\"".$plugin_form[btn_title]."\" />
    <label for=\"".$plugin_form[name]."\" title=\"".$plugin_form[btn_title]."\">".$plugin[full_name]."</label>
    <small><a href=\"".$plugin[uri]."\">".__('Plugin home page', 'wp-all-in-one-tools')."</a></small>
  </h2>
  <p class=\"descr\">".$plugin[description]."</p>");
    if($plugin[close_form]) print("\n</form>\n");
    if($plugin[options_div]) # Show options if it active, or it function
      $plugin_form[active] || $plugin_form[type] == 'function' ? print("<div class=\"opt\">\n") : print("<div style=\"display: none;\" class=\"opt\">\n");
  }
  global $aio_plugins, $aio_messages, $aio_act, $wp_version;
  static $active_plugins = array(),
         $plugin_form = array(),
         $wp_exploded_version = array();


  $active_plugins = aio_get_active_plugins(); ### Get active plugins

  ### Show strings from $aio_messages array
  if(!empty($aio_messages)) {
    print("<div class=\"updated fade\">");
    foreach($aio_messages as $message)
      print("<p>".$message."</p>");
    print("</div>");
  }

  /*print_r($active_plugins);
  print '<hr />'.plugins_list_to_str($active_plugins);
  print("<hr>");
  print get_option(AIO_OPTIONS);*/
?>

<div class="wrap">
<!--<?php _e('Hello bear!', 'wp-all-in-one-tools'); ?> /* from russia with love ^_^ */-->
<div class="aio_section_head"><?php _e('Security tools:', 'wp-all-in-one-tools'); ?></div>

<?php
  print_plugin_form(array(uniqId => 'replace-wp-version',
                          full_name => 'Replace WP-Version',
                          uri => 'http://bueltge.de/wordpress-version-verschleiern-plugin/602/',
                          description => __('Replace the WP-version with a random string &lt; WP 2.4 and eliminate WP-version &gt; WP 2.4', 'wp-all-in-one-tools'),
                          close_form => true), $active_plugins);

  ### Version of WP must be only 2.5.x
  if(defined(AIO_IS_2_5)) {
    print_plugin_form(array(uniqId => 'secret-key-edit',
                            full_name => 'Add/Change SECRET_KEY in wp-config.php',
                            uri => 'http://www.ActiveBlogging.com/',
                            description => __('Adds or changes the random SECRET_KEY in your WordPress wp-config.php file, if file permissions allow it. NOT needed on new installs unless you want to change the key; its useful on upgrades where you\'re reusing the old wp-config.php file. Just activate. On success the plugin will add the key, log you out, and then deactivate itself. On failure, it will give you a random key to add by hand. Note: if any problems, just delete this file or rename it and refresh your browser.', 'wp-all-in-one-tools'),
                            close_form => true,
                            options_div => true), $active_plugins);
    print('<p>'.__('Now SECRET_KEY is:', 'wp-all-in-one-tools').' <code><strong>'.SECRET_KEY.'</strong></code></p></div>');
  }


  print_plugin_form(array(uniqId => 'iodized-salt',
                          full_name => 'Iodized Salt (safe cookie)',
                          uri => 'http://blog.portal.khakrov.ua/',
                          description => __('It makes the cookie specific to your IP address, so it won\'t be usable from a different computer.Use this plugin if you have a static IP. If you have a dynamic IP address and it changes often you will get logged out frequently.', 'wp-all-in-one-tools'),
                          close_form => true), $active_plugins);
?>

<p>&nbsp;</p>

<div class="aio_section_head"><?php _e('WordPress Optimization:', 'wp-all-in-one-tools'); ?></div>

<?php
  print_plugin_form(array(uniqId => 'disable-core-update',
                          full_name => 'Disable WordPress Core Update',
                          uri => 'http://lud.icro.us/disable-core-update/',
                          description => __('Disables the WordPress core update checking and notification system.', 'wp-all-in-one-tools'),
                          close_form => true), $active_plugins);

  print_plugin_form(array(uniqId => 'disable-plugin-update',
                          full_name => 'Disable WordPress Plugin Updates',
                          uri => 'http://lud.icro.us/disable-wordpress-plugin-updates/',
                          description => __('Disables the plugin update checking and notification system.', 'wp-all-in-one-tools'),
                          close_form => true), $active_plugins);
?>

<p>&nbsp;</p>

<div class="aio_section_head"><?php _e('WordPress Patches:', 'wp-all-in-one-tools'); ?></div>

<?php
  print_plugin_form(array(uniqId => 'allow-numeric-stubs',
                          full_name => 'Allow Numeric Stubs',
                          uri => 'http://www.viper007bond.com/wordpress-plugins/allow-numeric-stubs/',
                          description => __('Allows children Pages to have a stub that is only a number. Sacrifices the <code>&lt;!--nextpage--&gt;</code> ability in Pages to accomplish it.', 'wp-all-in-one-tools'),
                          close_form => true), $active_plugins);

  if(defined(AIO_IS_2_5))
  print_plugin_form(array(uniqId => 'image-upload-2-5-fix',
                          full_name => 'Image Upload HTTP Error Fix',
                          uri => 'http://lud.icro.us/wordpress-plugin-image-upload-http-error-fix/',
                          description => __('Fixes the media uploader HTTP Error that some WordPress configurations suffer from.', 'wp-all-in-one-tools'),
                          close_form => true), $active_plugins);

?>

<p>&nbsp;</p>

<div class="aio_section_head"><?php _e('WordPress Usability:', 'wp-all-in-one-tools'); ?></div>

<?php
  print_plugin_form(array(uniqId => 'show-me-options',
                          full_name => 'Show Me Options',
                          uri => 'http://www.prelovac.com/vladimir/wordpress-plugins/show-me-options',
                          description => __('Allows you to quckly access plugin options upon activation.', 'wp-all-in-one-tools'),
                          close_form => true), $active_plugins);
?>

<p>&nbsp;</p>

<div class="aio_section_head"><?php _e('Comments&amp;Posts&amp;Feeds:', 'wp-all-in-one-tools'); ?></div>

<?php
  print_plugin_form(array(uniqId => 'minimum-comment-length',
                          full_name => 'Minimum Comment Length',
                          uri => 'http://yoast.com/wordpress/minimum-comment-length/',
                          description => __('Check the comment for a set minimum length and disapprove it if its too short (some errors try using cyrillic alphabet).', 'wp-all-in-one-tools'),
                          close_form => true,
                          options_div => true), $active_plugins);

  ### Author: Joost de Valk ########################################## begin ###
	$options['mincomlength'] = 15;
	$options['mincomlengtherror'] = __('Error: Your comment is too short. Please try to say something useful.', 'wp-all-in-one-tools');
	add_option('MinComLengthOptions', $options);
	// Overwrite defaults with saved settings
	if ( isset($_POST['mincomlength-submit']) ) {
		/*if (!current_user_can('manage_options')) die(__('You cannot edit the Minimum Comment Length options.'));
		  check_admin_referer('mincomlength-config');*/
		if (isset($_POST['mincomlength']) && $_POST['mincomlength'] != "" && is_numeric($options['mincomlength']))
			$options['mincomlength'] = $_POST['mincomlength'];
		if (isset($_POST['mincomlengtherror']) && $_POST['mincomlengtherror'] != "")
			$options['mincomlengtherror'] = htmlspecialchars($_POST['mincomlengtherror'], ENT_QUOTES);
		update_option('MinComLengthOptions', $options);
    $mincom_updated = true; # added by Nikolay
	}
	$options = get_option('MinComLengthOptions');
  ###################################################################### end ###

?>
  <form action="" method="post" id="mincomlength-conf">
    <table class="aio_table">
      <tr>
        <td>
          <label for="mincomlength"><?php _e('Minimum comment length:', 'wp-all-in-one-tools'); ?></label>
        </td><td>
          <label for="mincomlengtherror"><?php _e('Error message:', 'wp-all-in-one-tools'); ?></label>
        </td>
      </tr><tr>
        <td>
          <input type="text" value="<?php echo $options['mincomlength']; ?>" name="mincomlength" id="mincomlength" size="4" />
        </td><td>
          <input type="text" value="<?php echo stripslashes($options['mincomlengtherror']); ?>" name="mincomlengtherror" id="mincomlengtherror" size="50" />
        </td>
      </tr>
    </table>
    <input type="submit" name="mincomlength-submit" class="aio_submit<?php if($mincom_updated) print(" updated fade"); ?>" value="<?php _e('Update Settings &raquo;', 'wp-all-in-one-tools'); ?>" />
  </form>
</div> <?php /* div open tag wroted by function */?>

<?php
  print_plugin_form(array(uniqId => 'feed-copyright',
                          full_name => 'Angsumans Feed Copyrighter',
                          uri => 'http://blog.taragana.com/index.php/archive/wordpress-plugins-provided-by-taraganacom/',
                          description => __('Inserts copyright message in Feeds.', 'wp-all-in-one-tools'),
                          close_form => true,
                          options_div => true), $active_plugins);

  ### Author: Nikoly (cmepthuk) ###################################### begin ###
  if(get_option($aio_plugins['feed-copyright'][optionName]) == '')
	  add_option($aio_plugins['feed-copyright'][optionName], __('<hr />Copyright &copy; SITE_NAME. This Feed is for personal non-commercial use only. If you are not reading this material in your news aggregator, the site you are looking at is guilty of copyright infringement.', 'wp-all-in-one-tools'), 'WP All-in-one Tools Feed Copyright plugin config', 'yes');
	if (isset($_POST['feedcopyright-submit']) && $_POST['feedcopyright-text']) {
		update_option($aio_plugins['feed-copyright'][optionName], $_POST['feedcopyright-text']);
    $feed_copyright_updated = true;
	}
  ###################################################################### end ###
?>
  <form action="" method="post">
    <table class="aio_table">
      <tr>
        <td>
          <label for="mincomlength"><?php _e('Copyright text:', 'wp-all-in-one-tools'); ?></label> <small><?php _e('(You can use <u>HTML tags</u>)', 'wp-all-in-one-tools'); ?></small>
        </td>
      </tr><tr>
        <td>
          <textarea name="feedcopyright-text" cols="60" rows="4" style="width: 99%;"><?php print(stripslashes(get_option($aio_plugins['feed-copyright'][optionName]))); ?></textarea>
        </td>
      </tr>
    </table>
    <input type="submit" name="feedcopyright-submit" class="aio_submit<?php if($feed_copyright_updated) print(" updated fade"); ?>" value="<?php _e('Update Settings &raquo;', 'wp-all-in-one-tools'); ?>" /><br />

  </form>
</div> <?php /* div open tag wroted by function */?>
<p>&nbsp;</p>

</div>
<div class="aio_footer"><?php _e('Version', 'wp-all-in-one-tools'); ?> <?php print AIO_VERSION; ?>, <?php _e('Created in <a href="http://www.infine.ru/support/">infine.ru</a> web develop studio', 'wp-all-in-one-tools'); ?></div>

<?php }

function aio_add_admin_page() {
  add_options_page(__('All in One tools Options', 'wp-all-in-one-tools'), __('All in One tools', 'wp-all-in-one-tools'), 8, __FILE__, 'aio_admin_page');
}
add_action('admin_menu', 'aio_add_admin_page');
################################################################################
################################################################################


$aio_active_plugins = aio_get_active_plugins();

### Function check exist file, and load them
function aio_load_plugin($filename) {
  if(file_exists(AIO_PLUGINS_DIR.$filename))
    include(AIO_PLUGINS_DIR.$filename);
}

### Plugin Name: Replace WP-Version ########################### Version: 1.0 ###
### Author: Frank Bueltge ######################################################
if(!function_exists($aio_plugins['replace-wp-version'][cell_function])
   and
   in_array($aio_plugins['replace-wp-version'][uniqId], $aio_active_plugins)) {

  aio_load_plugin('replace-wp-version.php');

}
################################################################################


### Plugin Name: utopia39 - Add/Change SECRET_KEY ############ Version: 1.03 ###
### Author: David Pankhurst ####################################################
if(defined(AIO_IS_2_5)) {

  function utopia39_addSecretKey()
  {
    $file = ABSPATH."wp-config.php";
    $lines = @implode('', @file($file));
    $key = '';
    $pool = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+{}|:<>?- = [];, .";
    $limit = strlen($pool)-1;
    $len = mt_rand(33, 47);
    // generate new key
    while(--$len)
      $key.=  $pool[mt_rand(0, $limit)];
    // add or replace?
    $regex = '@([\n\r]+\s*define\s*\(\s*[\'"]SECRET_KEY[\'"]\s*, \s*)([\'"])[^\n\r]*(\2\s*\)\s*;)@si';
    if(preg_match($regex, $lines, $matches))
    {
      // current - insert new key only
      $line = $matches[1].$matches[2].$key.$matches[3];
      $lines = preg_replace($regex, $line, $lines);
    }
    else
    {
      // new - insert after DB_COLLATE line
      $line = "define('SECRET_KEY',  '$key');";
      $regex = '@(DB_COLLATE[^\n\r]*)([\n\r]+)@si';
      $lines = preg_replace($regex, '$1$2'.$line.'$2', $lines);
    }
    // write out
    $success = FALSE;
    if(is_writable($file))
    {
      if($handle = fopen($file, 'wb'))
      {
        if(fwrite($handle, $lines))
          $success = TRUE;
        fclose($handle);
      }
    }
    if(!$success)
      die('wp-config.php cannot be written to - please set permissions to allow
      writing or edit it yourself and add
      this line:<br>'.htmlentities($line, ENT_QUOTES, 'UTF-8'));
  }

}
################################################################################


### Plugin Name: Replace WP-Version ########################### Version: 1.0 ###
### Author: Frank Bueltge ######################################################
if(in_array($aio_plugins['iodized-salt'][uniqId], $aio_active_plugins)) {

  add_filter('salt','iodized_salt');
  function iodized_salt($key) {
  	return md5($key.$_SERVER["REMOTE_ADDR"]);
  }

}
################################################################################


### Plugin Name: Disable WordPress Core Update ################ Version: 1.1 ###
### Author: John Blackbourn ####################################################
if(in_array($aio_plugins['disable-core-update'][uniqId], $aio_active_plugins)) {

  add_action('init', create_function('$a', "remove_action('init', 'wp_version_check');"));
  add_filter('pre_option_update_core', create_function('$a', "return null;"));

}
################################################################################


### Plugin Name: Disable WordPress Plugin Updates ############# Version: 1.2 ###
### Author: John Blackbourn ####################################################
if(in_array($aio_plugins['disable-plugin-update'][uniqId], $aio_active_plugins)) {

  add_action('admin_menu', create_function('$a', "remove_action('load-plugins.php', 'wp_update_plugins');") );
  	# Why use the admin_menu hook? It's the only one available between the above hook being added and being applied
  add_action('admin_init', create_function('$a', "remove_action('admin_init', 'wp_update_plugins');"), 2 );
  add_action('init', create_function('$a', "remove_action('init', 'wp_update_plugins');"), 2 );
  add_filter('pre_option_update_plugins', create_function('$a', "return null;"));

}
################################################################################


### Plugin Name: Image Upload HTTP Error Fix  ################# Version: 1.1 ###
### Author: John Blackbourn ####################################################
if(!function_exists($aio_plugins['image-upload-2-5-fix'][cell_function])
   and
   in_array($aio_plugins['image-upload-2-5-fix'][uniqId], $aio_active_plugins)
   and
   $wp_exploded_version[0] == 2 && $wp_exploded_version[1] == 5){

  function iuehf_activate() {
  	global $wp_rewrite;
  	$hp = get_home_path();
  	if ((!file_exists($hp.'.htaccess') and !is_writable($hp))
  	  or (file_exists($hp.'.htaccess') and !is_writable($hp.'.htaccess')))
  		add_option( 'iuehf_notice', true );
  	$wp_rewrite->flush_rules();
  }

  function iuehf_notice() {
  	if ( get_option( 'iuehf_notice' ) ) {
  		?>
  		<div class="updated fade" id="iuhef_message"><p><?php _e('<strong>Your blog&#8217;s .htaccess file needs to be updated, but it is not writable.</strong> Please visit your <a href="options-permalink.php">Permalink Settings</a> page and follow the instructions on there for updating your .htaccess file.', 'wp-all-in-one-tools'); ?></p></div>
  		<?php
  	}
  }

  add_filter( 'mod_rewrite_rules', create_function(
  	'$rules', 'return $rules . "
  #BEGIN Image Upload HTTP Error Fix
  <IfModule mod_security.c>
  <Files async-upload.php>
  SecFilterEngine Off
  SecFilterScanPOST Off
  </Files>
  </IfModule>
  <IfModule security_module>
  <Files async-upload.php>
  SecFilterEngine Off
  SecFilterScanPOST Off
  </Files>
  </IfModule>
  <IfModule security2_module>
  <Files async-upload.php>
  SecFilterEngine Off
  SecFilterScanPOST Off
  </Files>
  </IfModule>
  #END Image Upload HTTP Error Fix
  ";'
  	) );

  add_action( 'admin_notices', 'iuehf_notice' );
  add_action( 'load-options-permalink.php', create_function( '$a', 'return delete_option( "iuehf_notice" );' ) );

  load_plugin_textdomain( 'iuhef', PLUGINDIR );
  register_activation_hook( __FILE__, 'iuehf_activate' );

}
################################################################################


### Plugin Name: Allow Numeric Stubs ######################## Version: 1.0.0 ###
### Author: Viper007Bond #######################################################
if(in_array($aio_plugins['allow-numeric-stubs'][uniqId], $aio_active_plugins)) {

  // Register plugin hooks
  register_activation_hook( __FILE__, 'allow_numeric_stubs_activate' );
  add_filter( 'page_rewrite_rules', 'allow_numeric_stubs' );


  // Force a flush of the rewrite rules when this plugin is activated
  function allow_numeric_stubs_activate() {
  	global $wp_rewrite;
  	$wp_rewrite->flush_rules();
  }


  // Remove the rule that "breaks" it and replace it with a non-paged version
  function allow_numeric_stubs( $rules ) {
  	unset( $rules['(.+?)(/[0-9]+)?/?$'] );
  	$rules['(.+?)?/?$'] = 'index.php?pagename=$matches[1]';
  	return $rules;
  }

}
################################################################################


### Plugin Name: Show Me Options ############################## Version: 0.2 ###
### Author: Vladimir Prelovac ##################################################
if(!class_exists($aio_plugins['show-me-options'][cell_class])
   and
   in_array($aio_plugins['show-me-options'][uniqId], $aio_active_plugins)) {

  aio_load_plugin('show-me-options.php');

}
################################################################################


### Plugin Name: SMinimum Comment Length ###################### Version: 0.5 ###
### Author: Joost de Valk ######################################################
if(!function_exists($aio_plugins['minimum-comment-length'][cell_function])
   and
   in_array($aio_plugins['minimum-comment-length'][uniqId], $aio_active_plugins)) {

  function check_comment_length($commentdata) {
  	$options = get_option('MinComLengthOptions');
  	if ($options['mincomlength'] == "")
  		$options['mincomlength'] = 15;

  	if ($options['mincomlengtherror'] == "")
  		$options['mincomlengtherror'] = __('Error: Your comment is too short. Please try to say something useful.', 'wp-all-in-one-tools');

  	if (strlen($commentdata['comment_content']) < $options['mincomlength']) {
  		wp_die(stripslashes($options['mincomlengtherror']));
  	} else {
  		return $commentdata;
  	}
  }
  add_filter('preprocess_comment','check_comment_length');

}
################################################################################


### Plugin Name: SMinimum Comment Length ###################### Version: 0.5 ###
### Author: Joost de Valk ######################################################
if(!function_exists($aio_plugins['feed-copyright'][cell_function])
   and
   in_array($aio_plugins['feed-copyright'][uniqId], $aio_active_plugins)) {

  function addCopyrightToFeed($content) {
    global $aio_plugins;
    if(is_feed()) {
      return $content.stripslashes(get_option($aio_plugins['feed-copyright'][optionName]));
    } else {
      return $content;
    }
  }
  add_filter('the_content', 'addCopyrightToFeed');

}
################################################################################
?>