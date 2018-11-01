<?php
/*
Plugin Name: WP Posts/Comments Time
Plugin URI: http://www.jovelstefan.de/
Description: Plugin to create a chart that shows the number of posts and comments for the hours of a day and/or the days of a week
Version: 0.2
License: GPL
Author: Stefan He&szlig;
Author URI: http://www.jovelstefan.de

Contact mail: jovelstefan@gmx.de
*/

// prevent file from being accessed directly
if ('pocoti.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not access this file directly. Thanks!');

function pocoti_update_charts() {
	$options = get_option('pocoti');

    $dir = dirname(__FILE__);
	@unlink($dir."/chartday.png");
	@unlink($dir."/charthour.png");

	include($dir."/chartday.php");
	include($dir."/charthour.php");
}

function pocoti_init_options() {

		$newoptions['titlestr'] = 'Posts/Comments Time';
		$newoptions['daytitlestr'] = 'Posts/Comments Day';
		$newoptions['poststr'] = '#Posts';
		$newoptions['commentstr'] = '#Comments';
		$newoptions['hourstr'] = 'Hour';
		$newoptions['daystr'] = 'Day of week';
		$newoptions['colorpost'] = 'red';
		$newoptions['colorcomment'] = 'blue';
		$newoptions['width'] = 400;
		$newoptions['height'] = 300;
		$newoptions['starthour'] = 0;
		$newoptions['init'] = TRUE;
		add_option('pocoti', $newoptions);
}


//build admin interface
function pocoti_option_page() {
	$dir = dirname(__FILE__);
	$options = $newoptions = get_option('pocoti');
	if ( $options['init']=='' ) { pocoti_init_options(); $options = $newoptions = get_option('pocoti'); }
	if ( $_POST['pocoti-submit'] ) {
		$newoptions['titlestr'] = stripslashes($_POST['pocoti-titlestr']);
		$newoptions['daytitlestr'] = stripslashes($_POST['pocoti-daytitlestr']);
		$newoptions['poststr'] = stripslashes($_POST['pocoti-poststr']);
		$newoptions['commentstr'] = stripslashes($_POST['pocoti-commentstr']);
		$newoptions['hourstr'] = stripslashes($_POST['pocoti-hourstr']);
		$newoptions['daystr'] = stripslashes($_POST['pocoti-daystr']);
		$newoptions['colorpost'] = stripslashes($_POST['pocoti-colorpost']);
		$newoptions['colorcomment'] = stripslashes($_POST['pocoti-colorcomment']);
		$newoptions['width'] = stripslashes($_POST['pocoti-width']);
		$newoptions['height'] = stripslashes($_POST['pocoti-height']);
		$newoptions['starthour'] = stripslashes($_POST['pocoti-starthour']);
	}
	if ( $options != $newoptions ) {
		$options = $newoptions;
		update_option('pocoti', $options);
	}
	pocoti_update_charts();

	$colors = array('red', 'blue', 'yellow', 'black', 'green', 'brown', 'orange', 'gold', 'gray', 'navy', 'silver');
?>


	<div style="width:75%;" class="wrap" id="pocoti_options_panel">
	<h2>Post/Comments Time</h2>

	<p><strong>Edit the colors and strings for the generated graph!</strong><br />For detailed information see the <a href="http://www.jovelstefan.de/posts-comments-time/" title="Plugin Page">plugin page</a>.</p>

	<div class="wrap">
		<form method="post">
			<table>
				<tr><td>Title for hour graph:</td><td><input type="text" value="<?php echo $options['titlestr']; ?>" name="pocoti-titlestr" id="pocoti-titlestr" /></td></tr>
				<tr><td>Title for day graph:</td><td><input type="text" value="<?php echo $options['daytitlestr']; ?>" name="pocoti-daytitlestr" id="pocoti-daytitlestr" /></td></tr>
				<tr><td>Label for posts axis:</td><td><input type="text" value="<?php echo $options['poststr']; ?>" name="pocoti-poststr" id="pocoti-poststr" /></td></tr>
				<tr><td>Label for comments axis:</td><td><input type="text" value="<?php echo $options['commentstr']; ?>" name="pocoti-commentstr" id="pocoti-commentstr" /></td></tr>
				<tr><td>Label for hour axis:</td><td><input type="text" value="<?php echo $options['hourstr']; ?>" name="pocoti-hourstr" id="pocoti-hourstr" /></td></tr>
				<tr><td>Label for day axis:</td><td><input type="text" value="<?php echo $options['daystr']; ?>" name="pocoti-daystr" id="pocoti-daystr" /></td></tr>
				<tr><td>Color for posts graph:</td><td><select name="pocoti-colorpost" id="pocoti-colorpost"><option><?php echo $options['colorpost']; ?></option>
					<?php for ($i=0; $i<count($colors); $i++)
						echo "<option>".$colors[$i]."</option>";
					?>
					</select></td></tr>
				<tr><td>Color for comments graph:</td><td><select name="pocoti-colorcomment" id="pocoti-colorcomment"><option><?php echo $options['colorcomment']; ?></option>
					<?php for ($i=0; $i<count($colors); $i++)
						echo "<option>".$colors[$i]."</option>";
					?>
					</select></td></tr>
				<tr><td>Image width:</td><td><input type="text" value="<?php echo $options['width']; ?>" name="pocoti-width" id="pocoti-width" size="5" maxlength="3" /></td></tr>
				<tr><td>Image height:</td><td><input type="text" value="<?php echo $options['height']; ?>" name="pocoti-height" id="pocoti-height" size="5" maxlength="3" /></td></tr>
				<tr><td>First hour of day:</td><td><select name="pocoti-starthour" id="pocoti-starthour"><option value="<?php echo $options['starthour']; ?>"><?php echo $options['starthour']; ?>h</option>
					<?php for ($i=0; $i<24; $i++)
						echo "<option value=".$i.">".$i."h</option>";
					?>
					</select></td></tr>
				<tr><td colspan="2"><input type="submit" name="pocoti-submit" id="pocoti-submit" value="Save settings &amp; generate graphs &raquo;" /></td></tr>
			</table>
		</form>
	</div>
	<p>Once they are generated, your graphs are available via the following URIs:<br />
	<a href="<?php echo get_option('siteurl') ?>/wp-content/plugins/pocoti/charthour.png" title="Pocoti"><i><?php echo get_option('siteurl') ?>/wp-content/plugins/pocoti/charthour.png</i></a><br />
	<a href="<?php echo get_option('siteurl') ?>/wp-content/plugins/pocoti/chartday.png" title="Pocoti"><i><?php echo get_option('siteurl') ?>/wp-content/plugins/pocoti/chartday.png</i></a>
	</p>
	You can put an image into any blog post or template file, that links to this URL!
	<p>
	<h3>PREVIEW</h3>
	<?php
	if ( (file_exists($dir."/chartday.png")) && (file_exists($dir."/charthour.png"))) {
	?>
	<img src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/pocoti/chartday.png?x=<?php echo time() ?>" title="Posts/comments chart"/>
	<br />
	<img src="<?php echo get_option('siteurl'); ?>/wp-content/plugins/pocoti/charthour.png?x=<?php echo time() ?>" title="Posts/comments chart"/>
	<?php } else { ?>
	No preview available!
	<?php } ?>
	</p>
	<h3>Credits</h3>
	<p>
	The graph is generated by the <a href="http://www.aditus.nu/jpgraph/" title="JpGraph Website">JpGraph Library</a>, which is for non-commercial use only!
	</p>

	<?php

}

function pocoti_add_options_panel() {
	add_options_page('Posts/Comments Time', 'Posts/Comments Time', 1, 'pocoti_options_page', 'pocoti_option_page');
}
add_action('admin_menu', 'pocoti_add_options_panel');
add_action('comment_post', 'pocoti_update_charts');
add_action('delete_comment', 'pocoti_update_charts');
add_action('delete_post', 'pocoti_update_charts');
add_action('publish_post', 'pocoti_update_charts');

?>