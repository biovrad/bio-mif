<?php
/*
Simple Forum 2.1
Forum Page Rendering
*/

function sf_render_page($paramtype, $paramvalue)
{
	global $user_login, $user_ID, $user_identity, $is_admin, $lockdown;

	//== SORT SOME VARIABLES FIRST
	get_currentuserinfo();

// 2.1 Patch 2
	$user_identity = stripslashes($user_identity);
	
	$guestcookie=sf_get_cookie();

	$lastvisit = sf_track_online($user_ID, $user_login, $guestcookie);

	$is_admin=false;
	if(sf_admin_status())
	{
		$is_admin=true;
	}

	$lockdown = get_option('sflockdown');
	
	//== WORK OUT WHAT WE ARE RENDERING ================================
	if(isset($_GET['forum']))
	{
		$forumlevel = 'forum';
		if($_GET['forum'] != 'all')
		{
			$forumid = intval($_GET['forum']);
		}
	} else {
		$forumlevel = 'group';
	}
	
	if(isset($_GET['topic']))
	{
		$forumlevel = 'topic';
		$topicid = intval($_GET['topic']);
	}

	if(isset($_GET['newposts']))
	{
		$forumlevel = 'newposts';
	}

	if(isset($_GET['search']))
	{
		if($_GET['forum'] == 'all')
		{
			$paramtype='SA';
			$forumlevel = 'searchall';
		} else {
			$paramtype='S';
			if(empty($paramvalue))
			{
				$paramvalue=$_GET['value'];
			}
		}		
	} else {
		$paramtype = '';
		$paramvalue = '';
	}

	//== TOP OF FORUM DISPLAY ==========================================
	$out = sf_render_queued_message();

	$out.= '<div id="sforum">'."\n";
	$out.= '<a id="forumtop"></a>'."\n";
	
	//== LOGIN/USER BLOCK

	// check if admin and if any posts waiting
	$newposts='';
	if($is_admin)
	{
		$newposts = sf_get_unread_forums();
	}

	if($lockdown)
	{
		$out.= '<div class="sfmessagestrip"><div class="sficonkey">';
		$out.= '<p><img src="'.SFRESOURCES.'locked.png" alt="" />'."\n";
		$out.= __("This forum is currently locked - access is read only", "sforum").'</p></div></div>'."\n";
	}
	
	$out.= sf_render_login_strip($user_ID, $user_identity, $guestcookie, $newposts, $lastvisit, $forumlevel, $lockdown);
	if(get_option('sfsearchbar'))
	{
		$out.= sf_render_searchbar($forumlevel, $paramtype, $paramvalue);
	}

	$page = intval($_GET['page']);
	
	switch ($forumlevel)
	{
		case 'group':
			$out.= sf_render_breadcrumbs(0, 0, 0);
			if(!get_option('sfshownewabove'))
			{
				$out.= sf_render_group();
			}
			if(($is_admin) && (get_option('sfshownewadmin')))
			{
				$out.= sf_render_new_post_list_admin($newposts);
			} else {
				if(get_option('sfshownewuser'))
				{
					$out.= sf_render_new_post_list_user($lastvisit);
				}
			}
			if(get_option('sfshownewabove'))
			{
				$out.= sf_render_group();
			}
			$out.='<br /><br />'.sf_render_bottom_iconstrip('group', 0, 0, $user_ID, $newposts);
			break;
		case 'forum':
			$out.= sf_render_breadcrumbs($forumid, 0, $page);
			$out.= sf_render_forum($forumid, $paramtype, $paramvalue, $lastvisit, $newposts);
			$out.= sf_render_bottom_iconstrip('forum', $forumid, 0, $user_ID, $newposts);
			break;
		case 'topic':
			$topicpage = 1;
			if(isset($_GET['tp'])) $topicpage=intval($_GET['tp']);
			$out.= sf_render_breadcrumbs($forumid, $topicid, $topicpage);
			sf_remove_from_waiting($topicid, $user_ID);
			sf_remove_users_newposts($topicid, $user_ID);
			sf_update_opened($topicid);
			$out.= sf_render_topic($forumid, $topicid, $paramvalue, $newposts);
			$out.= sf_render_bottom_iconstrip('topic', $forumid, $topicid, $user_ID, $newposts);		
			break;
		case 'newposts':
			$out.= sf_render_breadcrumbs(0, 0, 0);
			$out.=  sf_render_new_post_list_admin($newposts);
			break;
		case 'searchall':
			$out.= sf_render_breadcrumbs(0, 0, 0);
			$out.= sf_render_search_all($paramtype, $paramvalue);
			break;
	}

	$out.= sf_render_stats();
	$out.= sf_hook_footer_inside();
	$out.= sf_version_check();
	$out.= '<a id="forumbottom"></a>'."\n";
	$out.= '</div>'."\n";
	$out.= sf_hook_footer_outside();
	return $out;
}

//== DISPLAY GROUPS/FORUMS (FRONT) PAGE ====================================================

function sf_render_group()
{
	global $user_login, $user_ID, $is_admin, $lockdown;

	get_currentuserinfo();

	//== GET GROUP LIST
	if(isset($_GET['group'])) 
	{
		$groupid = intval($_GET['group']);
		if(!sf_group_exists($groupid))
		{
			update_sfnotice('sfmessage', sprintf(__('Group %s Not Found', 'sforum'), $groupid));
			$out = sf_render_queued_message();
			return $out;
		}
	} else {
		$groupid = Null;
	}
	$groups = sf_get_groups_all($groupid);
	if($groups)
	{
		foreach($groups as $group)
		{
			if(sf_access_granted($group->group_view))
			{		

				//== DISPLAY GROUP NAME
				$out.= '<div class="sfblock">'."\n";
				$out.= '<a id="g'.$group->group_id.'"></a>'."\n";
				$out.= '<div class="sfheading"><table><tr>'."\n";
				$out.= '<td class="sficoncell"><img class="" src="'.SFRESOURCES.'group.png" alt="" /></td>'."\n";
				$out.= '<td><p>'.stripslashes($group->group_name).'<br /><small>'.stripslashes($group->group_desc).'</small></p></td>'."\n";

// 2.1 Patch 1			
//				$out.= '<td class="sfadditemcell"><a class="sficon sfalignright" href="'.SFURL.'?group='.$group->group_id.'&amp;xfeed=group"><img src="'.SFRESOURCES.'feedgroup.png" alt="" />'.sf_render_icons('Group RSS').'&nbsp;</a></td>'."\n";
				$out.= '<td class="sfadditemcell"><a class="sficon sfalignright" href="'.sf_get_sfurl_plus_amp(SFURL).'group='.$group->group_id.'&amp;xfeed=group"><img src="'.SFRESOURCES.'feedgroup.png" alt="" />'.sf_render_icons('Group RSS').'&nbsp;</a></td>'."\n";
			
				$out.= '</tr></table></div>'."\n";
				
				//== GET FORUMS IN GROUP LIST
				$forums = sf_get_forums_in_group($group->group_id);
				if($forums)
				{
					$cols=get_option('sfforumcols');
					//== DISPLAY FORUM LIST HEADINGS
					$out.= '<table class="sfforumtable">'."\n";
					$out.= '<tr><th colspan="2">'.__("Forums", "sforum").'</th>'."\n";
					if($cols['last']) $out.= '<th>'.__("Last Post", "sforum").'</th>'."\n";
					if($cols['topics']) $out.= '<th>'.__("Topics", "sforum").'</th>'."\n";
					if($cols['posts']) $out.= '<th>'.__("Posts", "sforum").'</th>'."\n";
					$out.= '</tr>'."\n";
					
					foreach($forums as $forum)
					{
						if(sf_access_granted($forum->forum_view))
						{
							// GET LAST POST
							$last = sf_get_last_post_in_forum($forum->forum_id);
							//== DISPLAY FORUM ENTRY
							$out.= '<tr>'."\n";
							$out.= '<td class="sficoncell"><img src="'. SFRESOURCES .'forum.png" alt="" /></td>'."\n";
							
							//== DISPLAY TOPIC AND POSTS COUNT
							$topiccount = sf_get_topic_count($forum->forum_id, '');
							$out.= '<td><p>'.sf_get_forum_url($forum->forum_id, $forum->forum_name, $forum->forum_status)."\n";
							
							$out.= '<small>'.sf_get_topic_pagelinks($forum->forum_id, $topiccount).'</small>'."\n";							
							
							$out.= '<br /><small>'.stripslashes($forum->forum_desc).'</small></p>'."\n";
							$out.= sf_hook_post_forum($forum->forum_id);
							$out.= '</td>'."\n";
							
							//== DISPLAY TOPIC AND POSTS COUNT							
							if($last)
							{
								$poster = sf_filter_user($last->user_id, $last->display_name);
								if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($last->guest_name));
								if($cols['last']) $out.= '<td class="sfuserdetails"><p>'.mysql2date(SFDATES, $last->post_date)." - ".mysql2date(SFTIMES,$last->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p></td>'."\n";
							} else {
								if($cols['last']) $out.='<td align="center">-</td>'."\n";
							}
							
							if($cols['topics']) $out.= '<td class="sfcounts">'.$topiccount.'</td>'."\n";
							if($cols['posts']) $out.= '<td class="sfcounts">'.sf_get_posts_count_in_forum($forum->forum_id).'</td>'."\n";
							$out.= '</tr>'."\n";
						}
					}	
					$out.= '</table>'."\n";
				} else {
					$out.= '<div class="sfmessagestrip">'.__("There are No Forums defined in this Group", "sforum").'</div>'."\n";
				}
				$out.= '</div>'."\n";

				$out.= sf_hook_post_group($group->group_id);
			} else {
				//== WHAT IF DISALLOWED AND ONLY ONE GROUP?
				if(count($groups)==1)
				{
					update_sfnotice('sfmessage', __("Access Denied", "sforum"));
					$out = sf_render_queued_message();
					return $out;
				}
			}
		}
	} else {
		$out.= '<div class="sfmessagestrip">'.__("There are No Groups defined", "sforum").'</div>'."\n";
	}

	return $out;	
}

// == DISPLAY FORUM (TOPICS) ===================================================================

function sf_render_forum($forumid, $paramtype, $paramvalue, $lastvisit, $newposts)
{
	global $user_login, $user_ID, $is_admin, $lockdown;

	get_currentuserinfo();

	if(!sf_forum_exists($forumid))
	{
		update_sfnotice('sfmessage', sprintf(__('Forum %s Not Found', 'sforum'), $forumid));
		$out = sf_render_queued_message();
		return $out;
	}

	// == ICON KEY	
	$out.='<div class="sficonkey">';
	if($lastvisit > 0)
	{
		if('' != $user_ID)
		{
			$mess=__("since your last visit", "sforum");
		} else {
			$mess=__("since you last posted", "sforum");
		}
	
		$out.= '<small><img src="'. SFRESOURCES .'topickey.png" alt="" />&nbsp;&nbsp;'.__("New Posts", "sforum").'&nbsp;'.$mess.'&nbsp;&nbsp;</small>'."\n";
	}
	if('' != $user_ID)
	{
		$out.= '<small><img src="'. SFRESOURCES .'topickeyuser.png" alt="" />&nbsp;&nbsp;'.__("Topics you have posted in", "sforum").'</small>'."\n";	
	}
	$out.='</div>';
	
	$forum = sf_get_forum_row($forumid);

	if(sf_access_granted($forum->forum_view))
	{
		$search = false;
		$searchpage = '';
		$forumlock = false;
		$admintools = false;
		if((get_option('sfedit')) && ($is_admin)) $admintools= true;
	
		if($forum)
		{
		
			//== DISPLAY FORUM NAME
			$out.= '<div class="sfblock">'."\n";
			$out.= '<div class="sfheading"><table><tr>'."\n";
			$out.= '<td class="sficoncell"><img src="'.SFRESOURCES.'forum.png" alt="" /></td>'."\n";
			$out.= '<td><p>'.stripslashes($forum->forum_name)."\n";
	
			//== IS FORUM LOCKED?
			if($forum->forum_status == true)
			{
				$forumlock=true;
				$out.='<img src="'.SFRESOURCES.'locked.png" alt="" />'."\n";
			}
			
			//== CHECK SEARCH STATUS
			if(!empty($paramvalue))
			{
				$out.=' ('.__("Search Results", "sforum").': '.stripslashes(sf_deconstruct_search_parameter($paramvalue)).')'."\n";
				$search = true;
			}
			$out.= '<br /><small>'.stripslashes($forum->forum_desc).'</small>'."\n";
			$out.= '</p></td>'."\n";
	
			// == IS FORUM LOCKED OR CAN WE ADD
			if(('' != $user_ID) || (get_option('sfallowguests'))) $showadd = true;
			if($forumlock) $showadd = false;
			if($is_admin) $showadd = true;
			if($lockdown) $showadd = false;
	
			$out.= '<td class="sfadditemcell">'."\n";
			if($showadd)
			{
				$out.= '<a class="sficon" onclick="toggleLayer(\'sfentryform\');"><img src="'.SFRESOURCES.'addtopic.png" alt="" />'.sf_render_icons("Add a New Topic").'</a>'."\n";
			} else {
				if($forumlock)
				{
					$out.= '<img src="'.SFRESOURCES.'locked.png" alt="" />'.sf_render_icons("Forum Locked")."\n";
				}
			}
			$out.= '</td></tr></table></div>'."\n";
	
			//-- DISPLAY SEARCH IF IN SEARCH MODE
			if($search)
			{
				$displaytext=__("Topics Matching Search", "sforum");
			} else {
				$displaytext=__("Topics", "sforum");
			}
	
			//== SET CURRENT PAGE & SEACHPAGE TO 1 IF UNKNOWN
			if(isset($_GET['page']))
			{
				$page = intval($_GET['page']);
			} else {
				$page = 1;
			}
			if(isset($_GET['search']))
			{
				$searchpage = intval($_GET['search']);
			}

			//== DISPLAY PAGED LINKS
			$thispagelinks = sf_format_paged_topics($forumid, $page, $search, $searchpage);
			$out.= '<table class="sffooter"><tr>'."\n";
			$out.= '<td class="sfpagelinks">'.$thispagelinks.'</td>'."\n";
			$out.= '</tr></table>'."\n";
			
			//== GET TOPIC LIST FOR FORUM
			$topics = sf_get_topics_in_forum($forumid, $page, $paramvalue, $searchpage);
	
			if($topics)
			{
				//== DISPLAY TOPIC LIST HEADINGS
				$cols=get_option('sftopiccols');
	
				$out.= '<table class="sfforumtable">'."\n";
				$out.= '<tr><th colspan="2">'.$displaytext.'</th>'."\n";
				if($cols['first']) $out.= '<th>'.__("Started", "sforum").'</th>'."\n";
				if($cols['last']) $out.= '<th>'.__("Last Post", "sforum").'</th>'."\n";
				if($cols['posts']) $out.= '<th>'.__("Posts", "sforum").'</th>'."\n";
				if($cols['views']) $out.= '<th>'.__("Views", "sforum").'</th>'."\n";
	
				//== INCLUDE MANAGE ICON HEADING IF ADMIN AND OPTIONED
				if($admintools)
				{
					$out.= '<th width="16"></th></tr>'."\n";			
				} else {
					$out.= '</tr>'."\n";
				}
				
				foreach($topics as $topic)
				{			
					//== GET FIRST/LAST POSTER DETAILS - NEEDED FOR NEW POST VIEW
					$first = sf_get_first_post_in_topic($topic->topic_id);
					$last = sf_get_last_post_in_topic($topic->topic_id);
					$postcount = sf_get_posts_count_in_topic($topic->topic_id);
					
					//== DISPLAY TOPIC ENTRY
					$out.= '<tr>'."\n";
					$out.= sf_render_topic_icon($topic->topic_id, $user_ID, $lastvisit, $last->udate);
					if((isset($_POST['topicedit'])) && ($_POST['topicedit'] == $topic->topic_id))
					{
						$out.= sf_edit_topic_title($topic->topic_id, stripslashes($topic->topic_name), $forumid);
					} else {
						$out.= '<td><p>'.sf_get_topic_url($forumid, $topic->topic_id, $topic->topic_name, $topic->topic_status, $topic->topic_pinned, $search, $searchpage, urlencode($paramvalue), $forumlock, $topic->blog_post_id)."\n";
						$out.= sf_user_subscribed_icon($user_ID, $topic->topic_subs);
						$out.= '<small>'.sf_get_post_pagelinks($forumid, $topic->topic_id, $postcount, $searchpage, urlencode($paramvalue), $paramtype).'</small></p>'."\n";
						$out.= sf_hook_post_topic($forumid, $topic->topic_id);
						$out.= '</td>'."\n";
						
						//== DISPLAY FIRST/LAST POSTER DETAILS
						if($first)
						{
							$poster = sf_filter_user($first->user_id, $first->display_name);
							if(empty($poster)) $poster=stripslashes($first->guest_name);
							if($cols['first']) $out.= '<td class="sfuserdetails"><p>'.mysql2date(SFDATES, $first->post_date)."-".mysql2date(SFTIMES,$first->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p></td>'."\n";
						} else {
							if($cols['first']) $out.='<td></td>'."\n";
						}
		
						if($last)
						{
							$poster = sf_filter_user($last->user_id, $last->display_name);
							if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($last->guest_name));
							if($cols['last']) $out.= '<td class="sfuserdetails"><p>'.mysql2date(SFDATES, $last->post_date)." - ".mysql2date(SFTIMES,$last->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p></td>'."\n";
						} else {
							if($cols['last']) $out.='<td></td>'."\n";
						}
						
						//== DISPLAY POST COUNT IN TOPIC
						if($cols['posts']) $out.= '<td class="sfcounts">'.$postcount.'</td>'."\n";
						if($cols['views']) $out.= '<td class="sfcounts">'.$topic->topic_opened.'</td>'."\n";
						
						//= DISPLAY MANAGEMENT ICONS IF ADMIN & OPTIONED
						if($admintools)
						{
							$out.= '<td class="sfmanageicons">'.sf_render_topic_editicons($topic, $forum, $page).'</td></tr>'."\n";				
						} else {
							$out.= '</tr>'."\n";
						}
					}
				}
				$out.= '</table>'."\n";
	
				//== DISPLAY PAGED LINKS
				$out.= '<table class="sffooter"><tr>'."\n";
				$out.= '<td class="sfpagelinks">'.$thispagelinks.'</td>'."\n";
	
				//== DISPLAY ADD TOPIC OR LOCKED AS APPROPRIATE
				$out.= '<td class="sfadditemcell">'."\n";
				if($showadd)
				{
					$out.= '<a class="sficon" onclick="toggleLayer(\'sfentryform\');"><img src="'.SFRESOURCES.'addtopic.png" alt="" />'.sf_render_icons("Add a New Topic").'</a>'."\n";
				} else {
					if($forumlock)
					{
						$out.= '<img src="'.SFRESOURCES.'locked.png" alt="" />'.sf_render_icons("Forum Locked")."\n";
					}
				}
				$out.= '</td></tr></table>'."\n";
	
			} else {
				if($search)
				{
					$out.= '<div class="sfmessagestrip">'.__("The Search found No Results", "sforum").'</div>'."\n";
					delete_sfsetting($paramvalue);
				} else {
					$out.= '<div class="sfmessagestrip">'.__("There are No Topics defined in this Forum", "sforum").'</div>'."\n";
				}
			}
	
			$out.= '</div>'."\n";
	
		} else {
			$out.= '<div class="sfmessagestrip">'.__("Something Messed Up!", "sforum").'</div>'."\n";
		}
	
		//== DISPLAY NEW TOIPIC FORM (HIDDEN)
		if($showadd)
		{
			$out.= '<a id="dataform"></a>'."\n";
			$out.= sf_add_topic($forumid, $forum->forum_name, $user_ID);
		}
	} else {
		update_sfnotice('sfmessage', __("Access Denied", "sforum"));
		$out = sf_render_queued_message();
	}
	
	return $out;
}

//== DISPLAY TOPIC (POSTS) =================================================================================

function sf_render_topic($forumid, $topicid, $paramvalue, $newposts)
{
	global $user_login, $user_ID, $is_admin, $lockdown;

	get_currentuserinfo();

	if(!sf_topic_exists($topicid))
	{
		update_sfnotice('sfmessage', sprintf(__('Topic %s Not Found', 'sforum'), $topicid));
		$out = sf_render_queued_message();
		return $out;
	}

	if(sf_access_granted(sf_get_forum_accessrole($forumid)))
	{
		$editstrip = false;
		$admintools = false;
		if((get_option('sfedit')) && ($is_admin)) $admintools= true;
	
		//== SET CURRENT PAGE TO 1 IF UNKNOWN
		if(isset($_GET['page']))
		{
			$page = intval($_GET['page']);
		} else {
			$page = 1;
		}
		
		//== GET TOPIC DETAILS
		$topic = sf_get_topic_row($topicid);
		if($topic)
		{
			//== GET LOCK STATUS
			$topiclock = false;
			if(($topic->topic_status == true) || (sf_get_forum_status($topic->forum_id)))
			{
				$topiclock = true;
			}
			//== DISPLAY TOPIC NAME
			$out.= '<div class="sfblock">'."\n";
			$out.= '<div class="sfheading"><table><tr>'."\n";
			$out.= '<td class="sficoncell"><img src="'.SFRESOURCES.'topic.png" alt="" /></td>'."\n";
			$out.= '<td><p>'.stripslashes($topic->topic_name);
			
			if($topic->blog_post_id != 0)
			{
				$out.= '<br />'.sf_forum_show_blog_link($topic->blog_post_id);
			}			
			$out.= '</p></td>'."\n";
	
			$out.= '<td class="sfadditemcell">'."\n";
			
			//== DISPLAY REPLY TO POST LINK IF ALLOWED - OR LOCKED ICON IF TOPIC LOCKED
			if((!$topiclock) || ($is_admin))
			{
				if(('' != $user_ID) || (get_option('sfallowguests')))
				{
					if($lockdown == false)
					{
						$out.= '<a class="sficon" onclick="toggleLayer(\'sfentryform\');"><img src="'.SFRESOURCES.'addpost.png" alt="" />'.sf_render_icons("Reply to Post").'</a>'."\n";
					}
				}
			} else {
				$out.= '<img src="'.SFRESOURCES.'locked.png" alt="" />'.sf_render_icons("Topic Locked")."\n";
			}
	
			$out.= '</td></tr></table></div>'."\n";

			//== DISPLAY PAGE LINKS
			$thispagelinks = sf_format_paged_posts($forumid, $topicid, $page);
			$out.= '<table class="sffooter"><tr>'."\n";
			$out.= '<td class="sfpagelinks">'.$thispagelinks.'</td>'."\n";
			$out.= '</tr></table>'."\n";

			//== GET POST LIST FOR TOPIC
			$posts = sf_get_posts_in_topic($topicid, $topic->topic_sort, $page);
			if($posts)
			{
				$numposts=count($posts);
				$thispost=1;
				
				//== DISPLAY POST HEADINGS
				$out.= '<table class="sfposttable">'."\n";
				if(get_option('sfuserabove'))
				{
					$out.= '<tr><th>'.__("Post", "sforum").'</th>'."\n";
				} else {
					$out.= '<tr><th>'.__("User", "sforum").'</th><th>'.__("Post", "sforum").'</th>'."\n";
				}
	
				//== INCLUDE MANAGE ICON HEADING IF ADMIN AND OPTIONED
				if($admintools)
				{
					$out.= '<th width="16"></th></tr>'."\n";
				} else {
					$out.= '</tr>'."\n";
				}
	
				//= GET COOKIES IN CASE RETURNING GUEST
				$guestcookie=sf_get_cookie();
	
				foreach($posts as $post)
				{
				
					//== PREPARE POSTERS INFORMATION AND STATUS
					$posterstatus = 'user';
					if($post->user_id == ADMINID) $posterstatus = 'admin';
					$currentguest = false;
					$currentmember = false;
					
					//== WAS THIS POST POSTED BY CURRENT USER OF SYSTEM?
					if(('' != $user_ID) && ($user_ID == $post->user_id)) $currentmember=true;
	
					if(!empty($post->user_url))
					{
						$poster = '<a href="'.$post->user_url.'">'.sf_filter_user($post->user_id, $post->display_name).'</a>'."\n";
					} else {
// 2.1 Patch 2
						$poster = stripslashes($post->display_name);
					}
					$username = sf_filter_user($post->user_id, $post->display_name);
					
					if(empty($poster))
					{
						$poster = apply_filters('sf_show_post_name', stripslashes($post->guest_name));
						$posterstatus = 'guest';
						
						//== POSTED BY CURRENT GUEST USER?
						if((stripslashes($guestcookie["name"] == $poster)) && (stripslashes($guestcookie["email"] == stripslashes($post->guest_email))))
						{
							$currentguest = true;
						}
						$username = $poster;
					}
	
					//== GET POST COUNT FOR MEMBERS
					$postcount='';
					if(!empty($post->user_id))
					{
						$userposts = get_user_option('sfposts', $post->user_id);
						$postcount = __("posts ", "sforum").$userposts;
					}
	
					//== DISPLAY POST USERS INFORMATION
					$out.= '<tr valign="top">'."\n";
					if(get_option('sfuserabove'))
					{
						//== DISPLAY SINGLE COLUMN POST FORMAT
						$out.= '<td class="sfuserinfoabove">'.sf_display_avatar($posterstatus, $post->user_id, $post->user_email, $post->guest_email)."\n";
	
						if((get_option('sfextprofile')) && ($posterstatus != 'guest'))
						{
							$out.= '<div>'.sf_get_extended_profile_url($post->post_id, $post->user_id, $forumid, $topicid).'</div>'."\n";
						}
	
						$out.= '<p>'.mysql2date(SFTIMES, $post->post_date).' - '.mysql2date(SFDATES, $post->post_date).'<br />'."\n";
						$out.= '<strong>' . $poster . '</strong>'."\n";
						$out.= sf_display_usertype($posterstatus, $post->user_id, $userposts);
						$out.= $postcount.'</p></td>'."\n";
	
						if($admintools)
						{
							$out.= '<td class="sfuserinfoabove"></td>'."\n";
						}
						$out.= '</tr><tr valign="top">'."\n";
	
					} else {
	
						//== DISPLAY TWO COLUMN POST FORMAT
						$out.= '<td class="sfuserinfoside">'."\n";
	
						$out.= '<div><p>'.mysql2date(SFTIMES, $post->post_date).'<br />'.mysql2date(SFDATES, $post->post_date).'</p></div>'."\n";
						$out.= '<p><strong>' . $poster . '</strong></p>'.sf_display_avatar($posterstatus, $post->user_id, $post->user_email, $post->guest_email)."\n";

						if((get_option('sfextprofile')) && ($posterstatus != 'guest'))
						{
							$out.= sf_get_extended_profile_url($post->post_id, $post->user_id, $forumid, $topicid)."\n";
						}
						$out.= '<p>'.sf_display_usertype($posterstatus, $post->user_id, $userposts);
						$out.= '<br />'.$postcount.'</p></td>'."\n";
					}
					
					//== DETERMINE MODERATION STATUS OF POST
					$displaypost = true;
					if($post->post_status == 1)
					{
						if(($is_admin) && (!$admintools))
						{
							$approve_text.= '<span class="sfalignright">'.sf_render_post_editicons($topicid, $forumid, $post->post_id, $post->post_status, $post->user_email, $post->guest_email, $post->post_pinned, $page, true).'</span>'."\n";				
						}

						$approve_text.= '<p><em>'.__("Post Awaiting Approval by Forum Administrator", "sforum").'</em></p>';

						$out.= '<td class="sfpostcontent sfmoderate">'."\n";
						if(($is_admin == false) && (get_option('sfmoderate')))
						{
							$displaypost = false;
						}
					} else {
						$approve_text = '';
						$out.= '<td class="sfpostcontent">'."\n";
					}
					
					//== DISPLAY POST CONTENT
					
					//== DISPLAY CONTENT (EDIT MODE/NORMAL MODE/MODERATION MODE
					if(((isset($_POST['useredit'])) && ($_POST['useredit'] == $post->post_id)) || ((isset($_POST['adminedit'])) && ($_POST['adminedit'] == $post->post_id)))
					{
						$out.= sf_edit_post($post->post_id, $post->post_content, $forumid, $topicid, $page);
					} else {
						
						$out.= '<div class="sfposticonstrip">'."\n";
						$out.= '<a href="#forumtop"><img class="sfalignright" src="'.SFRESOURCES.'top.png" alt="" title="'.__("go to top", "sforum").'" /></a>'."\n";
						$editstrip = true;
	
						//== IS POST PINNED?
						if($post->post_pinned == 1)
						{
							$out.= '<img class="sfalignleft" src="'.SFRESOURCES.'pin.png" alt="" title="'.__("topic pinned", "sforum").'" />'."\n";
						}
										
						//== DO WE SHOW QUOTE AND/OR EDIT ICONS?
						if((($currentmember) || ($currentguest)) || ($displaypost))
						{
							
							//== IF CURRENT MEMBER/GUEST DISPLAY EDIT POST ICON
	
							//== QUOTE?
							if(($displaypost) && ($lockdown == false))
							{
								if((('' == $user_ID) && (get_option('sfallowguests'))) || ('' != $user_ID))
								{
									if(!$topiclock)
									{
										$out.= sf_render_post_user_quoteicon($post->post_id, $username);
									}
								}
							}
							
							//== EDIT?
							if((($currentmember) || ($currentguest)) && ($lockdown == false))
							{
								if(((get_option('sfstopedit')) && ($thispost == $numposts)) || (get_option('sfstopedit') == false))
								{
									$out.= sf_render_post_user_editicon($forumid, $topicid, $pageid, $post->post_id);
								}
							}
						}
						
						$out.= '</div>'."\n";
	
						//== DISPLAY ACTUAL POST CONTENT
						$out.= '<a id="p'.$post->post_id.'"></a>'."\n";
						$out.= '<div id="post'.$post->post_id.'"'."\n";
						
						if($post->post_pinned == 1)
						{
							$out.=' class="sfpinned">'."\n";
						} else {
							$out.='>'."\n";
						}

						if($displaypost)
						{
							$out.= $approve_text.sf_filter_content(stripslashes($post->post_content), $paramvalue);
						} else {
							$out.= sf_filter_content($approve_text, $paramvalue)."\n";
							
							if(($currentguest) || ($currentmember))
							{
								$out.= sf_filter_content(stripslashes($post->post_content), $paramvalue);						
							}
						}
						$out.= '</div><div>'."\n";
						if((get_option('sfusersig')) && ($posterstatus != 'guest'))
						{
							$sig = stripslashes(html_entity_decode(get_usermeta($post->user_id, 'signature'), ENT_QUOTES));
							if(!empty($sig))
							{
								$out.= '<hr /><img class="sfposticon" src="'.SFRESOURCES.'signature.png" alt="" />'."\n";
								$out.= '&nbsp;<small>'.$sig.'</small>'."\n";
							}
						}
						$out.= '</div><br />'."\n";					
					}
					$out.= sf_hook_post_post($topicid, $post->post_id);
					$out.= '</td>'."\n";
	
					//= DISPLAY MANAGEMENT ICONS IF ADMIN & OPTIONED
					if($admintools)
					{
						$out.= '<td class="sfmanageicons">'."\n";
						if($editstrip)
						{
							$out.= '<div class="sfposticonstrip"><p>'.$post->post_id.'</p></div>'."\n";
						}
						$out.= sf_render_post_editicons($topicid, $forumid, $post->post_id, $post->post_status, $post->user_email, $post->guest_email, $post->post_pinned, $page).'</td></tr>'."\n";				
					} else {
						$out.= '</tr>'."\n";
					}
					
					$thispost++;
				}
				$out.= '</table>'."\n";

				//== DISPLAY PAGED LINKS
				$out.= '<table class="sffooter"><tr>'."\n";
				$out.= '<td class="sfpagelinks">'.$thispagelinks.'</td>'."\n";
				$out.='<td class="sfadditemcell">'."\n";
	
				//== DISPLAY REPLY TO POST LINK IF ALLOWED - OR LOCKED ICON IF TOPIC LOCKED
				if((!$topiclock) || ($is_admin))
				{
					if(('' != $user_ID) || (get_option('sfallowguests')))
					{
						if($lockdown == false)
						{
							$out.= '<a class="sficon" onclick="toggleLayer(\'sfentryform\');"><img src="'.SFRESOURCES.'addpost.png" alt="" />'.sf_render_icons("Reply to Post").'</a>'."\n";
						}
					}
				}
	
				$out.= '</td></tr></table>'."\n";
				
			} else {
				$out.= '<div class="sfmessagestrip">'.__("There are No Posts for this Topic", "sforum").'</div>'."\n";
			}
			$out.= '</div>'."\n";
		}
	
		// DISPLAY ADD POST FORM
		if((!$topiclock) || ($is_admin))
		{
			if(('' != $user_ID) || (get_option('sfallowguests')))
			{
				$out.= '<a id="dataform"></a>'."\n";
				$out.= sf_add_post($forumid, $topicid, $topic->topic_name, $user_ID);
			}
		}
	} else {
		update_sfnotice('sfmessage', __("Access Denied", "sforum"));
		$out = sf_render_queued_message();
	}
	return $out;
}

//== DISPLAY NEW POSTS LIST (ADMIN)

function sf_render_new_post_list_admin($newposts)
{
	global $user_login, $user_ID, $is_admin, $lockdown;

	get_currentuserinfo();

	$thisforum='';
	
	if($newposts)
	{
		//== DISPLAY SECTION HEADING
		$out.= '<br /><div class="sfmessagestrip">'."\n";

		if($user_ID == ADMINID)
		{
			$out.= '<form action="'.SFURL.'" method="post" name="removequeue">'."\n";
			$out.= '<input type="hidden" name="doqueue" value="1" />'."\n";
			$out.= '<p><a href="javascript:document.removequeue.submit();"><img class="sfalignleft" src="'.SFRESOURCES.'delete.png" alt="" title="'.__("delete unread post list", "sforum").'" /></a>'."\n";

			$out.= '&nbsp;'.__("New/Unread Posts - Management View", "sforum").'</p>'."\n";
			$out.= '</form>'."\n";
		} else {
			$out.= '<p>'.__("New/Unread Posts - Management View", "sforum").'</p>'."\n";		
		}

		$out.= '</div>'."\n";
		$out.= '<div class="sfblock">'."\n";

		//== DISPLAY NEW POSTS HEADINGS
		$out.= '<table class="sfforumtable">'."\n";
		$out.= '<tr><th colspan="2">'.__("Topic", "sforum").'</th><th>'.__("Posts", "sforum").'</th></tr>'."\n";
	
		foreach($newposts as $newpost)
		{
			if($thisforum != $newpost->forum_id)
			{
				//== DISPLAY FORUM NAME
				$thisforum = $newpost->forum_id;
				$out.= '<tr>'."\n"; 
				$out.= '<td class="sfnewposticoncell"><img src="'. SFRESOURCES .'forum.png" alt="" /></td>'."\n";
				$out.= '<td class="sfnewpostforum"><p>'.sf_get_waiting_forum_url($newpost->forum_id).'</p></td><td class="sfnewpostforum"></td>'."\n";
				$out.= '</tr>'."\n";
			}
			
			//== DISPLAY TOPICS IN FORUM
			$out.= '<tr>'."\n";
			$out.= '<td class="sficoncell"><img src="'. SFRESOURCES .'new.png" alt="" /></td>'."\n";
			$out.= '<td><p>'.sf_get_topic_url_newpost($newpost->forum_id, $newpost->topic_id, $newpost->post_id).'</p></td>'."\n";
			$out.= '<td class="sfcounts">'.$newpost->post_count.'</td>'."\n";
			$out.= '</tr>'."\n";
		}
		$out.= '</table></div><br />'."\n";
	} else {
		$out.='<br /><div class="sfmessagestrip">'.__("There are No Unread Posts", "sforum").'</div>'."\n";
	}

	return $out;
}

//== DISPLAY NEW POSTS LIST (USER)

function sf_render_new_post_list_user($lastvisit)
{
	global $wpdb, $user_login, $user_ID, $is_admin, $lockdown;

	get_currentuserinfo();

	$sfposts = sf_get_simple_recent_post_list(get_option('sfshownewcount'));	

	if($sfposts)
	{
	
		//== DISPLAY SECTION HEADING
		if('' != $user_ID)
		{
			$out.= '<br /><div class="sfmessagestrip"><p>'.__("Most Recent Topics With Unread Posts", "sforum").'</p></div>'."\n";
		} else {
			$out.= '<br /><div class="sfmessagestrip"><p>'.__("Most Recent Posts", "sforum").'</p></div>'."\n";
		}
			
		$out.= '<div class="sfblock">'."\n";

		$out.= '<table class="sfforumtable">'."\n";
		$out.= '<tr><th colspan="2">'.__("Forum/Topic", "sforum").'</th><th>'.__("Started", "sforum").'</th><th>'.__("Last Post", "sforum").'</th><th>'.__("Posts", "sforum").'</th>'."\n";
		$out.= '</tr>'."\n";
		foreach($sfposts as $sfpost)
		{
			$forum = $wpdb->get_row("SELECT forum_name, forum_view FROM ".SFFORUMS." WHERE forum_id = $sfpost->forum_id");	
			if(sf_access_granted($forum->forum_view))
			{

				//== GET FIRST/LAST POSTER DETAILS - NEEDED FOR NEW POST VIEW
				$first = sf_get_first_post_in_topic($sfpost->topic_id);
				$last = sf_get_last_post_in_topic($sfpost->topic_id);

				//== DISPLAY TOPIC ENTRY
				$out.= '<tr>'."\n";				
				$out.= sf_render_topic_icon($sfpost->topic_id, $user_ID, $lastvisit, $last->udate);
				
				$out.= '<td><p>' . stripslashes($forum->forum_name);
				$out.= '<br /><a href="'.sf_url($sfpost->forum_id, $sfpost->topic_id, 0, 0, 0, $last->post_id).'#p'.$last->post_id.'">'.sf_get_topic_name($sfpost->topic_id).'</a></p>'."\n";
				$postcount = sf_get_posts_count_in_topic($sfpost->topic_id);
				$out.= '<small>'.sf_get_post_pagelinks($sfpost->forum_id, $sfpost->topic_id, $postcount).'</small></td>'."\n";
								
				//== DISPLAY FIRST/LAST POSTER DETAILS
				$out.= '<td class="sfuserdetails">'."\n";
				if($first) 
				{
					$poster = sf_filter_user($first->user_id, $first->display_name);
					if(empty($poster)) $poster=stripslashes($first->guest_name);
					$out.= '<p>'.mysql2date(SFDATES, $first->post_date)."-".mysql2date(SFTIMES,$first->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p>'."\n";
				}
				$out.='</td>'."\n";
				
				$out.= '<td class="sfuserdetails">'."\n";
				if($last) 
				{
					$poster = sf_filter_user($last->user_id, $last->display_name);
					if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($last->guest_name));
					$out.= '<p>'.mysql2date(SFDATES, $last->post_date)." - ".mysql2date(SFTIMES,$last->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p>'."\n";
				}
				$out.='</td>'."\n";
				
				//== DISPLAY POST COUNT IN TOPIC
				$out.= '<td class="sfcounts">'.$postcount.'</td>'."\n";
				$out.= '</tr>'."\n";
			}
		}	
		$out.= '</table></div>'."\n";

	} else {
		$out.='<br /><div class="sfmessagestrip">'.__("There are No Recent Unread Posts", "sforum").'</div>'."\n";
	}
	
	return $out;
}

function sf_render_search_all($paramtype, $paramvalue)
{
	global $wpdb;
	
	$searchpage = intval($_GET['search']);
	$search-true;
	
	//== DISPLAY SEARCH HEADING
	$out.= '<div class="sfblock">'."\n";
	$out.= '<div class="sfheading"><table><tr>'."\n";
	$out.= '<td class="sficoncell"><img src="'.SFRESOURCES.'searchicon.png" alt="" /></td>'."\n";
	$out.= '<td><p>'.__("Search All Forums", "sforum").' - ('.stripslashes(sf_deconstruct_search_for_display($paramvalue)).')</p></td>'."\n";
	$out.= '</tr></table></div>'."\n";

	//== GET TOPIC LIST FOR FORUM
	$topics = sf_get_full_topic_search($paramvalue, $searchpage);

	if($topics)
	{

		//== DISPLAY PAGE LINKS	
		$thispagelinks = sf_format_paged_topics('all', 0, true, $searchpage);
		$out.= '<table class="sffooter"><tr>'."\n";
		$out.= '<td class="sfpagelinks">'.$thispagelinks.'</td>'."\n";
		$out.= '</tr></table>'."\n";

		//== DISPLAY TOPIC LIST HEADINGS
		$cols=get_option('sftopiccols');

		$out.= '<table class="sfforumtable">'."\n";
		$out.= '<tr><th colspan="2">'.__("Forum/Topic", "sforum").'</th>'."\n";
		if($cols['first']) $out.= '<th>'.__("Started", "sforum").'</th>'."\n";
		if($cols['last']) $out.= '<th>'.__("Last Post", "sforum").'</th>'."\n";
		if($cols['posts']) $out.= '<th>'.__("Posts", "sforum").'</th>'."\n";
		$out.= '</tr>'."\n";

		foreach($topics as $topic)
		{
			$forum = $wpdb->get_row("SELECT forum_name, forum_view FROM ".SFFORUMS." WHERE forum_id = $topic->forum_id");	
			if(sf_access_granted($forum->forum_view))
			{

				//== GET FIRST/LAST POSTER DETAILS - NEEDED FOR NEW POST VIEW
				$first = sf_get_first_post_in_topic($topic->topic_id);
				$last = sf_get_last_post_in_topic($topic->topic_id);

				//== DISPLAY TOPIC ENTRY
				$out.= '<tr>'."\n";
				$out.= sf_render_topic_icon($topic->topic_id, $user_ID, $lastvisit, $last->udate);
				$out.= '<td><p>' . stripslashes($forum->forum_name)."\n";
				$out.= '<br /><a href="'.sf_url($topic->forum_id, $topic->topic_id, 0, $searchpage, 0, 0, urlencode($paramvalue)).'&amp;ret=all">'.stripslashes($topic->topic_name).'</a>'."\n";
				$postcount = sf_get_posts_count_in_topic($topic->topic_id);
				$out.= '<small>'.sf_get_post_pagelinks($topic->forum_id, $topic->topic_id, $postcount, $searchpage, urlencode($paramvalue), $paramtype).'</small></p></td>'."\n";

				//== DISPLAY FIRST/LAST POSTER DETAILS
				if($first)
				{
					$poster = sf_filter_user($first->user_id, $first->display_name);
					if(empty($poster)) $poster=stripslashes($first->guest_name);
					if($cols['first']) $out.= '<td class="sfuserdetails"><p>'.mysql2date(SFDATES, $first->post_date)."-".mysql2date(SFTIMES,$first->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p></td>'."\n";
				} else {
					if($cols['first']) $out.='<td></td>'."\n";
				}
				
				if($last)
				{
					$poster = sf_filter_user($last->user_id, $last->display_name);
					if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($last->guest_name));
					if($cols['last']) $out.= '<td class="sfuserdetails"><p>'.mysql2date(SFDATES, $last->post_date)." - ".mysql2date(SFTIMES,$last->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p></td>'."\n";
				} else {
					if($cols['last']) $out.='<td></td>'."\n";
				}
				
				//== DISPLAY POST COUNT IN TOPIC
				if($cols['posts']) $out.= '<td class="sfcounts">'.$postcount.'</td>'."\n";
								
				$out.= '</tr>'."\n";
			}
		}	
		$out.= '</table>'."\n";

		//== DISPLAY PAGED LINKS
		$out.= '<table class="sffooter"><tr>'."\n";
		$out.= '<td class="sfpagelinks">'.$thispagelinks.'</td>'."\n";
		$out.= '</tr></table>'."\n";
		$out.= '</div><br />'."\n";
	} else {

		$out.='<br /><div class="sfmessagestrip">'.__("No Matches Found", "sforum").'</div>'."\n";
		$out.='</div>'."\n";
		delete_sfsetting($paramvalue);
	}
	$out.= '<br /><a href="#forumtop"><img class="sfalignright" src="'.SFRESOURCES.'top.png" alt="" title="'.__("go to top", "sforum").'" /></a><br />'."\n";
	
	return $out;
}

?>