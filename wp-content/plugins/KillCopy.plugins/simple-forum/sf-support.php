<?php
/*
Simple Forum 2.1
Support Routines
*/

// === DATABASE QUERY ROUTINES ===========================================

function sf_get_groups_all($groupid=Null)
{
	global $wpdb;
	$where='';
	if(!is_null($groupid)) $where=" WHERE group_id=".$groupid;
	return $wpdb->get_results("SELECT * FROM ".SFGROUPS.$where." ORDER BY group_seq");
}

function sf_get_group_accessrole($groupid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT group_view FROM ".SFGROUPS." WHERE group_id=".$groupid);
}

function sf_get_forum_row($forumid)
{
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM ".SFFORUMS." WHERE forum_id=".$forumid);
}

function sf_get_forums_in_group($groupid)
{
	global $wpdb;
	return $wpdb->get_results("SELECT * FROM ".SFFORUMS." WHERE group_id=".$groupid." ORDER BY forum_seq");
}

function sf_get_forum_status($forumid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT forum_status FROM ".SFFORUMS." WHERE forum_id=".$forumid);
}

function sf_get_forum_accessrole($forumid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT forum_view FROM ".SFFORUMS." WHERE forum_id=".$forumid);
}

function sf_get_topic_row($topicid)
{
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM ".SFTOPICS." WHERE topic_id=".$topicid);
}

function sf_get_topic_sort($topicid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT topic_sort FROM ".SFTOPICS." WHERE topic_id=".$topicid);
}

function sf_get_topics_in_forum($forumid, $currentpage, $searchvalue, $currentsearchpage)
{
	global $wpdb;

	$tpaged=get_option('sfpagedtopics');
	if($tpaged < 1)
	{
		$tpaged=12;
		update_option('sfpagedtopics', $tpaged);
	}

 	if(empty($searchvalue))
	{
		if($currentpage == 1)
		{
			$currentpage = 1;
			$startlimit = 0;
		} else {
			$startlimit = ((($currentpage-1) * $tpaged));
		}
	} else {
		if($currentsearchpage == 1)
		{
			$currentpage = 1;
			$startlimit = 0;
		} else {
			$startlimit = ((($currentsearchpage-1) * $tpaged));
		}
	}

	$limit = ' LIMIT '.$startlimit.', '.$tpaged;

 	if(empty($searchvalue))
	{
		if(get_option('sftopicsort'))
		{
			$sort=' ORDER BY topic_pinned DESC, topic_date DESC';
		} else {
			$sort=' ORDER BY topic_pinned DESC, topic_id DESC';
		}
	} else {
		$sort = ' ORDER BY topic_id DESC';
	}

	if(empty($searchvalue))
	{
		return $wpdb->get_results("SELECT topic_id, topic_name, ".sf_zone_datetime(topic_date).", topic_status, topic_pinned, topic_sort, topic_opened, topic_subs, blog_post_id FROM ".SFTOPICS." WHERE forum_id=".$forumid.$sort.$limit);
	} else {
		$searchvalue=urldecode($searchvalue);
		$searchterm = sf_construct_search_term($searchvalue);

		$sql = "SELECT SQL_CALC_FOUND_ROWS DISTINCT ".SFTOPICS.".topic_id, topic_name, ".sf_zone_datetime(topic_date).", topic_status, topic_pinned, topic_sort, topic_opened, topic_subs, blog_post_id FROM ".SFTOPICS." LEFT JOIN ".SFPOSTS." ON ".SFTOPICS.".topic_id = ".SFPOSTS.".topic_id WHERE MATCH(".SFPOSTS.".post_content) AGAINST ('".$searchterm."' IN BOOLEAN MODE) AND ".SFTOPICS.".forum_id = ".$forumid.$sort.$limit;
		$results = $wpdb->get_results($sql);

		$totalrows = $wpdb->get_var("SELECT FOUND_ROWS()");
		add_sfsetting($searchvalue, $totalrows);
		return $results;
	}
}

function sf_get_full_topic_search($searchvalue, $currentsearchpage)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	$tpaged=get_option('sfpagedtopics');
	if($tpaged < 1)
	{
		$tpaged=12;
		update_option('sfpagedtopics', $tpaged);
	}

	if($currentsearchpage == 1)
	{
		$currentpage = 1;
		$startlimit = 0;
	} else {
		$startlimit = ((($currentsearchpage-1) * $tpaged));
	}

	$limit = ' LIMIT '.$startlimit.', '.$tpaged;
	$sort = ' ORDER BY topic_id DESC';

	$searchvalue=urldecode($searchvalue);
	if(substr($searchvalue, 0, 12) == "sf%members%1")
	{

		$items=explode('%', $searchvalue);
		$userid = substr($items[3], 4, 25);
		$sql="SELECT SQL_CALC_FOUND_ROWS DISTINCT ".SFTOPICS.".topic_id, ".SFTOPICS.".forum_id, topic_name FROM ".SFTOPICS." LEFT JOIN ".SFPOSTS." ON ".SFTOPICS.".topic_id = ".SFPOSTS.".topic_id WHERE ".SFPOSTS.".user_id = ".$userid.$sort.$limit;
	} else {

		$searchterm = sf_construct_search_term($searchvalue);

		$sql="SELECT SQL_CALC_FOUND_ROWS DISTINCT ".SFTOPICS.".topic_id, topic_name, ".SFTOPICS.".forum_id, ".sf_zone_datetime(topic_date).", topic_status, topic_pinned, topic_sort, topic_opened FROM ".SFTOPICS." LEFT JOIN ".SFPOSTS." ON ".SFTOPICS.".topic_id = ".SFPOSTS.".topic_id WHERE MATCH(".SFPOSTS.".post_content) AGAINST ('".$searchterm."' IN BOOLEAN MODE)".$sort.$limit;
	}

	$results = $wpdb->get_results($sql);

	$totalrows = $wpdb->get_var("SELECT FOUND_ROWS()");
	add_sfsetting($searchvalue, $totalrows);
	return $results;
}

function sf_get_posts_in_topic($topicid, $torder, $currentpage)
{
	global $wpdb;
	$order="ASC"; // default
	if(get_option('sfsortdesc')) $order="DESC"; // global override
	if(!is_null($torder)) $order=$torder; // topic override

	$ppaged=get_option('sfpagedposts');
	if($ppaged < 1)
	{
		$ppaged=20;
		update_option('sfpagedposts', $ppaged);
	}

	if($currentpage == 1)
	{
		$currentpage = 1;
		$startlimit = 0;
	} else {
		$startlimit = ((($currentpage-1) * $ppaged));
	}

	$limit = ' LIMIT '.$startlimit.', '.$ppaged;

	return $wpdb->get_results("SELECT post_id, post_content, ".sf_zone_datetime(post_date).", user_id, guest_name, guest_email, post_status, post_pinned, user_login, display_name, user_url, user_email FROM ".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID WHERE topic_id = ".$topicid." ORDER BY post_pinned DESC, post_id ".$order.$limit);
}

function sf_get_recent_post_list($limit)
{
	global $wpdb;
	return $wpdb->get_results("SELECT post_id, ".SFPOSTS.".forum_id, topic_id, ".sf_zone_datetime(post_date).", user_id, guest_name, user_login, user_url FROM (".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID) LEFT JOIN ".SFFORUMS." ON ".SFPOSTS.".forum_id = ".SFFORUMS.".forum_id WHERE forum_view = 'public' ORDER BY post_id DESC LIMIT ".$limit);
}

function sf_get_simple_recent_post_list($limit)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if('' != $user_ID)
	{
		$newpostlist=get_usermeta($user_ID, 'sfnewposts');
		$newpostlist=sf_check_users_newposts($user_ID, $newpostlist);

		if($newpostlist[0] != 0)
		{
			// we have a live user so construct SQL
			$wanted = $limit;
			$select_sql = "SELECT DISTINCT forum_id, topic_id FROM ".SFPOSTS." WHERE";
			$where='';
			if(count($newpostlist) < $limit) $limit = count($newpostlist);
			for($x=0; $x<$limit; $x++)
			{
				$where.= " topic_id=".$newpostlist[$x];
				if($x != $limit-1) $where.= " OR";
			}
			$sql = $select_sql.$where." ORDER BY post_id DESC";
			$recordset = $wpdb->get_results($sql);

			// try and marry the extra count if not enough to satisfy $limit
			if($limit < $wanted)
			{
// 2.1 Patch 2
// Removing exclude on current users user_id
//				$sql="SELECT DISTINCT forum_id, topic_id FROM ".SFPOSTS." WHERE user_id <> ".$user_ID." ORDER BY post_id DESC LIMIT ".$wanted;
				$sql="SELECT DISTINCT forum_id, topic_id FROM ".SFPOSTS." ORDER BY post_id DESC LIMIT ".$wanted;
				$extrarows = $wpdb->get_results($sql);
				if($extrarows)
				{

					for($x=0; $x<count($extrarows); $x++)
					{
						if(!in_array($extrarows[$x]->topic_id, $newpostlist))
						{
							$recordset[]=$extrarows[$x];
						}
						if(count($recordset) == $wanted) break;
					}
				}
			}
			return $recordset;
		}
	}

	if(($user_ID == '') || ($newpostlist[0] == 0))
	{
		return $wpdb->get_results("SELECT DISTINCT forum_id, topic_id FROM ".SFPOSTS." ORDER BY post_id DESC LIMIT ".$limit);
	}
}

function sf_get_first_post_in_topic($topicid)
{
	global $wpdb;
	return $wpdb->get_row("SELECT ".sf_zone_datetime(post_date).", guest_name, user_id, user_login, display_name FROM ".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID WHERE topic_id = ".$topicid." ORDER BY post_id ASC LIMIT 1");
}

function sf_get_last_post_in_topic($topicid)
{
	global $wpdb;
	return $wpdb->get_row("SELECT post_id, ".sf_zone_datetime(post_date).", UNIX_TIMESTAMP(post_date) as udate, guest_name, user_id, user_login, display_name FROM ".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID WHERE topic_id = ".$topicid." ORDER BY post_id DESC LIMIT 1");
}

function sf_get_last_post_in_forum($forumid)
{
	global $wpdb;
	return $wpdb->get_row("SELECT post_id, ".sf_zone_datetime(post_date).", UNIX_TIMESTAMP(post_date) as udate, guest_name, user_id, user_login, display_name FROM ".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID WHERE forum_id = ".$forumid." ORDER BY post_id DESC LIMIT 1");
}

function sf_find_user_in_topic($topicid, $userid)
{
	global $wpdb;
	return $wpdb->get_col("SELECT user_id FROM ".SFPOSTS." WHERE topic_id=".$topicid." AND user_id=".$userid);
}

function sf_get_forum_group($groupid)
{
	global $wpdb;
	return stripslashes($wpdb->get_var("SELECT group_name FROM .".SFGROUPS." WHERE group_id=".$groupid));
}

function sf_get_forum_group_from_forum($forumid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT ".SFGROUPS.".group_name FROM ".SFGROUPS." LEFT JOIN ".SFFORUMS." ON ".SFFORUMS.".group_id = ".SFGROUPS.".group_id WHERE ".SFFORUMS.".forum_id=".$forumid);
}

function sf_get_group_id($forumid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT group_id FROM ".SFFORUMS." WHERE forum_id=".$forumid);
}

function sf_group_exists($groupid)
{
	global $wpdb;
	if($wpdb->get_var("SELECT group_name FROM ".SFGROUPS." WHERE group_id=".$groupid))
	{
		return true;
	}
	return false;
}

function sf_forum_exists($forumid)
{
	global $wpdb;
	if($wpdb->get_var("SELECT forum_name FROM ".SFFORUMS." WHERE forum_id=".$forumid))
	{
		return true;
	}
	return false;
}

function sf_topic_exists($topicid)
{
	global $wpdb;
	if($wpdb->get_var("SELECT topic_name FROM ".SFTOPICS." WHERE topic_id=".$topicid))
	{
		return true;
	}
	return false;
}

// For Paging calcs (non-search mode)
function sf_get_topic_count($forumid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(topic_id) AS cnt FROM ".SFTOPICS." WHERE forum_id=".$forumid);
}

function sf_get_posts_count_in_topic($topicid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(post_id) AS cnt FROM ".SFPOSTS." WHERE topic_id=".$topicid);
}

function sf_get_posts_count_in_forum($forumid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(post_id) AS cnt FROM ".SFPOSTS." WHERE forum_id=".$forumid);
}

function sf_get_forum_name($forumid)
{
	global $wpdb;
	return stripslashes($wpdb->get_var("SELECT forum_name FROM ".SFFORUMS." WHERE forum_id=".$forumid));
}

function sf_get_topic_name($topicid)
{
	global $wpdb;
	return stripslashes($wpdb->get_var("SELECT topic_name FROM ".SFTOPICS." WHERE topic_id=".$topicid));
}

//== General Count stats
function sf_get_table_count($table)
{
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(*) FROM ".$table);
}

function sf_get_forum_count()
{
	global $wpdb;

	$cnt = 0;
	$forums = $wpdb->get_results("SELECT forum_id, forum_view fROM ".SFFORUMS);
	if($forums)
	{
		foreach($forums as $forum)
		{
			if(sf_access_granted($forum->forum_view)) $cnt++;
		}
	}
	return $cnt;
}

function sf_get_member_post_count()
{
	global $wpdb;
	$sql="SELECT SQL_CALC_FOUND_ROWS ID, display_name, CAST(meta_value AS UNSIGNED) AS posts FROM ".SFUSERMETA." LEFT JOIN ".SFUSERS." ON ".SFUSERMETA.".user_id = ".SFUSERS.".ID WHERE meta_key = '".$wpdb->prefix."sfposts' ORDER BY posts DESC LIMIT 0,6;";

	$results = $wpdb->get_results($sql);

	$totalrows = $wpdb->get_var("SELECT FOUND_ROWS()");
	update_sfsetting('membercount', $totalrows);
	return $results;
}

function sf_get_guest_count()
{
	global $wpdb;
	$guests = $wpdb->get_col("SELECT DISTINCT guest_name FROM ".SFPOSTS." WHERE guest_name IS NOT NULL");
	return count($guests);
}

// === WAITING/UNREAD ==================================================

function sf_get_unread_forums()
{
	global $wpdb;
	return $wpdb->get_results("SELECT topic_id, ".SFWAITING.".forum_id, forum_name, group_id, post_count, post_id FROM ".SFFORUMS." LEFT JOIN ".SFWAITING." ON ".SFFORUMS.".forum_id = ".SFWAITING.".forum_id WHERE post_count > 0 ORDER BY forum_id, topic_id");
}

function sf_get_awaiting_approval()
{
	global $wpdb;
	return $wpdb->get_var("SELECT COUNT(post_id) AS cnt FROM ".SFPOSTS." WHERE post_status=1");
}

function sf_remove_from_waiting($topic_id, $userid)
{
	global $wpdb;

	// if moderator and mods posts are to be shown get out quick
	if(sf_is_moderator($userid) && get_option('sfshowmodposts'))
	{
		return;
	}

	if($userid == ADMINID)
	{
		// first check there are no posts still to be moderated in this topic...
		$rows = $wpdb->get_col("SELECT post_status FROM ".SFPOSTS." WHERE topic_id=".$topic_id." AND post_status=1");
		If($rows)
		{
			return;
		} else {
			$wpdb->query("DELETE FROM ".SFWAITING." WHERE topic_id=".$topic_id);
		}
	}
	return;
}

function sf_remove_waiting_queue($userid)
{
	global $wpdb;

	$rows = $wpdb->get_col("SELECT topic_id FROM ".SFWAITING);
	if($rows)
	{
		$queued = array();
		foreach($rows as $row)
		{
			$queued[]=$row;
		}
		foreach($queued as $topic)
		{
			sf_remove_from_waiting($topic, $userid);
		}
	}
	return;
}

function sf_get_topic_url_newpost($forumid, $topicid, $postid)
{
	$out = '<a href="'.sf_url($forumid, $topicid, 0, 0, 0, $postid).'#p'.$postid.'">'.sf_get_topic_name($topicid).'</a>'."\n";
	return $out;
}

function sf_get_waiting_forum_url($forumid)
{
	$out = '<a href="'.sf_url($forumid).'">Forum: '.sf_get_forum_name($forumid).'</a>'."\n";
	return $out;
}

function sf_get_waiting_url($postlist, $right=false)
{
	// check if topic in url - if yes and it is in postlist - remove it.
	$newposts=array();
	$index=0;
	if(isset($_GET['topic']))
	{
		$topicid=intval($_GET['topic']);
		foreach($postlist as $post)
		{
			if($post->topic_id != $topicid)
			{
				$newposts[$index]->topic_id=$post->topic_id;
				$newposts[$index]->forum_id=$post->forum_id;
				$newposts[$index]->post_id=$post->post_id;
				$index++;
			}
		}
	} else {
		$newposts=$postlist;
	}

	if($newposts)
	{
		$out='';
		$cnt = count($newposts);
		if($right)
		{
			$out.= '<span id="newpostbottom" class="alignright">';
		} else {
			$out.= '<span id="newposttoptop">';
		}
		$out.= '<a class="sficon" href="'.sf_url(0, 0, 0, 0, 1).'"><img src="'. SFRESOURCES .'newpost.png" alt="" title="'.__("List New Posts", "sforum").'" />'.sf_render_icons("New Posts").'</a>'.'&nbsp;<span class="sfnewcount">'.$cnt.'</span>'."\n";
		$out.= '<a class="sficon" href="'.sf_url($newposts[0]->forum_id, $newposts[0]->topic_id, 0, 0, 0, $newposts[0]->post_id).'#p'.$newposts[0]->post_id.'"><img src="'. SFRESOURCES .'gonew.png" alt="" title="'.__("Go To First Post", "sforum").'" /></a>'."\n";
		//if($right) $out.='</span>';
		$out.='</span>';
	}
	return $out;
}

// === FORMAT/DISPLAY ROUTINES =========================================

function sf_get_forum_url($forumid, $forumname, $forumstatus)
{
	$lockicon='';

	if($forumstatus == 1)
	{
		$lockicon = '<img src="'.SFRESOURCES.'locked.png" alt="" />';
	}

	$out = '<a href="'.sf_url($forumid, 0, 1).'">'.stripslashes($forumname).'</a>'.$lockicon."\n";
	return $out;
}

function sf_get_topic_pagelinks($forumid, $topiccount)
{
	$topicpage=get_option('sfpagedtopics');
	if($topicpage >= $topiccount) return '';

// 2.1 Patch 1
	$out = '&nbsp;&nbsp;('.__("Page:", "sforum").' ';

	$totalpages=($topiccount / $topicpage);
	if(!is_int($totalpages)) $totalpages=intval($totalpages)+1;
	if($totalpages > 4)
	{
		$maxcount=4;
	} else {
		$maxcount=$totalpages;
	}

	for($x = 1; $x <= $maxcount; $x++)
	{
		$out.= '<a href="'.sf_url($forumid, 0, $x).'">|'.$x.'</a>'."\n";
	}

	if($totalpages > 4)
	{
		$out.= '&rarr;<a href="'.sf_url($forumid, 0, $totalpages).'">'.$totalpages.' </a>'."\n";
	}
	return $out.')';
}

function sf_get_forum_search_url($forumid, $searchpage, $searchvalue)
{
	$out = '<a class="sficon" href="'.sf_url($forumid, 0, 0, $searchpage, 0, 0, $searchvalue).'">'."\n";
	return $out;
}

function sf_get_topic_url($forumid, $topicid, $topicname, $topicstatus, $topicpinned, $search, $searchpage, $paramvalue, $forumlock, $blogpostid)
{
	$searchtext='';
	$lockicon='';
	$pinicon='';
	$bloglink='';

	if($search == true)
	{
		$searchtext = $searchpage;
		$searchvalue=$paramvalue;
	}

	if(($topicstatus == 1) || ($forumlock))
	{
		$lockicon = '<img src="'.SFRESOURCES.'locked.png" alt="" />';
	}

	if($topicpinned == 1)
	{
		$pinicon = '<img src="'.SFRESOURCES.'pin.png" alt="" />';
	}

	if($blogpostid != 0)
	{
		$bloglink = '<a href="'.get_permalink($blogpostid).'"><img src="'.SFRESOURCES.'bloglink.png" alt="" /></a>';
	}

	if((isset($_GET['page'])) && (intval($_GET['page']) <> 1))
	{
		$tp = '&amp;tp='.intval($_GET['page']);
	} else {
		$tp = '';
	}

	$out = '<a href="'.sf_url($forumid, $topicid, 1, $searchtext, 0, 0, $searchvalue).$tp.'">'.stripslashes($topicname).'</a>'.$pinicon.$lockicon.$bloglink."\n";
	return $out;
}

function sf_get_topic_url_dashboard($forumid, $topicid)
{
	$out = '<a href="'.sf_url($forumid, $topicid).'"><img src="'. SFRESOURCES .'new.png" alt="" />&nbsp;&nbsp;'.sf_get_topic_name($topicid).'</a>'."\n";
	return $out;
}

function sf_get_post_pagelinks($forumid, $topicid, $postcount, $searchpage=0, $paramvalue=0, $paramtype='')
{
	$postpage=get_option('sfpagedposts');
	if($postpage >= $postcount) return '';

// 2.1 Patch 1
	$out = '&nbsp;&nbsp;('.__("Page:", "sforum").' ';

	$totalpages=($postcount / $postpage);
	if(!is_int($totalpages)) $totalpages=intval($totalpages)+1;

	if($totalpages > 4)
	{
		$maxcount=4;
	} else {
		$maxcount=$totalpages;
	}

	if($searchpage != 0)
	{
		if($paramtype == 'SA')
		{
			$ret = '&amp;ret=all';
		} else {
			$ret = '';
		}
	}

	if((isset($_GET['page'])) && (intval($_GET['page']) <> 1))
	{
		$tp = '&amp;tp='.intval($_GET['page']);
	} else {
		$tp = '';
	}

	for($x = 1; $x <= $maxcount; $x++)
	{
		$out.= '<a href="'.sf_url($forumid, $topicid, $x, $searchpage, 0, 0, $paramvalue).$ret.$tp.'">|'.$x.'</a>'."\n";
	}

	if($totalpages > 4)
	{
		$out.= '&rarr;<a href="'.sf_url($forumid, $topicid, $totalpages, $searchpage, 0, 0, $paramvalue).$ret.$tp.'">'.$totalpages.' </a>'."\n";
	}
	return $out.')';
}

function sf_get_base_topic_url($forumid, $topicid)
{
	$out = '<a href="'.sf_url($forumid, $topicid).'">'.sf_get_topic_name($topicid).'</a>'."\n";
	return $out;
}

function sf_render_topic_editicons($topic, $forum, $page)
{
	$topicid=$topic->topic_id;
	$forumid=$forum->forum_id;

	$locktext=__("Lock this Topic", "sforum");
	if($topic->topic_status) $locktext=__("Unlock this Topic", "sforum");
	$pintext=__("Pin this Topic", "sforum");
	if($topic->topic_pinned) $pintext=__("Unpin this Topic", "sforum");

	$order="ASC"; // default
	if(get_option('sfsortdesc')) $order="DESC"; // global override
	if($topic->topic_sort) $order=$topic->topic_sort; // topic override
	if($order == "ASC")
	{
		$sorttext=__("Sort Most Recent Posts to Top", "sforum");
	} else {
		$sorttext=__("Sort Most Recent Posts to Bottom", "sforum");
	}

	$boxname = 'tool'.$topicid;
	$out = '<a class="sficon" href="" onclick="return sfboxOverlay(this, \''.$boxname.'\', \'bottom\');"><img src="'.SFRESOURCES.'tools.png" alt="" title="'.__("show edit tools", "sforum").'" /></a>'."\n";
	$out.= '<div id="'.$boxname.'" style="display: none;">'."\n";

	$out.= '<form action="'.sf_url($forumid, 0, $page).'" method="post" name="topiclock'.$topicid.'">'."\n";
	$out.= '<input type="hidden" name="locktopic" value="'.$topicid.'" />'."\n";
	$out.= '<input type="hidden" name="locktopicaction" value="'.$locktext.'" />'."\n";
	$out.= '<a href="javascript:document.topiclock'.$topicid.'.submit();"><img src="'.SFRESOURCES.'locked.png" alt="" title="'.$locktext.'" /></a><br />'."\n";
	$out.= '</form>'."\n";

	$out.= '<form action="'.sf_url($forumid, 0, $page).'" method="post" name="topicpin'.$topicid.'">'."\n";
	$out.= '<input type="hidden" name="pintopic" value="'.$topicid.'" />'."\n";
	$out.= '<input type="hidden" name="pintopicaction" value="'.$pintext.'" />'."\n";
	$out.= '<a href="javascript:document.topicpin'.$topicid.'.submit();"><img src="'.SFRESOURCES.'pin.png" alt="" title="'.$pintext.'" /></a><br />'."\n";
	$out.= '</form>'."\n";

	$out.= '<form action="'.sf_url($forumid, 0, $page).'#topicedit" method="post" name="edittopic'.$topicid.'">'."\n";
	$out.= '<input type="hidden" name="topicedit" value="'.$topicid.'" />'."\n";
	$out.= '<a href="javascript:document.edittopic'.$topicid.'.submit();"><img src="'.SFRESOURCES.'edit.png" alt="" title="'.__("edit this topic title", "sforum").'" /></a><br />'."\n";
	$out.= '</form>'."\n";

	$out.= '<form action="'.sf_url($forumid, 0, $page).'" method="post" name="topicsort'.$topicid.'">'."\n";
	$out.= '<input type="hidden" name="sorttopic" value="'.$topicid.'" />'."\n";
	$out.= '<input type="hidden" name="sorttopicaction" value="'.$sorttext.'" />'."\n";
	$out.= '<a href="javascript:document.topicsort'.$topicid.'.submit();"><img src="'.SFRESOURCES.'sort.png" alt="" title="'.$sorttext.'" /></a><br />'."\n";
	$out.= '</form>'."\n";

	$out.= '<form action="'.sf_url($forumid, 0, $page).'" method="post" name="topickill'.$topicid.'">'."\n";
	$out.= '<input type="hidden" name="killtopic" value="'.$topicid.'" />'."\n";
	$out.= '<a href="javascript: if(confirm(\'Are%20you%20sure%20you%20want%20to%20delete%20this%20Topic?\')) {document.topickill'.$topicid.'.submit();}"><img src="'.SFRESOURCES.'delete.png" alt="" title="'.__("delete this topic", "sforum").'" /></a><br />'."\n";
	$out.= '</form>'."\n";

	$out.= '<form action="'.SFURL.'" method="post" name="topicmove'.$topicid.'">'."\n";
	$out.= '<input type="hidden" name="movetopic" value="'.$topicid.'" />'."\n";
	$out.= '<input type="hidden" name="forum" value="'.intval($_GET['forum']).'" />'."\n";
	$out.= '<input type="hidden" name="page" value="'.intval($_GET['page']).'" />'."\n";
	$out.= '<a href="javascript:document.topicmove'.$topicid.'.submit();"><img src="'.SFRESOURCES.'move.png" alt="" title="'.__("move this topic", "sforum").'" /></a><br />'."\n";
	$out.= '</form>'."\n";

	if($topic->blog_post_id != 0)
	{
		$out.= '<form action="'.sf_url($forumid, 0, $page).'" method="post" name="breaklink'.$topicid.'">'."\n";
		$out.= '<input type="hidden" name="linkbreak" value="'.$topicid.'" />'."\n";
		$out.= '<input type="hidden" name="blogpost" value="'.$topic->blog_post_id.'" />'."\n";
		$out.= '<a href="javascript:document.breaklink'.$topicid.'.submit();"><img src="'.SFRESOURCES.'breaklink.png" alt="" title="'.__("break topic link to blog post", "sforum").'" /></a><br />'."\n";
		$out.= '</form>'."\n";
	}

	$out.= "</div>"."\n";

	return $out;
}

function sf_render_post_editicons($topicid, $forumid, $postid, $poststatus, $useremail, $guestemail, $pinned, $page, $approve_only=false)
{
	if($approve_only == false)
	{
		if($pinned)
		{
			$pintext = __("Unpin this Post", "sforum");
		} else {
			$pintext = __("Pin this Post", "sforum");
		}

		$boxname = 'tool'.$postid;
		$out = '<a class="sficon" href="" onclick="return sfboxOverlay(this, \''.$boxname.'\', \'bottom\');"><img src="'.SFRESOURCES.'tools.png" alt="" title="'.__("show edit tools", "sforum").'" /></a>'."\n";
		$out.= '<div id="'.$boxname.'" style="display: none;">'."\n";
	}
	if($poststatus == 1)
	{
		$out.= '<form action="'.sf_url($forumid, $topicid, $page).'#p'.$postid.'" method="post" name="postapprove'.$postid.'">'."\n";
		$out.= '<input type="hidden" name="approvepost" value="'.$postid.'" />'."\n";
		$out.= '<a href="javascript:document.postapprove'.$postid.'.submit();"><img src="'.SFRESOURCES.'approve.png" alt="" title="'.__("approve this post", "sforum").'" /></a>';
		if($approve_only == false) $out.='<br />';
		$out.= '</form>'."\n";
	}
	if($approve_only == false)
	{
		$email=$useremail;
		if(empty($email)) $email=$guestemail;
		$out.= '<form action="">'."\n";
		$out.= '<a href="http://" onclick="prompt(\'Users Email Address\',\''.$email.'\');return false;"><img src="'.SFRESOURCES.'email.png" alt="" title="'.__("show users email address", "sforum").'" /></a><br />'."\n";
		$out.= '</form>'."\n";

		$out.= '<form action="'.sf_url($forumid, $topicid, $page).'" method="post" name="postpin'.$postid.'">'."\n";
		$out.= '<input type="hidden" name="pinpost" value="'.$postid.'" />'."\n";
		$out.= '<input type="hidden" name="pinpostaction" value="'.$pintext.'" />'."\n";
		$out.= '<a href="javascript:document.postpin'.$postid.'.submit();"><img src="'.SFRESOURCES.'pin.png" alt="" title="'.$pintext.'" /></a><br />'."\n";
		$out.= '</form>'."\n";

		$out.= '<form action="'.sf_url($forumid, $topicid, $page).'#postedit" method="post" name="admineditpost'.$postid.'">'."\n";
		$out.= '<input type="hidden" name="adminedit" value="'.$postid.'" />'."\n";
		$out.= '<a href="javascript:document.admineditpost'.$postid.'.submit();"><img src="'.SFRESOURCES.'edit.png" alt="" title="'.__("edit this post", "sforum").'" /></a><br />'."\n";
		$out.= '</form>'."\n";

		$out.= '<form action="'.sf_url($forumid, $topicid, $page).'" method="post" name="postkill'.$postid.'">'."\n";
		$out.= '<input type="hidden" name="killpost" value="'.$postid.'" />'."\n";
		$out.= '<input type="hidden" name="killposttopic" value="'.$topicid.'" />'."\n";
		$out.= '<a href="javascript: if(confirm(\'Are%20you%20sure%20you%20want%20to%20delete%20this%20Post?\')) {document.postkill'.$postid.'.submit();}"><img src="'.SFRESOURCES.'delete.png" alt="" title="'.__("delete this post", "sforum").'" /></a><br /><br />'."\n";
		$out.= '</form>'."\n";

		$out.= '</div>'."\n";
	}
	return $out;
}

function sf_render_topic_icon($topicid, $userid, $lastvisit, $lastudate)
{
	$icon = 1;

	if('' == $userid)
	{
		if(($lastvisit > 0) && ($lastvisit < $lastudate)) $icon = 2;
	} else {
		if(sf_is_in_users_newposts($topicid, $userid)) $icon = 2;
		if(($icon == 1) && (sf_find_user_in_topic($topicid, $userid))) $icon = 3;
		if(($icon == 2) && (sf_find_user_in_topic($topicid, $userid))) $icon = 4;
	}
	switch($icon)
	{
		case 1:
			$topicicon = 'topic.png';
			break;
		case 2:
			$topicicon = 'topicnew.png';
			break;
		case 3:
			$topicicon = 'topicuser.png';
			break;
		case 4:
			$topicicon = 'topicnewuser.png';
			break;
	}

	return '<td class="sficoncell"><img src="'. SFRESOURCES . $topicicon. '" alt="" /></td>'."\n";
}

function sf_get_extended_profile_url($postid, $userid, $forumid, $topicid)
{
	$out.= '<form class="proflink" action="'.sf_url($forumid, $topicid).'" method="post" name="extprofile'.$postid.'">'."\n";
	$out.= '<input type="hidden" name="profileext" value="'.$userid.'" />'."\n";
	$out.= '<a href="javascript:document.extprofile'.$postid.'.submit();"><img src="'.SFRESOURCES.'user.png" alt="" title="'.__("view user profile", "sforum").'" /></a>'."\n";
	$out.= '</form>'."\n";
	return $out;
}

function sf_render_icons($icontext)
{
	global $icons;

	if($icons[$icontext] == 1)
	{
		return __($icontext, "sforum");
	} else {
		return '';
	}
}

function sf_is_moderator($userid)
{
	$moderators = explode(';',get_option('sfmodusers'));
	$ismod = false;

	if($moderators)
	{
		foreach($moderators as $moderator)
		{
			if(!empty($moderator))
			{
				if($moderator == $userid)
				{
					$ismod = true;
				}
			}
		}
	}
	return $ismod;
}

function sf_admin_status()
{
	global $user_ID;

	get_currentuserinfo();
	if(($user_ID == ADMINID) || (sf_is_moderator($user_ID)))
	{
		return true;
	} else {
		return false;
	}
}

function sf_format_paged_topics($forumid, $currentpage, $search, $currentsearchpage)
{
	$tpaged=get_option('sfpagedtopics');

	if(!isset($currentpage)) $currentpage = 1;
	if(($search) && (!isset($currentsearchpage))) $currentsearchpage = 1;
	$cpage=$currentpage;
	if($search)
	{
		$cpage=$currentsearchpage;
		$searchvalue=urldecode($_GET['value']);
	}

	if($forumid == 'all')
	{
		$topiccount = get_sfsetting($searchvalue);
		delete_sfsetting($searchvalue);
	} else {
		if($search)
		{
			$topiccount = get_sfsetting($searchvalue);
			delete_sfsetting($searchvalue);
		} else {
			$topiccount = sf_get_topic_count($forumid);
		}
	}

	$totalpages = ($topiccount / $tpaged);
	if(!is_int($totalpages)) $totalpages = (intval($totalpages)+1);
// 2.1 Patch 1
//	$baseurl = '<a href="'.SFURL.'?forum='.$forumid;
	$baseurl = '<a href="'.sf_get_sfurl_plus_amp(SFURL).'forum='.$forumid;
	if($search)
	{
		$baseurl.= '&amp;value='.urlencode($searchvalue);
	}

	$out= __("Page:", "sforum").' ';

	$out.= sf_pn_next($cpage, $search, $totalpages, $baseurl);
	if($search)
	{
		$out.= '&nbsp;&nbsp;' . $baseurl. '&amp;search='.$cpage . '" class="current">'.$cpage.'</a>'. '&nbsp;&nbsp;'."\n";
	} else {
		$out.= '&nbsp;&nbsp;' . $baseurl. '&amp;page='.$cpage . '" class="current">'.$cpage.'</a>'. '&nbsp;&nbsp;'."\n";
	}
	$out.= sf_pn_previous($cpage, $search, $totalpages, $baseurl);

	return $out;
}

function sf_pn_next($cpage, $search, $totalpages, $baseurl)
{
	$start = ($cpage - SFPNSHOW);
	if($start < 1) $start = 1;
	$end = ($cpage - 1);
	$out='';

	if($start > 1)
	{
		$out.= sf_pn_url($cpage, 1, $search, $baseurl);
		$out.= sf_pn_url($cpage, $cpage-1, $search, $baseurl, 'Previous');

	}

	if($end > 0)
	{
		for($i = $start; $i <= $end; $i++)
		{
			$out.= sf_pn_url($cpage, $i, $search, $baseurl);
		}
	} else {
		$end = 0;
	}

	return $out;
}

function sf_pn_previous($cpage, $search, $totalpages, $baseurl)
{
	$start = ($cpage + 1);
	$end = ($cpage + SFPNSHOW);
	if($end > $totalpages) $end = $totalpages;
	$out='';

	if($start <= $totalpages)
	{
		for($i = $start; $i <= $end; $i++)
		{
			$out.= sf_pn_url($cpage, $i, $search, $baseurl);
		}
		if($end < $totalpages)
		{
			$out.= sf_pn_url($cpage, $cpage+1, $search, $baseurl, 'Next');
			$out.= sf_pn_url($cpage, $totalpages, $search, $baseurl);
		}
	} else {
		$start = 0;
	}

	return $out;
}

function sf_pn_url($cpage, $thispage, $search, $baseurl, $arrow='None')
{
	$out='';

	if($search)
	{
		$out.= $baseurl . '&amp;search='.$thispage;
	} else {
		$out.= $baseurl . '&amp;page='.$thispage;
	}

	Switch($arrow)
	{
		case 'None':
			$out.= '">'.$thispage.'</a>';
			break;
		case 'Previous':
			$out.= '" class="sfpointer"><img src="'.SFRESOURCES.'arrowl.png" alt="" /></a>'."\n";
			break;
		case 'Next':
			$out.= '" class="sfpointer"><img src="'.SFRESOURCES.'arrowr.png" alt="" /></a>'."\n";
			break;
	}
	return $out;
}

function sf_format_paged_posts($forumid, $topicid, $currentpage)
{
	if(!isset($currentpage))
	{
		$currentpage = 1;
	}

	$ppaged=get_option('sfpagedposts');

	$postcount = sf_get_posts_count_in_topic($topicid);
	$totalpages = ($postcount / $ppaged);
	if(!is_int($totalpages))
	{
		$totalpages = (intval($totalpages)+1);
	}

	if((isset($_GET['tp'])) && (intval($_GET['tp']) <> 1))
	{
		$tp = '&amp;tp='.intval($_GET['tp']);
	} else {
		$tp = '';
	}

	$out= __("Page:", "sforum").'  ';
	$baseurl = '<a href="'.sf_url($forumid, $topicid);

	for($i = 0; $i < $totalpages; $i++)
	{
		$out.= $baseurl . '&amp;page='.($i+1).$tp;

		if(($i+1) == $currentpage)
		{
			$out.= '" class="current">'.($i+1).'</a>'."\n";
		} else {
			$out.= '">'.($i+1).'</a>'."\n";
		}
	}
	return $out."\n";
}

function sf_filter_user($userid, $username)
{
	if($userid == ADMINID)
	{
		return ADMINNAME;
	} else {
		return stripslashes($username);
	}
}

function sf_get_user_url($userid, $username, $userurl)
{
	global $wpdb;

	$poster=sf_filter_user($userid, $username);

	if(!empty($userurl))
	{
		$out='<a href="'.$userurl.'">'.$poster.'</a>'."\n";
	} else {
		$out=$poster;
	}
	return $out."\n";
}

function sf_access_granted($itemrole)
{
	if($itemrole == 'public') return true;
	if(sf_admin_status()) return true;
	if(sf_user_allowed($itemrole)) return true;
	return false;
}

function sf_user_allowed($itemrole)
{
	global $user_ID;

	get_currentuserinfo();

	if(($itemrole == 'public') || (empty($itemrole))) return true;

	// if not a logged in user disallow
	if('' == $user_ID) return false;

	// Get level of item ($itemrole)
	$itemlevel = sf_get_level($itemrole);

	// Get level of users role
	$userrole=sf_get_user_role($user_ID);
	$userlevel = sf_get_level($userrole);

	if($userlevel >= $itemlevel)
	{
		return true;
	} else {
		return false;
	}
}

function sf_get_user_role($userid)
{
	global $wp_roles;

	foreach ($wp_roles->get_names() as $rolename => $roledescription)
	{
	    if (current_user_can($rolename)) {
	        $usersroles[] = $rolename;
	    }
	}
	return $usersroles[0];
}

function sf_get_level($role)
{
	if($role=='public') return 0;

	// return the level cap of the item to be viewed
	$caps=get_role($role);

	$caps=$caps->capabilities;
	$level=0;
	$x=10;

	if((empty($caps)) || (!isset($caps)))
	{
		update_sfnotice('sfmessage', sprintf(__('Role Type %s could not be verified', "sforum"). $role));
		return 0;
	}

	for($x; $x!=0; $x--)
	{
		if(array_key_exists('level_'.$x, $caps))
		{
			$level=$x;
			break;
		}
	}
	return $level;
}

// == DB RECORD SAVES ==================

function sf_save_edited_post()
{
	global $wpdb;

	$postcontent = $wpdb->escape($_POST['editpostcontent']);

	$sql = 'UPDATE '.SFPOSTS.' SET post_content="'.$postcontent.'" WHERE post_id='.$_POST['pid'];

	if($wpdb->query($sql) === false)
	{
		update_sfnotice('sfmessage', __("Update Failed!", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Updated Post Saved", "sforum"));
	}
	return;
}

function sf_save_edited_topic()
{
	global $wpdb;

	$topicname = $wpdb->escape($_POST['topicname']);

	$sql = 'UPDATE '.SFTOPICS.' SET topic_name="'.$topicname.'" WHERE topic_id='.$_POST['tid'];

	if($wpdb->query($sql) === false)
	{
		update_sfnotice('sfmessage', __("Update Failed!", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Updated Topic Title Saved", "sforum"));
	}
	return;
}

function sf_save_profile()
{
	global $wpdb, $user_ID, $user_login, $user_identity;

	check_admin_referer('forum-userform_profile');

	$inc_pw = false;

	get_currentuserinfo();

	// if check field has a value in it return gracefully
	if($_POST['username'] != $user_login)
	{
		update_sfnotice('sfmessage', __('Profile Update Aborted', "sforum"));
		return;
	}

	if(empty($_POST['email']))
	{
		update_sfnotice('sfmessage', __('Email Address is Required', "sforum"));
		return;
	}

	if(!is_email($_POST['email']))
	{
		update_sfnotice('sfmessage', sprintf(__('%s is in invaid email address', "sforum"), $_POST['email']));
		return;
	} else {
		$email = $wpdb->escape($_POST['email']);
	}

	// only the email is required so save the other bits first

	if(get_option('sfextprofile'))
	{
		update_usermeta($user_ID, 'first_name', $wpdb->escape($_POST['first_name']));
		update_usermeta($user_ID, 'last_name', $wpdb->escape($_POST['last_name']));
		update_usermeta($user_ID, 'aim', $wpdb->escape($_POST['aim']));
		update_usermeta($user_ID, 'yim', $wpdb->escape($_POST['yim']));
		update_usermeta($user_ID, 'jabber', $wpdb->escape($_POST['jabber']));
		update_usermeta($user_ID, 'msn', $wpdb->escape($_POST['msn']));
		update_usermeta($user_ID, 'description', $wpdb->escape($_POST['description']));
		update_usermeta($user_ID, 'signature', $wpdb->escape($_POST['signature']));
	}

	if(!empty($_POST['url']))
	{
		$url=clean_url($_POST['url']);
		$url=$wpdb->escape($_POST['url']);
	}

	$display_name = $wpdb->escape($_POST['display_name']);
	if(empty($display_name)) $display_name = $user_login;
	$user_identity = $display_name;

	if(!empty($_POST['oldone']))
	{
		// check old password is correct
		$currentp = $wpdb->get_var("SELECT user_pass FROM ".SFUSERS." WHERE ID=".$user_ID);

		if($currentp != md5($_POST['oldone']))
		{
			update_sfnotice('sfmessage', __('Current Password is Incorrect', "sforum"));
			return;
		}
		if((empty($_POST['newone1'])) || (empty($_POST['newone2'])))
		{
			update_sfnotice('sfmessage', __('New Password must be Entered Twice', "sforum"));
			return;
		}
		if($_POST['newone1'] != $_POST['newone2'])
		{
			update_sfnotice('sfmessage', __('The Two New Passwords entered are Not the Same!', "sforum"));
			return;
		}
		// OK to save new pw
		$newp = md5($_POST['newone1']);
		$inc_pw = true;
	}

	$sql = 'UPDATE '.SFUSERS.' SET ';
	$sql.= 'user_url="'.$url.'", ';
	$sql.= 'display_name="'.$display_name.'", ';
	$sql.= 'user_email="'.$email.'" ';
	if($inc_pw)
	{
		$sql.= ', user_pass="'.$newp.'" ';
	}
	$sql.= 'WHERE ID='.$user_ID.';';

	$wpdb->query($sql);

	$mess=__("Profile Updated. ", "sforum");

	if($_FILES['avatar']['error'] == 4)
	{
		update_sfnotice('sfmessage', __('Profile Record: ', "sforum").$mess);
	} else {
		update_sfnotice('sfmessage', sf_upload_avatar($user_ID).__(' - Profile Record: ', "sforum").$mess);
	}
	return;
}

function sf_save_subscription($topicid, $userid, $retmessage)
{
	global $wpdb;

	if(('' == $userid) || (!get_option('sfsubscriptions'))) return;

	// is user already subscribed to this topic?
	if(sf_is_subscribed($userid, $topicid))
	{
		if($retmessage)
		{
			update_sfnotice('sfmessage', __('You are already subscribed to this topic', "sforum"));
			return;
		}
	}

	// OK  -subscribe them to the topic
	$list=$wpdb->get_var("SELECT topic_subs FROM ".SFTOPICS." WHERE topic_id=".$topicid);
	if(empty($list))
	{
		$list = $userid;
	} else {
		$list.= '@'.$userid;
	}
	$wpdb->query("UPDATE ".SFTOPICS." SET topic_subs = '".$list."' WHERE topic_id=".$topicid);

	// plus note the topic against their usermeta record
	$list = get_user_option('sfsubscribe', $userid);

	if(empty($list))
	{
		$list = $topicid;
	} else {
		$list.= '@'.$topicid;
	}
	update_user_option($userid, 'sfsubscribe', $list);

	if($retmessage)
	{
		update_sfnotice('sfmessage', __('Subscription added', "sforum"));
	}
	return;
}

function sf_remove_subscription($topic, $userid)
{
	global $wpdb;

	$list = $wpdb->get_var("SELECT topic_subs FROM ".SFTOPICS." WHERE topic_id=".$topic);
	if($list == $userid)
	{
		$newlist = '';
	} else {
		$list = explode('@', $list);
		foreach($list as $i)
		{
			if($i != $userid)
			{
				$newlist = $i.'@';
			}
		}
		$newlist = substr($newlist, 0, strlen($newlist)-1);
	}
	$wpdb->query("UPDATE ".SFTOPICS." SET topic_subs = '".$newlist."' WHERE topic_id=".$topic);
	return;
}

function sf_update_subscriptions($userid)
{
	global $wpdb;

	check_admin_referer('forum-userform_subs');

	// do it the easy way - remove everything and then rebuild list
	$list = get_user_option('sfsubscribe', $userid);
	if(!empty($list))
	{
		$list = explode('@', $list);
		foreach($list as $topic)
		{
			sf_remove_subscription($topic, $userid);
		}
		update_user_option($userid, 'sfsubscribe', '');
	}
	if(!empty($_POST['topic']))
	{
		foreach($_POST['topic'] as $topic)
		{
			sf_save_subscription($topic, $userid, false);
		}
	}
	update_sfnotice('sfmessage', __('Subscriptions Updated', "sforum"));
	return;
}

function sf_is_subscribed($userid, $topicid)
{
	global $wpdb;

	$list=$wpdb->get_var("SELECT topic_subs FROM ".SFTOPICS." WHERE topic_id=".$topicid);

	if(empty($list))
	{
		return false;
	}
	$found = false;
	$list = explode('@', $list);
	foreach($list as $i)
	{
		if($i == $userid) $found=true;
	}
	return $found;
}

function sf_user_subscribed_icon($userid, $subs)
{
	if('' == $userid) return;

	$out = '';
	if(!empty($subs))
	{
		$sublist = explode('@', $subs);
		foreach($sublist as $i)
		{
			if($i == $userid) $out = '<small><img class="sficonkey" src="'. SFRESOURCES .'usersubscribed.png" alt="" title="'.__("You are subscribed to this topic", "sforum").'" /></small>';
		}
		if($userid == ADMINID) $out = '<small><img class="sficonkey" src="'. SFRESOURCES .'usersubscribed.png" alt="" title="'.__("This topic has User Subscriptions", "sforum").'" /></small>';
	}
	return $out;
}

// === TOPIC MANAGEMENT ROUTINES

function sf_icon_toggle()
{
	$state=get_option('sfedit');
	if($state)
	{
		$state=false;
	} else {
		$state=true;
	}
	update_option('sfedit', $state);
	return;
}

function sf_lock_topic_toggle($topicid)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}

	if($_POST['locktopicaction'].$topicid == get_sfsetting('sfaction')) return;

	$status = $wpdb->get_var("SELECT topic_status FROM ".SFTOPICS." WHERE topic_id=".$topicid);
	if($status == 1)
	{
		$status = 0;
	} else {
		$status = 1;
	}

	$wpdb->query("UPDATE ".SFTOPICS." SET topic_status = ".$status." WHERE topic_id=".$topicid);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Topic Lock Toggle Failed", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Topic Lock Toggled", "sforum"));
		update_sfsetting('sfaction', $_POST['locktopicaction'].$topicid);
	}
	return;
}

function sf_pin_topic_toggle($topicid)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}

	if($_POST['pintopicaction'].$topicid == get_sfsetting('sfaction')) return;

	$status = $wpdb->get_var("SELECT topic_pinned FROM ".SFTOPICS." WHERE topic_id=".$topicid);
	if($status == 1)
	{
		$status = 0;
	} else {
		$status = 1;
	}

	$wpdb->query("UPDATE ".SFTOPICS." SET topic_pinned = ".$status." WHERE topic_id=".$topicid);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Topic Pin Toggle Failed", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Topic Pin Toggled", "sforum"));
		update_sfsetting('sfaction', $_POST['pintopicaction'].$topicid);
	}
	return;
}

function sf_sort_topic_toggle($topicid)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}
	if($_POST['sorttopicaction'].$topicid == get_sfsetting('sfaction')) return;

	$currentsort='ASC';
	if(get_option('sfsortdesc')) $currentsort='DESC';
	$overridesort=$wpdb->get_var("SELECT topic_sort FROM ".SFTOPICS." WHERE topic_id=".$topicid);
	if(!is_null($overridesort)) $currentsort=$overridesort;

	if($currentsort == 'ASC')
	{
		$newsort='DESC';
	} else {
		$newsort='ASC';
	}
	$wpdb->query("UPDATE ".SFTOPICS." SET topic_sort = '".$newsort."' WHERE topic_id=".$topicid);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Topic Sort Toggle Failed", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Topic Sort Toggled", "sforum"));
		update_sfsetting('sfaction', $_POST['sorttopicaction'].$topicid);
	}
	return;
}

function sf_pin_post_toggle($postid)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}

	if($_POST['pinpostaction'].$postid == get_sfsetting('sfaction')) return;

	$status = $wpdb->get_var("SELECT post_pinned FROM ".SFPOSTS." WHERE post_id=".$postid);
	if($status == 1)
	{
		$status = 0;
	} else {
		$status = 1;
	}

	$wpdb->query("UPDATE ".SFPOSTS." SET post_pinned = ".$status." WHERE post_id=".$postid);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Post Pin Toggle Failed", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Post Pin Toggled", "sforum"));
		update_sfsetting('sfaction', $_POST['pinpostaction'].$postid);
	}
	return;
}

function sf_update_opened($topicid)
{
	global $wpdb;

	$current=$wpdb->get_var("SELECT topic_opened FROM ".SFTOPICS." WHERE topic_id=".$topicid);
	$current++;
	$wpdb->query("UPDATE ".SFTOPICS." SET topic_opened = ".$current." WHERE topic_id=".$topicid);
	return;
}

function sf_delete_topic($topicid)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}

	// check of there is a post link to it?
	$postid = $wpdb->get_var("SELECT blog_post_id FROM ".SFTOPICS." WHERE topic_id = ".$topicid);
	if($postid != 0)
	{
		// break the link
		sf_blog_links_postmeta('delete', $postid, '');
	}

	// delete from waiting just in case
	$wpdb->query("DELETE FROM ".SFWAITING." WHERE topic_id=".$topicid);

	// delete from topic
	$wpdb->query("DELETE FROM ".SFTOPICS." WHERE topic_id=".$topicid);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Deletion Failed", "sforum"));
	}

	// now delete all the posts on the topic
	$wpdb->query("DELETE FROM ".SFPOSTS." WHERE topic_id=".$topicid);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Deletion of Posts in Topic Failed", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Topic Deleted", "sforum"));
	}
	return;
}

// === POST MANAGEMENT ROUTINES

function sf_delete_post($postid, $topicid)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}

	// if just one post then remove topic as well
	if(sf_get_posts_count_in_topic($topicid) == 1)
	{
		sf_delete_topic($topicid);
	} else {
		$wpdb->query("DELETE FROM ".SFPOSTS." WHERE post_id=".$postid);
		if($wpdb == false)
		{
			update_sfnotice('sfmessage', __("Deletion Failed", "sforum"));
		} else {
			update_sfnotice('sfmessage', __("Post Deleted", "sforum"));
		}
	}
	return;
}

function sf_move_topic()
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}

	if(count($_POST['forumid']) == 0)
	{
		update_sfnotice('sfmessage', __("Topic move abandoned - No Forum selected!", "sforum"));
		return;
	}

	if(count($_POST['forumid']) != 1)
	{
		update_sfnotice('sfmessage', __("Topic can NOT be moved to more than One Forum", "sforum"));
		return;
	}

	// change topic record to new forum
	$wpdb->query("UPDATE ".SFTOPICS." SET forum_id = ".$_POST['forumid'][0]." WHERE topic_id=".$_POST['id']);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Topic Move Failed", "sforum"));
		return;
	}

	// change posts record(s) to new forum
	$wpdb->query("UPDATE ".SFPOSTS." SET forum_id = ".$_POST['forumid'][0]." WHERE topic_id=".$_POST['id']);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Topic Move Failed", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Topic Moved", "sforum"));
	}
	return;
}

function sf_approve_post($postid)
{
	global $wpdb, $user_ID;

	get_currentuserinfo();

	if(sf_admin_status() == false)
	{
		update_sfnotice('sfmessage', __('Access Denied', "sforum"));
		return;
	}

	$wpdb->query("UPDATE ".SFPOSTS." SET post_status = 0 WHERE post_id=".$postid);
	if($wpdb == false)
	{
		update_sfnotice('sfmessage', __("Post Approval Failed", "sforum"));
	} else {
		update_sfnotice('sfmessage', __("Post Approved", "sforum"));
		sf_remove_from_waiting(intval($_GET['topic']), $user_ID);
	}
	return;
}

// === Tracking on  line and last visit

function sf_track_online($userid, $username, $guestcookie)
{
	global $wpdb;

	$lastvisit = 0;

	if('' != $userid)
	{
		// it's a member
		$trackuserid = $userid;
		$trackname = $username;

		$lastvisit=get_user_option('sflast', $userid);
		if((is_null($lastvisit)) || ($lastvisit == 0))
		{
			$lastvisit = time();
		}

	} elseif(isset($guestcookie['name'])) {
		// it's a returning guest
		$trackuserid=0;
		$trackname = $guestcookie['name'].$_SERVER['REMOTE_ADDR'];
		$lastvisit = $guestcookie['last'];
		if(is_null($lastvisit)) $lastvisit=0;

	} else {
		// Unklnown guest
		$trackuserid=0;
		$trackname = $_SERVER['REMOTE_ADDR'];
	}

	// Update tracking
	$id=$wpdb->get_var("SELECT id FROM ".SFTRACK." WHERE trackname='".$trackname."'");
	if($id)
	{
		// they are still here
		$wpdb->query("UPDATE ".SFTRACK." SET trackdate=now() WHERE id=".$id);
	} else {
		// newly arrived
		$wpdb->query("INSERT INTO ".SFTRACK." (trackuserid, trackname, trackdate) VALUES (".$trackuserid.", '".$trackname."', now())");
		if('' != $userid)
		{
			sf_construct_users_newposts($userid, $lastvisit);
		}
	}

	// Check for expired tracking - so may have left the scene
	$expired=$wpdb->get_results("SELECT * FROM ".SFTRACK." WHERE trackdate	< DATE_SUB(now(), INTERVAL 10 MINUTE)");
	if($expired)
	{
		// if any Members expired - update user meta
		foreach($expired as $expire)
		{
			if($expire->trackuserid > 0)
			{
				sf_set_last_visited($expire->trackuserid);
				sf_destroy_users_newposts($expire->trackuserid);
			}
		}
		// finally delete them
		$wpdb->query("DELETE FROM ".SFTRACK." WHERE trackdate < DATE_SUB(now(), INTERVAL 10 MINUTE)");
	}
	if('' != $userid)
	{
		return strtotime($lastvisit);
	} else {
		return $lastvisit;
	}
}

function sf_set_last_visited($userid)
{
	global $wpdb;

	if('' != $userid)
	{
// 2.1 Patch 1
		$check = $wpdb->get_results("SELECT meta_value FROM ".SFUSERMETA." WHERE user_id=".$userid." AND meta_key='".$wpdb->prefix."sflast'");
		if($check)
		{
			$wpdb->query("UPDATE ".SFUSERMETA." SET meta_value=now() WHERE user_id=".$userid." AND meta_key='".$wpdb->prefix."sflast'");
		} else {
			$wpdb->query("INSERT INTO ".SFUSERMETA." (meta_value, user_id, meta_key) VALUES (now(), ".$userid.", '".$wpdb->prefix."sflast')");
		}
	}
	return;
}

function sf_construct_users_newposts($userid, $lastvisit)
{
	global $wpdb;

// 2.1 Patch 3 (if ot works!)
//	if(sf_admin_status())
	if($userid == ADMINID)
	{
		$topics=$wpdb->get_col("SELECT DISTINCT topic_id FROM ".SFWAITING." ORDER BY topic_id ASC;");
	} else {
		$topics=$wpdb->get_col("SELECT DISTINCT topic_id FROM ".SFPOSTS." WHERE post_date > '".$lastvisit."' ORDER BY topic_id DESC;");
	}

	if(!$topics)
	{
		$topics[0]=0;
	}

	update_usermeta($userid, 'sfnewposts', $topics);
	update_usermeta($userid, 'sfchecktime', time());

	return;
}

function sf_check_users_newposts($userid, $newpostlist)
{
	global $wpdb;

	if($newpostlist[0]==0)
	{
		$newpostlist='';
		$newpostlist=array();
	}

	$checktime=get_usermeta($userid, 'sfchecktime');
	$newpostlist=array_reverse($newpostlist);

// 2.1 Patch 3
	if($userid == ADMINID)
	{
		$topics=$wpdb->get_col("SELECT DISTINCT topic_id FROM ".SFWAITING." ORDER BY topic_id ASC;");
	} else {
		$topics=$wpdb->get_col("SELECT DISTINCT topic_id FROM ".SFPOSTS." WHERE UNIX_TIMESTAMP(post_date) > '".$checktime."' ORDER BY topic_id DESC;");
	}

	if($topics)
	{
		foreach($topics as $topic)
		{
			if(!in_array($topic, $newpostlist))
			{
				$newpostlist[] = $topic;
			}
		}
	}
// Patch ends here

	$newpostlist=array_reverse($newpostlist);

	if(count($newpostlist) == 0)
	{
		$newpostlist[0]=0;
	}
	update_usermeta($userid, 'sfnewposts', $newpostlist);
	update_usermeta($userid, 'sfchecktime', time());

	return $newpostlist;
}

function sf_remove_users_newposts($topicid, $userid)
{
	if('' != $userid)
	{
		$newpostlist=get_usermeta($userid, 'sfnewposts');
		if(($newpostlist) && ($newpostlist[0] != 0))
		{
			if((count($newpostlist) == 1) && ($newpostlist[0] == $topicid))
			{
				sf_destroy_users_newposts($userid);
			} else {
				$remove = -1;
				for($x=0; $x < count($newpostlist); $x++)
				{
					if($newpostlist[$x] == $topicid)
					{
						$remove = $x;
					}
				}
				if($remove != -1)
				{
					array_splice($newpostlist, $remove, 1);
					update_usermeta($userid, 'sfnewposts', $newpostlist);
				}
			}
		}
	}
	return;
}

function sf_is_in_users_newposts($topicid, $userid)
{
	if('' != $userid)
	{
		$newpostlist=get_usermeta($userid, 'sfnewposts');
	}

	$found = false;
	if(($newpostlist) && ($newpostlist[0] != 0))
	{
		for($x=0; $x < count($newpostlist); $x++)
		{
			if($newpostlist[$x] == $topicid) $found=true;
		}
	}
	return $found;
}

function sf_destroy_users_newposts($userid)
{
	$empty[0] = 0;
	update_usermeta($userid, 'sfnewposts', $empty);
	return;
}

function sf_update_max_online($current)
{
	$max = get_sfsetting('maxonline');
	if(empty($max)) $max = 0;

	if($current > $max)
	{
		update_sfsetting('maxonline', $current);
	}
	return;
}

// === Time Zone handling

function sf_zone_datetime($datefield)
{
	$zone = get_option('sfzone');
	if($zone == 0)
	{
		return $datefield;
	}
	if($zone < 0)
	{
		$out='DATE_SUB('.$datefield.', INTERVAL '.abs($zone).' HOUR) as post_date';
		return $out;
	}
	// must be positive then
	$out='DATE_ADD('.$datefield.', INTERVAL '.abs($zone).' HOUR) as post_date';
	return $out;
}

function sf_url($f=0, $t=0, $p=0, $s=0, $n=0, $i=0, $v=0)
{
	// f=forum/t=topic/p=page/s=searchpage/n=newposts/i=postitem/v=searchvalue
	// if post ($i) is set then we need to determine which page it is on and set the page as well
	if($i != 0)
	{
		$p = sf_determine_page(intval($t), intval($i));
	}

	$url = SFURL;

	// first does it need the ?
	if(strpos($url, '?') === false)
	{
		$url .= '?';
		$and = '';
	} else {
		$and = '&amp;';
	}

	// forum first
	if(($f != 0) || (!empty($f)))
	{
		$url.= $and.'forum='.intval($f);
		$and = '&amp;';
	}

	// topic
	if($t != 0)
	{
		$url.= $and.'topic='.intval($t);
		$and = '&amp;';
	}

	// page
	if($p != 0)
	{
		$url.= $and.'page='.intval($p);
		$and = '&amp;';
	}

	// search
	if($s != 0)
	{
		$url.= $and.'search='.intval($s);
		$and = '&amp;';
	}

	// newposts
	if($n != 0)
	{
		$url.= $and.'newposts='.intval($n);
		$and = '&amp;';
	}

	// post
	if($i != 0)
	{
		$url.= $and.'post='.intval($i);
		$and = '&amp;';
	}

	// search value
	if(($v != 0) || (!empty($v)))
	{
		$url.= $and.'value='.$v;
	}

	return $url;
}

function sf_determine_page($topicid, $postid)
{
	global $wpdb;
	$order="ASC"; // default
	if(get_option('sfsortdesc')) $order="DESC"; // global override
	$torder=sf_get_topic_sort($topicid);
	if(!is_null($torder)) $order=$torder; // topic override

	$ppaged=get_option('sfpagedposts');
	$x = 1;

	$posts=$wpdb->get_results("SELECT post_id FROM ".SFPOSTS." WHERE topic_id = ".$topicid." ORDER BY post_pinned DESC, post_id ".$order);

	foreach($posts as $post)
	{
		if($post->post_id == $postid)
		{
			// DO THE SETTING OF PAGE AND RETURN IT
			$page = ($x/$ppaged);
			if(!is_int($page))
			{
				$page=intval(($page)+1);
			}
			break;
		} else {
			$x++;
		}
	}
	return $page;
}

function sf_filter_content($content, $searchvalue)
{
	$content = apply_filters('sf_show_post_content', $content);
	if(get_option('sfsmilies'))
	{
		$content = convert_smilies($content);
	}
	if(empty($searchvalue))
	{
		return $content."\n";
	}

	$searchvalue=urldecode($searchvalue);

	// It's a search so we need to perform highlighting
	if(substr($searchvalue, 0, 12) != "sf%members%1")
	{
		$terms = sf_deconstruct_search_term($searchvalue);
		foreach($terms as $term)
		{
			$replacevalue = "<cite>".$term."</cite>";
			$content = str_replace($term, $replacevalue, $content);
		}
	}

	return $content."\n";
}

?>