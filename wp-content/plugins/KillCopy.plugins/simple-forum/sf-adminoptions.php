<?php
/*
Simple Forum 2.1
Admin Option Forms Rendering
*/

function sfa_options_form($sfoptions, $moderators, $icons, $rankings)
{ ?>
	<!-- Options Panel -->

<div class="wrap">

	<form action="<?php echo(SFADMINPATH); ?>&amp;panel=options" method="post" name="sfoptions">
	<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_options'); ?>

	<input type="submit" class="sfacontrol" name="sfoptions" value="<?php _e("Save Option Changes", "sforum") ?>" />

	<div class="tabber">

<?php

//== ADMIN Tab ============================================================

	sfa_paint_options_init();

	sfa_paint_open_tab(__("Admin", "sforum"));

		sfa_paint_open_panel(__("The Page ID and the Page Slug have to match those of the WordPress Page the forum will use. If you change them - they require changing here as well as on the page record", "sforum"));
			sfa_paint_open_fieldset(__("Forum Page Details", "sforum"));
				sfa_paint_input(__("Forum Page ID", "sforum"), "sfpage", $sfoptions['sfpage']);
				sfa_paint_input(__("Forum Page Slug", "sforum"), "sfslug", $sfoptions['sfslug']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("You can set any registered user to be a Moderator granting them Admin rights within the forum. This does NOT grant them any other rights within your weblog", "sforum"));
			sfa_paint_open_fieldset(__("Forum Moderators", "sforum"));

				if($moderators)
				{
					foreach($moderators as $moderator)
					{
						sfa_paint_select_start(__("Select User", "sforum"), "mod[]");
						echo(sfa_create_moderator_select($moderator));
						sfa_paint_select_end();
					}
				}
				sfa_paint_select_start(__("Select User", "sforum"), "mod[]");
				echo(sfa_create_moderator_select(0));
				sfa_paint_select_end();

			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_tab_new_row();

		sfa_paint_open_panel(__("This must be set to the user ID of the main forum Administrator - usually the person that installed it. If you prefer to keep your login name secret, you can set an alternative for the forum to use", "sforum"));
			sfa_paint_open_fieldset(__("Forum Administrator", "sforum"));
				sfa_paint_input(__("Forum Administrator ID", "sforum"), "sfadmin", $sfoptions['sfadmin']);
				sfa_paint_input(__("Forum Display Name", "sforum"), "sfadminname", $sfoptions['sfadminname']);
			sfa_paint_close_fieldset();

			sfa_paint_open_fieldset(__("Lock Down Forum", "sforum"));
				sfa_paint_checkbox(__("Lock the entire forum (read only)", "sforum"), "sflockdown", $sfoptions['sflockdown']);
			sfa_paint_close_fieldset();

		sfa_paint_close_panel();

		sfa_paint_open_panel(__("An email can be sent to the Admin when a new post is created and unread posts can be dispalyed on the front page. The Admin tool icons can also be turned on/off within the forum itself", "sforum"));
			sfa_paint_open_fieldset(__("New Posts and Tools", "sforum"));
				sfa_paint_checkbox(__("Notify Admin (email) on new Topic/Post", "sforum"), "sfnotify", $sfoptions['sfnotify']);
				sfa_paint_checkbox(__("Display Unread Posts on Front Page", "sforum"), "sfshownewadmin", $sfoptions['sfshownewadmin']);
				sfa_paint_checkbox(__("Include Posts by Moderators in list", "sforum"), "sfshowmodposts", $sfoptions['sfshowmodposts']);
				sfa_paint_checkbox(__("Display Admin Tool Icons", "sforum"), "sfedit", $sfoptions['sfedit']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== USERS Tab ============================================================

	sfa_paint_open_tab(__("Users", "sforum"));

		sfa_paint_open_panel(__("Your forum can be for registered members only or can allow 'guests' to post. You can optionally moderate posts, checking them before publishing and full Member profile data can be collected and displayed", "sforum").'<br />'.__("You can allow Members to save a textual signature. Edits to a post can be denied after the post has received a reply. You can allow Members to subscribe to email notifications for topics", "sforum"));

			if (false == get_option('users_can_register'))
			{
				echo (__('New User Registrations are Currently Disabled', "sforum"));
				$disabled=true;
				$sfoptions['sfshowlogin'] = false;
				$sfoptions['sfmodmembers'] = false;
			} else {
				$disabled-false;
			}

			sfa_paint_open_fieldset(__("Users and Registration", "sforum"));
				sfa_paint_checkbox(__("Allow Guests to Post in Public Forums", "sforum"), "sfallowguests", $sfoptions['sfallowguests']);
				sfa_paint_checkbox(__("Show Login/Logout/Register if allowed", "sforum"), "sfshowlogin", $sfoptions['sfshowlogin'], $disabled);
				sfa_paint_checkbox(__("Moderate Non-Registered Users Posts", "sforum"), "sfmoderate", $sfoptions['sfmoderate']);
				sfa_paint_checkbox(__("Moderate Registered Members Posts", "sforum"), "sfmodmembers", $sfoptions['sfmodmembers'], $disabled);
				sfa_paint_checkbox(__("Moderate First Post Only", "sforum"), "sfmodonce", $sfoptions['sfmodonce']);
				sfa_paint_checkbox(__("Collect and Display Extended Profile", "sforum"), "sfextprofile", $sfoptions['sfextprofile']);
			sfa_paint_close_fieldset();

			sfa_paint_open_fieldset(__("User Permissions", "sforum"));
				sfa_paint_checkbox(__("Allow User Signatures (text)", "sforum"), "sfusersig", $sfoptions['sfusersig']);
				sfa_paint_checkbox(__("Disallow User Edits after Reply to Post", "sforum"), "sfstopedit", $sfoptions['sfstopedit']);
				sfa_paint_checkbox(__("Allow Users to Subscribe (email) to Topics", "sforum"), "sfsubscriptions", $sfoptions['sfsubscriptions']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("You can set rankings against users marking the number of posts they have made. Set the rankings in numeric order. Set the final ranking high enough", "sforum"));
			sfa_paint_open_fieldset(__("User Rankings", "sforum"));

				sfa_paint_rankings_table($rankings);

			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== GLOBAL Tab ============================================================

	sfa_paint_open_tab(__("Global", "sforum"));

		sfa_paint_open_panel(__("Select whether to display Avatars or not. You can choose to accept Gravatars by default or allow users to upload files to your site. Alowing both will use local avatars when the Gravatr servier is not available. Finally, you can set the size limit to allow", "sforum"));
			sfa_paint_open_fieldset(__("Avatars", "sforum"));
				sfa_paint_checkbox(__("Display Avatars", "sforum"), "sfshowavatars", $sfoptions['sfshowavatars']);
				sfa_paint_checkbox(__("Use Gravatars by default", "sforum"), "sfgravatar", $sfoptions['sfgravatar']);
				sfa_paint_checkbox(__("Allow Registered User Avatar Upload", "sforum"), "sfavatars", $sfoptions['sfavatars']);
				sfa_paint_input(__("Maximum Avatar Width/Height (pixels)", "sforum"), "sfavatarsize", $sfoptions['sfavatarsize']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("Select whether to allow people to subscribe to RSS2 feeds from forums and topics. You may also set how many most recent items to allow to be fed. Finally determine whether to allow a complete post (set to 0) or to limit each post to so many words", "sforum"));
			sfa_paint_open_fieldset(__("RSS Feeds (Forums/Topics)", "sforum"));
				sfa_paint_checkbox(__("Allow RSS Feeds", "sforum"), "sfrss", $sfoptions['sfrss']);
				sfa_paint_input(__("Number of Recent Posts to feed", "sforum"), "sfrsscount", $sfoptions['sfrsscount']);
				sfa_paint_input(__("Limit to Number of Words (0=all)", "sforum"), "sfrsswords", $sfoptions['sfrsswords']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_tab_new_row();

		sfa_paint_open_panel(__("Select whether or not to use the Spam Post prevention tools on postings. This uses, amongst other devices, the simple math question. You may also set the url to use for the 'Home' link in the forum breadcrumbs", "sforum"));
			sfa_paint_open_fieldset(__("Spam Detection", "sforum"));
				sfa_paint_checkbox(__("Use Spam Prevention Tools", "sforum"), "sfspam", $sfoptions['sfspam']);
				sfa_paint_checkbox(__("Use Spam Tools for Admin and Moderators", "sforum"), "sfadminspam", $sfoptions['sfadminspam']);
				sfa_paint_checkbox(__("Use Spam Tools on User Registrations", "sforum"), "sfregmath", $sfoptions['sfregmath']);
			sfa_paint_close_fieldset();
			sfa_paint_open_fieldset(__("Breadcrumb 'Home' Link", "sforum"));
				sfa_paint_checkbox(__("Show Home Link", "sforum"), "sfshowhome", $sfoptions['sfshowhome']);
				sfa_paint_input(__("Home", "sforum"), "sfhome", $sfoptions['sfhome']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("The forums use the TinyMCE rich text editor by default. If you prefer a simpler editor, you can select to use the Quicktags editor instead", "sforum"));
			sfa_paint_open_fieldset(__("Post Editing", "sforum"));
				sfa_paint_checkbox(__("Use 'Quicktags' Editor", "sforum"), "sfquicktags", $sfoptions['sfquicktags']);
				sfa_paint_checkbox(__("Convert Smilies (Quicktags)", "sforum"), "sfsmilies", $sfoptions['sfsmilies']);
			sfa_paint_close_fieldset();
			sfa_paint_open_fieldset(__("Page Title", "sforum"));
				sfa_paint_checkbox(__("Show Forum/Topic in Page Title", "sforum"), "sftitle", $sfoptions['sftitle']);
			sfa_paint_close_fieldset();
			sfa_paint_open_fieldset(__("Display Forum Statistics", "sforum"));
				sfa_paint_checkbox(__("Display Forum Statistics", "sforum"), "sfstats", $sfoptions['sfstats']);
				sfa_paint_checkbox(__("Display Search Bar", "sforum"), "sfsearchbar", $sfoptions['sfsearchbar']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== FORUMS Tab ============================================================

	sfa_paint_open_tab(__("Forums", "sforum"));

		sfa_paint_open_panel(__("You can select to have a recent post list shown to users on the front page and also determne how many posts to show", "sforum"));
			sfa_paint_open_fieldset(__("Forum View Formatting", "sforum"));
				sfa_paint_checkbox(__("Display Recent Posts on Front Page", "sforum"), "sfshownewuser", $sfoptions['sfshownewuser']);
				sfa_paint_input(__("Number of Recent Posts to Display", "sforum"), "sfshownewcount", $sfoptions['sfshownewcount']);
				sfa_paint_checkbox(__("Display Recent Posts Above Groups", "sforum"), "sfshownewabove", $sfoptions['sfshownewabove']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("Select which columns to display when viewing the list of Forums in Groups", "sforum"));
			sfa_paint_open_fieldset(__("Forum View Columns", "sforum"));
				sfa_paint_checkbox(__("Show the Last Post Column", "sforum"), "fc_last", $sfoptions['fc_last']);
				sfa_paint_checkbox(__("Show the Topic Count Column", "sforum"), "fc_topics", $sfoptions['fc_topics']);
				sfa_paint_checkbox(__("Show the Post Count Column", "sforum"), "fc_posts", $sfoptions['fc_posts']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== TOPIC Tab ============================================================

	sfa_paint_open_tab(__("Topics", "sforum"));

		sfa_paint_open_panel(__("Choose how many Topics to display per page. You can also opt to have topics sorted so that those with most recent posting appear at the top - the default is  by creation date", "sforum"));
			sfa_paint_open_fieldset(__("Topic View Formatting", "sforum"));
				sfa_paint_input(__("Topics to Display Per Page", "sforum"), "sfpagedtopics", $sfoptions['sfpagedtopics']);
				sfa_paint_checkbox(__("Sort Topics by Most recent Postings (newest first)", "sforum"), "sftopicsort", $sfoptions['sftopicsort']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("Select which columns to display when viewing the list of Topics in a Forum", "sforum"));
			sfa_paint_open_fieldset(__("Topic View Columns", "sforum"));
				sfa_paint_checkbox(__("Show the Topic Started Column", "sforum"), "tc_first", $sfoptions['tc_first']);
				sfa_paint_checkbox(__("Show the Last Post Column", "sforum"), "tc_last", $sfoptions['tc_last']);
				sfa_paint_checkbox(__("Show the Post Count Column", "sforum"), "tc_posts", $sfoptions['tc_posts']);
				sfa_paint_checkbox(__("Show the Views Count Column", "sforum"), "tc_views", $sfoptions['tc_views']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== POSTS Tab ============================================================

	sfa_paint_open_tab(__("Posts", "sforum"));

		sfa_paint_open_panel(__("Determine how many Posts to display per page. You can display the user information above the post content which is useful if you are tight for width. You can also change the default sort order of posts so newest posts are at the top", "sforum"));
			sfa_paint_open_fieldset(__("Post View Formatting", "sforum"));
				sfa_paint_input(__("Posts to Display Per Page", "sforum"), "sfpagedposts", $sfoptions['sfpagedposts']);
				sfa_paint_checkbox(__("Display User Info Above Post", "sforum"), "sfuserabove", $sfoptions['sfuserabove']);
				sfa_paint_checkbox(__("Sort Posts Newest to Oldest", "sforum"), "sfsortdesc", $sfoptions['sfsortdesc']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("Set the formatting for date and time display. This uses standard php codes. You can also set the +/- hours from your server so times are shown in your local time zone", "sforum"));
			sfa_paint_open_fieldset(__("Date/Time Formatting", "sforum"));
				sfa_paint_input(__("Date Display Format", "sforum"), "sfdates", $sfoptions['sfdates']);
				sfa_paint_input(__("Time Display Format", "sforum"), "sftimes", $sfoptions['sftimes']);
				sfa_paint_input(__("+/- Hours From Server", "sforum"), "sfzone", $sfoptions['sfzone']);
				sfa_paint_link("http://codex.wordpress.org/Formatting_Date_and_Time", __("Date/Time Help", "sforum"));
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== LINKS Tab ============================================================

	sfa_paint_open_tab(__("Links", "sforum"));

		sfa_paint_open_panel(__("Blog posts and Forum posts can be linked in that creating one can create a parallel post in the other", "sforum"));
			sfa_paint_open_fieldset(__("Post Linking", "sforum"));
				sfa_paint_checkbox(__("Enable Post Linking", "sforum"), "sflinkuse", $sfoptions['sflinkuse']);
				sfa_paint_checkbox(__("Post Excerpt only to Forum", "sforum"), "sflinkexcerpt", $sfoptions['sflinkexcerpt']);
				sfa_paint_input(__("If Excerpt - How many Words", "sforum"), "sflinkwords", $sfoptions['sflinkwords']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("You can enter the text to display on linked items", "sforum"));
			sfa_paint_open_fieldset(__("Link Display Text", "sforum"));

				sfa_paint_checkbox(__("Display Link Above Post Content", "sforum"), "sflinkabove", $sfoptions['sflinkabove']);
				sfa_paint_textarea(__("Blog Post - Link Text to Display", "sforum"), "sflinkblogtext", $sfoptions['sflinkblogtext']);
				sfa_paint_textarea(__("Forum Post - Link Text to Display", "sforum"), "sflinkforumtext", $sfoptions['sflinkforumtext']);
				sfa_paint_message(__("Text can include HTML, class name and the placeholder %ICON%", "sforum"));
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== TAGS Tab ============================================================

	sfa_paint_open_tab(__("Tags", "sforum"));

		sfa_paint_open_panel(__("The 'Annouce' template tag can be used to show the most recent posts and can also be set to auto-refresh", "sforum"));
			sfa_paint_open_fieldset(__("Announce Template Tag", "sforum"));
				sfa_paint_checkbox(__("Enable Announce Tag", "sforum"), "sfuseannounce", $sfoptions['sfuseannounce']);
				sfa_paint_checkbox(__("Display as Unordered List (default=Table)", "sforum"), "sfannouncelist", $sfoptions['sfannouncelist']);
				sfa_paint_input(__("How many most recent posts to display", "sforum"), "sfannouncecount", $sfoptions['sfannouncecount']);
				sfa_paint_input(__("Tag display Heading", "sforum"), "sfannouncehead", $sfoptions['sfannouncehead']);
				sfa_paint_textarea(__("Text format of tag post link", "sforum"), "sfannouncetext", $sfoptions['sfannouncetext']);
				sfa_paint_message(__("Text can include the following placeholders: %FORUMNAME%, %TOPICNAME%, %POSTER% and %DATETIME%", "sforum"));
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("You can optionally set the template tag to automatically refresh the data", "sforum"));
			sfa_paint_open_fieldset(__("Announce Auto Refresh", "sforum"));
				sfa_paint_checkbox(__("Enable Auto-Refresh", "sforum"), "sfannounceauto", $sfoptions['sfannounceauto']);
				sfa_paint_input(__("How many most seconds before refresh", "sforum"), "sfannouncetime", $sfoptions['sfannouncetime']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== STYLE Tab ============================================================

	sfa_paint_open_tab(__("Style", "sforum"));

		sfa_paint_open_panel(__("Select both the 'Skin' to use and the 'Icon Set' for the display of your forums.", "sforum"));
			sfa_paint_open_fieldset(__("Forum Skin/Icons", "sforum"));

				sfa_paint_select_start(__("Select Skin", "sforum"), "sfskin");
				echo(sfa_create_skin_select($sfoptions['sfskin']));
				sfa_paint_select_end();

				sfa_paint_select_start(__("Select Icon Set", "sforum"), "sficon");
				echo(sfa_create_icon_select($sfoptions['sficon']));
				sfa_paint_select_end();

			sfa_paint_close_fieldset();
			sfa_paint_help_panel(__("Select the language file to be used by the Rich Text Editor.", "sforum"));
			sfa_paint_open_fieldset(__("Editor Language", "sforum"));

				sfa_paint_select_start(__("Select 2 letter Language Code", "sforum"), "sflang");
				echo(sfa_create_language_select($sfoptions['sflang']));
				sfa_paint_select_end();

			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

		sfa_paint_open_panel(__("If you are using an Icon Set that includes text on the graphic icons, you can repress the text display of that icon below", "sforum"));
			sfa_paint_open_fieldset(__("Display Icon Text", "sforum"));

				$x = 0;
				foreach($icons as $key=>$value)
				{
					sfa_paint_checkbox(__($key, "sforum"), "icon$x", $value);
					$x++;
				}

			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

//== UNINSTALL Tab ============================================================

	sfa_paint_open_tab(__("Uninstall", "sforum"));

		sfa_paint_open_panel(__("<strong>NOTE:</strong> Checking this option will completely remove all tables and options when the Simple Forum plugin is de-activated<br />The Page is NOT removed", "sforum"));
			sfa_paint_open_fieldset(__("Uninstall", "sforum"));
				sfa_paint_checkbox(__("Completely Remove Forum", "sforum"), "sfuninstall", $sfoptions['sfuninstall']);
			sfa_paint_close_fieldset();
		sfa_paint_close_panel();

	sfa_paint_close_tab();

?>

	</div><br />
	<input type="submit" class="sfacontrol" name="sfoptions" value="<?php _e("Save Option Changes", "sforum") ?>" />

	</form>
</div>
<br /><br />

<?php
	return;
}

//== PAINT ROUTINES

function sfa_paint_options_init()
{
	global $x;

	$x=1;
	return;
}

function sfa_paint_open_tab($tabname)
{
	echo "<div class='tabbertab'>\n";
	echo "<h2>$tabname</h2>\n";
	echo "<table width='100%' cellpadding='2' cellspacing='4'>\n";
	echo "<tr valign='top'>\n";
	return;
}

function sfa_paint_close_tab()
{
	echo "</tr>\n";
	echo "</table>\n";
	echo "</div>\n";
	return;
}

function sfa_paint_tab_new_row()
{
	echo "</tr>\n";
	echo "<tr valign='top'>\n";
	return;
}

function sfa_paint_open_panel($helptext)
{
	echo "<td width='50%'>\n";
	echo sfa_paint_help_panel($helptext);
	return;
}

function sfa_paint_close_panel()
{
	echo "</td>\n";
	return;
}

function sfa_paint_open_fieldset($legend)
{
	echo "<fieldset class='sffieldset'>\n";
	echo "<legend>$legend</legend>\n";
	echo "<table width='100%'>\n";
	return;
}

function sfa_paint_close_fieldset()
{
	echo "</table>\n";
	echo "</fieldset>\n";
	return;
}

function sfa_paint_help_panel($helptext)
{
	echo "<div class='tabhelp'>\n";
	echo "<p>$helptext</p>\n";
	echo "</div>\n";
	return;
}

function sfa_paint_input($label, $name, $value, $disabled=false)
{
	global $x;

	echo "<tr valign='top'>\n";
	echo "<td width='60%'>$label:</td>\n";
	echo "<td><input type='text' class='sfcontrol' tabindex='$x' name='$name' value='$value' ";
	if($disabled == true)
	{
		echo "disabled='disabled' ";
	}
	echo "/></td>\n";
	echo "</tr>\n";
	$x++;
	return;
}

function sfa_paint_textarea($label, $name, $value)
{
	global $x;

	echo "<tr valign='top'>\n";
	echo "<td width='60%'>$label:</td>\n";
	echo "<td><textarea rows='3' class='sfcontrol' tabindex='$x' name='$name'>$value</textarea></td>\n";
	echo "</tr>\n";
	$x++;
	return;
}

function sfa_paint_checkbox($label, $name, $value, $disabled=false)
{
	global $x;

	echo "<tr valign='top'>\n";
	echo "<td width='80%'>$label:</td>\n";
	echo "<td><input type='checkbox' class='sfcontrol' tabindex='$x' name='$name' id='$name' ";
	if($value == true)
	{
		echo "checked='checked' ";
	}
	if($disabled == true)
	{
		echo "disabled='disabled' ";
	}
	echo "/></td>\n";
	echo "</tr>\n";
	$x++;
	return;
}

function sfa_paint_select_start($label, $name)
{
	global $x;

	echo "<tr valign='top'>\n";
	echo "<td width='60%'>$label:</td>\n";
	echo "<td><select class='sfcontrol' tabindex='$x' name='$name'>\n";
	$x++;
	return;
}

function sfa_paint_select_end()
{
	echo "</select></td>\n";
	echo "</tr>\n";
	return;
}

function sfa_paint_link($link, $label)
{
	echo "<tr>\n";
	echo "<td>\n";
	echo "<a href=\"$link\">$label</a>\n";
	echo "</td>\n";
	echo "</tr>\n";
	return;
}

function sfa_paint_message($text)
{
	echo "<tr>\n";
	echo "<td><br /><small>".$text.".</small><br /></td></tr>\n";
	return;
}

function sfa_paint_rankings_table($rankings)
{
	global $x;

	echo "<table><tr>\n";
	echo "<th>Описание</th><th></th><th>Сообщений</th><th></th></tr>\n";

	foreach($rankings as $desc=>$posts)
	{
		echo "<tr>";
		echo "<td><input type='text' class='sfcontrol' tabindex='$x' name=\"rankdesc[]\" value='$desc' /></td>";
		echo "<td> до -> </td>";
		echo "<td><input type='text' class='sfcontrol' tabindex='$x' name=\"rankpost[]\" value='$posts' /></td>";
		echo "<td> сообщений</td>";
		echo "</tr>";
	}
	echo "<tr>";
	echo "<td><input type='text' class='sfcontrol' tabindex='$x' name=\"rankdesc[]\" value='' /></td>";
	echo "<td> до -> </td>";
	echo "<td><input type='text' class='sfcontrol' tabindex='$x' name=\"rankpost[]\" value='' /></td>";
	echo "<td> сообщений</td>";
	echo "</tr>";
	echo "</table>\n";
	return;
}

?>