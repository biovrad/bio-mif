<?php
/*
Simple Forum 2.1
Forum/Blog Links
*/

if(get_option('sflinkuse'))
{
	add_action('dbx_post_sidebar', 'sf_blog_link_form');
	add_action('save_post', 'sf_save_blog_link');
	add_action('publish_post', 'sf_publish_blog_link');
	add_filter('the_content', 'sf_blog_show_link');
	add_action('delete_post', 'sf_blog_link_delete');
}

//== FROM BLOG to FORUM - ACTIONS & FILTERS =====================================================

function sf_blog_link_form() 
{
	global $post;

	$forumid = 0;
	$text = '';

	if(isset($post->ID))
	{
		$islink = sf_blog_links_postmeta('read', $post->ID, '');
		if($islink)
		{
			$forumid = $islink->meta_value;
			$text = 'checked="checked"';
		}
	}
	
	?>
		<fieldset id="sflinks_dbx" class="dbx-box">
		<h3 class="dbx-handle"><strong><?php _e("Link To Forum", "sforum"); ?></strong></h3> 
		<div class="dbx-content">
		<label for="sflink" class="selectit">
		<input type="checkbox" <?php echo($text); ?> name="sflink" id="sflink" />	
		<?php _e("Create Forum Topic", "sforum"); ?></label>
		<label for="sfforum" class="selectit"><?php _e("Select Forum:", "sforum"); ?></label>
		<?php echo(sf_blog_links_list($forumid)); ?>
		</div></fieldset>
	<?php
}

function sf_save_blog_link($postid)
{
	if(isset($_POST['sflink']))
	{
		// sadly need to check if both items already set (forum @ topic)
		$checkrow = sf_blog_links_postmeta('read', $postid, '');
		if(($checkrow) && (strpos($checkrow->meta_value, '@')))
		{
			//already cooked
			return;
		} else {
			$text = $_POST['sfforum'];
			sf_blog_links_postmeta('save', $postid, $text);
		}
	}
	return;
}

function sf_publish_blog_link($postid)
{
	global $wpdb;

	if(isset($_POST['sflink']))
	{
		// sadly need to check if both items already set (forum @ topic)
		$checkrow = sf_blog_links_postmeta('read', $postid, '');
		if(($checkrow) && (strpos($checkrow->meta_value, '@')))
		{
			//already cooked
			return;
		} else {	
			// first - get the post content
			$content = $wpdb->get_row("SELECT post_content, post_title, post_author FROM ".$wpdb->prefix."posts WHERE ID = ".$postid.";");
			$post_title = $wpdb->escape($content->post_title);
			
			// now create the topic and post records - it should already be escaped fully.
			$sql = "INSERT INTO ".SFTOPICS." (topic_name, topic_date, forum_id, user_id, blog_post_id) VALUES ('".$post_title."', now(), ".$_POST['sfforum'].", ".$content->post_author.", ".$postid.");";
			$wpdb->query($sql);
	
			$topicid = $wpdb->insert_id;

			// Full content or excerpt?
			$postcontent = sf_make_excerpt($postid, $content->post_content);
			$postcontent = $wpdb->escape($postcontent);

			$sql = "INSERT INTO ".SFPOSTS." (post_content, post_date, topic_id, user_id, forum_id) VALUES ('".$postcontent."', now(), ".$topicid.", ".$content->post_author.", ".$_POST['sfforum'].");";
			$wpdb->query($sql);
			
			// and then update postmeta with forum AND topic
			$text = $_POST['sfforum']."@".$topicid;
			sf_blog_links_postmeta('save', $postid, $text);
		}
	}
	return;
}

function sf_blog_show_link($content)
{
	global $wp_query;
	
	$postid = $wp_query->post->ID;
	$out = '';
	$checkrow = sf_blog_links_postmeta('read', $postid, '');
	if($checkrow)
	{
		// link found for this post
		$keys = explode('@', $checkrow->meta_value);
		
		$text = get_option('sflinkblogtext');
		$icon = '<img src="'.SFRESOURCES.'bloglink.png" alt="" />';
		$text = str_replace('%ICON%', $icon, $text);
		
		$out = '<span class="sfforumlink"><a href="'.sf_url($keys[0], $keys[1]).'">'.$text.'</a></span>';
// 2.1 Patch 1

		if(get_option('sflinkabove'))
		{
			return $out.'<br />'.$content;
		} else {
			return $content.'<br />'.$out;
		}
	} else {
		return $content;
	}
}

function sf_forum_show_blog_link($postid)
{
	$text = stripslashes(get_option('sflinkforumtext'));
	$icon = '<img src="'.SFRESOURCES.'bloglink.png" alt="" />';
	$text = str_replace('%ICON%', $icon, $text);

	$out = '<span class="sfbloglink"><a href="'.get_permalink($postid).'">'.$text.'</a></span>';
	return $out;
}

function sf_blog_link_delete($postid)
{
	$islink = sf_blog_links_postmeta('read', $postid, '');
	if($islink)
	{
		$keys = explode('@', $islink->meta_value);
		sf_break_post_link($keys[1], $postid);
	}
	return;
}

//== SUPPORT ROUTINES ======================================================

function sf_blog_links_list($forumid)
{
	$groups = sf_get_groups_all();
	if($groups)
	{
		$out = '';
		$out.= '<select id="sfforum" name="sfforum">'."\n";

		foreach($groups as $group)
		{
			if(sf_access_granted($group->group_view))
			{		
				$out.= '<optgroup label="'.$group->group_name.'">'."\n";
				$forums = sf_get_forums_in_group($group->group_id);
				if($forums)
				{
					foreach($forums as $forum)
					{
						if(sf_access_granted($forum->forum_view))
						{
							if($forumid == $forum->forum_id)
							{
								$text = 'selected="selected" ';
							} else {
								$text = '';
							}
							$out.='<option '.$text.'value="'.$forum->forum_id.'">'.stripslashes($forum->forum_name).'</option>'."\n";
						}
					}
				}
				$out.='</optgroup>';		
			}
		}
		$out.='</select>'."\n";
	}
	return $out;
}

function sf_blog_links_postmeta($action, $postid, $item)
{
	global $wpdb;

// 2.1 Patch 3
	// seems to sometimes get triggered by other plugins althoug it suggests a core WP bug
	if(!isset($postid)) return;

	if($action == 'save')
	{
		// check if there already...
		$result = $wpdb->get_results("SELECT meta_value FROM ".$wpdb->prefix."postmeta WHERE post_id=".$postid." AND meta_key='forumlink';");
		if($result)	
		{
			$action = 'update';
		} else {
			$sql="INSERT INTO ".$wpdb->prefix."postmeta (post_id, meta_key, meta_value) VALUES (".$postid.", 'forumlink', '".$item."');";
			$wpdb->query($sql);
			return;
		}
	}
	if($action == 'update')
	{
		$sql="UPDATE ".$wpdb->prefix."postmeta SET meta_value='".$item."' WHERE post_id=".$postid." AND meta_key='forumlink';";
		$wpdb->query($sql);
		return;
	}
	if($action == 'read')
	{
		$sql = "SELECT * FROM ".$wpdb->prefix."postmeta WHERE post_id=".$postid." AND meta_key='forumlink';";
		return($wpdb->get_row($sql));
	}
	if($action == 'delete')
	{
		$sql = "DELETE FROM ".$wpdb->prefix."postmeta WHERE post_id=".$postid." AND meta_key='forumlink';";
		return($wpdb->get_row($sql));
	}
}

function sf_break_post_link($topicid, $postid)
{
	global $wpdb;
	
	// remove from postmeta
	sf_blog_links_postmeta('delete', $postid, '');
	// and set blog_oost_id to zero in topic record
	$wpdb->query("UPDATE ".SFTOPICS." SET blog_post_id = 0 WHERE topic_id = ".$topicid.";");
	return;
}

function sf_make_excerpt($postid, $postcontent)
{
	if(get_option('sflinkexcerpt') == false)
	{
		return $postcontent.'<br />'.sf_forum_show_blog_link($postid);
	}
	
	// so an excerpt then
	$words = get_option('sflinkwords');
	if((empty($words)) || ($words == 0)) $words = 50;

	$excerpt = '';
		
	$textarray=explode(' ', $postcontent);
	if(count($textarray) <= $words)
	{
		$excerpt = $postcontent;
	} else {
		for($x=0; $x<$words; $x++)
		{
			$excerpt.= $textarray[$x].' ';
		}
		$excerpt.= '...';
	}
	$excerpt.= '<br />'.sf_forum_show_blog_link($postid);

	return $excerpt;
}

?>