<?php
/*
Simple Forum 2.1
Form Rendering
*/

function sf_add_topic($forumid, $forumname, $user_id)
{
	$out.='<br /><br />'."\n";
	$out.='<div id="sfentryform">'."\n";
	$out.='<fieldset>'."\n";
	$out.='<legend>'.sprintf(__("Add New Topic to: <strong>%s</strong>", "sforum"), stripslashes($forumname)).'</legend>'."\n";

	$out.= '<form action="'.get_settings("siteurl").'/wp-content/plugins/simple-forum/sf-post.php" method="post" name="addtopic">'."\n";

	if(function_exists('wp_nonce_field'))
	{
		$out.= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce('forum-userform_addtopic') . '" />'."\n";
		$ref = sf_attribute_escape($_SERVER['REQUEST_URI']);
		$out.= '<input type="hidden" name="_wp_http_referer" value="'. $ref . '" />'."\n";

		if ( wp_get_original_referer() ) {
			$original_ref = sf_attribute_escape(stripslashes(wp_get_original_referer()));
			$out.= '<input type="hidden" name="_wp_original_http_referer" value="'. $original_ref . '" />'."\n";
		}
	}

	$out.='<input type="hidden" name="forumid" value="'.$forumid.'" />'."\n";

	if(empty($user_id))
	{
		if(get_option(sfmoderate))
		{
			$out.='<p><strong>'.__("NOTE: New Posts are subject to administrator approval before being displayed", "sforum").'</strong></p>'."\n";
		}
		$guest=sf_get_cookie();

		$out.='<p>'.__("Guest Name (Required)", "sforum").':</p>'."\n";
		$out.='<input type="text" tabindex="1" class="sfcontrol sftextcontrol" size="45" name="guestname" value="'.stripslashes($guest["name"]).'" />'."\n";

		$out.='<p>'.__("Guest EMail (Required)", "sforum").':</p>'."\n";
		$out.='<input type="text"  tabindex="2" class="sfcontrol sftextcontrol" size="45" name="guestemail" value="'.stripslashes($guest["email"]).'" />'."\n";
	}

	$out.='<p>'.__("Topic Name", "sforum").':</p>'."\n";
	$out.='<input type="text"  tabindex="3" class="sfcontrol sftextcontrol" size="60" name="newtopicname" value="" />'."\n";

	// Start Spam Measures
	$usemath=get_option('sfspam');
	if($usemath)
	{
		if((sf_admin_status()) && (get_option('sfadminspam') == false))
		{
			$usemath = false;
		}
	}

	$enabled=' ';
	if($usemath)
	{
		$enabled = ' disabled="disabled" ';
		$out.='<div id="sfhide">'."\n";
		$out.='<p>Guest URL (required)<br />'."\n";
		$out.='<input type="text" class="yscontrol" size="30" name="url" value="" /></p>'."\n";
		$out.='</div>'."\n";

		$spammath = sf_math_spam_build();

		$out.='<p><strong>'.__("Math Required!", "sforum").'</strong><br />'."\n";
		$out.=sprintf(__("What is the sum of: <strong> %s + %s </strong>", "sforum"), $spammath[0], $spammath[1]).'&nbsp;&nbsp;&nbsp;'."\n";
		$out.='<input type="text" tabindex="4" class="sfcontrol" size="7" name="sfvalue1" value="" onchange="setTopicButton()" />&nbsp;&nbsp;&nbsp;<em>'.__("(Required)", "sforum").'</em></p>'."\n";
		$out.='<input type="hidden" name="sfvalue2" value="'.$spammath[2].'" />'."\n";
	}
	// End Spam Measures

	$out.='<p>'.__("Topic Message", "sforum").':</p>'."\n";

	$out.='<div class="sfcontainer">'."\n";

	if((get_option('sfquicktags')) || (sf_is_safari()))
	{
		$out.='<div class="quicktags">'."\n";
		$out.='<script type="text/javascript">edToolbar();</script><textarea  tabindex="5" class="qttext" name="newtopicpost" id="newtopicpost" rows="12"></textarea><script type="text/javascript">var edCanvas = document.getElementById("newtopicpost");</script>'."\n";
		$out.='</div>'."\n";
	} else {
		$out.="\n";
		$out.='<textarea  tabindex="5" class="qttext" name="newtopicpost" id="newtopicpost" cols="60" rows="12"></textarea>'."\n";
	}

	$out.='</div>'."\n";

	$out.='<p>';
	if($user_id == ADMINID)
	{
		$out.='<input type="checkbox" class="sfcontrol" name="topiclock" id="topiclock" tabindex="6" /><img class="" src="'.SFRESOURCES.'locked.png" alt="" />&nbsp;&nbsp;'.__("Lock this Topic", "sforum")."\n";
		$out.='<input type="checkbox" class="sfcontrol" name="topicpin" id="topicpin" tabindex="7"  /><img class="" src="'.SFRESOURCES.'pin.png" alt="" />&nbsp;&nbsp;'.__("Pin this Topic", "sforum")."\n";
	}

	if((get_option('sflinkuse')) && (current_user_can('publish_posts')))
	{
		$gif= SFJSCRIPT.'working.gif';
		$site=get_option('siteurl')."/wp-content/plugins/simple-forum/ahah/sf-categories.php";
		$out.='<input type="checkbox" class="sfcontrol" name="bloglink" id="bloglink" tabindex="8" onchange="getCategories(\''.$gif.'\', \''.$site.'\')" /><img class="" src="'.SFRESOURCES.'bloglink.png" alt="" />&nbsp;&nbsp;'.__("Create Linked Post", "sforum")."\n";
		$out.='<div id="sfcats"></div>';
	}

	$out.= '</p>';
	$out.='<br /><br />'."\n";

	$out.='<input type="submit"'.$enabled.'tabindex="9" class="sfcontrol" name="newtopic" value="'.__("Save New Topic", "sforum").'" />'."\n";
	$out.='&nbsp;<input type="button" tabindex="10" class="sfcontrol" name="cancel" value="'.__("Cancel", "sforum").'" onclick="toggleLayer(\'sfentryform\');" />'."\n";

	if((get_option('sfsubscriptions')) && (!empty($user_id)))
	{
		$out.='<br /><p>'.__("Email Subscriptions on this forum are open.<br />To be notified of responses, click on Subscribe", "sforum").'</p>'."\n";
	}

	$out.='</form>'."\n";
	$out.='</fieldset>'."\n";
	$out.='</div>'."\n";

	return $out;
}

function sf_add_post($forumid, $topicid, $topicname, $user_id)
{
	//update_option('sfppage', $_GET['page']);

	$out.='<br /><br />'."\n";
	$out.='<div id="sfentryform">'."\n";
	$out.='<fieldset>'."\n";
	$out.='<legend>'.sprintf(__("Reply to Topic: <strong> %s </strong>", "sforum"), stripslashes($topicname)).'</legend>'."\n";

	$out.= '<form action="'.get_settings("siteurl").'/wp-content/plugins/simple-forum/sf-post.php" method="post" name="addpost">'."\n";

	if(function_exists('wp_nonce_field'))
	{
		$out.= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce('forum-userform_addpost') . '" />'."\n";
		$ref = sf_attribute_escape($_SERVER['REQUEST_URI']);
		$out.= '<input type="hidden" name="_wp_http_referer" value="'. $ref . '" />'."\n";

		if ( wp_get_original_referer() ) {
			$original_ref = sf_attribute_escape(stripslashes(wp_get_original_referer()));
			$out.= '<input type="hidden" name="_wp_original_http_referer" value="'. $original_ref . '" />'."\n";
		}
	}

	$out.='<input type="hidden" name="forumid" value="'.$forumid.'" />'."\n";
	$out.='<input type="hidden" name="topicid" value="'.$topicid.'" />'."\n";

	if(empty($user_id))
	{
		if(get_option(sfmoderate))
		{
			$out.='<p><strong>'.__("NOTE: New Posts are subject to administrator approval before being displayed", "sforum").'</strong></p>'."\n";
		}
		$guest=sf_get_cookie();
		$out.='<p>'.__("Guest Name (Required)", "sforum").':</p>'."\n";
		$out.='<input type="text" tabindex="1" class="sfcontrol sftextcontrol" size="45" name="guestname" value="'.stripslashes($guest["name"]).'" />'."\n";

		$out.='<p>'.__("Guest EMail (Required)", "sforum").':</p>'."\n";
		$out.='<input type="text" tabindex="2" class="sfcontrol sftextcontrol" size="45" name="guestemail" value="'.stripslashes($guest["email"]).'" />'."\n";
	}

	// Start Spam Measures
	$usemath=get_option('sfspam');
	if($usemath)
	{
		if((sf_admin_status()) && (get_option('sfadminspam') == false))
		{
			$usemath = false;
		}
	}

	$enabled=' ';
	if($usemath)
	{
		$enabled = ' disabled="disabled" ';
		$out.='<div id="sfhide">'."\n";
		$out.='<p>Guest URL (required)<br />'."\n";
		$out.='<input type="text" class="yscontrol" size="30" name="url" value="" /></p>'."\n";
		$out.='</div>'."\n";

		$spammath = sf_math_spam_build();

		$out.='<p><strong>'.__("Math Required!", "sforum").'</strong><br />'."\n";
		$out.=sprintf(__("What is the sum of: <strong> %s + %s </strong>", "sforum"), $spammath[0], $spammath[1]).'&nbsp;&nbsp;&nbsp;'."\n";
		$out.='<input type="text" tabindex="3" class="sfcontrol" size="7" name="sfvalue1" id="sfvalue1" value="" onchange="setPostButton()" />&nbsp;&nbsp;&nbsp;<em>'.__("(Required)", "sforum").'</em></p>'."\n";
		$out.='<input type="hidden" name="sfvalue2" id ="sfvalue2" value="'.$spammath[2].'" />'."\n";
	}
	// End Spam Measures

	$out.='<p>'.__("Topic Reply", "sforum").':</p>'."\n";

	$out.='<div class="sfcontainer">'."\n";

	if((get_option('sfquicktags')) || (sf_is_safari()))
	{
		$out.='<div class="quicktags">'."\n";
		$out.='<script type="text/javascript">edToolbar();</script><textarea tabindex="4" class="qttext" name="newtopicpost" id="newtopicpost" rows="12"></textarea><script type="text/javascript">var edCanvas = document.getElementById("newtopicpost");</script>'."\n";
		$out.='</div>'."\n";
	} else {
		$out.="\n";
		$out.='<textarea  tabindex="4" class="qttext" name="newtopicpost" id="newtopicpost" cols="60" rows="12"></textarea>'."\n";
	}

	$out.='</div>'."\n";

	if($user_id == ADMINID)
	{
		$out.='<p><input type="checkbox" class="sfcontrol" name="postpin" id="postpin" tabindex="5"  /><img class="" src="'.SFRESOURCES.'pin.png" alt="" />&nbsp;&nbsp;'.__("Pin this Post", "sforum").'</p>'."\n";
	}

	$out.='<br />'."\n";

	$out.='<input type="submit"'.$enabled.'tabindex="6" class="sfcontrol" name="newpost" value="'.__("Save New Post", "sforum").'" />'."\n";
	$out.='&nbsp;<input type="button" tabindex="7" class="sfcontrol" name="cancel" value="'.__("Cancel", "sforum").'" onclick="toggleLayer(\'sfentryform\');" />'."\n";

	if((get_option('sfsubscriptions')) && (!empty($user_id)))
	{
		$out.='<br /><p>'.__("Email Subscriptions on this forum are open.<br />To be notified of responses, click on Subscribe", "sforum").'</p>'."\n";
	}

	$out.='</form>'."\n";
	$out.='</fieldset>'."\n";
	$out.='</div>'."\n";

	return $out;
}

function sf_edit_post($postid, $postcontent, $forumid, $topicid, $page)
{
	$out = '<a id="postedit"></a>'."\n";
	$out.='<form action="'.sf_url($forumid, $topicid, $page).'#p'.$postid.'" method="post" name="editpostform">'."\n";

	if((get_option('sfquicktags')) || (sf_is_safari()))
	{
		$out.='<div class="quicktags">'."\n";
		$out.='<script type="text/javascript">edToolbar();</script><textarea class="qttext" name="editpostcontent" id="editpostcontent" rows="12" >'.stripslashes($postcontent).'</textarea><script type="text/javascript">var edCanvas = document.getElementById("newtopicpost");</script>'."\n";
		$out.='</div>'."\n";
	} else {
		$out.="<br />\n";
		$out.='<textarea name="editpostcontent" id="editpostcontent">'.stripslashes($postcontent).'</textarea>'."\n";
	}

	$out.='<input type="hidden" name="pid" value="'.$postid.'" />'."\n";

	$out.='<input type="submit" class="sfcontrol" name="editpost" value="'.__("Save Edited Post", "sforum").'" />'."\n";
	$out.='&nbsp;<input type="submit" class="sfcontrol" name="cancel" value="'.__("Cancel", "sforum").'" />'."\n";

	$out.='</form>'."\n";

	return $out;
}

function sf_edit_topic_title($topicid, $topicname, $forumid)
{
	$out = '<a id="topicedit"></a>'."\n";
	$out.='<form action="'.sf_url($forumid).'" method="post" name="edittopicform">'."\n";
	$out.='<input type="hidden" name="tid" value="'.$topicid.'" />'."\n";
	$out.='<td><input type="text" class="sfcontrol" name="topicname" value="'.stripslashes($topicname).'" /></td>'."\n";
	$out.='<td><input type="submit" class="sfcontrol" name="edittopic" value="'.__("Save", "sforum").'" /></td>'."\n";
	$out.='<td><input type="submit" class="sfcontrol" name="cancel" value="'.__("Cancel", "sforum").'" /></td>'."\n";
	$out.= '</form>'."\n";

	return $out;
}

function sf_searchbox($forumlevel)
{
	global $user_ID;

	get_currentuserinfo();

	$out.='<div id="sfsearchform">'."\n";

	$out.='<form action="'.get_settings("siteurl").'/wp-content/plugins/simple-forum/sf-search.php" method="post" name="search">'."\n";

	$out.='<fieldset>'."\n";
	$out.='<legend>'.__("Search Forums", "sforum").':</legend>'."\n";

	$out.='<div class="sfsearchblock">'."\n";
	$out.='<input type="text" class="sfcontrol" size="20" name="searchvalue" value="" />'."\n";
	$out.='<br /><br />'."\n";
	$out.= '<img src="'.SFRESOURCES.'searchicon.png" alt="" />&nbsp;'."\n";
	$out.='<input type="submit" class="sfcontrol" name="search" value="'.__("Search Forum", "sforum").'" />'."\n";
	$out.='</div>'."\n";

	// all or current forum?
	$out.='<div class="sfsearchblock">'."\n";
	$ccheck='checked="checked"';
	$acheck='';
	if(($forumlevel == 'forum') || ($forumlevel == 'topic'))
	{
		$out.= '<input type="hidden" name="forumid" value="'.intval($_GET['forum']).'" />'."\n";
		$out.= '<input type="radio" class="sfcontrol" name="searchoption" value="Current" '.$ccheck.' />'.__("Current Forum", "sforum").'<br />'."\n";
	} else {
		$acheck='checked="checked"';
	}
	$out.= '<input type="radio" class="sfcontrol" name="searchoption" value="All Forums" '.$acheck.' />'.__("All Forums", "sforum")."\n";
	$out.='</div>'."\n";

	// search type?
	$out.='<div class="sfsearchblock">'."\n";
	$out.= '<input type="radio" class="sfcontrol" name="searchtype" value="1" checked="checked" />'.__("Match Any Word", "sforum").'<br />'."\n";
	$out.= '<input type="radio" class="sfcontrol" name="searchtype" value="2" />'.__("Match All Words", "sforum").'<br />'."\n";
	$out.= '<input type="radio" class="sfcontrol" name="searchtype" value="3" />'.__("Match Phrase", "sforum")."\n";

	$out.='</div><br />'."\n";

 	$out.='</fieldset>'."\n";

	if('' != $user_ID)
	{
		$out.='<fieldset>'."\n";
		$out.='<legend>'.__("Member Search", "sforum").':</legend>'."\n";

		$out.= '<img src="'.SFRESOURCES.'searchicon.png" alt="" />&nbsp;'."\n";
		$out.= '<input type="hidden" name="userid" value="'.$user_ID.'" />'."\n";

		$out.='<input type="submit" class="sfcontrol" name="membersearch" value="'.__("List Topics You Have Posted To", "sforum").'" />'."\n";

		$out.='</fieldset>'."\n";
	}
	$out.='</form>'."\n";


	$out.='</div>'."\n";

	return $out;
}

function sf_profile()
{
	global $wpdb, $user_ID, $user_login, $user_email, $user_url, $user_identity;

	get_currentuserinfo();

	$ext = get_option('sfextprofile');

	if($ext)
	{
		$profile=array();

		$profile['first_name']=stripslashes(get_usermeta($user_ID, 'first_name'));
		$profile['last_name']=stripslashes(get_usermeta($user_ID, 'last_name'));
		$profile['aim']=stripslashes(get_usermeta($user_ID, 'aim'));
		$profile['yim']=stripslashes(get_usermeta($user_ID, 'yim'));
		$profile['jabber']=stripslashes(get_usermeta($user_ID, 'jabber'));
		$profile['msn']=stripslashes(get_usermeta($user_ID, 'msn'));
		$profile['description']=stripslashes(get_usermeta($user_ID, 'description'));
		$profile['signature']=stripslashes(get_usermeta($user_ID, 'signature'));
	}

// 2.1 Patch 2
	$user_identity = stripslashes($user_identity);

	$out.= '<div id="sforum">'."\n";

	$out.='<br /><br />'."\n";
	$out.='<div id="sfprofileform">'."\n";
	$out.='<fieldset>'."\n";
	$out.='<legend>'.sprintf(__("Profile Information for: <strong> %s </strong>", "sforum"), $user_login.' ('.$user_identity.')').'</legend>'."\n";

	if($ext)
	{
		$out.='<p>'.__("Note: Except for the email address - all other information may be made public", "sforum").'</p>'."\n";
	}

	$out.='<form action="'.SFURL.'" method="post" name="upprofile" enctype="multipart/form-data">'."\n";

	$out.= '<table width="99%" rules="cols"><tr><td width="50%" valign="top">'."\n";

	$out.='<input type="text" class="checkcontrol" size="45" name="username" value="'.$user_login.'" />'."\n";

	if(function_exists('wp_nonce_field'))
	{
		$out.= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce('forum-userform_profile') . '" />'."\n";
		$ref = sf_attribute_escape($_SERVER['REQUEST_URI']);
		$out.= '<input type="hidden" name="_wp_http_referer" value="'. $ref . '" />'."\n";

		if ( wp_get_original_referer() ) {
			$original_ref = sf_attribute_escape(stripslashes(wp_get_original_referer()));
			$out.= '<input type="hidden" name="_wp_original_http_referer" value="'. $original_ref . '" />'."\n";
		}
	}

	$out.='<p>'.__("Email Address (Required)", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="email" value="'.$user_email.'" />'."\n";
	$out.='<p>'.__("Website URL", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="url" value="'.$user_url.'" />'."\n";
	$out.='<p>'.__("Display Name", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="display_name" value="'.$user_identity.'" />'."\n";

	$out.='<p><strong>'.__("Change Password", "sforum").'</strong></p>'."\n";
	$out.='<p>'.__("Current Password", "sforum").':</p><input type="password" class="sfcontrol" size="20" name="oldone" value="" />'."\n";
	$out.='<p>'.__("New Password", "sforum").':</p><input type="password" class="sfcontrol" size="20" name="newone1" value="" />'."\n";
	$out.='<p>'.__("Repeat New Password", "sforum").':</p><input type="password" class="sfcontrol" size="20" name="newone2" value="" />'."\n";

	$out.= '</td>'."\n";
	$out.= '<td width="50%" valign="top">'."\n";

	if($ext)
	{
		$out.='<p>'.__("First Name", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="first_name" value="'.$profile["first_name"].'" />'."\n";
		$out.='<p>'.__("Last Name", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="last_name" value="'.$profile["last_name"].'" />'."\n";

		$out.='<p><strong>'.__("User IDs", "sforum").'</strong></p>'."\n";
		$out.='<p>'.__("AIM", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="aim" value="'.$profile["aim"].'" />'."\n";
		$out.='<p>'.__("Yahoo IM", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="yim" value="'.$profile["yim"].'" />'."\n";
		$out.='<p>'.__("Jabber/Google Talk", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="jabber" value="'.$profile["jabber"].'" />'."\n";
		$out.='<p>'.__("MSN Messenger", "sforum").':</p><input type="text" class="sfcontrol" size="26" name="msn" value="'.$profile["msn"].'" />'."\n";
	}

	$out.= '</td></tr></table>'."\n";
	$out.='<br />'."\n";

	if(get_option('sfavatars'))
	{
		if(get_option('sfgravatar'))
		{
			$out.='<p>'.__("This site supports Gravatars but also allows for uploading an avatar if you do not have a gravatar and to use when gravatars are not available", "sforum").'</p>'."\n";
		}
		$maxsize = get_option('sfavatarsize');
		$out.='<p><strong>'.__("Upload Avatar", "sforum").':</strong></p>'."\n";
		$out.='<table><tr><td>'."\n";
		$out.= sf_display_avatar('user', $user_ID, $user_email, '')."\n";
		$out.='</td><td>'."\n";
		$out.='<p>'.sprintf(__("Files accepted: GIF, PNG, JPG and JPEG<br />Maximum size accepted: %s x %s pixels", "sforum"), $maxsize, $maxsize).'</p><br />'."\n";
		$out.='<input type="file" class="sfcontrol" size="35" name="avatar" />'."\n";
		$out.='</td></tr></table>'."\n";
	}

	if($ext)
	{
		$out.='<br />'."\n";
		$out.='<p>'.__("Short Biographical Note (Please keep it brief)", "sforum").':</p>'."\n";
		$out.='<textarea class="qttext" rows="3" cols="40" name="description" >'.$profile["description"].'</textarea>'."\n";
	}
	if(get_option('sfusersig'))
	{
		$out.='<p>'.__("Signature", "sforum").':</p><input type="text" class="sfcontrol" size="53" name="signature" value="'.$profile['signature'].'" />'."\n";
	}

	$out.='<br /><br /><br />'."\n";

	$out.='<input type="submit" class="sfcontrol" name="subprofile" value="'.__("Update Profile", "sforum").'" />'."\n";

	if(get_option('sfsubscriptions'))
	{
		$out.='<input type="submit" class="sfcontrol" name="mansubs" value="'.__("Manage Subscriptions", "sforum").'" />'."\n";
	}

	$out.='&nbsp;<a class="sfalink" href="'.SFURL.'">'.__("Return to Forum", "sforum").'</a>'."\n";

	$out.='</form>'."\n";

	$out.='</fieldset>'."\n";
	$out.='<br />'."\n";
	$out.='</div></div>'."\n";

	return $out;
}

function sf_subscription_form()
{
	global $wpdb, $user_ID, $user_login, $user_identity;

	get_currentuserinfo();

// 2.1 Patch 2
	$user_identity = stripslashes($user_identity);

	$out.= '<div id="sforum">'."\n";

	$out.='<br /><br />'."\n";
	$out.='<div id="sfprofileform">'."\n";
	$out.='<fieldset>'."\n";
	$out.='<legend>'.sprintf(__("Current Topic Subscriptions for: <strong> %s </strong>", "sforum"), $user_login.' ('.$user_identity.')').'</legend>'."\n";

	$out.='<form action="'.SFURL.'" method="post" name="upsubs">'."\n";

	if(function_exists('wp_nonce_field'))
	{
		$out.= '<input type="hidden" name="_wpnonce" value="' . wp_create_nonce('forum-userform_subs') . '" />'."\n";
		$ref = sf_attribute_escape($_SERVER['REQUEST_URI']);
		$out.= '<input type="hidden" name="_wp_http_referer" value="'. $ref . '" />'."\n";

		if ( wp_get_original_referer() ) {
			$original_ref = sf_attribute_escape(stripslashes(wp_get_original_referer()));
			$out.= '<input type="hidden" name="_wp_original_http_referer" value="'. $original_ref . '" />'."\n";
		}
	}

	$list = get_user_option('sfsubscribe', $user_ID);
	if(empty($list))
	{
		$out.= '<p>'.__("You are currently subscribed to No Topics", "sforum").'</p><br />'."\n";
	} else {
		$out.= '<p>'.__("To Unsubscribe, uncheck topic", "sforum").'</p><br />'."\n";
		$list = explode('@', $list);
		foreach($list as $topicid)
		{
			$out.= '<p><input name="topic[]" type="checkbox" id="'.$topicid.'" value="'.$topicid.'" checked="checked" />&nbsp;&nbsp;&nbsp;'."\n";
			$out.= sf_get_topic_name($topicid).'</p>'."\n";
		}
	}

	$out.='<br /><input type="submit" class="sfcontrol" name="uptopsubs" value="'.__("Update Subscriptions", "sforum").'" />'."\n";
	$out.='&nbsp;<a class="sfalink" href="'.SFPROFILE.'">'.__("Return to Profile", "sforum").'</a>'."\n";
	$out.='&nbsp;<a class="sfalink" href="'.SFURL.'">'.__("Return to Forum", "sforum").'</a>'."\n";

	$out.='</form>'."\n";
	$out.='</fieldset>'."\n";
	$out.='<br />'."\n";
	$out.='</div></div>'."\n";

	return $out;
}

function sf_view_profile($userid)
{
	global $wpdb;

	$profile=array();

	if($userid == ADMINID)
	{
		$loginname=ADMINNAME;
		$profile['url'] = get_option('home');
		$email = get_option('admin_email');
		$display_name = ADMINNAME;
	} else {
		$userinfo = $wpdb->get_row("SELECT * FROM ".SFUSERS." WHERE ID=".$userid);
		$display_name = $userinfo->display_name;
		$profile['url'] = $userinfo->user_url;
		$email = $userinfo->user_email;
	}

	$profile['first_name']=stripslashes(get_usermeta($userid, 'first_name'));
	$profile['last_name']=stripslashes(get_usermeta($userid, 'last_name'));
	$profile['aim']=stripslashes(get_usermeta($userid, 'aim'));
	$profile['yim']=stripslashes(get_usermeta($userid, 'yim'));
	$profile['jabber']=stripslashes(get_usermeta($userid, 'jabber'));
	$profile['msn']=stripslashes(get_usermeta($userid, 'msn'));
	$profile['description']=stripslashes(get_usermeta($userid, 'description'));

	$out.= '<div id="sforum">'."\n";
	$out.='<br /><br />'."\n";
	$out.='<div id="sfprofileform">'."\n";
	$out.='<fieldset>'."\n";
	$out.='<legend>'.sprintf(__("Profile Information for: <strong> %s </strong>", "sforum"), $display_name).'</legend>'."\n";

	if(get_option('sfavatars'))
	{
		$out.= '<br />'.sf_display_avatar('user', $userid, $email, '').'<br />'."\n";
	}
	$out.='<table border="0"><tr>'."\n";

	$out.='<tr><td width="30%">'.__("First Name", "sforum").':</td><td>'.$profile['first_name'].'</td></tr>'."\n";
	$out.='<tr><td>'.__("Last Name", "sforum").':</td><td>'.$profile['last_name'].'</td></tr>'."\n";
	$out.='<tr><td>'.__("Website URL", "sforum").':</td><td>'.$profile['url'].'</td></tr>'."\n";
	$out.='<tr><td>'.__("Bio", "sforum").':</td><td>'.$profile['description'].'</td></tr>'."\n";
	$out.='<tr><td>'.__("AIM", "sforum").':</td><td>'.$profile['aim'].'</td></tr>'."\n";
	$out.='<tr><td>'.__("Yahoo IM", "sforum").':</td><td>'.$profile['yim'].'</td></tr>'."\n";
	$out.='<tr><td>'.__("Jabber/Google Talk", "sforum").':</td><td>'.$profile['jabber'].'</td></tr>'."\n";
	$out.='<tr><td>'.__("MSN Messenger", "sforum").':</td><td>'.$profile['msn'].'</td></tr>'."\n";

	$out.='</table>'."\n";

	$out.='</fieldset>'."\n";
	$out.='<br />'."\n";

	$out.='<form action="'.get_settings("siteurl").'/wp-content/plugins/simple-forum/sf-search.php" method="post" name="search">'."\n";
	$out.='&nbsp;<a class="sfalink" href="'.SFURL.'">'.__("Return to Forum", "sforum").'</a>'."\n";

	$out.='<input type="hidden" name="userid" value="'.$userid.'" />'."\n";

	$out.='<input type="submit" class="sfcontrol" name="membersearch" value="'.sprintf(__("List Topics %s Has Posted To", "sforum"), $display_name).'" />'."\n";
	$out.='</form>'."\n";
	$out.='</div></div>'."\n";

	return $out;
}

function sf_move_topic_form($topicid, $forumid, $page)
{
	global $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return __("Access Denied", "sforum");
	}

	$topicname = sf_get_topic_name($topicid);

	$out.= '<div id="sforum">'."\n";

	$out.= '<div class="sfmessagestrip">'.sprintf(__("Select new Forum for Topic: &nbsp;&nbsp;&nbsp; %s", "sforum"), $topicname).'</div>'."\n";

	$out.='<form action="'.sf_url($forumid, 0, $page).'" method="post" name="movetopicform">'."\n";

	$out.= '<input type="hidden" name="id" value="'.$topicid.'">'."\n";

	//== GET GROUP LIST
	$groups = sf_get_groups_all();
	if($groups)
	{
		foreach($groups as $group)
		{
			//== DISPLAY GROUP NAME
			$out.= '<div class="sfblock">'."\n";
			$out.= '<div class="sfheading"><table><tr>'."\n";
			$out.= '<td class="sficoncell"><img class="" src="'.SFRESOURCES.'group.png" alt="" /></td>'."\n";
			$out.= '<td><p>'.stripslashes($group->group_name).'</p></td>'."\n";
			$out.= '</tr></table></div>'."\n";

			//== GET FORUMS IN GROUP LIST
			$forums = sf_get_forums_in_group($group->group_id);
			if($forums)
			{

				//== DISPLAY FORUM LIST HEADINGS
				$out.= '<table class="sfforumtable">'."\n";
				$out.= '<tr><th colspan="2">'.__("Forums", "sforum").'</th><th>'.__("Select", "sforum").'</th></tr>'."\n";
				foreach($forums as $forum)
				{

					//== DISPLAY FORUM ENTRY
					$out.= '<tr>'."\n";
					$out.= '<td class="sficoncell"><img src="'. SFRESOURCES .'forum.png" alt="" /></td>'."\n";
					$out.= '<td><p><strong>'.stripslashes($forum->forum_name).'</strong></p></td>'."\n";

					$out.= '<td class="sfcounts">'."\n";

					$out.= '<input name="forumid[]" type="checkbox" id="'.$forum->forum_id.'" value="'.$forum->forum_id.'" />'."\n";

					$out.= '</td>'."\n";

					$out.= '</tr>'."\n";
				}
				$out.= '</table>'."\n";
			} else {
				$out.= '<div class="sfmessagestrip">'.__("There are No Forums defined in this Group", "sforum").'</div>'."\n";
			}
			$out.= '</div><br />'."\n";
		}
	}

	$out.='<input type="submit" class="sfcontrol" name="maketopicmove" value="'.__("Move Topic to Selected Forum", "sforum").'" />'."\n";
	$out.='&nbsp;<a class="sfalink" href="'.SFURL.'">'.__("Return to Forum", "sforum").'</a>'."\n";

	$out.= '</form>'."\n";
	$out.= '</div>'."\n";

	return $out;
}

?>