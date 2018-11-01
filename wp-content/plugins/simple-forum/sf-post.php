<?php
/*
Simple Forum 2.1
Forum Post Saves
*/

require(dirname(dirname(dirname(dirname(__FILE__)))).'/wp-config.php');

global $wpdb, $forumurl, $targettopic, $targetpost;

require_once('sf-includes.php');
require_once('sf-primitives.php');
require_once('sf-support.php');
require_once('sf-links.php');

$f=0;
$targettopic=0;
$targetpost=0;
if(isset($_POST['forumid'])) $f=$_POST['forumid'];
if(isset($_POST['topicid'])) $targettopic=$_POST['topicid'];

$forumurl=str_replace('&amp;', '&', sf_url($f, $targettopic, 0,0,0, $targetpost));

delete_sfnotice('sfmessage');

// check for new topic creation	
if (isset($_POST['newtopic'])) 
{
	$message = sf_save_topic();
}

// check for new post creation
if (isset($_POST['newpost']))
{
	$message = sf_save_post();
}

update_sfnotice('sfmessage', $message);

$forumurl=str_replace('&amp;', '&', sf_url($f, $targettopic, 0,0,0, $targetpost)).'#p'.$targetpost;

wp_redirect($forumurl);

// === DATABASE & SAVE TOPIC/POST SUPPORT ROUTINES =========================

function sf_save_topic()
{
	global $wpdb, $user_ID, $user_login, $user_identity, $targettopic, $forumurl, $targetpost;

	check_admin_referer('forum-userform_addtopic');

	// Spam Check
	$usemath=get_option('sfspam');
	if($usemath)
	{
		if((sf_admin_status()) && (get_option('sfadminspam') == false))
		{
			$usemath = false;
		}
	}
	if($usemath)
	{
		$spamtest=sf_spamcheck();
		if($spamtest[0] == true) return $spamtest[1];
	}

	$topicname = $_POST['newtopicname'];		
	$postcontent = $_POST['newtopicpost'];
	$guestname = '';
	$guestemail = '';
	$notifyname = '';
	$topiclock = 0;
	$topicpin = 0;
	
	if(empty($topicname)) 
	{
		return __('No Topic Name has been entered! Post can not be saved', "sforum");
	}	
	if(empty($postcontent)) 
	{
		return __('No Topic Message has been entered! Post can not be saved', "sforum");
	}

	//force maximum length
	$topicname - substr($topicname, 0, 100);
	
	$topicname = apply_filters('sf_save_topic_title', $topicname);
	$postcontent = apply_filters('sf_save_post_content', $postcontent);

	get_currentuserinfo();

	if('' == $user_ID)
	{
		// if not registered user check the forum allows guest posting (just in case it slipped through)
		if(get_option('sfallowguests') == false)
		{
			return __("Unable to Save New Topic Record", "sforum");
		}

		$guestname = apply_filters('sf_save_post_name', $_POST['guestname']);
		$guestemail = apply_filters('sf_save_post_email', $_POST['guestemail']);
		if(empty($guestname))
		{
			return __('A Guest Name is Required', "sforum");
		}
		if((empty($guestemail)) || (!is_email($guestemail)))
		{
			return __('A Valid  Email Address is Required', "sforum");
		}
		
		// force maximum lengths
		$guestname = substr($guestname, 0, 20);
		$guestemail = substr($guestemail, 0, 50);
		
		$notifyname = $guestname;
	} else {
// 2.1 Patch 2
		$notifyname = stripslashes($user_identity);
		if($user_ID == ADMINID) $notifyname = ADMINNAME;
	}

	if(isset($_POST['topiclock'])) $topiclock=1;
	if(isset($_POST['topicpin'])) $topicpin=1;

	
	// OK - all there so we can save it - first the topic
	if((sf_write_topic($topicname, $_POST['forumid'], $user_ID, $topiclock, $topicpin)) == false)
	{
		return __("Unable to Save New Topic Record", "sforum");
	}	

	$topicid = $wpdb->insert_id;
	$targettopic = $topicid;
	
	// Now the post
	if((sf_write_post($postcontent, $topicid, $_POST['forumid'], $guestname, $guestemail, $user_ID)) == false)
	{
		return __("Unable to Save New Post Message", "sforum");
	}

	// set the return url
	$forumurl=str_replace('&amp;', '&', sf_url($_POST['forumid'], $targettopic, 0, 0, 0, $targetpost)).'#p'.$targetpost;
	
	$wpdb->flush();

	$note='';
	$out = sf_email_notifications($user_ID, $_POST['forumid'], $topicid, $notifyname, $targetpost);
	If($out != '') 
	{
		$note = '('.$out.')';
	}

	sf_add_to_waiting($topicid, $_POST['forumid'], $user_ID);

//== CREATE A NEW BLOG POST IF LINKED ======

	if($_POST['bloglink'] == 'on')
	{
		$catlist = array();
		foreach ($_POST['post_category'] as $key=>$value)
		{
			$catlist[] = $value;
		}
	
		// post
		$post_content = $postcontent;
		$post_title   = $topicname;
		$post_status  = 'publish';
		
		$post = compact('post_content', 'post_title', 'post_status');
		
		$post_id = wp_insert_post($post);
		
		// categories
		if(version_compare($wp_version, '2.1', '<'))
		{
			wp_set_post_cats(0, $post_id, $catlist);
		} else {
			wp_set_post_categories($post_id, $catlist);
		}
		
		// postmeta
		$metadata = $_POST['forumid'].'@'.$topicid;
		sf_blog_links_postmeta('save', $post_id, $metadata);
		
		// go back and insert blog_post_id in topic record
		$wpdb->query("UPDATE ".SFTOPICS." SET blog_post_id = ".$post_id." WHERE topic_id = ".$topicid.";");
	}

	return __("New Topic Saved ", "sforum").$note;
}

function sf_save_post()
{
	global $wpdb, $user_ID, $user_login, $user_identity, $forumurl, $targetpost;

	check_admin_referer('forum-userform_addpost');

	// Spam Check
	$usemath=get_option('sfspam');
	if($usemath)
	{
		if((sf_admin_status()) && (get_option('sfadminspam') == false))
		{
			$usemath = false;
		}
	}
	if($usemath)
	{
		$spamtest=sf_spamcheck();
		if($spamtest[0] == true) return $spamtest[1];
	}
	
	$postcontent = $_POST['newtopicpost'];
	$guestname = null;
	$guestemail = null;
	$notifyname = '';
	$postpin = 0;

	if(empty($postcontent)) 
	{
		return __('No Topic Message has been entered! Post can not be saved', "sforum");
	}

	$postcontent = apply_filters('sf_save_post_content', $postcontent);
	
	get_currentuserinfo();

	if('' == $user_ID)
	{
		// if not registered user check the forum allows guest posting (just in case it slipped through)
		if(get_option('sfallowguests') == false)
		{
			return __("Unable to Save New Post Message", "sforum");
		}

		$guestname = apply_filters('sf_save_post_name', $_POST['guestname']);
		$guestemail = apply_filters('sf_save_post_email', $_POST['guestemail']);
		if(empty($guestname))
		{
			return __('A Guest Name is Required', "sforum");
		}
		if((empty($guestemail)) || (!is_email($guestemail)))
		{
			return __('A Valid Email Address is Required', "sforum");
		}

		// force maximum lengths
		$guestname = substr($guestname, 0, 20);
		$guestemail = substr($guestemail, 0, 50);

		$notifyname = $guestname;
	} else {
// 2.1 Patch 2
		$notifyname = stripslashes($user_identity);
		if($user_ID == ADMINID) $notifyname = ADMINNAME;
	}
	
	if(isset($_POST['postpin'])) $postpin=1;
	
	// Write the post
	if((sf_write_post($postcontent, $_POST['topicid'], $_POST['forumid'], $guestname, $guestemail, $user_ID, $postpin)) == false)
	{
		return __("Unable to Save New Post Message", "sforum");
	}

	// set the return url
	$forumurl=str_replace('&amp;', '&', sf_url($_POST['forumid'], $_POST['topicid'], 0, 0, 0, $targetpost)).'#p'.$targetpost;

	$wpdb->flush();


	$note='';
	$out = sf_email_notifications($user_ID, $_POST['forumid'], $_POST['topicid'], $notifyname, $targetpost);
	If($out != '') 
	{
		$note = '('.$out.')';
	}
	
	sf_add_to_waiting($_POST['topicid'], $_POST['forumid'], $user_ID);

	return __("New Post Saved ", "sforum").$note;
}

// == DB RECORD SAVES ==================

function sf_write_topic($topicname, $forumid, $userid, $topiclock=0, $topicpin=0)
{
	global $wpdb;

	$topicname = $wpdb->escape($topicname);
	
	$sql =  "INSERT INTO ".SFTOPICS;
	$sql .= " (topic_name, topic_date, forum_id, topic_status, topic_pinned, user_id) ";
	$sql .= "VALUES (";
	$sql .= "'".$topicname."', ";
	$sql .= "now(), ";
	$sql .= $forumid.", ";
	$sql .= $topiclock.", ";
	$sql .= $topicpin.", ";
	if('' == $userid)
	{
		$sql .= "NULL);";
	} else {
		$sql .= $userid.");";
	}

	if($wpdb->query($sql) === false)
	{
		return false;
	} else {
		return true;
	}
}

function sf_write_post($postcontent, $topicid, $forumid, $guestname, $guestemail, $userid, $postpin=0)
{
	global $wpdb, $targetpost;

	$poststatus = 0;

	// If a Guest posting...
	if((!empty($guestname)) && (get_option('sfmoderate'))) 
	{
		$poststatus = 1;
		// unless mod once is on and they have posted bfore...
		if(get_option('sfmodonce'))
		{
			$prior=$wpdb->get_row("SELECT post_id FROM ".SFPOSTS." WHERE guest_name='".$guestname."' AND guest_email='".$guestemail."' AND post_status=0 LIMIT 1");
			if($prior) $poststatus=0;
		}
	}

	// If a Member posting...
	if((get_option('sfmodmembers')) && (($userid != ADMINID) || (sf_is_moderator($userid))))
	{
		$poststatus = 1;
		// unless mod once is on and they have posted bfore...
		if(get_option('sfmodonce'))
		{
			$prior=$wpdb->get_row("SELECT post_id FROM ".SFPOSTS." WHERE user_id=".$userid." AND post_status=0 LIMIT 1");
			if($prior) $poststatus=0;
		}
	}

	$postcontent = $wpdb->escape($postcontent);
	$guestname = $wpdb->escape($guestname);
	$guestemail = $wpdb->escape($guestemail);

	$sql =  "INSERT INTO ".SFPOSTS;
	$sql .= " (post_content, post_date, topic_id, forum_id, user_id, guest_name, guest_email, post_pinned, post_status) ";
	$sql .= "VALUES (";
	$sql .= "'".$postcontent."', ";
	$sql .= "now(), ";
	$sql .= $topicid.", ";
	$sql .= $forumid.", ";
	if('' == $userid)
	{
		$sql .= "NULL, ";
	} else {
		$sql .= $userid.", ";
	}
	$sql .= "'".$guestname."', ";
	$sql .= "'".$guestemail."', ";
	$sql .= $postpin. ", ";
	$sql .= $poststatus.");";
	
	if($wpdb->query($sql) === false)
	{
		return false;
	} else {

		$targetpost = $wpdb->insert_id;

		if('' == $userid)
		{
			sf_write_cookie($guestname, $guestemail);
		} else {
			$postcount = (get_user_option('sfposts', $userid)+1);
			update_user_option($userid, 'sfposts', $postcount);
		}
	}
	// update topic date
	$wpdb->query("UPDATE ".SFTOPICS." SET topic_date=now() WHERE topic_id=".$topicid);
	return true;
}

function sf_email_notifications($userid, $forumid, $topicid, $poster, $targetpost)
{
	global $wpdb, $forumurl;

	$groupname = sf_get_forum_group_from_forum($forumid);
	$forumname = sf_get_forum_name($forumid);
	$topicname = sf_get_topic_name($topicid);
	$out = '';

	// notify admin?
	if((get_option('sfnotify')) && ($userid != ADMINID))
	{
		$eol = "\n";
		$post_content = $wpdb->get_var("SELECT post_content FROM ".SFPOSTS." WHERE post_id=".$targetpost);
		$emessage .= "\r\n" . sprintf(__('Text:  %s', "sforum"), "\r\n" . $post_content) . "\r\n\r\n";
		
		//optional html message
		$htmlmsg = '<p>';
		$htmlmsg .= sprintf(__('New forum post on your site %s:', "sforum"), get_option('blogname')) . "<br /><br />\n";
		$htmlmsg .= sprintf(__('From:  %s', "sforum"), '<strong>'.$poster.'</strong>') . "<br />\n";
		$htmlmsg .= sprintf(__('Group: %s', "sforum"), '<strong>'.$groupname.'</strong>') . "<br />\n";          
		$htmlmsg .= sprintf(__('Forum: %s', "sforum"), '<strong>'.$forumname.'</strong>') . "<br />\n";
		$htmlmsg .= sprintf(__('Topic: <a href="%s">%s</a>', "sforum"), $forumurl, $topicname ) . "<br />\n";
		$htmlmsg .= '</p><p>';
		$htmlmsg .= sprintf(__('Post:', "sforum"), $topicname, $forumurl) . "<br />{$post_content}\n";
		$htmlmsg .= '</p>';
		
		//email header
		$header  = "From: ". get_settings('blogname') . " <" . get_settings('admin_email') . ">" . $eol;
		$header .= "Content-Type: multipart/alternative; boundary=\"----MIME_BOUNDRY_main_message\"";
		$body = "This is a multi-part message in MIME format."  . $eol;
		$body .= "------MIME_BOUNDRY_main_message"  . $eol;
		$body .= "Content-Type: text/plain; charset=\"" . get_option('blog_charset') . "\"" . $eol;
		$body .= "Content-Transfer-Encoding: quoted-printable"  . $eol . $eol;
		
		$body .= str_replace('=','=3D', strip_tags(stripslashes($emessage),'<a>') ) . $eol;
		$body .= "------MIME_BOUNDRY_main_message"  . $eol;
		$body .= "Content-Type: text/html; charset=\"" . get_option('blog_charset') . "\"". $eol;
		$body .= "Content-Transfer-Encoding: quoted-printable"  . $eol . $eol;;
		$body .= "<!DOCTYPE HTML PUBLIC \"-//W3C//DTD HTML 4.0 Transitional//EN\">"  . $eol;
		$body .= "<HTML>" . $eol;
		$body .= "<HEAD><style></style></HEAD>" . $eol;
		$body .= "<BODY>" . $eol;
		$body .= str_replace('=', '=3D', stripslashes($htmlmsg) ). $eol;              
		$body .= "</BODY>{$eol}</HTML>"  . $eol . $eol;
		
		@mail(get_option('admin_email'), sprintf(__('[%s] New Forum Post', "sforum"), get_option('blogname')), $body, $header);
				
		$out .= __(' Notified: Administrator', "sforum"); 
	}

	// any subscribers?
	if(get_option('sfsubscriptions'))
	{
		// get list of users on this topic (not including current one)
		$users=$wpdb->get_var("SELECT topic_subs FROM ".SFTOPICS." WHERE topic_id=".$topicid);
		if($users)
		{
			$sent = false;
			$users=explode('@', $users);
			foreach($users as $user)
			{
				if($user != $userid)
				{
					// get user email address
					$email = $wpdb->get_var("SELECT user_email FROM ".SFUSERS." WHERE ID=".$user);
					
					$emessage  = sprintf(__('New post on a forum topic you are subscribed to at %s:', "sforum"), get_option('blogname')) . "\r\n\r\n";
					$emessage .= sprintf(__('From:  %s', "sforum"), $poster) . "\r\n\r\n";
					$emessage .= sprintf(__('Group: %s', "sforum"), $groupname) . "\r\n\r\n";		
					$emessage .= sprintf(__('Forum: %s', "sforum"), $forumname) . "\r\n\r\n";
					$emessage .= sprintf(__('Topic: %s', "sforum"), $topicname) . "\r\n\r\n";			
					$emessage .= sprintf(__('Link:  %s', "sforum"), $forumurl) . "\r\n";

					@wp_mail($email, sprintf(__('[%s] New Forum Post', "sforum"), get_option('blogname')), $emessage, '');
					$sent = true;
				}
			}
		}
		if($sent)
		{
			$out .=__(' Notified: Subscribers', "sforum");
		}
	}
	return $out;
}

function sf_write_cookie($guestname, $guestemail)
{
	$cookiepath = preg_replace('|https?://[^/]+|i', '', get_option('home') . '/' );
	
	setcookie('guestname_' . COOKIEHASH, $guestname, time() + 30000000, $cookiepath, false);
	setcookie('guestemail_' . COOKIEHASH, $guestemail, time() + 30000000, $cookiepath, false);
	setcookie('sflast_' . COOKIEHASH, time(), time() + 30000000, $cookiepath, false);

	return;
}

function sf_add_to_waiting($topicid, $forumid, $userid)
{
	global $wpdb, $targetpost;
	
	if($userid == ADMINID) return;
	if((sf_is_moderator($userid)) && (get_option('sfshowmodposts') == false)) return;

	if($userid == '') $userid=0;
	
	//first is this topic already in waiting?
	$result = $wpdb->get_row("SELECT * FROM ".SFWAITING." WHERE topic_id = ".$topicid);
	if($result)
	{
		// add one to post count
		$pcount = ($result->post_count + 1);
		$sql = 'UPDATE '.SFWAITING.' SET ';
		$sql.= 'post_count='.$pcount." ".', user_id='.$userid.' ';
		$sql.= 'WHERE topic_id='.$topicid.';';
		$wpdb->query($sql);
	} else {
		$pcount = 1;
		$sql =  "INSERT INTO ".SFWAITING." ";
		$sql .= "(topic_id, forum_id, post_id, user_id, post_count) ";
		$sql .= "VALUES (";
		$sql .= $topicid.", ";
		$sql .= $forumid.", ";
		$sql .= $targetpost.", ";
		$sql .= $userid.", ";
		$sql .= $pcount.");";			
		$wpdb->query($sql);
	}
	return;
}


?>