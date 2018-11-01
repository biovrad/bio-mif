<?php
/*
Simple Forum 2.1
Template Tag(s)
*/

/* 	=====================================================================================

	sf_recent_posts_tag($limit, $forum, $user, $postdate, $listtags, $forumids)

	displays the most recent topics to have received a new post
	
	parameters:
		
		$limit			How many items to show in the list		number			5
		$forum			Show the Forum Title					true/false		false
		$user			Show the Users Name						true/false		true
		$postdate		Show date of posting					true/false		false
		$listtags		Wrap in <li> tags (li only)				true/false		true
		$forumids		comma delimited list of forum id's		optional		0

 	===================================================================================*/

function sf_recent_posts_tag($limit=5, $forum=false, $user=true, $postdate=false, $listtags=true, $forumids=0)
{
	global $wpdb, $user_ID;
	
	get_currentuserinfo();
	$out.'';

	// are we passing forum ID's?
	if($forumids == 0)
	{
		$where = '';
	} else {
		$flist = explode(",", $forumids);
		$where=' WHERE ';
		$x=0;
		for($x; $x<count($flist); $x++)
		{
			$where.= 'forum_id = '.$flist[$x];
			if($x != count($flist)-1) $where.= " OR ";
		}
	}
	
	$sfposts = $wpdb->get_results("SELECT DISTINCT forum_id, topic_id FROM ".SFPOSTS.$where." ORDER BY post_id DESC LIMIT ".$limit);	

	if($sfposts)
	{
		foreach($sfposts as $sfpost)
		{
			$thisforum = $wpdb->get_row("SELECT forum_name, forum_view FROM ".SFFORUMS." WHERE forum_id = $sfpost->forum_id");	
			if(sf_access_granted($thisforum->forum_view))
			{
				$p=false;
				
				$postdetails = sf_get_last_post_in_topic($sfpost->topic_id);

				//== Start contruction
				if($listtags) $out.="<li class='sftagli'>\n";
	
				$out.=sf_get_topic_url_newpost($sfpost->forum_id, $sfpost->topic_id, $postdetails->post_id);

				if($forum)
				{
					$out.="<p class='sftagp'>".__("posted in forum: ", "sforum").stripslashes($thisforum->forum_name)."&nbsp;"."\n";
					$p=true;
				}

				if($user)
				{
					if($p == false) $out.="<p class='sftagp'>";
					$poster = sf_filter_user($postdetails->user_id, $postdetails->display_name);
					if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($postdetails->guest_name));
					$out.=__("by: ", "sforum").$poster."&nbsp;"."\n";
					$p=true;
				}

				if($postdate)
				{
					if($p == false) $out.="<p class='sftagp'>";
					$out.=__("on: ", "sforum").mysql2date(SFDATES, $postdetails->post_date)."\n";
					$p=true;
				}
					
				if($p) $out.="</p>\n";
				
				if($listtags) $out.="</li>\n";
			}
		}
	}
	echo($out);
	return;
}

/* 	=====================================================================================

	sf_new_post_announce()

	displays the latest forum post in  the sidebar - updated every 30 seconds
	
	parameters: None
	
	The option to use this tag MUST be turned on in the forum options

 	===================================================================================*/

function sf_new_post_announce()
{
	if(get_option('sfuseannounce'))
	{
		$url=get_option('siteurl')."/wp-content/plugins/simple-forum/ahah/sf-announce.php";
		
		if(get_option('sfannounceauto'))
		{
			$timer = (get_option('sfannouncetime') * 1000);
			echo '<script type="text/javascript">';
			echo 'sfNewPostCheck("'.$url.'", "sfannounce", "'.$timer.'");';
			echo '</script>';
		}
		echo '<div id="sfannounce">';
		sf_new_post_announce_display();
		echo '</div>';
	}
	return;	
}

function sf_new_post_announce_display()
{
	global $wpdb, $user_login, $user_identity, $user_ID;

	get_currentuserinfo();
	$guestcookie=sf_get_cookie();

	$lastvisit = sf_track_online($user_ID, $user_login, $guestcookie);
	$is_admin=false;

	$aslist = get_option('sfannouncelist');
	$out = '';
	
	$sfposts = sf_get_simple_recent_post_list(get_option('sfannouncecount'));	
	
	if($sfposts)
	{
		if($aslist)
		{
			$out = '<ul><li>'.stripslashes(get_option('sfannouncehead')).'<ul>';
		} else {
			$out = '<p>'.stripslashes(get_option('sfannouncehead')).'<p>';
			$out.= '<table cellpadding="0" cellspacing="0" border="0">';
		}
		
		foreach($sfposts as $sfpost)
		{
			$forum = $wpdb->get_row("SELECT forum_name, forum_view FROM ".SFFORUMS." WHERE forum_id = $sfpost->forum_id");	
			if(sf_access_granted($forum->forum_view))
			{
				//== GET LAST POSTER DETAILS
				$last = sf_get_last_post_in_topic($sfpost->topic_id);
				
				$poster = sf_filter_user($last->user_id, $last->display_name);
				if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($last->guest_name));

				if(!$aslist)
				{
					$out.= '<tr><td valign="top">';
					//== DISPLAY TOPIC ENTRY
					$topicicon = 'announceold.png';
					if('' != $user_ID)
					{
						if(sf_is_in_users_newposts($sfpost->topic_id, $user_ID)) $topicicon = 'announcenew.png';
					} else {
						if(($lastvisit > 0) && ($lastvisit < $last->udate)) $topicicon = 'announcenew.png';
					}
					$out.= '<img src="'. SFRESOURCES . $topicicon. '" alt="" />'."\n";
				}
				
				if($aslist)
				{
					$out.= '<li>';
				} else {
					$out.='</td><td valign="top"><p>';
				}
				$out.= '<a href="'.sf_url($sfpost->forum_id, $sfpost->topic_id, 0, 0, 0, $last->post_id).'#p'.$last->post_id.'">'.sf_format_announce_tag($forum->forum_name, sf_get_topic_name($sfpost->topic_id), $poster, $last->post_date).'</a>';
				
				if($aslist)
				{
					$out.= '</li>';
				} else {
					$out.='</p></td></tr>';
				}
			}
		}
		if($aslist)
		{
			$out.= '</ul></li></ul>';
		} else {
			$out.='</table>';
		}
	}	
	echo $out;
}

function sf_format_announce_tag($forumname, $topicname, $poster, $postdate)
{
	$text=stripslashes(get_option('sfannouncetext'));
	
	$text = str_replace('%TOPICNAME%', stripslashes($topicname), $text);
	$text = str_replace('%FORUMNAME%', stripslashes($forumname), $text);
	$text = str_replace('%POSTER%', stripslashes($poster), $text);
	$text = str_replace('%DATETIME%', mysql2date(SFDATES, $postdate)." - ".mysql2date(SFTIMES,$postdate), $text);
	return $text;
}

/* 	=====================================================================================

	sf_group_link($groupid, $linktext, $listtags)

	displays a link to a specific forum group if current user has access privilege
	
	parameters:
		
		$groupid		ID of the group to display				Required
		$linktext		Text for link - leave as empty string to use group name
		$listtags		Wrap in <li> tags (li only)				true/false		true

 	===================================================================================*/

function sf_group_link($groupid, $linktext, $listtags=true)
{
	if(empty($groupid)) return '';
	$out='';
	if(sf_group_exists($groupid))
	{
		if(sf_access_granted(sf_get_group_accessrole($groupid)))
		{
			if(empty($linktext)) $linktext=sf_get_forum_group($groupid);
			if($listtags) $out.="<li>\n";
			$out.= '<a href="'.SFQURL.'group='.$groupid.'">'.$linktext.'</a>'."\n";
			if($listtags) $out.="</li>\n";
		}
	} else {
		$out=printf(__('Group %s Not Found', 'sforum'), $groupid)."\n";
	}
	echo $out;
	return;
}

/* 	=====================================================================================

	sf_forum_link($forumid, $linktext, $listtags)

	displays a link to a specific forum topic listing if current user has access privilege
	
	parameters:
		
		$forumid		ID of the forum to display				Required
		$linktext		Text for link - leave as empty string to use forum name
		$listtags		Wrap in <li> tags (li only)				true/false		true

 	===================================================================================*/

function sf_forum_link($forumid, $linktext, $listtags=true)
{
	if(empty($forumid)) return '';
	$out='';
	if(sf_forum_exists($forumid))
	{
		if(sf_access_granted(sf_get_forum_accessrole($forumid)))
		{
			if(empty($linktext)) $linktext=sf_get_forum_name($forumid);
			if($listtags) $out.="<li>\n";
			$out.= '<a href="'.sf_url($forumid).'">'.$linktext.'</a>'."\n";
			if($listtags) $out.="</li>\n";
		}
	} else {
		$out=printf(__('Forum %s Not Found', 'sforum'), $forumid)."\n";
	}
	echo $out;
	return;
}

/* 	=====================================================================================

	sf_topic_link($forumid, $topicid, $linktext, $listtags)

	displays a link to a specific topic post listing if current user has access privilege
	
	parameters:
		
		$forumid		ID of the forum topic belongs to		Required
		$topicid		ID of the topic to display posts of		Required
		$linktext		Text for link - leave as empty string to use topic name
		$listtags		Wrap in <li> tags (li only)				true/false		true

 	===================================================================================*/

function sf_topic_link($forumid, $topicid, $linktext, $listtags=true)
{
	if(empty($forumid)) return '';
	if(empty($topicid)) return '';
	$out='';
	if(sf_topic_exists($topicid))
	{
		if(sf_access_granted(sf_get_forum_accessrole($forumid)))
		{
			if(empty($linktext)) $linktext=sf_get_topic_name($topicid);
			if($listtags) $out.="<li>\n";
			$out.= '<a href="'.sf_url($forumid, $topicid).'">'.$linktext.'</a>'."\n";
			if($listtags) $out.="</li>\n";
		}
	} else {
		$out=printf(__('Topic %s Not Found', 'sforum'), $topicid)."\n";
	}
	echo $out;
	return;
}

/* 	=====================================================================================

	sf_forum_dropdown($forumids)

	displays a dropdown of links to forums
	
	parameters:
		
		$forumids		ID's of forums (comma delimited in quotes)		Required

 	===================================================================================*/

function sf_forum_dropdown($forumid = 0)
{
	$out='';

	if($forumid == 0) return;

	$forums=explode(',', $forumid);
	$out.= '<select name="forumselect" class="sfcontrol" onChange="javascript:changeURL(this)">'."\n";
	$out.= '<option>Select Forum</option>'."\n";
	foreach($forums as $forum)
	{
		$out.='<option value="'.sf_url($forum).'">--'.stripslashes(sf_get_forum_name($forum)).'</option>'."\n";
	}
	$out.='</select>'."\n";
	return $out;
}

/* 	=====================================================================================

	sf_recent_posts_expanded($limit=5)	
	
	displays the most recent topics to have received a new post in full expanded view

	parameters:
		
		$limit			How many items to show in the list		number			5

	NOTE: This is not an ordinary tag. It replictes the new post list from the forum.
	For proper results you need to include the forum CSS file.
	
 	===================================================================================*/

function sf_recent_posts_expanded($limit=5)
{
	global $wpdb, $user_ID;
	
	get_currentuserinfo();

	$sfposts = $wpdb->get_results("SELECT DISTINCT post_id, forum_id, topic_id FROM ".SFPOSTS." ORDER BY post_id DESC LIMIT ".$limit);	

	if($sfposts)
	{
		//== DISPLAY TOPIC LIST HEADINGS
		$out = '<div id="sforum">'."\n";
		$out.= '<table class="sfforumtable">'."\n";
		$out.= '<tr><th colspan="2">'.__("Forum/Topic", "sforum").'</th><th>'.__("Started", "sforum").'</th><th>'.__("Last Post", "sforum").'</th><th>'.__("Posts", "sforum").'</th>'."\n";
		$out.= '</tr>'."\n";
		foreach($sfposts as $sfpost)
		{
			$forum = $wpdb->get_row("SELECT forum_name, forum_view FROM ".SFFORUMS." WHERE forum_id = $sfpost->forum_id");	
			if(sf_access_granted($forum->forum_view))
			{
				//== DISPLAY TOPIC ENTRY
				$out.= '<tr>'."\n";
				$out.= '<td class="sficoncell"><img src="'. SFRESOURCES .'topic.png" alt="" /></td>'."\n";
				$out.= '<td>' . stripslashes($forum->forum_name);
				$out.= '<br /><a href="'.sf_url($sfpost->forum_id, $sfpost->topic_id, 0, 0, 0, $sfpost->post_id).'">'.sf_get_topic_name($sfpost->topic_id).'</a></td>';
				$postcount = sf_get_posts_count_in_topic($sfpost->topic_id);
				$out.= '<small>'.sf_get_post_pagelinks($sfpost->forum_id, $sfpost->topic_id, $postcount).'</small></td>';							
				
				//== GET FIRST/LAST POSTER DETAILS - NEEDED FOR NEW POST VIEW
				$first = sf_get_first_post_in_topic($sfpost->topic_id);
				$last = sf_get_last_post_in_topic($sfpost->topic_id);
				
				//== DISPLAY FIRST/LAST POSTER DETAILS
				$out.= '<td class="sfuserdetails">'."\n";
				if($first) 
				{
					$poster = sf_filter_user($first->user_id, $first->display_name);
					if(empty($poster)) $poster=stripslashes($first->guest_name);
					$out.= '<p>'.mysql2date(SFDATES, $first->post_date)." - ".mysql2date(SFTIMES,$first->post_date).'</p><p>'.__("by", "sforum").' '.$poster.'</p>'."\n";
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
		$out.= '</table>'."\n";
		$out.= '</div>'."\n";
	} else {
		$out.='<br /><div class="sfmessagestrip">'.__("There are No Recent Posts", "sforum").'</div>'."\n";
	}

	echo($out);
	return;
}

// ===== RECENT FOUM POST WIDGET =================================================================================

add_action('widgets_init', 'sf_post_widget_init');

function sf_post_widget_init()
{
	// Check for the required plugin functions.
	if(!function_exists('register_sidebar_widget'))
	{
		return;
	}

	function sf_post_widget($args) 
	{
		// $args: before_widget, before_title, after_widget, after_title are the array keys. Default tags: li and h2.
		extract($args);

		$options = get_option('widget_sforum');
		$title = empty($options['title']) ? 'Recent Forum Posts' : $options['title'];
		$limit = empty($options['limit']) ? 5 : $options['limit'];
		$forum = empty($options['forum']) ? 0 : $options['forum'];
		$user = empty($options['user']) ? 0 : $options['user'];
		$postdate = empty($options['postdate']) ? 0 : $options['postdate'];
		$idlist = empty($options['idlist']) ? 0 : $options['idlist'];

		// generate output
		echo $before_widget . $before_title . $title . $after_title . "<ul class='sftagul'>";
		sf_recent_posts_tag($limit, $forum, $user, $postdate, true, $idlist);
		echo "</ul>".$after_widget;
	}

	function sf_post_widget_control()
	{
		// Get our options and see if we're handling a form submission.
		$options = get_option('widget_sforum');
		if(!is_array($options))
		{
			$options = array('title'=>'', 'limit'=>0, 'forum'=>0, 'user'=>0, 'postdate'=>0, 'idlist'=>0);
		}

		if ($_POST['sfpostwidget-submit']) 
		{
			$options['title'] = strip_tags(stripslashes($_POST['forum-title']));
			$options['limit'] = strip_tags(stripslashes($_POST['forum-limit']));
			if(isset($_POST['forum-forum']))
			{
				$options['forum'] = 1;
			} else {
				$options['forum'] = 0;
			}
			if(isset($_POST['forum-user']))
			{
				$options['user'] = 1;
			} else {
				$options['user'] = 0;
			}
			if(isset($_POST['forum-postdate']))
			{
				$options['postdate'] = 1;
			} else {
				$options['postdate'] = 0;
			}
			$options['idlist'] = strip_tags(stripslashes($_POST['forum-idlist']));
			
			update_option('widget_sforum', $options);
		}

		$title = htmlspecialchars($options['title'], ENT_QUOTES);
		$limit = htmlspecialchars($options['limit'], ENT_QUOTES);
		$forum = $options['forum'];
		$user = $options['user'];
		$postdate = $options['postdate'];
		$idlist = htmlspecialchars($options['idlist'], ENT_QUOTES);
		
		// The option form
		?>

		<!--title-->
		<p style="text-align:right;">
		<label for="forum-title"><?php _e('Title:', 'sforum')?>
			<input style="width: 200px;" type="text" id="forum-title" name="forum-title" value="<?php echo $title?>"/>
		</label></p>
		
		<!--how many to show -->
		<p style="text-align:right;">
		<label for="forum-limit"><?php _e('List how many posts:', 'sforum')?>
			<input style="width: 50px;" type="text" id="forum-limit" name="forum-limit" value="<?php echo $limit?>"/>
		</label></p>
		
		<!--include forum name-->
		<p style="text-align:right;">
		<label for="forum-forum"><?php _e('Show forum name:', 'sforum')?>
			<input type="checkbox" id="forum-forum" name="forum-forum"
			<?php if($options['forum'] == TRUE) {?> checked="checked" <?php } ?> />
		</label></p>
			
		<!--include user name-->
		<p style="text-align:right;">
		<label for="forum-user"><?php _e('Show users name:', 'sforum')?>
			<input type="checkbox" id="forum-user" name="forum-user"
			<?php if($options['user'] == TRUE) {?> checked="checked" <?php } ?> />
		</label></p>
		
		<!--include post date-->
		<p style="text-align:right;">
		<label for="forum-postdate"><?php _e('Show date of post:', 'sforum')?>
			<input type="checkbox" id="forum-postdate" name="forum-postdate"
			<?php if($options['postdate'] == TRUE) {?> checked="checked" <?php } ?> />
		</label></p>

		<!--forum id list (comma separated)-->
		<p style="text-align:right;">
		<label for="forum-idlist"><?php _e('Forum IDs:', 'sforum')?>
			<input style="width: 100px;" type="text" id="forum-idlist" name="forum-idlist" value="<?php echo $idlist?>"/>
		</label></p>
		<small><?php _e("If specified, Forum ID's must be separated by commas. To use ALL forums, enter a value of zero", 'sforum')?></small, >

		<input type="hidden" id="sfpostwidget-submit" name="sfpostwidget-submit" value="1" />
		<?php
	}

	$name = "Simple Forum";

    // Register the widget
    register_sidebar_widget(array($name, 'widgets'), 'sf_post_widget');

    // Registers the widget control form
    register_widget_control(array($name, 'widgets'), 'sf_post_widget_control', 300, 230);
}

?>