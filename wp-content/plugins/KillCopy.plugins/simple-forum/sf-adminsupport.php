<?php
/*
Simple Forum 2.1
Admin Support Routines
*/

//== GROUP RELATED

function sfa_get_other_groups($group_id)
{
	global $wpdb;
	return $wpdb->get_results("SELECT group_id, group_seq FROM ".SFGROUPS." WHERE group_id <> ".$group_id." ORDER BY group_seq;");
}

function sfa_get_other_forums($group_id, $forum_id)
{
	global $wpdb;
	return $wpdb->get_results("SELECT forum_id, forum_seq FROM ".SFFORUMS." WHERE group_id=".$group_id." AND forum_id <> ".$forum_id." ORDER BY forum_seq;");
}

function sfa_get_group_row($group_id)
{
	global $wpdb;
	return $wpdb->get_row("SELECT * FROM ".SFGROUPS." WHERE group_id=".$group_id);
}

function sfa_update_group_row($group_id, $groupdata, $updateforums)
{
	global $wpdb;

	// update forums view?
	if($updateforums)
	{
		$wpdb->query("UPDATE ".SFFORUMS." SET forum_view='".$groupdata['group_view']."' WHERE group_id=".$group_id);
	}
	
	$groupname = $wpdb->escape($groupdata['group_name']);
	$groupdesc = $wpdb->escape($groupdata['group_desc']);
	
	$sql = "UPDATE ".SFGROUPS." SET ";
	$sql.= 'group_name="'.$groupname.'", ';
	$sql.= 'group_desc="'.$groupdesc.'", ';
	$sql.= 'group_view="'.$groupdata['group_view'].'", ';
	$sql.= 'group_seq='.$groupdata['group_seq']." ";
	$sql.= "WHERE group_id=".$group_id.";";

	return $wpdb->query($sql);
}

function sfa_update_forum_row($forum_id, $forumdata)
{
	global $wpdb;

	$forumname = $wpdb->escape($forumdata['forum_name']);
	$forumdesc = $wpdb->escape($forumdata['forum_desc']);
	
	$sql = "UPDATE ".SFFORUMS." SET ";
	$sql.= 'forum_name="'.$forumname.'", ';
	$sql.= 'forum_desc="'.$forumdesc.'", ';
	$sql.= 'forum_view="'.$forumdata['forum_view'].'", ';
	$sql.= 'group_id='.$forumdata['group_id'].', ';
	$sql.= 'forum_status='.$forumdata['forum_status'].', ';
	$sql.= 'forum_seq='.$forumdata['forum_seq']." ";
	$sql.= "WHERE forum_id=".$forum_id.";";

	return $wpdb->query($sql);
}

function sfa_create_group_row($groupdata)
{
	global $wpdb;

	$groupname = $wpdb->escape($groupdata['group_name']);
	$groupdesc = $wpdb->escape($groupdata['group_desc']);
	
	$sql ="INSERT INTO ".SFGROUPS." (group_name, group_desc, group_view, group_seq) ";
	$sql.="VALUES ('".$groupname."', '".$groupdesc."', '".$groupdata['group_view']."', ".$groupdata['group_seq'].");";

	return $wpdb->query($sql);
}

function sfa_create_forum_row($forumdata)
{
	global $wpdb;

	$forumname = $wpdb->escape($forumdata['forum_name']);
	$forumdesc = $wpdb->escape($forumdata['forum_desc']);
	$forumview = $wpdb->get_var("SELECT group_view FROM ".SFGROUPS." WHERE group_id=".$forumdata['group_id']);

	$sql ="INSERT INTO ".SFFORUMS." (forum_name, forum_desc, forum_view, group_id, forum_status, forum_seq) ";
	$sql.="VALUES ('".$forumname."', '".$forumdesc."', '".$forumview."', ".$forumdata['group_id'].", ".$forumdata['forum_status'].", ".$forumdata['forum_seq'].");";

	return $wpdb->query($sql);
}

function sfa_bump_group_seq($id, $seq)
{
	global $wpdb;
	
	$sql = "UPDATE ".SFGROUPS." SET ";
	$sql.= 'group_seq='.$seq." ";
	$sql.= "WHERE group_id=".$id.";";

	$wpdb->query($sql);
	return;
}

function sfa_bump_forum_seq($id, $seq)
{
	global $wpdb;
	
	$sql = "UPDATE ".SFFORUMS." SET ";
	$sql.= 'forum_seq='.$seq." ";
	$sql.= "WHERE forum_id=".$id.";";

	$wpdb->query($sql);
	return;
}

function sfa_remove_group_data($group_id)
{
	global $wpdb;

	// select all the forums in the group
	$forums = sf_get_forums_in_group($group_id);
	// remove the topics and posts in each forum
	foreach($forums as $forum)
	{
		$wpdb->query("DELETE FROM ".SFPOSTS." WHERE forum_id=".$forum->forum_id);
		$wpdb->query("DELETE FROM ".SFTOPICS." WHERE forum_id=".$forum->forum_id);
	}
	//now remove the forums themselves
	$wpdb->query("DELETE FROM ".SFFORUMS." WHERE group_id=".$group_id);
	// and finaly remove the group
	$wpdb->query("DELETE FROM ".SFGROUPS." WHERE group_id=".$group_id);
	return;
}

function sfa_remove_forum_data($forum_id)
{
	global $wpdb;
	
	$wpdb->query("DELETE FROM ".SFPOSTS." WHERE forum_id=".$forum_id);
	$wpdb->query("DELETE FROM ".SFTOPICS." WHERE forum_id=".$forum_id);
	$wpdb->query("DELETE FROM ".SFFORUMS." WHERE forum_id=".$forum_id);
	return;
}

function sfa_next_group_seq()
{
	global $wpdb;
	return $wpdb->get_var("SELECT MAX(group_seq) FROM ".SFGROUPS);
}

function sfa_next_forum_seq($groupid)
{
	global $wpdb;
	return $wpdb->get_var("SELECT MAX(forum_seq) FROM ".SFFORUMS." WHERE group_id=".$groupid);
}

function sfa_create_group_select($groupid = 0)
{
	$groups = sf_get_groups_all();
	$out='';
	$default='';
	foreach($groups as $group)
	{
		if($group->group_id == $groupid)
		{
			$default = 'selected="selected" ';
		} else {
			$default - null;
		}
		$out.='<option '.$default.'value="'.$group->group_id.'">'.stripslashes($group->group_name).'</option>'."\n";
		$default='';
	}
	return $out;
}

function sfa_create_roles_select($currentrole, $parentrole='public')
{
	global $wp_roles;
	
	$out='';
	$default='';
	$minlevel = 0;

	// do public first - only allow if group is public and if not - get group level
	if($parentrole == 'public')
	{
		if($currentrole == 'public')
		{
			$default = 'selected="selected" ';
		} else {
			$default - null;
		}
		$out.='<option '.$default.'value="public">Public</option>'."\n";
	} else {
		$minlevel = sf_get_level($parentrole);
	}
	
	$default = null;
	
	// now the rest
	foreach( $wp_roles->role_names as $role => $name )
	{
		// check role is allowed if group is not public
		if(sf_get_level($role) >= $minlevel)
			{
			if ($currentrole == $role )
			{
				$default = 'selected="selected" ';
			} else {
				$default - null;
			}
			$out.='<option '.$default.'value="'.$role.'">'.$name.'</option>'."\n";
			$default='';
		}
	}
	return $out;
}

function sfa_create_skin_select($skin)
{
	$path = '../wp-content/plugins/'.basename(dirname(__FILE__)).'/skins';

	$out='';
	$default='';
	$dlist = opendir($path);

	while (false !== ($file = readdir($dlist))) 
	{
		if ($file != "." && $file != "..") 
		{
			if($file == $skin)
			{
				$default = 'selected="selected" ';
			} else {
				$default - null;
			}
			$out.='<option '.$default.'value="'.$file.'">'.$file.'</option>'."\n";
			$default='';
		}
	}
	closedir($dlist);
	return $out;
}

function sfa_create_icon_select($icon)
{
	$path = '../wp-content/plugins/'.basename(dirname(__FILE__)).'/icons';
	$out='';
	$default='';
	$dlist = opendir($path);

	while (false !== ($file = readdir($dlist))) 
	{
		if ($file != "." && $file != "..") 
		{
			if($file == $icon)
			{
				$default = 'selected="selected" ';
			} else {
				$default - null;
			}
			$out.='<option '.$default.'value="'.$file.'">'.$file.'</option>'."\n";
			$default='';
		}
	}
	closedir($dlist);
	return $out;
}

function sfa_create_language_select($lang="en")
{
	$path = '../wp-content/plugins/'.basename(dirname(__FILE__)).'/tinymce/langs';
	$out='';
	$default='';
	$dlist = opendir($path);

	while (false !== ($file = readdir($dlist))) 
	{
		if ($file != "." && $file != "..") 
		{
			$langcode=explode(".", $file);
			$langcode=$langcode[0];
			if($langcode == $lang)
			{
				$default = 'selected="selected" ';
			} else {
				$default - null;
			}
			$out.='<option '.$default.'value="'.$langcode.'">'.$langcode.'</option>'."\n";
			$default='';
		}
	}
	closedir($dlist);
	return $out;
}

function sfa_create_moderator_select($userid = 0)
{
	$users = sf_get_users();
	$out='<option></option>';
	$default='';
	foreach($users as $user)
	{
		if($user->ID == $userid)
		{
			$default = 'selected="selected" ';
		} else {
			$default - null;
		}
		$out.='<option '.$default.'value="'.$user->ID.'">'.$user->user_login.'</option>'."\n";
		$default='';
	}
	return $out;
}

function sf_get_users()
{
	global $wpdb;
	
	return $wpdb->get_results("SELECT ID, user_login FROM ".SFUSERS." WHERE ID <> ".ADMINID." ORDER BY user_login");
}

function update_check_option($key)
{
	if(isset($_POST[$key]))
	{
		update_option($key, true);
	} else {
		update_option($key, false);
	}
	return;
}

?>