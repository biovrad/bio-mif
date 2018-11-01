<?php
/*
Simple Forum 2.1
Forum Page Components Rendering
*/

function sf_render_queued_message()
{
	$message = get_sfnotice('sfmessage');
	if(!empty($message))
	{
		sf_message($message);
		delete_sfnotice('sfmessage', '');
	}
	return;
}

function sf_render_login_strip($user_ID, $user_identity, $guestcookie, $newposts, $lastvisit, $forumlevel, $lockdown)
{
	global $icons;

	//== USER NAME
	$out.= '<div class="sfloginstrip">'."\n";
	$out.= '<table><tr>'."\n";
	$out.= '<td class="sfusercell">'."\n";
	
	if('' == $user_ID)
	{
		if(get_option('sfallowguests'))
		{
			$out.= __("Current User: <strong>Guest</strong>", "sforum")."\n";
			if(isset($guestcookie['name']))
			{
				$out.= ': <strong>'.stripslashes($guestcookie['name']).'</strong>'."\n";
// 2.1 Patch 1
//				if(!empty($lastvisit)) $out.= '<br />'.__("Last Post", "sforum").': '.date_i18n(SFDATES, $lastvisit)."\n";		
				if((!empty($lastvisit)) && ($lastvisit > 0))
				{
					$out.= '<br />'.__("Last Post", "sforum").': '.date_i18n(SFDATES, $lastvisit)."\n";
				}
			}
		} else {
			$out.= '<strong>'.__("You must be logged in to post", "sforum").'</strong>'."\n";
		}	

		$out.= sf_user_help_box();

	} else {

		$out.= sprintf(__("Logged in as <strong> %s </strong>", "sforum"), sf_filter_user($user_ID, $user_identity))."\n";
		if(!empty($lastvisit)) $out.= '<br />'.__("Last Visit", "sforum").': '.date_i18n(SFDATES, $lastvisit)."\n";		
	}
	
	$out.= '</td>'."\n";
	
	//== LOGIN/REGISTER ICONS
	$out.= '<td class="sflogincell">'."\n";
	
	if('' == $user_ID)
	{
		if(get_option('sfshowlogin'))
		{
			$out.= '<a class="sficon" href="'.SFLOGIN.'"><img src="'.SFRESOURCES.'login.png" alt="" />'.sf_render_icons("Login").'</a>'."\n";
			if ((TRUE == get_option('users_can_register')) && ($lockdown == false))
			{
				$out.= '<a class="sficon" href="'.SFREGISTER.'"><img src="'.SFRESOURCES.'register.png" alt="" />'.sf_render_icons("Register").'</a>'."\n";
			}
		}
	} else {

		if((($user_ID == ADMINID) || (sf_is_moderator($user_ID))) && ($newposts))
		{
			$out.= sf_get_waiting_url($newposts, false);
		}
		if(get_option('sfshowlogin'))
		{		
  			$out.= '<a class="sficon" href="'.SFLOGOUT.'"><img src="'.SFRESOURCES.'logout.png" alt="" />'.sf_render_icons("Logout").'</a>'."\n";
  		}
  		if($lockdown == false)
  		{
			$out.= '<a class="sficon" href="'.SFPROFILE.'"><img src="'.SFRESOURCES.'profile.png" alt="" />'.sf_render_icons("Profile").'</a>'."\n";
		}
		
		if((($user_ID == ADMINID) || (sf_is_moderator($user_ID))) && ($forumlevel != 'group'))
		{
			$forumid=0;
			$topicid=0;
			$state='on';

			if(isset($_GET['forum'])) 
			{
				if($forumid != 'all')
				{
					$forumid=intval($_GET['forum']);
				}
			}	

			if(isset($_GET['topic'])) $topicid=intval($_GET['topic']);

			if($forumid != 'all')
			{
				if(get_option('sfedit')) $state='off';
				$out.= '<form class="alignright" action="'.sf_url($forumid, $topicid).'" method="post" name="toggleicons">'."\n";
				$out.= '<input type="hidden" name="icontoggle" value="1" />'."\n";
				$out.= '<a class="alignright" href="javascript:document.toggleicons.submit();"><img src="'.SFRESOURCES.$state.'.png" alt="" title="'.__("toggle admin icons", "sforum").'" /></a>'."\n";
				$out.= '</form>'."\n";
			}
		}
	}

	$out.= '</td>'."\n"; 

	$out.= '</tr></table>'."\n";
	$out.= '</div>'."\n";

	$out.= sf_hook_post_loginstrip();	
	return $out;
}

function sf_user_help_box()
{
	$out = '<a class="sficon" href="" onclick="return sfboxOverlay(this, \'sfhelpbox\', \'bottom\');"><img src="'.SFRESOURCES.'information.png" alt="" /></a>'."\n";
	$out.= '<div id="sfhelpbox">'."\n";
	$out.= '<div align="right"><a href="" onclick="sfboxOverlayclose(\'sfhelpbox\'); return false;"><img src= "'.SFRESOURCES.'cancel.png" alt="" /></a></div>'."\n";
	$out.= '<ul>'."\n";
	if(get_option('sfallowguests'))
	{
		$out.='<li>'.__("This forum allows Guest Users to post", "sforum").'</li>'."\n";
		$out.='<li>'.__("Guests may not subscribe to email notifications", "sforum").'</li>'."\n";
		if(get_option('sfmoderate')) $out.='<li>'.__("Posts by Guest Users will be moderated prior to publishing", "sforum").'</li>'."\n";
	} else {
		$out.='<li>Guests disallowed</li>'."\n";
	}
	if (TRUE == get_option('users_can_register'))
	{	
		if(get_option('sfallowguests')) 
		{
			$out.='<li>'.__("This forum allows for Member Registration", "sforum").'</li>'."\n";
		} else {
			$out.='<li>'.__("This forum requires Members to register", "sforum").'</li>'."\n";
		}
		if(get_option('sfsubscriptions')) $out.='<li>'.__("Registered Members can subscribe to email notifications", "sforum").'</li>'."\n";
		if(get_option('sfavatars')) $out.='<li>'.__("Registered Members can upload personal avatars", "sforum").'</li>'."\n";
	}
	$out.= '</ul></div>'."\n";
	
	return $out;
}

function sf_render_searchbar($forumlevel, $paramtype, $paramvalue)
{
	$out='<div class="sfmessagestrip">'."\n";
	$out.='<table><tr>'."\n";
	$out.= '<td><a class="sficon" onclick="toggleLayer(\'sfsearchform\');"><img class="sficon" src="'.SFRESOURCES.'search.png" alt="" />'.sf_render_icons("Search").'&nbsp;</a></td>'."\n";

	if($forumlevel == 'topic')
	{
		//== IF SEARCH MODE DISPLAY LINK TO RETURN TO SEARCH RESULTS
		if(isset($_GET['search']))
		{
			$forumid=intval($_GET['forum']);
			if(isset($_GET['ret'])) $forumid='all';
			$out.= '<td>'.sf_get_forum_search_url($forumid, intval($_GET['search']), urlencode($paramvalue)).'<img class="sficon" src="'.SFRESOURCES.'results.png" alt="" />'.sf_render_icons("Return to Search Results").'</a></td>'."\n";
		}
	}

	$groups = sf_get_groups_all();
	if($groups)
	{
		$out.= '<td>'."\n";
		$out.= '<select id="sfquicklinks" name="sfquicklinks" class="sfcontrol" onchange="javascript:changeURL(this)">'."\n";
		$out.= '<option>'.__("Select Forum", "sforum").'&mdash;&mdash;</option>'."\n";

		foreach($groups as $group)
		{
			if(sf_access_granted($group->group_view))
			{		
				$out.= '<option disabled="disabled" value="#">'.$group->group_name.'</option>'."\n";
				$forums = sf_get_forums_in_group($group->group_id);
				if($forums)
				{
					foreach($forums as $forum)
					{
						if(sf_access_granted($forum->forum_view))
						{
							$out.='<option value="'.sf_url($forum->forum_id).'">&mdash;'.stripslashes($forum->forum_name).'</option>'."\n";
						}
					}
				}
			}
		}
		$out.='</select></td>'."\n";
	}

	$out.='</tr></table></div>'."\n";
	
	//== DISPLAY SEARCH FORM
	$out.= sf_searchbox($forumlevel);
	return $out;
}

function sf_render_breadcrumbs($forumid, $topicid, $page)
{
	$arr = '<img src="'.SFRESOURCES.'arrowr.png" alt="" />'."\n";
	$out.= '<div class="sfmessagestrip">'."\n";
	$out.= '<table><tr><td valign="middle"><p>'."\n";

	$out.= '<a class="sficon" href="#forumbottom"><img src="'.SFRESOURCES.'bottom.png" alt="" title="'.__("go to bottom", "sforum").'" /></a>'."\n";
	if(get_option('sfshowhome'))
	{
		$out.= '<a class="sficon" href="'.get_option('sfhome').'">'.__("Home", "sforum").'</a>'."\n";
	}	
	$out.= '<a class="sficon" href="'.trailingslashit(SFURL).'">'.$arr.__("Forums", "sforum").'</a>'."\n";
	if($forumid <> 0)
	{
		$forum = sf_get_forum_row($forumid);
		$out.= '<a class="sficon" href="'.sf_url($forum->forum_id, 0, $page).'">'.$arr.stripslashes($forum->forum_name).'</a>'."\n";
	}
	if($topicid <> 0)
	{
		$topic = sf_get_topic_row($topicid);
		$out.= '<a class="sficon" href="'.sf_url($forumid, $topic->topic_id, 1).'">'.$arr.stripslashes($topic->topic_name).'</a>'."\n";
	}
	
	$out.= '</p></td></tr></table>'."\n";
	$out.= '</div>'."\n";
	$out.= sf_hook_post_breadcrumbs();
	return $out;
}

function sf_render_post_user_editicon($forumid, $topicid, $pageid, $postid)
{
	$out.= '<div class="formlink">'."\n";
	$out.= '<form action="'.sf_url($forumid, $topicid, $pageid, 0, 0, $postid).'#postedit" method="post" name="usereditpost'.$postid.'">'."\n";
	$out.= '<input type="hidden" name="useredit" value="'.$postid.'" />'."\n";
	$out.= '<a class="sfalignleft" href="javascript:document.usereditpost'.$postid.'.submit();"><img src="'.SFRESOURCES.'useredit.png" alt="" title="'.__("edit your post", "sforum").'" />&nbsp;'.sf_render_icons("Edit Your Post").'</a>'."\n";
	$out.= '</form>'."\n";
	$out.= '</div>'."\n";

	return $out;
}

function sf_render_post_user_quoteicon($postid, $username)
{
	$editor = get_option('sfquicktags');
	if($editor) 
	{
		$editor='0';
	} else {
		$editor='1';
	}
	$intro = '&lt;p&gt;'.$username.' '.__("said:", "sforum").'&lt;/p&gt;';
	$out.= '<a class="sfalignleft" onclick="quotePost(\'post'.$postid.'\', \''.$intro.'\', '.$editor.');"><img src="'.SFRESOURCES.'quote.png" alt="" title="'.__("Quote and Reply", "sforum").'" />&nbsp;'.sf_render_icons("Quote and Reply").'</a>'."\n";

	return $out;
}

function sf_display_avatar($icon, $user_id, $useremail, $guestemail)
{
	$out='';

	if(get_option('sfshowavatars') == true)
	{
		$image='';
		$size = get_option('sfavatarsize');
		
		switch($icon)
		{
			case 'user':
				if(!empty($user_id)) $image=get_user_option('sfavatar', $user_id);
				if(empty($image)) $image='userdefault.png';
				break;
				
			case 'admin':
				if(!empty($user_id)) $image=get_user_option('sfavatar', $user_id);
				if(empty($image)) $image='admindefault.png';
				break;
	
			case 'guest':
				$image = 'guestdefault.png';
				break;
		}
		
		$default = SFAVATARURL.$image;

		if(get_option('sfgravatar'))
		{
			$email=$useremail;
			if(empty($email)) $email=$guestemail;
			$size = get_option('sfavatarsize');
			
			if (function_exists('gravatar_path')) 
			{
				$url=gravatar_path($email, $default);
			} else {
				$url = "http://www.gravatar.com/avatar.php?gravatar_id=".md5($email)."&amp;default=".urlencode($default)."&amp;size=".$size."&amp;rating=X";
			}
		} else {
			$url = $default;
		}
		
		$class='sfavatar';
		if(get_option('sfuserabove')) $class='sfavatar sfposticon';
		$out='<div class="sfuseravatar"><img class="'.$class.'" src="'.$url.'" alt="" height="'.$size.'" width="'.$size.'" /></div>'."\n";
	}
	return $out;
}

function sf_display_usertype($status, $userid, $userposts)
{
	switch($status)
	{
		case 'admin':
			$out= __("Admin", "sforum").' '."\n";
			break;
		case 'user':
			if(sf_is_moderator($userid))
			{
				$out= __("Moderator", "sforum").' '."\n";
			} else {
				$out= sf_get_user_ranking($userposts, __("Member", "sforum")).' '."\n";
				//$out= __("Member", "sforum").' '."\n";
			}
			break;
		case 'guest':
			$out= __("Guest", "sforum").' '."\n";
			break;
	}
	return $out.'<br />'."\n";
}

function sf_get_user_ranking($userposts, $default)
{
	$out = $default;
	$ranks = array_reverse(get_option('sfrankings'));
	if($ranks)
	{
		foreach($ranks as $desc=>$posts)
		{
			if($userposts <= $posts)
			{
				$out = $desc;
			}
		}
	}
	return $out;
}

function sf_render_bottom_iconstrip($view, $forum=0, $topic=0, $user='', $newposts='')
{
	$out ='<br /><div id="sfbottomiconstrip">'."\n";

$out.='<table><tr><td>';
	//== RSS
	if(get_option('sfrss'))
	{
		switch($view)
		{
			case 'group':
// 2.1 Patch 1
//				$url=SFURL.'?xfeed=all';
				$url=sf_get_sfurl_plus_amp(SFURL).'xfeed=all';
				$icon='feedall.png';
				$text='All RSS';
				break;
			case 'forum':
				$url=sf_url($forum).'&amp;xfeed=forum';
				$icon='feedforum.png';
				$text='Forum RSS';
				break;
			case 'topic':
				$url=sf_url($forum, $topic).'&amp;xfeed=topic';
				$icon='feedtopic.png';
				$text='Topic RSS';
				break;
		}
		$out.= '<a class="sficon sfalignleft" href="'.$url.'"><img src="'.SFRESOURCES.$icon.'" alt="" />'.sf_render_icons($text).'&nbsp;</a>'."\n";
	}
		
	//== Subscribe

	if(('' != $user) && (get_option('sfsubscriptions')) && ($view=='topic'))
	{
		$out.= '<a class="sficon sfalignleft" href="'.sf_url($forum, $topic).'&amp;subscribe=user"><img src="'.SFRESOURCES.'subscribe.png" alt="" title="subscribe to this topic" />'.sf_render_icons("Subscribe").'</a>'."\n";
	}
	$out.='</td><td>';
	
	//== New Post Links for Admin
	if((($user == ADMINID) || (sf_is_moderator($user))) && ($newposts))
	{
		$out.= sf_get_waiting_url($newposts, true);
	}

	//== Go to top button
	$out.= '<a href="#forumtop"><img class="sfalignright" src="'.SFRESOURCES.'top.png" alt="" title="'.__("go to top", "sforum").'" /></a><br />'."\n";
	$out.='</td></tr></table>';
	$out.='</div><br />'."\n";
	return $out;
}

function sf_render_stats()
{
	$out = '';
	if(get_option('sfstats'))
	{
		$out.= '<br /><table id="sfstatstrip">'."\n";
		$out.= '<tr><th colspan="4"><p>'.sprintf(__("About the %s forum", "sforum"), get_bloginfo('name')).'</p></th></tr>'."\n";
		
		$out.= '<tr>'."\n";
		$out.= sf_get_online();
		$out.= sf_get_forum_stats(); 
		$out.= sf_get_member_stats();
		$out.= '</tr></table><br />'."\n";
	}
	return $out;
}

function sf_get_online()
{
	global $wpdb;
	
	$guests=0;
	$label=' '.__("Guests", "sforum");
	
	$online=$wpdb->get_results("SELECT trackuserid, trackname FROM ".SFTRACK." ORDER BY trackuserid");
	if($online)
	{
		sf_update_max_online(count($online));
		$out = '<td width="25%">'."\n";
		$out.='<p><strong>'.__("Currently Online", "sforum").': </strong></p>'."\n";
		foreach($online as $user)
		{
			if($user->trackuserid == 0)
			{
				$guests++;
			} else {
				if($user->trackuserid == ADMINID)
				{
					$out.= '<p>'.ADMINNAME. '</p>'."\n";
				} else {
					$out.= '<p>'.$user->trackname.'</p>'."\n";
				}
			}
		}
		if($guests > 0)
		{
			if($guests == 1) $label=' '.__("Guest", "sforum");
			$out.= '<p>'.$guests.$label.'</p>'."\n";
		}
		$out.='<p>'.__("Maximum Online", "sforum").': '.get_sfsetting('maxonline').'</p>'."\n";
		$out.='</td>'."\n";
	}
	return $out;
}

function sf_get_forum_stats()
{
	$out = '<td width="25%">'."\n";
	$out.= '<p><strong>'.__("Forums: ", "sforum").'</strong></p>'."\n";
	$out.= '<p>'.__("Groups: ", "sforum").sf_get_table_count(SFGROUPS).'</p>'."\n";
	$out.= '<p>'.__("Forums: ", "sforum").sf_get_forum_count().'</p>'."\n";

	$out.= '<p>'.__("Topics: ", "sforum").sf_get_table_count(SFTOPICS).'</p>'."\n";
	$out.= '<p>'.__("Posts: ", "sforum").sf_get_table_count(SFPOSTS).'</p>'."\n";
	$out.= '</td>'."\n";
	return $out;
}

function sf_get_member_stats()
{
	$members = sf_get_member_post_count();
	$guests = sf_get_guest_count();
	
	$out = '<td width="25%">'."\n";
	$out.= '<p><strong>'.__("Members:", "sforum").'</strong></p>'."\n";
	if($members)
	{
		$membercount=get_sfsetting('membercount');
	} else {
		$membercount = 0;
	}
	$out.='<p>'.sprintf(__("There are %s members", "sforum"), $membercount).'</p>'."\n";
	if($guests)
	{
		$out.='<p>'.sprintf(__("There are %s guests", "sforum"), $guests).'</p>'."\n";
	}
	if($members)
	{
		foreach($members as $member)
		{
			if($member->ID == ADMINID)
			{
				$out.='<p>'.sprintf(__("%s has made %s posts", "sforum"), ADMINNAME, $member->posts).'</p>'."\n";
			}
		}
	}

	if($members)
	{
		$out.= '</td><td width="25%">'."\n";
		$out.='<p><strong>'.__("Top Posters:", "sforum").'</strong></p>'."\n";
		foreach($members as $member)
		{
			if($member->ID != ADMINID)
			{
// 2.1 Patch 2
				$out.='<p>'.stripslashes($member->display_name).' - '.$member->posts.'</p>'."\n";
			}
		}
	}
	$out.= '</td>'."\n";
	return $out;
}

function sf_version_check()
{
	//$out = '<div id="sfversion">'.__("�����", "sforum").' - '.__("������", "sforum").' '.SFVERSION.' (������ 1.0)</div><br />'."\n";
	//return $out;
}

function sf_acknowledgements()
{
	$out = '<a class="sficon" href="" onclick="return sfboxOverlay(this, \'sfacknowledge\', \'right\');"><img src="'.SFRESOURCES.'information.png" alt="" /></a>'."\n";
	$out.= '<div id="sfacknowledge">'."\n";
	$out.= '<div align="right"><a href="" onclick="sfboxOverlayclose(\'sfacknowledge\'); return false;"><img src= "'.SFRESOURCES.'cancel.png" alt="" /></a></div>'."\n";

	$out.= '<p>'.__("Simple111 Forum WordPress Plugin created by Andy Staines: ", "sforum").'<strong><a href="http://www.stuff.yellowswordfish.com">Yellow Swordfish</a></strong></p>'."\n";
	$out.= '<p>'.__("Forum Skin/Icons", "sforum").': '.get_option('sfskin').' / '.get_option('sficon').'</p>'."\n";
	$out.= '<p>'.__("Default 'Silk' Icon Set created by Mark James: ", "sforum").'<strong><a href="http://www.famfamfam.com/lab/icons/silk/">fam fam fam</a></strong></p>'."\n";
	$out.= '<p>'.__("Math Spam Protection based on code by Michael Woehrer: ", "sforum").'<strong><a href="http://sw-guide.de/">Software Guide</a></strong></p>'."\n";
	$out.= '<p>'.__("Tabbed Admin uses Tabifier by Patrick Fitzgerald: ", "sforum").'<strong><a href="http://www.barelyfitz.com/">BarelyFitz Designs</a></strong></p>'."\n";
	$out.= '<br /><p>'.__("My thanks to all the people who have aided, abetted, suggested and helped test this plugin", "sforum").'</p>'."\n";

	$out.= '</div>'."\n";
	return $out;
}

?>