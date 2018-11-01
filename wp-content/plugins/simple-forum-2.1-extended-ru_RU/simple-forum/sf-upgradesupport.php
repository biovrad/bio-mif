<?php
/*
Simple Forum 2.1
Install & Upgrade Support Routines
*/

//== UPGRADE DB FUNCTION ===================================

function sf_upgrade_database($table_name, $column_name, $create_ddl) 
{
	global $wpdb;
	foreach ($wpdb->get_col("DESC $table_name", 0) as $column )
	{
		if ($column == $column_name)
		{
			return true;
		}
    }
	//didn't find it try to create it.
    $q = $wpdb->query($create_ddl);
	// we cannot directly tell that whether this succeeded!
	foreach ($wpdb->get_col("DESC $table_name", 0) as $column )
	{
		if ($column == $column_name)
		{
			return true;
		}
	}
	die(sprintf(__("DATABASE ERROR: Unable to ALTER the %s to create new column %s", "sforum"), $table_name, $column));
}

//== Called by 1.6 to clear up previous deletion orphans
function sf_check_data_integrity()
{
	global $wpdb;
	
	$topiclist = array();
	$postlist = array();
	
	// to be run against a 1.5 install to clean up orphaned posts
	// Step 1: Loop through topics in case forum is gone and remove
	$topics = $wpdb->get_results("SELECT topic_id, forum_id FROM ".SFTOPICS);
	if($topics)
	{
		foreach($topics as $topic)
		{
			$test=$wpdb->get_col("SELECT forum_id FROM ".SFFORUMS." WHERE forum_id=".$topic->forum_id);
			if(!$test)
			{
				$topiclist[]=$topic->topic_id;
			}
		}
		if($topiclist)
		{
			foreach($topiclist as $topic)
			{
				$wpdb->query("DELETE FROM ".SFTOPICS." WHERE topic_id=".$topic);
			}
		}
	}
	
	// Step 2: Loop through posts in case topic is gone and remove
	$posts = $wpdb->get_results("SELECT post_id, topic_id FROM ".SFPOSTS);
	if($posts)
	{
		foreach($posts as $post)
		{
			$test=$wpdb->get_col("SELECT topic_id FROM ".SFTOPICS." WHERE topic_id=".$post->topic_id);
			if(!$test)
			{
				$postlist[]=$post->post_id;
			}
		}
		if($postlist)
		{
			foreach($postlist as $post)
			{
				$wpdb->query("DELETE FROM ".SFPOSTS." WHERE post_id=".$post);
			}
		}
	}
	return;
}

//== Called by 1.7 to re-route subscriptions from usermeta to topics
function sf_rebuild_subscriptions()
{
	global $wpdb;
	
	//Build a list of users with subscribe set
	$users = $wpdb->get_col("SELECT user_id FROM ".SFUSERMETA." WHERE meta_key='".$wpdb->prefix."sfsubscribe'");
	if($users)
	{
		// clear out the old sfsubcribe values ready for the new
		$wpdb->query("DELETE FROM ".SFUSERMETA." WHERE meta_key='".$wpdb->prefix."sfsubscribe'");

		foreach($users as $user)
		{	
			// now build the list of topics into which each user has posted
			$topics = $wpdb->get_col("SELECT DISTINCT topic_id FROM ".SFPOSTS." WHERE user_id=".$user);
			if($topics)
			{
				foreach($topics as $topic)
				{
					sf_save_subscription($topic, $user, false);
				}
			}
		}
	}
	return;
}

//== Called by 2.0 to clean up the topic subs lists where duplicates have crept in
function sf_clean_topic_subs()
{
	global $wpdb;
	
	// build list of topics with subscriptions
	$topics = $wpdb->get_results("SELECT topic_id, topic_subs FROM ".SFTOPICS." WHERE topic_subs IS NOT NULL;");
	if(!$topics) return;
	
	foreach($topics as $topic)
	{
		$nvalues = array();
		$cvalues = explode('@', $topic->topic_subs);
		$nvalues[0] = $cvalues[0];
		foreach($cvalues as $cvalue)
		{
			$notfound = true;
			foreach($nvalues as $nvalue)
			{
				if($nvalue == $cvalue) $notfound = false;
			}
			if($notfound) $nvalues[]=$cvalue;
		}
		$nvaluelist = implode('@', $nvalues);
		$wpdb->query("UPDATE ".SFTOPICS." SET topic_subs='".$nvaluelist."' WHERE topic_id=".$topic->topic_id);
	}
	return;
}

// Called by 2.0 to relocate avatars
function sf_relocate_avatars()
{
	$success = true;
	$newpath = ABSPATH.'wp-content/forum-avatars';
	$oldpath = ABSPATH.'wp-content/plugins/simple-forum/avatars';

	if(!is_dir($newpath))
	{
		@mkdir($newpath, 0777);
	} else {
		@chmod($newpath, 0777);
	}
	if(is_dir($newpath))
	{
		$avlist = opendir($oldpath);
		while (false !== ($file = readdir($avlist))) 
		{
			if ($file != "." && $file != "..") 
			{
				if(rename($oldpath.'/'.$file, $newpath.'/'.$file) == false)
				{
					$success=false;
					echo (FAILED);
					break;
				}
			}
		}
		closedir($avlist);
		@rmdir($oldpath);
	}
	return;
}

// Called by 2.1 to correct old timestamp in usermeta (sflast)
function sf_correct_sflast()
{
	global $wpdb;
// 2.2 fix
	$sql = "UPDATE ".SFUSERMETA." SET meta_value=now() WHERE meta_key = ".$wpdb->prefix."sflast' AND meta_value < DATE_SUB(CURDATE(), INTERVAL 1 YEAR);";
	$wpdb->query($sql);
	return;
}

// Called by 2.1 Patch 2 to pre-create last visited date for all existing users who don't have one - Corrects the zero problem
function sf_precreate_sflast()
{
	global $wpdb;
	
	$users = $wpdb->get_results("SELECT ID FROM ".SFUSERS);
	if($users)
	{
		foreach($users as $user)
		{
			$check = $wpdb->get_var("SELECT umeta_id FROM ".SFUSERMETA." WHERE meta_key='".$wpdb->prefix."sflast' AND user_id=".$user->ID);
			if(!$check)
			{
				sf_set_first_visit($user->ID);
			}
		}
	}
	return;
}

?>