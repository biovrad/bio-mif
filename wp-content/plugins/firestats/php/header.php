<?php
require_once(dirname(__FILE__).'/init.php');
require_once(dirname(__FILE__).'/version-check.php');
require_once(dirname(__FILE__).'/html-utils.php');
require_once(dirname(__FILE__).'/layout.php');
require_once(dirname(__FILE__).'/auth.php');
?>
<h1>
<?php
global $fs_hide_support_button;
if (!isset($fs_hide_support_button))
{
	?>
		<span class='normal_font' style='float:<?php H_END()?>;margin:10px;'>
		<button class="button" onclick="FS.openDonationWindow()"><?php fs_e('Support FireStats')?></button><br/>
		<?php
		$user = fs_get_current_user();
		if (isset($user->logged_in) && $user->logged_in)
		{
		?>
		<button class="button" onclick="sendRequest('action=logout')">
			<?php echo sprintf(fs_r('Log out %s'),"<b>$user->username</b>")?>
		</button>
		<?php 
		}?>
		</span>
	<?php
}
?>
<?php
$home = FS_HOMEPAGE;
echo "<a style='border-bottom: 0px' href='$home'><img alt='".fs_r('FireStats')."' src='".fs_url("img/firestats-header.png")."'></img></a>";
echo '<span class="normal_font" style="padding-left:10px">';
echo sprintf("%s %s\n",FS_VERSION,(fs_is_demo() ? fs_r('Demo') : ''))."<br/>";
echo '<span class="hidden" id="new_firestats_version_notification"></span>';
echo '<span class="hidden" id="new_ip2c_db_notification"></span></span>';
?>
</h1>


<div id="feedback_div">
<button class="button" onclick="hideFeedback();"><?php fs_e('Hide');?></button>
<span id="feedback_zone"></span>
</div><!-- feedback_div -->

<div id="network_status"></div>
