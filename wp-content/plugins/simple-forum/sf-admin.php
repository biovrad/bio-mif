<?php
/*
Simple Forum 2.1
Admin Panels
*/

define('SFADMINPATH', 'edit.php?page=simple-forum/sf-admin.php');
define('SFUSERPATH', 'edit.php?page=simple-forum/sf-adminusers.php');

include_once('sf-adminforms.php');
include_once('sf-adminoptions.php');
include_once('sf-adminsupport.php');


// check up to date with version
sf_setup_data();

//== DISTRIBUTION ==================================================

// Are we saving Options?
if(isset($_POST['sfoptions'])) sfa_save_options();

// upodating the permaink?
if(isset($_GET['upperm'])) sfa_update_permalink();

// creating a group
if(isset($_POST['newgroup'])) sfa_create_group();

// creating a forum
if(isset($_POST['newforum'])) sfa_create_forum();

// updating a group
if(isset($_POST['updategroup'])) sfa_update_group();

// updating a forum
if(isset($_POST['updateforum'])) sfa_update_forum();

// deleting a group
if(isset($_POST['deletegroup'])) sfa_remove_group();

// deleting a forum
if(isset($_POST['deleteforum'])) sfa_remove_forum();

// display manage forums page?
if((!isset($_GET['panel'])) || ($_GET['panel'] == 'forums')) sfa_forumspage();

// display Options page?
if($_GET['panel'] == 'options') sfa_optionspage();

// display edit group form?
if(isset($_GET['edgroup'])) sfa_edit_group(intval($_GET['id']));

// display edit forum form
if(isset($_GET['edforum'])) sfa_edit_forum(intval($_GET['id']));

// display delete group form?
if(isset($_GET['delgroup'])) sfa_delete_group(intval($_GET['id']));

// display delete forum form?
if(isset($_GET['delforum'])) sfa_delete_forum(intval($_GET['id']));

// display new group form?
if(isset($_GET['newaction']))
{
	switch ($_GET['newaction'])
	{
		case 'group':
			sfa_new_group();
			break;
		case 'forum':
			sfa_new_forum();
			break;
	}
}

	sfa_footer();

//== OPTIONS PANEL PREPARE DATA ====================================

function sfa_optionspage()
{
	global $user_ID, $user_login, $wpdb;

	get_currentuserinfo();
	
	sfa_header(__('Options'), "sforum");
	
	// prepare options data
	$sfoptions = array();
	$sfoptions['sfpage']=get_option('sfpage');
	$sfoptions['sfslug']=get_option('sfslug');
	$sfoptions['sfadmin']=get_option('sfadmin');
	$sfoptions['sfadminname']=get_usermeta($sfoptions['sfadmin'], $wpdb->prefix.'sfadmin');
	$sfoptions['sfallowguests']=get_option('sfallowguests');
	$sfoptions['sfmoderate']=get_option('sfmoderate');
	$sfoptions['sfnotify']=get_option('sfnotify');
	$sfoptions['sfsubscriptions']=get_option('sfsubscriptions');
	$sfoptions['sfsmilies']=get_option('sfsmilies');
	$sfoptions['sfpagedtopics']=get_option('sfpagedtopics');
	$sfoptions['sfedit']=get_option('sfedit');
	$sfoptions['sfuninstall']=get_option('sfuninstall');
	$sfoptions['sfsortdesc']=get_option('sfsortdesc');
	$sfoptions['sfavatars']=get_option('sfavatars');
	$sfoptions['sfshownewadmin']=get_option('sfshownewadmin');
	$sfoptions['sfshownewuser']=get_option('sfshownewuser');
	$sfoptions['sfshownewcount']=get_option('sfshownewcount');
	$sfoptions['sfdates']=get_option('sfdates');
	$sfoptions['sftimes']=get_option('sftimes');
	$sfoptions['sfzone']=get_option('sfzone');
	$sfoptions['sfshowavatars']=get_option('sfshowavatars');
	$sfoptions['sfuserabove']=get_option('sfuserabove');
	$sfoptions['sfquicktags']=get_option('sfquicktags');
	$sfoptions['sfskin']=get_option('sfskin');
	$sfoptions['sficon']=get_option('sficon');
	$sfoptions['sfstopedit']=get_option('sfstopedit');
	$sfoptions['sfmodmembers']=get_option('sfmodmembers');
	$sfoptions['sftopicsort']=get_option('sftopicsort');
	$sfoptions['sfavatarsize']=get_option('sfavatarsize');
	$sfoptions['sfspam']=get_option('sfspam');
	$sfoptions['sfextprofile']=get_option('sfextprofile');
	$sfoptions['sfusersig']=get_option('sfusersig');
	$sfoptions['sfhome']=get_option('sfhome');
	$sfoptions['sfrss']=get_option('sfrss');
	$sfoptions['sfrsscount']=get_option('sfrsscount');
	$sfoptions['sfrsswords']=get_option('sfrsswords');
	$sfoptions['sfpagedposts']=get_option('sfpagedposts');
	$sfoptions['sfgravatar']=get_option('sfgravatar');
	$sfoptions['sfmodonce']=get_option('sfmodonce');
	$sfoptions['sftitle']=get_option('sftitle');
	$sfoptions['sflang']=get_option('sflang');
	$sfoptions['sfstats']=get_option('sfstats');
	$sfoptions['sfshownewabove']=get_option('sfshownewabove');
	$sfoptions['sfshowlogin']=get_option('sfshowlogin');
	$sfoptions['sfregmath']=get_option('sfregmath');
	$sfoptions['sfsearchbar']=get_option('sfsearchbar');
	$sfoptions['sfadminspam']=get_option('sfadminspam');
	$sfoptions['sflinkuse']=get_option('sflinkuse');
	$sfoptions['sflinkexcerpt']=get_option('sflinkexcerpt');
	$sfoptions['sflinkwords']=get_option('sflinkwords');
	$sfoptions['sflinkblogtext']=get_option('sflinkblogtext');
	$sfoptions['sflinkforumtext']=get_option('sflinkforumtext');
	$sfoptions['sflinkabove']=get_option('sflinkabove');
	$sfoptions['sfuseannounce']=get_option('sfuseannounce');
	$sfoptions['sfannouncecount']=get_option('sfannouncecount');
	$sfoptions['sfannouncehead']=get_option('sfannouncehead');
	$sfoptions['sfannounceauto']=get_option('sfannounceauto');
	$sfoptions['sfannouncetime']=get_option('sfannouncetime');
	$sfoptions['sfannouncetext']=get_option('sfannouncetext');
	$sfoptions['sfannouncelist']=get_option('sfannouncelist');
	$sfoptions['sfshowhome']=get_option('sfshowhome');
	$sfoptions['sflockdown']=get_option('sflockdown');
	$sfoptions['sfshowmodposts']=get_option('sfshowmodposts');
	
	if(empty($sfoptions['sfdates'])) $sfoptions['sfdates']='j F Y';
	if(empty($sfoptions['sftimes'])) $sfoptions['sftimes']='g:i a';
	if(empty($sfoptions['sfzone'])) $sfoptions['sfzone']='0';
	
	// only required for display
	$sfoptions['adminlogin'] = $user_login;

	//== Load Moderators
	$moderators = array();
	$moderators = explode(';', get_option('sfmodusers'));
			
	//== Load icon List
	$icons = array();
	$list = explode('@', get_option('sfshowicon'));
	
	foreach($list as $i)
	{
		$temp=explode(';', $i);
		$icons[$temp[0]] = $temp[1];
	}
	
	//== Load View Column Settings
	$cols=get_option('sfforumcols');
	$sfoptions['fc_topics']=$cols['topics'];
	$sfoptions['fc_posts']=$cols['posts'];
	$sfoptions['fc_last']=$cols['last'];

	$cols=get_option('sftopiccols');
	$sfoptions['tc_first']=$cols['first'];
	$sfoptions['tc_last']=$cols['last'];
	$sfoptions['tc_posts']=$cols['posts'];
	$sfoptions['tc_views']=$cols['views'];
	
	$rankings = get_option('sfrankings');

	sfa_options_form($sfoptions, $moderators, $icons, $rankings);

	return;
}

function sfa_update_permalink()
{
	update_option('sfpermalink', get_permalink(get_option('sfpage')));
	sfa_message(__('Forum Permalink Updated', "sforum"));
	return;
}

//== FORUMS PANEL CONTROL ==========================================

function sfa_forumspage()
{
	sfa_header(__('Forums', "sforum"));
	$is_group = sfa_render_forum_index($flag);
	sf_render_forum_buttonbox($is_group);	
	return;
}

function sfa_edit_group($group_id)
{
	$group = sfa_get_group_row($group_id);
	sfa_edit_group_form($group);
	return;
}

function sfa_edit_forum($forum_id)
{
	$forum = sf_get_forum_row($forum_id);
	sfa_edit_forum_form($forum);
	return;
}

function sfa_delete_group($group_id)
{
	$group = sfa_get_group_row($group_id);
	sfa_delete_group_form($group);
	return;
}

function sfa_delete_forum($forum_id)
{
	$forum = sf_get_forum_row($forum_id);
	sfa_delete_forum_form($forum);
	return;
}

function sfa_new_group()
{
	$seq = sfa_next_group_seq();
	sfa_new_group_form($seq+1);
	return;
}

function sfa_new_forum()
{
	sfa_new_forum_form();
	return;
}

//== SAVE OPTIONS ==================================================

function sfa_save_options()
{
	global $wpdb;

	check_admin_referer('forum-adminform_options');
	
	update_option('sfpage', $_POST['sfpage']);
	update_option('sfslug', $_POST['sfslug']);
	update_option('sfadmin', $_POST['sfadmin']);
	update_option('sfpagedtopics', $_POST['sfpagedtopics']);
	update_option('sfshownewcount', $_POST['sfshownewcount']);
	update_option('sfdates', $_POST['sfdates']);
	update_option('sftimes', $_POST['sftimes']);
	update_option('sfzone', $_POST['sfzone']);
	update_option('sfskin', $_POST['sfskin']);
	update_option('sficon', $_POST['sficon']);
	update_option('sfavatarsize', $_POST['sfavatarsize']);
	update_option('sfhome', $_POST['sfhome']);
	update_option('sfrsscount', $_POST['sfrsscount']);
	update_option('sfrsswords', $_POST['sfrsswords']);
	update_option('sfpagedposts', $_POST['sfpagedposts']);
	update_option('sflang', $_POST['sflang']);
	update_option('sflinkwords', $_POST['sflinkwords']);
	update_option('sflinkblogtext', $_POST['sflinkblogtext']);
	update_option('sflinkforumtext', $_POST['sflinkforumtext']);
	update_option('sfannouncecount', $_POST['sfannouncecount']);
	update_option('sfannouncehead', $_POST['sfannouncehead']);
	update_option('sfannouncetime', $_POST['sfannouncetime']);
	update_option('sfannouncetext', $_POST['sfannouncetext']);

	update_check_option('sfedit');
	update_check_option('sfallowguests');
	update_check_option('sfmoderate');
	update_check_option('sfnotify');
	update_check_option('sfsubscriptions');
	update_check_option('sfsmilies');
	update_check_option('sfuninstall');
	update_check_option('sfsortdesc');
	update_check_option('sfavatars');
	update_check_option('sfshownewuser');
	update_check_option('sfshownewadmin');
	update_check_option('sfshowavatars');
	update_check_option('sfuserabove');
	update_check_option('sfquicktags');
	update_check_option('sfstopedit');
	update_check_option('sfmodmembers');
	update_check_option('sftopicsort');
	update_check_option('sfspam');
	update_check_option('sfextprofile');
	update_check_option('sfusersig');
	update_check_option('sfrss');
	update_check_option('sfgravatar');
	update_check_option('sfmodonce');
	update_check_option('sftitle');
	update_check_option('sfstats');
	update_check_option('sfshownewabove');
	update_check_option('sfshowlogin');
	update_check_option('sfregmath');
	update_check_option('sfsearchbar');
	update_check_option('sfadminspam');
	update_check_option('sflinkuse');
	update_check_option('sflinkexcerpt');
	update_check_option('sflinkabove');
	update_check_option('sfuseannounce');
	update_check_option('sfannounceauto');
	update_check_option('sfannouncelist');
	update_check_option('sfshowhome');
	update_check_option('sflockdown');
	update_check_option('sfshowmodposts');
	
	//== Save View Column Settings
	$fcols='';
	$fcols['topics']=false;
	$fcols['posts']=false;
	$fcols['last']=false;
	if(isset($_POST['fc_topics'])) $fcols['topics']=true;
	if(isset($_POST['fc_posts'])) $fcols['posts']=true;
	if(isset($_POST['fc_last'])) $fcols['last']=true;
	update_option('sfforumcols', $fcols);
	
	$tcols='';
	$tcols['first']=false;
	$tcols['last']=false;
	$tcols['posts']=false;
	$tcols['views']=false;
	if(isset($_POST['tc_first'])) $tcols['first']=true;
	if(isset($_POST['tc_last'])) $tcols['last']=true;
	if(isset($_POST['tc_posts'])) $tcols['posts']=true;
	if(isset($_POST['tc_views'])) $tcols['views']=true;
	update_option('sftopiccols', $tcols);

	update_usermeta($_POST['sfadmin'], $wpdb->prefix.'sfadmin', $_POST['sfadminname']);

	// Save Icon String

	//== Load icon List
	$icons = array();
	$list = explode('@', get_option('sfshowicon'));
	
	foreach($list as $i)
	{
		$temp=explode(';', $i);
		$icons[$temp[0]] = $temp[1];
	}

	$x = 0;
	$list='';
	foreach($icons as $key=>$value)
	{
		$list .= $key.';';
		if(isset($_POST['icon'.$x]))
		{
			$list .= '1@';
		} else {
			$list .= '0@';
		}
		$x++;
	}
	$list = substr($list, 0, strlen($list)-1);
	update_option('sfshowicon', $list);

	// Save Moderators String
	$list=$_POST['mod'];
	$mods='';
	for($x=0; $x<count($list); $x++)
	{
		if(!empty($list[$x]))
		{
			$mods.=$list[$x].';';
		}
	}
	if(!empty($mods))
	{
		$mods = substr($mods, 0, strlen($mods)-1);
	}
	update_option('sfmodusers', $mods);

	update_option('sfpermalink', get_permalink(get_option('sfpage')));

	// Save Rankings
	$rankings = array();
	for($x=0; $x<count($_POST['rankdesc']); $x++)
	{
		if(!empty($_POST['rankdesc'][$x]))
		{
			$rankings[$_POST['rankdesc'][$x]] = $_POST['rankpost'][$x];
		}
	}
	update_option('sfrankings', $rankings);

	//== Some minor data integrity checks
	if((get_option('sfmoderate') == false) && (get_option('sfmodmembers') == false))
	{
		update_option('sfmodonce', false);
	}

	if(get_option('sfshowavatars') == false)
	{
		update_option('sfgravatar', false);
		update_option('sfavatars', false);
		update_option('sfavatarsize', 0);
	}
	
	if(get_option('sfrss') == false)
	{
		update_option('sfrsscount', 0);
		update_option('sfrsswords', 0);
	}

	if(get_option('sfquicktags') == false)
	{
		update_option('sfsmilies', false);
	}	

	if(get_option('sfshownewuser') == false)
	{
		update_option('sfshownewcount', 0);
		update_option('sfshownewabove', false);
	}

	if(get_option('sfshownewuser') == true)
	{
		$cnt = get_option('sfshownewcount');
		if(empty($cnt))
		{
			update_option('sfshownewcount',6);
		}
	}
	
	if(get_option('sfuninstall'))
	{
		$mess = __('Options Updated.  Simple Forum will be removed when de-activated', "sforum");
	} else {
		$mess = __('Options Updated', "sforum");
	}
	
	sfa_message($mess);

	return;
}

function sfa_create_group()
{
	check_admin_referer('forum-adminform_groupnew');

	$seq = (sfa_next_group_seq() +1);
	$groupdata = array();

	if(empty($_POST['group_name']))
	{
		$groupdata['group_name'] = __("New Forum Group", "sforum");
	} else {
		$groupdata['group_name'] = $_POST['group_name'];
	}
	if(empty($_POST['group_seq']))
	{
		$groupdata['group_seq'] = $seq;
	} else {
		$groupdata['group_seq'] = $_POST['group_seq'];
	}
	
	$groupdata['group_desc'] = $_POST['group_desc'];
	$groupdata['group_view'] = $_POST['group_view'];

	//force max size
	$groupdata['group_name'] = substr($groupdata['group_name'], 0, 50);
	$groupdata['group_desc'] = substr($groupdata['group_desc'], 0, 150);
	
	// check if we need to shuffle sequence numbers
	if($groupdata['group_seq'] < $seq)
	{
		$groups = sf_get_groups_all();
		foreach($groups as $group)
		{
			if($group->group_seq >= $groupdata['group_seq'])
			{
				sfa_bump_group_seq($group->group_id, ($group->group_seq +1));
			}
		}
	}
	$success = sfa_create_group_row($groupdata);
	
	if($success == false)
	{
		sfa_message(__("New Group Creation Failed!", "sforum"));
	} else {
		sfa_message(__("New Forum Group Created", "sforum"));
	}
	return;
}

function sfa_create_forum()
{
	check_admin_referer('forum-adminform_forumnew');

	$seq = (sfa_next_forum_seq($_POST['group_id']) +1);
	$forumdata = array();
	$forumdata['group_id'] = $_POST['group_id'];
	$forumdata['forum_desc'] = $_POST['forum_desc'];
	
	$forumdata['forum_status'] = 0;
	if(isset($_POST['forum_status'])) $forumdata['forum_status'] = 1;
	
	if(empty($_POST['forum_name']))
	{
		$forumdata['forum_name'] = __("New Forum", "sforum");
	} else {
		$forumdata['forum_name'] = $_POST['forum_name'];
	}
	if(empty($_POST['forum_seq']))
	{
		$forumdata['forum_seq'] = $seq;
	} else {
		$forumdata['forum_seq'] = $_POST['forum_seq'];
	}

	// force max length
	$forumdata['forum_name'] = substr($forumdata['forum_name'], 0, 75);
	$forumdata['forum_desc'] = substr($forumdata['forum_desc'], 0, 150);

	// check if we need to shuffle sequence numbers
	if($forumdata['forum_seq'] < $seq)
	{
		$forums = sf_get_forums_in_group($forumdata['group_id']);
		foreach($forums as $forum)
		{
			if($forum->forum_seq >= $forumdata['forum_seq'])
			{
				sfa_bump_forum_seq($forum->forum_id, ($forum->forum_seq +1));
			}
		}
	}
	$success = sfa_create_forum_row($forumdata);
	
	if($success == false)
	{
		sfa_message(__("New Forum Creation Failed!", "sforum"));
	} else {
		sfa_message(__("New Forum Created", "sforum"));
	}
	return;
}

function sfa_update_group()
{
	check_admin_referer('forum-adminform_groupedit');

	$groupdata=array();
	$group_id = $_POST['group_id'];	
	$groupdata['group_name'] = $_POST['group_name'];
	$groupdata['group_seq'] = $_POST['group_seq'];
	$groupdata['group_desc'] = $_POST['group_desc'];
	$groupdata['group_view'] = $_POST['group_view'];
	
	if($groupdata['group_name'] == $_POST['cgroup_name'] && $groupdata['group_seq'] == $_POST['cgroup_seq'] && $groupdata['group_desc'] == $_POST['cgroup_desc'] && $groupdata['group_view'] == $_POST['cgroup_view'])
	{
		sfa_message(__("No Data Changed", "sforum"));
		return;
	}

	//force max size
	$groupdata['group_name'] = substr($groupdata['group_name'], 0, 50);
	$groupdata['group_desc'] = substr($groupdata['group_desc'], 0, 150);

	// has the sequence changed?
	if($groupdata['group_seq'] != $_POST['cgroup_seq'])
	{
		// need to iterate through the groups to change sequence number
		$groups = sfa_get_other_groups($group_id);
		$cnt = count($groups);
		for($i = 0; $i < $cnt; $i++)
		{
			if(($i+1) < $groupdata['group_seq'])
			{
				sfa_bump_group_seq($groups[$i]->group_id, ($i+1));
			} else {
				sfa_bump_group_seq($groups[$i]->group_id, ($i+2));
			}
		}
	}
	
	// do we need to update the forums in a non public group?
	$updateforums = false;
	if($groupdata['group_view'] != $_POST['cgroup_view']) $updateforums = true;
	
	$success = sfa_update_group_row($group_id, $groupdata, $updateforums);
	
	if($success == false)
	{
		sfa_message(__("Update Failed!", "sforum"));
	} else {
		sfa_message(__("Forum Group Record Updated", "sforum"));
	}
	return;
}

function sfa_update_forum()
{
	check_admin_referer('forum-adminform_forumedit');

	$forumdata=array();
	$forum_id=$_POST['forum_id'];
	$forumdata['forum_name']=$_POST['forum_name'];
	$forumdata['forum_desc']=$_POST['forum_desc'];
	$forumdata['forum_seq']=$_POST['forum_seq'];
	$forumdata['group_id']=$_POST['group_id'];
	$forumdata['forum_view']=$_POST['forum_view'];

	$forumdata['forum_status'] = 0;
	if(isset($_POST['forum_status'])) $forumdata['forum_status'] = 1;

	if(($forumdata['forum_name'] == $_POST['cforum_name']) && ($forumdata['forum_seq'] == $_POST['cforum_seq']) && ($forumdata['group_id'] == $_POST['cgroup_id']) && ($forumdata['forum_status'] == $_POST['cforum_status']) && ($forumdata['forum_desc'] == $_POST['cforum_desc']) && ($forumdata['forum_view'] == $_POST['cforum_view']))
	{
		sfa_message(__("No Data Changed", "sforum"));
		return;
	}

	// force max length
	$forumdata['forum_name'] = substr($forumdata['forum_name'], 0, 75);
	$forumdata['forum_desc'] = substr($forumdata['forum_desc'], 0, 150);
	
	// has the forum changed to a new group
	if($forumdata['group_id'] != $_POST['cgroup_id'])
	{
		// let's resequence old group list first
		$forums = sfa_get_other_forums($_POST['cgroup_id'], $forum_id);
		$cnt = count($forums);
		for($i = 0; $i < $cnt; $i++)
		{
			sfa_bump_forum_seq($forums[$i]->forum_id, ($i+1));
		}
		
		// now we can make room in  new group
		
		$seq = (sfa_next_forum_seq($forumdata['group_id']) +1);		
		if($forumdata['forum_seq'] < $seq)
		{
			$forums = sf_get_forums_in_group($forumdata['group_id']);
			foreach($forums as $forum)
			{
				if($forum->forum_seq >= $forumdata['forum_seq'])
				{
					sfa_bump_forum_seq($forum->forum_id, ($forum->forum_seq +1));
				}
			}
		}
	} else {
		// same group but has the seq changed?
		if($forumdata['forum_seq'] != $_POST['cforum_seq'])
		{
			$forums = sfa_get_other_forums($_POST['cgroup_id'], $forum_id);
			$cnt = count($forums);
			for($i = 0; $i < $cnt; $i++)
			{		
				if(($i+1) < $forumdata['forum_seq'])
				{
					sfa_bump_forum_seq($forums[$i]->forum_id, ($i+1));
				} else {
					sfa_bump_forum_seq($forums[$i]->forum_id, ($i+2));
				}
			}
		}
	}

	// Finally - we can save the updaqted forum record!	
	$success = sfa_update_forum_row($forum_id, $forumdata);
	
	if($success == false)
	{
		sfa_message(__("Update Failed!", "sforum"));
	} else {
		sfa_message(__("Forum Record Updated", "sforum"));
	}
	return;
}

function sfa_remove_group()
{
	check_admin_referer('forum-adminform_groupdelete');

	$group_id = $_POST['group_id'];	
	$cseq = $_POST['cgroup_seq'];
	
	sfa_remove_group_data($group_id);

	// need to iterate through the groups
	$groups = sf_get_groups_all();
	foreach($groups as $group)
	{
		if($group->group_seq > $cseq)
		{
			sfa_bump_group_seq($group->group_id, ($group->group_seq -1));
		}
	}
	
	sfa_message(__("Forum Group Deleted", "sforum"));
	return;
}

function sfa_remove_forum()
{
	check_admin_referer('forum-adminform_forumdelete');

	$group_id = $_POST['group_id'];
	$forum_id = $_POST['forum_id'];	
	$cseq = $_POST['cforum_seq'];

	sfa_remove_forum_data($forum_id);

	// need to iterate through the groups
	$forums = sf_get_forums_in_group($group_id);
	foreach($forums as $forum)
	{
		if($forum->forum_seq > $cseq)
		{
			sfa_bump_forum_seq($forum->forum_id, ($forum->forum_seq -1));
		}
	}
	return;
}

function sfa_message($message)
{
	echo '<div id="message" class="updated fade"><p><strong>'.$message.'</strong></p></div>';
	return;
}

?>