<?php
/*
Plugin Name: Shutter Reloaded
Plugin URI: http://www.laptoptips.ca/projects/wp-shutter-reloaded/
Description: Darkens the current page and displays an image on top like Lightbox, Thickbox, etc. However this script is a lot smaller and faster.
Version: 2.1
Author: Andrew Ozz
Author URI: http://www.laptoptips.ca/

Acknowledgement: some ideas from: Shutter by Andrew Sutherland - http://code.jalenack.com, WordPress - http://wordpress.org, Lightbox by Lokesh Dhakar - http://www.huddletogether.com, IE6 css position:fixed ideas from gunlaug.no and quirksmode.org, the icons are from Crystal Project Icons, Everaldo Coelho, http://www.everaldo.com

Released under the GPL version 2 or newer, http://www.gnu.org/copyleft/gpl.html

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
*/

$srel_autoset = false;
$srel_load_txtdomain = true;

function srel_txtdomain() {
    global $srel_load_txtdomain;
    
    if( $srel_load_txtdomain ) {
        load_plugin_textdomain('srel-l10n', 'wp-content/plugins/shutter-reloaded/languages');
        $srel_load_txtdomain = false;
    }
}

function srel_makeshutter() { 
    global $post, $srel_autoset;
    $srel_options = get_option('srel_options');
    $srel_main = get_option('srel_main');
    $srel_included = (array) get_option('srel_included');
    $srel_excluded = (array) get_option('srel_excluded');
    srel_txtdomain();

    $addshutter = false;
    switch( $srel_main ) {
    case 'srel_pages' :
        if ( in_array($post->ID, $srel_included) )
            $addshutter = 'shutterReloaded.Init();';
        break;
    
    case 'auto_set' :
        if ( ! in_array($post->ID, $srel_excluded) ) {
            $addshutter = "shutterReloaded.Init('sh');";
            $srel_autoset = true;
        }
        break;
    
    case 'srel_class' :
        $addshutter = "shutterReloaded.Init('sh');";
        break;
    
    case 'srel_lb' :
        $addshutter = "shutterReloaded.Init('lb');";
        break;
    
    default :
        if ( ! in_array($post->ID, $srel_excluded) ) 
            $addshutter = 'shutterReloaded.Init();';
    }
    
    if ( $addshutter ) {
?>
<link rel="stylesheet" href="<?php bloginfo('wpurl'); ?>/wp-content/plugins/shutter-reloaded/shutter-reloaded.css?ver=2.0" type="text/css" media="screen" />
<?php 
if( $srel_options['custom'] == 1 ) { ?>
<style type="text/css">
<?php 
    if( $srel_options['btncolor'] != 'cccccc' ) { ?>
div#shNavBar a {color:#<?php echo $srel_options['btncolor']; ?>;}
<?php }
    if( $srel_options['menucolor'] != '3e3e3e' ) { ?>
div#shNavBar {background-color:#<?php echo $srel_options['menucolor']; ?>;}
<?php }
    if( $srel_options['imgcountcolor'] != '999999' ) { ?>
div#shNavBar {color:#<?php echo $srel_options['imgcountcolor']; ?>;}
<?php }
    if( $srel_options['shcolor'] != '000000' || $srel_options['opacity'] != '80' ) { ?>
div#shShutter{background-color:#<?php echo $srel_options['shcolor']; ?>;opacity:<?php echo ($srel_options['opacity']/100); ?>;filter:alpha(opacity=<?php echo $srel_options['opacity']; ?>);}
<?php }
    if( $srel_options['capcolor'] != 'ffffff' ) { ?>
#shDisplay div#shTitle {color:#<?php echo $srel_options['capcolor']; ?>;}
<?php } ?>
</style>
<?php } ?>
<script type="text/javascript">
//<![CDATA[
shImgDir = '<?php bloginfo('wpurl'); ?>/wp-content/plugins/shutter-reloaded/menu/';
<?php if( $srel_options['shImageCount'] == 1 ) { ?>shImageCount = true;
<?php } else { ?>shImageCount = false;
<?php } 
    if( $srel_options['shTextBtns'] == 1 ) { ?>shTextBtns = true;
<?php } else { ?>shTextBtns = false;
<?php } ?>
shL10n = ['<?php echo js_escape(__("Previous", "srel-l10n")); ?>','<?php echo js_escape(__("Next", "srel-l10n")); ?>','<?php echo js_escape(__("Close", "srel-l10n")); ?>','<?php echo js_escape(__("Full Size", "srel-l10n")); ?>','<?php echo js_escape(__("Fit to Screen", "srel-l10n")); ?>','<?php echo js_escape(__("Image", "srel-l10n")); ?>','<?php echo js_escape(__("of", "srel-l10n")); ?>','<?php echo js_escape(__("Loading...", "srel-l10n")); ?>'];
shutterOnload = function(){<?php echo $addshutter; ?>};
//]]>
</script>
<script src="<?php bloginfo('wpurl'); ?>/wp-content/plugins/shutter-reloaded/shutter-reloaded.js?ver=2.0" type="text/javascript"></script>
<?php }
}
add_action('wp_head', 'srel_makeshutter' );

function srel_auto_set($content) {
	global $post, $srel_autoset;
  	
  	if( $srel_autoset ) {
        $pattern = array( '/<a([^>]*)href=[\'"]([^"\']+).(gif|jpeg|jpg|png)[\'"]([^>]*>)/i', '/<a class="shutterset_%SRELID%" href="([^"]+)"([^>]*)class=[\'"]([^"\']+)[\'"]([^>]*>)/i' );
        $replacement = array( '<a class="shutterset_%SRELID%" href="$2.$3"$1$4', '<a class="shutterset_%SRELID% $3" href="$1"$2$4' );
        $content = preg_replace($pattern, $replacement, $content);

        return str_replace('%SRELID%', $post->ID, $content);
    }
    return $content;
}
add_filter('the_content', 'srel_auto_set', 65 );

function srel_deactiv() {
    delete_option('srel_options');
    delete_option('srel_main');
    delete_option('srel_included');
    delete_option('srel_excluded');
}
add_action('deactivate_shutter-reloaded/shutter-reloaded.php', 'srel_deactiv');

function srel_optpage() {

    if ( ! current_user_can('manage_options') )
        die( __('Permission denied', 'srel_l10n') );
    
    $opt = get_option('srel_main') ? get_option('srel_main') : 'srel_all';
    srel_txtdomain();
  
    if ( isset($_POST['srel_main']) ) {
        check_admin_referer('srel-save-options');
        $newopt = $_POST['srel_all'] ? 'srel_all' : '';
        $newopt = $_POST['srel_class'] ? 'srel_class' : $newopt;
        $newopt = $_POST['auto_set'] ? 'auto_set' : $newopt;
        $newopt = $_POST['srel_pages'] ? 'srel_pages' : $newopt;
        $newopt = $_POST['srel_lb'] ? 'srel_lb' : $newopt;
        if( $newopt != $opt ) {
            $opt = $newopt;
            update_option("srel_main", $newopt);
        }
    }
    
    $srel_options = get_option('srel_options');
    $def = array( 'shcolor' => '000000', 'opacity' => '80', 'capcolor' => 'ffffff', 'menucolor' => '3e3e3e', 'btncolor' => 'cccccc', 'imgcountcolor' => '999999' );
    
    if( ! is_array($srel_options) ) {
        $srel_options = array_merge( $def, array('shImageCount' => '1', 'shTextBtns' => '0', 'custom' => '0') );
        add_option( 'srel_options', $srel_options, 'Shutter Reloaded' );
    }

    if ( isset($_POST['srel_saveopt']) ) {
        check_admin_referer('srel-save-options');
        $new_srel_options['shcolor'] = preg_match("/[0-9A-Fa-f]{6}/", $_POST['shcolor']) ? strtolower($_POST['shcolor']) : '000000';
        $new_srel_options['capcolor'] = preg_match("/[0-9A-Fa-f]{6}/", $_POST['capcolor']) ? strtolower($_POST['capcolor']) : 'ffffff';
        $new_srel_options['menucolor'] = preg_match("/[0-9A-Fa-f]{6}/", $_POST['menucolor']) ? strtolower($_POST['menucolor']) : '3e3e3e';
        $new_srel_options['btncolor'] = preg_match("/[0-9A-Fa-f]{6}/", $_POST['btncolor']) ? strtolower($_POST['btncolor']) : 'cccccc';
        $new_srel_options['imgcountcolor'] = preg_match("/[0-9A-Fa-f]{6}/", $_POST['imgcountcolor']) ? strtolower($_POST['imgcountcolor']) : '999999';
        $new_srel_options['shImageCount'] = $_POST['shImageCount'] ? 1 : 0;
        $new_srel_options['shTextBtns'] = $_POST['shTextBtns'] ? 1 : 0;
        $new_srel_options['opacity'] = preg_match("/^[0-9][0-9]?$/", $_POST['opacity']) ? $_POST['opacity'] : '80';
        
        $new_opt = $new_srel_options;
        unset($new_opt['shImageCount'], $new_opt['shTextBtns'], $new_opt['custom']);
        if( $def != $new_opt ) $new_srel_options['custom'] = 1;
        else $new_srel_options['custom'] = 0;
        
        if( $new_srel_options != $srel_options ) {
            $srel_options = $new_srel_options;
            update_option('srel_options', $new_srel_options);
        }
    }

    $excluded = get_option('srel_excluded') ? (array) get_option('srel_excluded') : array();
    $included = get_option('srel_included') ? (array) get_option('srel_included') : array();
    
    if ( isset($_POST['srel_add_excluded']) ) {
        check_admin_referer('srel-save-options');
        $exclude = (int) $_POST['srel_exclude'];
        if ( $exclude < 1 ) 
            wp_die(__('Please enter valid post ID.', 'srel-l10n'));
        
        $excluded[] = $exclude;
        $excluded = array_values(array_unique($excluded));
        sort($excluded);

        update_option('srel_excluded', $excluded);
    }
  
    if ( isset($_POST['srel_rem_excluded']) ) {
        check_admin_referer('srel-save-options');
        $rem_exclude = (int) $_POST['srel_exclude'];
        if ( ! in_array($rem_exclude, $excluded) ) { ?>
            <div style="background-color: rgb(230, 230, 255);" id="error" class="updated fade-5555cc"><p><strong><?php _e('This post ID is not currently excluded.', 'srel-l10n'); ?></strong></p></div>
<?php   } else {
            
            $excluded = array_diff($excluded, (array) $rem_exclude );
            if( is_array($excluded) ) sort($excluded);
            else $excluded = array();
            update_option('srel_excluded', $excluded);
        }
    }

    if ( isset($_POST['srel_add_included']) ) {
        check_admin_referer('srel-save-options');
        $include = (int) $_POST['srel_include'];
        if ( $include < 1 ) 
            wp_die(__('Please enter valid post ID.', 'srel-l10n'));
        
        $included[] = $include;
        $included = array_values(array_unique($included));
        sort($included);

        update_option('srel_included', $included);
    }
  
    if ( isset($_POST['srel_rem_included']) ) {
        check_admin_referer('srel-save-options');
        $rem_include = (int) $_POST['srel_include'];
        if ( ! in_array($rem_include, $included) ) { ?>
            <div style="background-color: rgb(230, 230, 255);" id="error" class="updated fade-5555cc"><p><strong><?php _e('This post ID is not currently included.', 'srel-l10n'); ?></strong></p></div>
<?php   } else {
            
            $included = array_diff($included, (array) $rem_include);
            if( is_array($included) ) sort($included);
            else $included = array();
            update_option('srel_included', $included);
        }
    }

    if ( isset($_POST['srel_saveopt']) ) { ?>
    <div id="message" class="updated"><p><strong><?php _e('Options saved!', 'srel-l10n'); ?></strong></p></div>
<?php } ?>

    <div class="wrap">
    <h2><?php _e('Shutter Reloaded Options', 'srel-l10n'); ?></h2>
    <form method="post" name="srel_mainform" id="srel_mainform" action="">
    
    <p class="tablenav" style="text-align:right;"><input type="button" id="srelhide" class="button" onclick="if(document.getElementById('srelhelp').style.display == 'none'){document.getElementById('srelhelp').style.display = 'block';document.getElementById('srelhide').value = '<?php echo js_escape(__("Hide Help", "srel-l10n")); ?>';
} else {document.getElementById('srelhelp').style.display = 'none';document.getElementById('srelhide').value = '<?php echo js_escape(__("Show Help", "srel-l10n")); ?>';}" 
value="<?php echo js_escape(__("Show Help", "srel-l10n")); ?>" /></p>
    
    <div id="srelhelp" style="display:none;border: 1px solid #C6D9E9;padding:0 12px;margin:10px 0 0">
    <h4><?php _e('Setup and Usage', 'srel-l10n'); ?></h4>
    <p><?php _e('Shutter is activated by <strong>the link</strong> pointing to the image you want to display, with or without a thumbnail (text links work too). The activation class and the title have to be set on that link.', 'srel-l10n'); ?></p>
    <p><?php _e('To take full control of Shutter\'s activation and to make multiple image sets on the same page, you will need to add the <strong>class=&quot;shutter&quot;</strong> or <strong>&quot;shutterset&quot;</strong> or <strong>&quot;shutterset_setname&quot;</strong> to your links in &quot;Code&quot; view on the Write/Edit Post page.', 'srel-l10n'); ?></p>
    <p><?php _e('To add caption to the images, set the <strong>title=&quot;...&quot;</strong> attribute of the <strong>links</strong> pointing to them.', 'srel-l10n'); ?></p>
    <p><?php _e('If you want to use image sets, you will need to add <strong>class=&quot;shutterset&quot;</strong> to all <strong>links</strong> that point to the images for that set. If you want to apply css style to the links, you can add second class, like this: class=&quot;shutterset myClass&quot;, but &quot;shutterset&quot; should be first.', 'srel-l10n'); ?></p>
    <p><?php _e('Adding class=&quot;shutterset&quot; will also trigger activation (for the first activation option). There is no need to add both &quot;shutter&quot; and &quot;shutterset&quot;.', 'srel-l10n'); ?></p>
    <p><?php _e('To make more than one set per page, use <strong>class=&quot;shutterset_setname&quot;.</strong> The underscore is required and setname can be any short ASCII word and/or number (different for each set).', 'srel-l10n'); ?></p>
    <p><?php _e('You can use the &quot;Activate shutter on all image links&quot; and also make sets by adding class=&quot;shutterset&quot; or class=&quot;shutterset_setname&quot; or rel=&quot;lightbox[...]&quot; to some of the image links.', 'srel-l10n'); ?></p>
    </div>
    
    <div style="padding:0 12px 12px;">
    <h4><?php _e('You can add Shutter Reloaded to your site in five different ways:', 'srel-l10n'); ?> </h4>
    <?php wp_nonce_field( 'srel-save-options' ); ?>
    <input type="hidden" name="srel_main" value="srel_main" />

    <p style="margin:8px;padding:2px;<?php if ( $opt == 'srel_class' ) { ?>color:#999;<?php } ?>">
    <input class="button" type="submit" name="srel_class" value="<?php _e('Activate', 'srel-l10n'); ?>" /> <?php _e('Shutter on all image links with class=&quot;shutter&quot; or &quot;shutterset&quot; or &quot;shutterset_setname&quot;.', 'srel-l10n'); ?></p>

    <p style="margin:8px;padding:2px;<?php if ( $opt == 'srel_all' ) { ?>color:#999;<?php } ?>">
    <input class="button" type="submit" name="srel_all" value="<?php _e('Activate', 'srel-l10n'); ?>" /> <?php _e('Shutter on all image links. Sets created with class=&quot;shutterset&quot;, &quot;shutterset_setname&quot; or rel=&quot;lightbox[...]&quot; will still work.', 'srel-l10n'); ?></p>

    <p style="margin:8px;padding:2px;<?php if ( $opt == 'auto_set' ) { ?>color:#999;<?php } ?>">
    <input class="button" type="submit" name="auto_set" value="<?php _e('Activate', 'srel-l10n'); ?>" /> <?php _e('Shutter on all image links and automatically make image sets for each Post/Page.', 'srel-l10n'); ?></p>

    <p style="margin:8px;padding:2px;<?php if ( $opt == 'srel_pages' ) { ?>color:#999;<?php } ?>">
    <input class="button" type="submit" name="srel_pages" value="<?php _e('Activate', 'srel-l10n'); ?>" /> <?php _e('Shutter on all image links on specific page(s).', 'srel-l10n'); ?></p>
    
    <p style="margin:8px;padding:2px;<?php if ( $opt == 'srel_lb' ) { ?>color:#999;<?php } ?>">
    <input class="button" type="submit" name="srel_lb" value="<?php _e('Activate', 'srel-l10n'); ?>" /> <?php _e('Shutter on all image links and use LightBox style (rel=&quot;lightbox[...]&quot;) activation and sets.', 'srel-l10n'); ?></p>
	</div>
    </form>
    
    <div style="padding:0 12px 4px;border: 1px solid #C6D9E9;">
<?php
if ( $opt == 'srel_all' || $opt == 'auto_set' ) { ?>

    <form method="post" name="srel_excluded" id="srel_excluded" action="">
    <div>
<?php
    if( $opt == 'srel_all' ) { ?><p><strong><?php _e('Shutter is activated for all links pointing to an image.', 'srel-l10n'); ?></strong></p><?php }
    else { ?><p><strong><?php _e('Shutter is activated for all links pointing to an image and will create different image set for each Post/Page.', 'srel-l10n'); ?></strong><br /><?php _e('This option is most suitable if you display several Posts on your home page and want to have different image set for each Post. It adds shutter\'s activation class at runtime and doesn\'t modify the html.', 'srel-l10n'); ?></p><?php }
?>
    <p><?php _e('Excluded Posts or Pages (by ID):', 'srel-l10n'); ?> <?php
    if ( is_array($excluded) && !empty($excluded) ) { 
        foreach( $excluded as $excl ) { ?>
          <span style="border: 1px solid #ccc;padding:2px 4px;cursor:pointer;" onclick="document.forms.srel_excluded.srel_exclude.value = '<?php echo $excl; ?>'"><?php echo $excl; ?></span>
<?php   }
    } else { ?>
        <?php _e('[none]', 'srel-l10n'); ?>
<?php
    } ?>
    </p>

    <input type="text" name="srel_exclude" size="4" maxlength="4" tabindex="4" value="" />
    <input class="button" type="submit" name="srel_add_excluded" value="<?php _e('Add Excluded ID', 'srel-l10n'); ?>" 
        onclick="if (form.srel_exclude.value == ''){alert('<?php echo js_escape(__("Please enter the Page/Post ID that you want to add to this list.", "srel-l10n")); ?>');return false;}" />
    
    <input class="button" type="submit" name="srel_rem_excluded" value="<?php _e('Remove Excluded ID', 'srel-l10n'); ?>" 
    onclick="if (form.srel_exclude.value == ''){alert('<?php echo js_escape(__("Please enter the Page/Post ID that you want to remove from this list.", "srel-l10n")); ?>');return false;}" />
    
    <div style="color:#888;"><?php _e('Please enter the ID for the post/page you want to exclude. You can see it in your browser\'s status bar(at the bottom of the window) when hovering over the name at the <a href="edit-pages.php">Manage Pages</a> or the <a href="edit.php">Manage Posts</a> page.', 'srel-l10n'); ?></div>
    <?php wp_nonce_field( 'srel-save-options' ); ?>
    </div>
    </form>
<?php
    }

if ( $opt == 'srel_class' ) { ?>

    <div>    
    <p><strong><?php _e('Shutter is activated for all links pointing to an image, that have class = &quot;shutter&quot;, &quot;shutterset&quot; or &quot;shutterset_setname&quot;', 'srel-l10n'); ?></strong></p>

    <p><?php _e('Class = &quot;shutter&quot; will display a single image, class = &quot;shutterset&quot; will create a single set for all images and class=&quot;shutterset_setname&quot;, where setname is a short ASCII word and/or number, will create multiple sets on the same page.', 'srel-l10n'); ?></p>
    </div>
<?php
    }

if ( $opt == 'srel_pages' ) { ?>

    <form method="post" name="srel_included" id="srel_included" action="">
    <div>
    <p><strong><?php _e('Shutter is activated for the following Posts and Pages (by ID):', 'srel-l10n'); ?> 
<?php
    if ( is_array($included) && !empty($included) ) { 
        foreach( $included as $incl ) { ?>
            <span style="border: 1px solid #ccc;padding:2px 4px;cursor:pointer;" onclick="document.forms.srel_included.srel_include.value = '<?php echo $incl; ?>'"><?php echo $incl; ?></span>
<?php   }
    } else { ?>
        <?php _e('[none]', 'srel-l10n'); ?>
<?php
    } ?>
    </strong></p>

    <input type="text" name="srel_include" size="4" maxlength="4" tabindex="4" value="" />
    <input class="button" type="submit" name="srel_add_included" value="<?php _e('Add ID', 'srel-l10n'); ?>" 
        onclick="if (form.srel_include.value == ''){alert('<?php echo js_escape(__("Please enter the Page/Post ID that you want to add to this list.", "srel-l10n")); ?>');return false;}" />
    <input class="button" type="submit" name="srel_rem_included" value="<?php _e('Remove ID', 'srel-l10n'); ?>" 
    onclick="if (form.srel_include.value == ''){alert('<?php echo js_escape(__("Please enter the Page/Post ID that you want to remove from this list.", "srel-l10n")); ?>');return false;}" />
    
    <div style="color:#888;"><?php _e('Please enter the ID for the post/page you want to exclude. You can see it in your browser\'s status bar(at the bottom of the window) when hovering over the name at the <a href="edit-pages.php">Manage Pages</a> or the <a href="edit.php">Manage Posts</a> page.', 'srel-l10n'); ?></div>
    <?php wp_nonce_field( 'srel-save-options' ); ?>
    </div>
    </form>
<?php 
    } 
    
    if ( $opt == 'srel_lb' ) { ?>
	<div>
    <p><strong><?php _e('Shutter uses Lightbox style activation.', 'srel-l10n'); ?></strong></p>

    <p><?php _e('Shutter is activated for all links pointing to an image, that have rel=&quot;lightbox&quot; or rel=&quot;lightbox[...]&quot;. To make sets of images, you will have to add rel=&quot;lightbox[abc]&quot;, where &quot;abc&quot; can be any short ASCII word and/or number.', 'srel-l10n'); ?></p>
    </div>
<?php
    }
?>
	</div>
	
    <form method="post" name="srel_saveoptform" id="srel_saveoptform" action="">
    <table class="widefat" style="padding:5px 12px;border:0;margin-top:20px;">
    <thead>
	<tr><th colspan="2" style="text-align:center;">
    <?php _e('Customization', 'srel-l10n'); ?>
    </th></tr>
    </thead>
    
    <tbody>
    <tr><td style="width:50%;text-align:right;">
    <?php _e('Shutter color (default 000000):', 'srel-l10n'); ?> <br /><?php _e('Please enter valid HTML color codes, from 000000 to FFFFFF.', 'srel-l10n'); ?> 
    </td><td>
    <input type="text" name="shcolor" size="6" maxlength="6" tabindex="" value="<?php echo $srel_options['shcolor']; ?>" />
    <input type="text" name="shcolor2" size="6" disabled style="padding:4px;border:1px solid #888;background-color:#<?php echo $srel_options['shcolor']; ?>;" />
    </td></tr>
    
    <tr><td style="text-align:right;">
    <?php _e('Shutter opacity (default 80):', 'srel-l10n'); ?> <br /><?php _e('Enter a number between 1 (see-through) and 99 (solid color).', 'srel-l10n'); ?>
    </td><td>
    <input type="text" name="opacity" size="6" maxlength="3" tabindex="" value="<?php echo $srel_options['opacity']; ?>" />
    <input type="text" name="opacity2" size="6" disabled style="padding:4px;border:1px solid #888;background-color:#<?php echo $srel_options['shcolor']; ?>;opacity:<?php echo ($srel_options['opacity']/100); ?>;filter:alpha(opacity=<?php echo $srel_options['opacity']; ?>);" />
    </td></tr>
    
    <tr><td style="text-align:right;">
    <?php _e('Caption text color (default FFFFFF):', 'srel-l10n'); ?>
    </td><td>
    <input type="text" name="capcolor" size="6" maxlength="6" tabindex="" value="<?php echo $srel_options['capcolor']; ?>" />
    <input type="text" name="capcolor2" size="6"  disabled style="padding:4px;border:1px solid #888;background-color:#<?php echo $srel_options['capcolor']; ?>;" />
    </td></tr>
        
    <tr><td style="text-align:right;">
    <?php _e('Menubar color (default 3E3E3E):', 'srel-l10n'); ?>
    </td><td>
    <input type="text" name="menucolor" size="6" maxlength="6" tabindex="" value="<?php echo $srel_options['menucolor']; ?>" />
    <input type="text" name="menucolor2" size="6" disabled style="padding:4px;border:1px solid #888;background-color:#<?php echo $srel_options['menucolor']; ?>;" />
    </td></tr>
    
    <tr><td style="text-align:right;">
    <?php _e('Show images count for sets (Image 1 of ...):', 'srel-l10n'); ?>
    </td><td>
    <input type="checkbox" class="checkbox"  name="shImageCount" id="shImageCount" <?php if ($srel_options['shImageCount'] == 1) { echo ' checked="checked"'; } ?> />
    </td></tr>
    
    <tr><td style="text-align:right;">
    <?php _e('Images count color (default 999999):', 'srel-l10n'); ?>
    </td><td>
    <input type="text" name="imgcountcolor" size="6" maxlength="6" tabindex="" value="<?php echo $srel_options['imgcountcolor']; ?>" />
    <input type="text" name="imgcountcolor2" size="6" disabled style="padding:4px;border:1px solid #888;background-color:#<?php echo $srel_options['imgcountcolor']; ?>;" />
    </td></tr>
    
    <tr><td style="text-align:right;">
    <?php _e('Text buttons (instead of images):', 'srel-l10n'); ?>
    </td><td>
    <input type="checkbox" class="checkbox"  name="shTextBtns" id="shTextBtns" <?php if ($srel_options['shTextBtns'] == 1) { echo ' checked="checked"'; } ?> />
    </td></tr>
    
    <tr><td style="text-align:right;">
    <?php _e('Text buttons color (default CCCCCC):', 'srel-l10n'); ?>
    </td><td>
    <input type="text" name="btncolor" size="6" maxlength="6" tabindex="" value="<?php echo $srel_options['btncolor']; ?>" />
    <input type="text" name="btncolor2" size="6" disabled style="padding:4px;border:1px solid #888;background-color:#<?php echo $srel_options['btncolor']; ?>;" />
    </td></tr>
    </tbody>
    </table>

    <p><?php _e('To restore the defaults, delete the current value(s) and submit the form.', 'srel-l10n'); ?></p>
	<p class="submit"><input type="submit" name="srel_saveopt" value="<?php _e('Save Options', 'srel-l10n'); ?>" /></p>
    <?php wp_nonce_field( 'srel-save-options' ); ?>
    </form>
    </div>
<?php
} // end srel_optpage

function srel_addmenu() {
    if ( function_exists('add_options_page') ) {
        srel_txtdomain();
        add_options_page(__('Shutter Reloaded', 'srel-l10n'), __('Shutter Reloaded', 'srel-l10n'), 9,  __FILE__, 'srel_optpage');
    }
}
add_action('admin_menu', 'srel_addmenu');
?>