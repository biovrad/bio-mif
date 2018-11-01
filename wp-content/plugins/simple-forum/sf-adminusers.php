<?php
/*
Simple Forum 2.1
Admin Scrub Spam Users
*/

define('SFADMINPATH', 'edit.php?page=/simple-forum/sf-admin.php');
define('SFUSERPATH', 'edit.php?page=/simple-forum/sf-adminusers.php');

global $wpdb;

if(isset($_POST['scrubusers']))
{
	check_admin_referer('forum-adminform_scrubusers');

	$numspam = 0;
	
	//first out select users registered more than X days ago
	$registrations = $wpdb->get_results("SELECT ID, user_login, user_registered FROM ".$wpdb->prefix."users WHERE user_registered < DATE_SUB(CURDATE(), INTERVAL 5 DAY);");

	if($registrations)
	{
		//second select all users who have never posted to the forum	
		$badusers = $wpdb->get_results("SELECT ID, user_login, user_id FROM ".$wpdb->prefix."users LEFT JOIN ".$wpdb->prefix."sfposts ON ".$wpdb->prefix."users.ID = ".$wpdb->prefix."sfposts.user_id WHERE ".$wpdb->prefix."sfposts.user_id IS NULL;");
	
		if($badusers)
		{
			$candelete = false;
			$found = false;
			
			foreach($badusers as $baduser)
			{
				// OK so they have never posted but are they in the old registrations list?
				foreach($registrations as $registration)
				{
					if($baduser->ID == $registration->ID)
					{
						$found = true;
						$candelete = true;
					}
				}
				
				//if they were then have they ever authored a post?
				if($found)
				{
					$found = $wpdb->get_results("SELECT ID FROM ".$wpdb->prefix."posts WHERE post_author = ".$baduser->ID);
					if($found)
					{
						$candelete = false;
					} else {
						//if no - what about left a comment?
						$found = $wpdb->get_results("SELECT comment_id FROM ".$wpdb->prefix."comments WHERE user_id = ".$baduser->ID);
						if($found)
						{
							$candelete = false;
						}
					}
				}
				
				// so? can we delete them?	
				if($candelete)
				{
					$wpdb->query("DELETE FROM ".$wpdb->prefix."users WHERE ID=".$baduser->ID);
					$wpdb->query("DELETE FROM ".SFUSERMETA." WHERE user_id=".$baduser->ID);
					$numspam++;
				}
			}
		}
	}
	?>
		<div class="wrap">

		<h2><?php _e("Remove Spam User Registrations", "sforum") ?></h2>

	<?php
		echo('<h4>'.$numspam.__(" registered users were removed", "sforum").'</h4><br />');
	?>
		<a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums"><?php _e("Return to Forum Management", "sforum") ?></a>
	</div>

	<?php
} else {
	?>
	
	<div class="wrap">
	<h2><?php _e("Remove Spam User Registrations", "sforum") ?></h2>
	<p><?php _e("This option should be used with great care! It will remove ALL user registrations that", "sforum") ?>:</p>
	<ul>
		<li><?php _e("are now over 7 days old", "sforum") ?></li>
		<li><?php _e("where the user has never posted to the forum", "sforum") ?></li>
		<li><?php _e("where the user has never authored a post", "sforum") ?></li>
		<li><?php _e("where the user has never left a comment", "sforum") ?></li>
	</ul>
	<p><?php _e("Use at your own risk!", "sforum") ?></p>
	<br />
	
	<form action="<?php echo(SFUSERPATH); ?>" method="post" name="sfscrubusers">

		<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_scrubusers'); ?>			
		<input type="submit" class="sfcontrol" name="scrubusers" value="<?php _e("Remove Spam Users", "sforum") ?>">

	</form><br />
	<a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums"><?php _e("Return to Forum Management", "sforum") ?></a>
	</div>
	<?php
}

?>