<?php
/*
Simple Forum 2.1
Upgrade Path Routines
*/

include_once('sf-upgradesupport.php');


// == ACTIONS - ACTIVATE (DB+ Setup) ===========================================================

function sf_setup_data()
{
	$current_version = get_option('sfversion');
	$current_build = get_option('sfbuild');

	// Have we already installed the base stuff?
	if(version_compare($current_version, '1.0', '<'))
	{
		// Initialise version controls
		add_option('sfversion', '1.0');
		add_option('sfbuild', 1);
		sf_setup_base_data();
		$current_build = 1;
	}

	// Base already installed - check Build Number (not version at this point)
	if($current_build < SFBUILD)
	{
		sf_perform_upgrades($current_version, $current_build);
	}

	return;
}

// == SETUP BASE (V1) DATA =====================================================================

function sf_setup_base_data()
{
	global $wpdb, $wp_version, $user_ID, $user_identity;

	get_currentuserinfo();

	//== CREATE THE BASE TABLES

	$sql = "
		CREATE TABLE IF NOT EXISTS ".SFGROUPS." (
			group_id bigint(20) NOT NULL auto_increment,
			group_name varchar(50) default NULL,
			group_seq int(4) default NULL,
			PRIMARY KEY (group_id)
		) ENGINE=MyISAM ".sf_charset().";";
	$wpdb->query($sql);

	$sql = "
		CREATE TABLE IF NOT EXISTS ".SFFORUMS." (
			forum_id bigint(20) NOT NULL auto_increment,
			forum_name varchar(75) default NULL,
			group_id bigint(20) NOT NULL,
			forum_seq int(4) default NULL,
			PRIMARY KEY (forum_id)
		) ENGINE=MyISAM ".sf_charset().";";
	$wpdb->query($sql);

	$sql = "
		CREATE TABLE IF NOT EXISTS ".SFTOPICS." (
			topic_id bigint(20) NOT NULL auto_increment,
			topic_name varchar(100) NOT NULL,
			topic_date datetime NOT NULL,
			topic_status int(4) NOT NULL default '0',
			forum_id bigint(20) NOT NULL,
			user_id bigint(20) default NULL,
			topic_pinned smallint(1) NOT NULL default '0',
			PRIMARY KEY (topic_id)
		) ENGINE=MyISAM ".sf_charset().";";
	$wpdb->query($sql);

	$sql = "
		CREATE TABLE IF NOT EXISTS ".SFPOSTS." (
			post_id bigint(20) NOT NULL auto_increment,
			post_content text,
			post_date datetime NOT NULL,
			topic_id bigint(20) NOT NULL,
			user_id bigint(20) default NULL,
			forum_id bigint(20) NOT NULL,
			guest_name varchar(25) default NULL,
			guest_email varchar(50) default NULL,
			post_status int(4) NOT NULL default '0',
			PRIMARY KEY (post_id),
			FULLTEXT KEY post_content (post_content)
		) ENGINE=MyISAM ".sf_charset().";";
	$wpdb->query($sql);

	$sql = "
		CREATE TABLE IF NOT EXISTS ".SFWAITING." (
			topic_id bigint(20) NOT NULL,
			forum_id bigint(20) NOT NULL,
			post_count int(4) NOT NULL default '0',
			PRIMARY KEY (topic_id)
		) ENGINE=MyISAM ".sf_charset().";";
	$wpdb->query($sql);

	//== CREATE THE WP PAGE RECORD

	if(version_compare($wp_version, '2.1', '<'))
	{
		//== WP 2.0 VERSION
		$wpdb->query("INSERT INTO ".$wpdb->prefix."posts ( post_status, post_title, post_name, comment_status, ping_status, post_author, post_date, post_modified ) VALUES ( 'static', 'Simple Forum', 'sf-forum', 'closed', 'closed', ".$user_ID." , now('Y-m-d G:i:s') , now('Y-m-d G:i:s'))");
	} else {
		//== WP 2.1 and 2.2 VERSION
		$wpdb->query("INSERT INTO ".$wpdb->prefix."posts ( post_type, post_status, post_title, post_name, comment_status, ping_status, post_author, post_date, post_modified ) VALUES ( 'page', 'publish', 'Simple Forum', 'sf-forum', 'closed', 'closed', ".$user_ID." , now('Y-m-d G:i:s') , now('Y-m-d G:i:s'))");
	}

	//== GRAB NEW PAGE ID

	$id = $wpdb->insert_id;

	// update the guid for the new page
	$guid = get_permalink($id);
	$wpdb->query("UPDATE {$wpdb->prefix}posts SET guid='".$guid."' WHERE ID=".$id);

	//== CREATE BASE OPTION RECORDS

	add_option('sfpage', $id);
	add_option('sfslug', 'sf-forum');
	add_option('sfedit', true);
	add_option('sfallowguests', true);
	add_option('sfmoderate', true);
	add_option('sfsearch', '');
	add_option('sfsmilies', true);
	add_option('sfadmin', $user_ID);
	add_option('sfnotify', true);
	add_option('sfsubscriptions', true);
	add_option('sfpagedtopics', 12);
	add_option('sfuninstall', false);

// 2.1 Patch 2
	update_usermeta($user_ID, $wpdb->prefix.'sfadmin', stripslashes($user_identity));

	return;
}

// == VERSION UPGRADES =========================================================================

function sf_perform_upgrades($version, $build)
{
	global $wpdb;

	// 1.2 ====================================================================================
	if(version_compare($version, '1.2', '<'))
	{
		add_option('sfsortdesc', false);
	}

	// 1.3 ====================================================================================
	if(version_compare($version, '1.3', '<'))
	{
		add_option('sfavatars', true);
		add_option('sfshownewadmin', true);
		add_option('sfshownewuser', true);
		add_option('sfshownewcount', 6);
		add_option('sfdates', get_option('date_format'));
		add_option('sftimes', get_option('time_format'));
		add_option('sfzone', 0);

		$create_ddl = "ALTER TABLE ".SFFORUMS. " ADD (forum_desc varchar(150) default NULL)";
		sf_upgrade_database(SFFORUMS, 'forum_desc', $create_ddl);
	}

	// 1.4 ====================================================================================
	if(version_compare($version, '1.4', '<'))
	{
		add_option('sfshowavatars', true);
		add_option('sfuserabove', false);
		add_option('sfrte', true);
		add_option('sfskin', 'default');
		add_option('sficon', 'default');
	}

	// 1.6 ====================================================================================
	if(version_compare($version, '1.6', '<'))
	{
		$create_ddl = "ALTER TABLE ".SFFORUMS. " ADD (forum_status int(4) NOT NULL default '0')";
		sf_upgrade_database(SFFORUMS, 'forum_status', $create_ddl);

		$create_ddl = "ALTER TABLE ".SFPOSTS. " ADD (post_pinned smallint(1) NOT NULL default '0')";
		sf_upgrade_database(SFPOSTS, 'post_pinned', $create_ddl);

		$postusers = $wpdb->get_results("SELECT user_id, COUNT(post_id) AS numposts FROM ".SFPOSTS." WHERE user_id IS NOT NULL GROUP BY user_id");
		if($postusers)
		{
			foreach($postusers as $postuser)
			{
				update_user_option($postuser->user_id, 'sfposts', $postuser->numposts);
			}
		}

		add_option('sfstopedit', true);
		add_option('sfmodmembers', false);
		add_option('sfmodusers', '');
		add_option('sftopicsort', false);

		sf_check_data_integrity();
	}

	// 1.7 ====================================================================================
	if(version_compare($version, '1.7', '<'))
	{

		$create_ddl = "ALTER TABLE ".SFTOPICS. " ADD (topic_subs longtext)";
		sf_upgrade_database(SFTOPICS, 'topic_subs', $create_ddl);

		sf_rebuild_subscriptions();

		add_option('sfavatarsize', 50);
		$qt = get_option('sfrte');
		if($qt)
		{
			add_option('sfquicktags', false);
		} else {
			add_option('sfquicktags', true);
		}

		delete_option('sffilters');
		delete_option('sfrte');

		$create_ddl = "ALTER TABLE ".SFGROUPS. " ADD (group_desc varchar(150) default NULL)";
		sf_upgrade_database(SFGROUPS, 'group_desc', $create_ddl);

		$create_ddl = "ALTER TABLE ".SFGROUPS. " ADD (group_view varchar(20) default 'public')";
		sf_upgrade_database(SFGROUPS, 'group_view', $create_ddl);

		$create_ddl = "ALTER TABLE ".SFFORUMS. " ADD (forum_view varchar(20) default 'public')";
		sf_upgrade_database(SFFORUMS, 'forum_view', $create_ddl);
	}

	// 1.8 ====================================================================================
	if(version_compare($version, '1.8', '<'))
	{
		$create_ddl = "ALTER TABLE ".SFTOPICS. " ADD (topic_sort varchar(4) default NULL)";
		sf_upgrade_database(SFTOPICS, 'topic_sort', $create_ddl);

		add_option('sfspam', true);
		add_option('sfpermalink', get_permalink(get_option('sfpage')));
		add_option('sfextprofile', true);
		add_option('sfusersig', true);
		add_option('sfhome', get_option('home'));
	}

	// 1.9 ====================================================================================
	if(version_compare($version, '1.9', '<'))
	{
		$create_ddl = "ALTER TABLE ".SFTOPICS. " ADD (topic_opened bigint(20) NOT NULL default '0')";
		sf_upgrade_database(SFTOPICS, 'topic_opened', $create_ddl);

		$icons='Login;1@Register;1@Logout;1@Profile;1@Add a New Topic;1@Forum Locked;1@Reply to Post;1@Topic Locked;1@Quote and Reply;1@Edit Your Post;1@Return to Search Results;1@Subscribe;1@Forum RSS;1@Topic RSS;1';
		update_option('sfshowicon', $icons);

		add_option('sfrss', true);
		add_option('sfrsscount', 15);
		add_option('sfrsswords', 0);
		add_option('sfpagedposts', 20);
		add_option('sfgravatar', false);
		add_option('sfmodonce', false);
		add_option('sftitle', true);
		add_option('sflang', 'en');

		$fcols['topics']=true;
		$fcols['posts']=true;
		add_option('sfforumcols', $fcols);

		$tcols['first']=true;
		$tcols['last']=true;
		$tcols['posts']=true;
		$tcols['views']=true;
		add_option('sftopiccols', $tcols);

		$sql = "
		CREATE TABLE IF NOT EXISTS ".SFTRACK." (
			id bigint(20) NOT NULL auto_increment,
			trackuserid bigint(20) default 0,
			trackname varchar(25) NOT NULL,
			trackdate datetime NOT NULL,
			PRIMARY KEY (id)
		) ENGINE=MyISAM ".sf_charset().";";
		$wpdb->query($sql);
	}

	// 2.0 ====================================================================================
	if(version_compare($version, '2.0', '<'))
	{
		$create_ddl = "ALTER TABLE ".SFWAITING. " ADD (post_id bigint(20) NOT NULL default '0')";
		sf_upgrade_database(SFWAITING, 'post_id', $create_ddl);

		$sql = "ALTER TABLE ".SFTRACK." MODIFY trackname VARCHAR(50) NOT NULL;";
		$wpdb->query($sql);

		sf_clean_topic_subs();

		$icons=get_option('sfshowicon');
		if(strpos($icons, '@New Posts;') === false)
		{
			$icons.= '@All RSS;1@Search;1@New Posts;1';
			update_option('sfshowicon', $icons);
		}

		$sql = "
			CREATE TABLE IF NOT EXISTS ".SFSETTINGS." (
				setting_id bigint(20) NOT NULL auto_increment,
				setting_name varchar(20) NOT NULL,
				setting_value longtext,
				PRIMARY KEY (setting_id)
		) ENGINE=MyISAM ".sf_charset().";";
		$wpdb->query($sql);

		$sql = "
			CREATE TABLE IF NOT EXISTS ".SFNOTICE." (
				id varchar(30) NOT NULL,
				item varchar(15),
				message longtext,
				PRIMARY KEY (id)
		) ENGINE=MyISAM ".sf_charset().";";
		$wpdb->query($sql);

		delete_option('sfsearch');
		delete_option('sfaction');
		delete_option('sfppage');
		delete_option('sftpage');
		delete_option('sfmessage');

		add_option('sfstats', true);
		add_option('sfshownewabove', false);
		add_option('sfshowlogin', true);

		sf_relocate_avatars();
	}

	// 2.1 ====================================================================================
	if((version_compare($version, '2.1', '<')) || ($build < 225))
	{
		sf_correct_sflast();

		$wpdb->query("DELETE FROM ".SFSETTINGS." WHERE setting_name <> 'maxonline';");
		$wpdb->query("ALTER TABLE ".SFSETTINGS." MODIFY setting_name VARCHAR(50) NOT NULL;");

		$create_ddl = "ALTER TABLE ".SFSETTINGS." ADD (setting_date datetime NOT NULL);";
		sf_upgrade_database(SFSETTINGS, 'setting_date', $create_ddl);

		$create_ddl = "ALTER TABLE ".SFNOTICE." ADD (ndate datetime NOT NULL);";
		sf_upgrade_database(SFNOTICE, 'ndate', $create_ddl);

		$create_ddl = "ALTER TABLE ".SFWAITING." ADD (user_id bigint(20) default 0);";
		sf_upgrade_database(SFWAITING, 'user_id', $create_ddl);

		$wpdb->query("ALTER TABLE ".SFFORUMS." ADD INDEX groupf_idx (group_id);");
		$wpdb->query("ALTER TABLE ".SFPOSTS." ADD INDEX topicp_idx (topic_id);");
		$wpdb->query("ALTER TABLE ".SFPOSTS." ADD INDEX forump_idx (forum_id);");
		$wpdb->query("ALTER TABLE ".SFTOPICS." ADD INDEX forumt_idx (forum_id);");

		add_option('sfregmath', true);
		add_option('sfsearchbar', true);
		add_option('sfadminspam', true);
		add_option('sfshowhome', true);
		add_option('sflockdown', false);
		add_option('sfshowmodposts', true);

		// --LINKS---------------------------
		$create_ddl = "ALTER TABLE ".SFTOPICS. " ADD (blog_post_id bigint(20) NOT NULL default '0')";
		sf_upgrade_database(SFTOPICS, 'blog_post_id', $create_ddl);

		add_option('sflinkuse', false);
		add_option('sflinkexcerpt', false);
		add_option('sflinkwords', 100);
		add_option('sflinkblogtext', '%ICON% Обсудить статью в форуме');
		add_option('sflinkforumtext', '%ICON% Прочитать оригинал статьи в блоге');
		add_option('sflinkabove', false);

		// --ANNOUNCE TAG --------------------
		add_option('sfuseannounce', false);
		add_option('sfannouncecount', 8);
		add_option('sfannouncehead', 'Последние сообщения с форума');
		add_option('sfannounceauto', false);
		add_option('sfannouncetime', 60);
		add_option('sfannouncetext', '%TOPICNAME% написал %POSTER% в форуме %FORUMNAME% в %DATETIME%');
		add_option('sfannouncelist', false);

		// --RANKINGS -----------------------
		$ranks=array('Новичок' => 2, 'Участник' => 1000);
		add_option('sfrankings', $ranks);

		$icons=get_option('sfshowicon');
		if(strpos($icons, '@Group RSS;') === false)
		{
			$icons.= '@Group RSS;1';
			update_option('sfshowicon', $icons);
		}
	}

	if($build < 228)
	{
		// new since build 225
		$cols=get_option('sfforumcols');
		$cols['last'] = false;
		update_option('sfforumcols', $cols);
	}

	// 2.1 Patch 2 ====================================================================================
	// From this version onwards use Build Number instead of Version Number

	if($build < 236)
	{
		// pre-create last visit dates for all existing users who don't have one
		sf_precreate_sflast();
	}

	// Finished Upgrades ========================================================================

	update_option('sfversion', SFVERSION);
	update_option('sfbuild', SFBUILD);

	return;
}


// == ACTIONS (REMOVE ALL AGAIN) ===============================================================

function sf_remove_data()
{
	global $wpdb;

	if(get_option('sfuninstall'))
	{
		// first remove tables
		$wpdb->query("DROP TABLE IF EXISTS ".SFGROUPS);
		$wpdb->query("DROP TABLE IF EXISTS ".SFFORUMS);
		$wpdb->query("DROP TABLE IF EXISTS ".SFTOPICS);
		$wpdb->query("DROP TABLE IF EXISTS ".SFPOSTS);
		$wpdb->query("DROP TABLE IF EXISTS ".SFWAITING);
		$wpdb->query("DROP TABLE IF EXISTS ".SFTRACK);
		$wpdb->query("DROP TABLE IF EXISTS ".SFSETTINGS);
		$wpdb->query("DROP TABLE IF EXISTS ".SFNOTICE);

		// and remove optionb records
		$optionlist = array('sfversion', 'sfbuild', 'sfpage', 'sfslug', 'sfedit', 'sfsearch', 'sfsmilies', 'sfadmin', 'sfnotify', 'sfsubscriptions', 'sfpagedtopics', 'sfuninstall', 'sfallowguests', 'sfmoderate', 'sfsortdesc', 'sfmessage', 'sfavatars', 'sfshownewadmin', 'sfshownewuser', 'sfshownewcount', 'sfdates', 'sftimes', 'sfzone', 'sfshowavatars', 'sfuserabove', 'sfskin', 'sficon', 'sfshowicon', 'sfstopedit', 'sfmodmembers', 'sfmodusers', 'sftopicsort', 'sfavatarsize', 'sfquicktags', 'sfspam', 'sfpermalink', 'sfextprofile', 'sfusersig', 'sfhome', 'sftpage', 'sfaction', 'sfrss', 'sfrsscount', 'sfrsswords', 'sfpagedposts', 'sfforumcols', 'sftopiccols', 'sfmodonce', 'sfppage', 'sflang', 'sftitle', 'sfgravatar', 'sfstats', 'sfshownewabove', 'sfshowlogin', 'sfregmath', 'sfsearchbar', 'sfadminspam', 'sflinkuse', 'sflinkexcerpt', 'sflinkwords', 'sflinkblogtext', 'sflinkforumtext', 'sflinkabove', 'sfuseannounce', 'sfannouncecount', 'sfannouncehead', 'sfannounceauto', 'sfannouncetime', 'sfannouncetext', 'sfannouncelist', 'sfshowhome', 'sflockdown', 'sfshowmodposts', 'sfrankings');
		foreach($optionlist as $option)
		{
			delete_option($option);
		}

		// now remove user meta data
		$wpdb->query("DELETE FROM ".SFUSERMETA." WHERE meta_key='".$wpdb->prefix."sfadmin' OR meta_key='".$wpdb->prefix."sfavatar' OR meta_key='".$wpdb->prefix."sfposts' OR meta_key='".$wpdb->prefix."sfsubscribe';");
	}
	return;
}


?>