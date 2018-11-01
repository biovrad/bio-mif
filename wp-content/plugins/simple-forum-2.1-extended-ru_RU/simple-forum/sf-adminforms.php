<?php
/*
Simple Forum 2.1
Admin Forum Form Rendering
*/

function sfa_header($panel)
{ ?>
	<!-- Common wrapper and header -->

	<div class="wrap">

		<h2><?php printf(__("Simple Forum Version %s - Manage %s", "sforum"), SFVERSION, $panel); ?></h2>

		<a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums"><?php _e("Manage Forums", "sforum") ?></a>
		<a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=options"><?php _e("Manage Options", "sforum") ?></a>
		
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;upperm"><?php _e("Update Forum Permalink", "sforum") ?></a>
		
		<?php
		if (TRUE == get_option('users_can_register'))
		{ ?>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="sfacontrol" href="<?php echo(SFUSERPATH); ?>"><?php _e("Manage Spam Users", "sforum") ?></a>
		<?php
		} ?>

		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="sfacontrol" href="<?php echo(get_option('sfpermalink')); ?>"><?php _e("Visit Forum", "sforum") ?></a>

	</div>
<?php

	return;
}

function sfa_footer()
{ ?>
	<div class="wrap">

		<div id="sffooter">
			<table width="100%" cellpadding="0" cellspacing="0">
				<tr>
					<td width="33%" align="center" valign="top">
						<small><?php _e("if you encounter problems that the guide cannot explain - or you find a bug in this plugin - please visit the support forum", "sforum") ?><br/></small>
					</td>
					<td width="33%" align="center" valign="top">
						<small><?php printf(__("Simple Forum Version %s", "sforum"), SFVERSION); ?><br /><?php _e("copyright 2006-2007 - Andy Staines", "sforum") ?><br /></small>
					</td>
					<td width="33%" align="center" valign="top">
						<small><?php _e("If you find this plugin useful please consider a small donation to my running, hosting and bandwidth costs", "sforum") ?><br /></small>
					</td>					
				</tr>	
				<tr>
					<td align="center" valign="top">
						<br />
						<a href="http://www.stuff.yellowswordfish.com/support-forum/">
<!-- 2.1 Patch 1 -->
<!--						<img src="/wp-content/plugins/simple-forum/admin/support.gif" alt="" /> -->
						<img src="<?php echo(trailingslashit(get_option('siteurl'))); ?>wp-content/plugins/simple-forum/admin/support.gif" alt="" />
						</a>
					</td>
					<td align="center" valign="top">
						<br />
						<a href="http://www.yellowswordfish.com">

<!-- 2.1 Patch 1 -->
<!--						<img src="/wp-content/plugins/simple-forum/admin/adminlogo.gif" alt="" /> -->
						<img src="<?php echo(trailingslashit(get_option('siteurl'))); ?>wp-content/plugins/simple-forum/admin/adminlogo.gif" alt="" />
						</a>
					</td>
					<td align="center" valign="top">
						<div id="paypal">
							<br />
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
								<input type="hidden" name="cmd" value="_s-xclick" />

<!-- 2.1 Patch 1 -->
<!--								<input type="image" src= "/wp-content/plugins/simple-forum/admin/paypal.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" /> -->	
									<input type="image" src= "<?php echo(trailingslashit(get_option('siteurl'))); ?>wp-content/plugins/simple-forum/admin/paypal.gif" name="submit" alt="Make payments with PayPal - it's fast, free and secure!" />
								
								<img alt="" border="0" src="https://www.paypal.com/en_GB/i/scr/pixel.gif" width="1" height="1" />
								<input type="hidden" name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----" />
							</form>
						</div>
					</td>
				</tr>
			</table>
		</div>	
		
	</div>
<?php
	return;
}

//=== GROUP RELATED

function sfa_render_forum_index($is_group)
{ ?>
<div class="wrap">
	<div class="sfmaintable">
<?php
	$is_group=true;
	$groups = sf_get_groups_all(Null);
	if($groups)
	{
		foreach($groups as $group)
		{ ?>
			<table class="sfgrouptable" cellpadding="5" cellspacing="3" rules="rows" frame="below">
				<tr>
					<th align="center" width="10%" scope="col"><?php _e("Group ID", "sforum") ?></th>
					<th align="left" scope="col"><?php _e("Group Name", "sforum") ?></th>
					<th align="center" width="11%" scope="col"><?php _e("Privacy", "sforum") ?></th>
					<th align="center" width="6%" scope="col"></th>
					<th align="center" width="9%" scope="col"><?php _e("Sequence", "sforum") ?></th>
					<th align="right" width="6%" scope="col"></th>
					<th align="right" width="6%" scope="col"></th>
				</tr>
				<tr>
					<td align="center"><?php echo($group->group_id); ?></td>
					<td><?php echo(stripslashes($group->group_name)); ?></td>
					<td align="center"><?php echo($group->group_view); ?></td>
					<td align="center"></td>
					<td align="center"><?php echo($group->group_seq); ?></td>
					<td><a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums&amp;delgroup&amp;id=<?php echo($group->group_id); ?>#formtop"><?php _e("Delete", "sforum") ?></a></td>
					<td><a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums&amp;edgroup&amp;id=<?php echo($group->group_id); ?>#formtop"><?php _e("Edit", "sforum") ?></a></td>
		
				</tr>
			</table>
<?php
			$forums = sf_get_forums_in_group($group->group_id);
			if($forums)
			{ ?>
			<table  class="sfforumtable" cellpadding="5" cellspacing="3" rules="rows" frame="below">
				<tr>
					<th align="center" width="10%" scope="col"><?php _e("Forum ID", "sforum") ?></th>
					<th align="left" scope="col"><?php _e("Forum Name", "sforum") ?></th>
					<th align="center" width="11%" scope="col"><?php _e("Privacy", "sforum") ?></th>
					<th align="center" width="6%" scope="col"></th>
					<th align="center" width="9%" scope="col"><?php _e("Sequence", "sforum") ?></th>
					<th align="right" width="6%" scope="col"></th>
					<th align="right" width="6%" scope="col"></th>
				</tr>
<?php
				foreach($forums as $forum)
				{
					$locked='';
					if($forum->forum_status) $locked=__("Locked", "sforum");
					?>
				<tr>
					<td align="center"><?php echo($forum->forum_id); ?></td>
					<td><?php echo(stripslashes($forum->forum_name)); ?></td>
					<td align="center"><?php echo($forum->forum_view); ?></td>
					<td align="center"><?php echo($locked); ?></td>
					<td align="center"><?php echo($forum->forum_seq); ?></td>
					<td><a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums&amp;delforum&amp;id=<?php echo($forum->forum_id); ?>#formtop"><?php _e("Delete", "sforum") ?></a></td>
					<td><a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums&amp;edforum&amp;id=<?php echo($forum->forum_id); ?>#formtop"><?php _e("Edit", "sforum") ?></a></td>
				</tr>
<?php
				} ?>
			</table>
			<br />
<?php				
				
			} else {
				echo('<div class="sfempty">&nbsp;&nbsp;&nbsp;&nbsp;'.__("There are No Forums defined in this Group", "sforum").'</div>');
			}
		}
	} else {
		$is_group = false;
		echo('<div class="sfempty">&nbsp;&nbsp;&nbsp;&nbsp;'.__("There are No Groups defined", "sforum").'</div>');
	}
?>
		</div>
<?php
	return $is_group;
}

function sf_render_forum_buttonbox($is_group)
{ ?>
		<div class="sfbuttonbox">
			<br />
			<a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums&amp;newaction=group#formtop"><?php _e("Create New Group", "sforum") ?></a><br /><br /><br />
<?php		if($is_group) { ?>
			<a class="sfacontrol" href="<?php echo(SFADMINPATH); ?>&amp;panel=forums&amp;newaction=forum#formtop"><?php _e("Create New Forum", "sforum") ?></a><br /><br />
<?php		} ?>
		</div>
	<div class="clearboth"></div>
</div> <!-- closing wrap--> 
<?php
	return;
}

function sfa_edit_group_form($group)
{ ?>
	<div class="wrap">
		<a id="formtop"></a>
		<h2> <?php _e("Edit Forum Group - ", "sforum") ?><?php echo(stripslashes($group->group_name)); ?></h2>

		<fieldset class="sffieldset"><legend><?php _e("Edit Forum Group", "sforum") ?></legend>
			<form action="<?php echo(SFADMINPATH); ?>&amp;panel=forums" method="post" name="sfgroupedit">

				<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_groupedit'); ?>			

				<input type="hidden" name="group_id" value="<?php echo($group->group_id); ?>" />
				<input type="hidden" name="cgroup_name" value="<?php echo(stripslashes($group->group_name)); ?>" />
				<input type="hidden" name="cgroup_desc" value="<?php echo(stripslashes($group->group_desc)); ?>" />
				<input type="hidden" name="cgroup_seq" value="<?php echo($group->group_seq); ?>" />
				<input type="hidden" name="cgroup_view" value="<?php echo($group->group_view); ?>" />
			
				<p><?php _e("Group Name", "sforum") ?>:
				<input type="text" class="sfcontrol" size="45" name="group_name" value="<?php echo(stripslashes($group->group_name)); ?>" />

				<?php _e("Display Sequence", "sforum") ?>:
				<input type="text" class="sfcontrol" size="5" name="group_seq" value="<?php echo($group->group_seq); ?>" /></p>

				<p><?php _e("Description", "sforum") ?>:&nbsp;
				<input type="text" class="sfcontrol" size="85" name="group_desc" value="<?php echo(stripslashes($group->group_desc)); ?>" /></p>

				<p><?php _e("Select Privacy Level", "sforum") ?>:
				<select name="group_view">
					<?php echo(sfa_create_roles_select($group->group_view)); ?>
				</select></p>

				<input type="submit" class="sfcontrol" name="updategroup" value="<?php _e("Update Forum Group", "sforum"); ?>" />
				<input type="submit" class="sfcontrol" name="cancel" value="<?php _e("Cancel", "sforum"); ?>" />

			</form>
		</fieldset>

	</div>
<?php
	return;
}

function sfa_edit_forum_form($forum)
{
	global $wpdb;
	
	$groupview = $wpdb->get_var("SELECT group_view FROM ".SFGROUPS." WHERE group_id=".$forum->group_id);
?>
	<div class="wrap">
		<a id="formtop"></a>
		<h2> <?php _e("Edit Forum - ", "sforum"); ?><?php echo(stripslashes($forum->forum_name)); ?></h2>

		<fieldset class="sffieldset"><legend><?php _e("Edit Forum", "sforum") ?></legend>
			<form action="<?php echo(SFADMINPATH); ?>&amp;panel=forums" method="post" name="sfforumedit">

				<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_forumedit'); ?>			

				<input type="hidden" name="forum_id" value="<?php echo($forum->forum_id); ?>" />
				<input type="hidden" name="cgroup_id" value="<?php echo($forum->group_id); ?>" />
				<input type="hidden" name="cforum_name" value="<?php echo(stripslashes($forum->forum_name)); ?>" />
				<input type="hidden" name="cforum_seq" value="<?php echo($forum->forum_seq); ?>" />
				<input type="hidden" name="cforum_desc" value="<?php echo(stripslashes($forum->forum_desc)); ?>" />
				<input type="hidden" name="cforum_status" value="<?php echo($forum->forum_status); ?>" />
				<input type="hidden" name="cforum_view" value="<?php echo($forum->forum_view); ?>" />

				<p><?php _e("Select Group", "sforum") ?>:
				<select name="group_id">
					<?php echo(sfa_create_group_select($forum->group_id)); ?>
				</select>

				<?php _e("Select Privacy Level", "sforum") ?>:
				<select name="forum_view">
					<?php echo(sfa_create_roles_select($forum->forum_view, $groupview)); ?>
				</select></p>

				<p><?php _e("Forum Name", "sforum") ?>:
				<input type="text" class="sfcontrol" size="45" name="forum_name" value="<?php echo(stripslashes($forum->forum_name)); ?>" />

				<?php _e("Display Sequence", "sforum") ?>:
				<input type="text" class="sfcontrol" size="5" name="forum_seq" value="<?php echo($forum->forum_seq); ?>" />

				<?php _e("Locked", "sforum") ?>:
				<input type="checkbox" class="sfcontrol" name="forum_status"
				<?php if($forum->forum_status == TRUE) {?> checked="checked" <?php } ?> /></p>

				<p><?php _e("Description", "sforum") ?>:&nbsp;&nbsp;
				<input type="text" class="sfcontrol" size="85" name="forum_desc" value="<?php echo(stripslashes($forum->forum_desc)); ?>" /></p>

				<input type="submit" class="sfcontrol" name="updateforum" value="<?php _e("Update Forum", "sforum") ?>" />
				<input type="submit" class="sfcontrol" name="cancel" value="<?php _e("Cancel", "sforum") ?>" />

			</form>
		</fieldset>

	</div>
<?php
	return;
}

function sfa_new_group_form($seq)
{ ?>
	<div class="wrap">
		<a id="formtop"></a>
		<h2><?php _e("Create New Forum Group", "sforum") ?></h2>

		<fieldset class="sffieldset"><legend><?php _e("Create New Forum Group", "sforum") ?></legend>
			<form action="<?php echo(SFADMINPATH); ?>&amp;panel=forums" method="post" name="sfgroupnew">

				<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_groupnew'); ?>			
			
				<p><?php _e("Group Name", "sforum") ?>:
				<input type="text" class="sfcontrol" size="45" name="group_name" value="" />

				<?php _e("Display Sequence", "sforum") ?>:
				<input type="text" class="sfcontrol" size="5" name="group_seq" value="<?php echo($seq); ?>" /></p>

				<p><?php _e("Description", "sforum") ?>:&nbsp;
				<input type="text" class="sfcontrol" size="85" name="group_desc" value="" /></p>

				<p><?php _e("Select Privacy Level", "sforum") ?>:
				<select name="group_view">
					<?php echo(sfa_create_roles_select('public')); ?>
				</select></p>

				<input type="submit" class="sfcontrol" name="newgroup" value="<?php _e("Create Forum Group", "sforum") ?>" />
				<input type="submit" class="sfcontrol" name="cancel" value="<?php _e("Cancel", "sforum") ?>" />

			</form>
		</fieldset>

	</div>
<?php
	return;
}

function sfa_new_forum_form()
{ ?>
	<div class="wrap">
		<a id="formtop"></a>
		<h2><?php _e("Create New Forum", "sforum") ?></h2>

		<fieldset class="sffieldset"><legend><?php _e("Create New Forum", "sforum") ?></legend>
			<form action="<?php echo(SFADMINPATH); ?>&amp;panel=forums" method="post" name="sfforumnew">

				<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_forumnew'); ?>			

				<p><?php _e("Select Group", "sforum") ?>:
				<select name="group_id">
					<?php echo(sfa_create_group_select()); ?>
				</select>
				
				&nbsp;&nbsp;(<?php _e("To set privacy level, edit this record after creation", "sforum") ?>)</p>

				<p><?php _e("Forum Name", "sforum") ?>:
				<input type="text" class="sfcontrol" size="45" name="forum_name" value="" />

				<?php _e("Display Sequence", "sforum") ?>:
				<input type="text" class="sfcontrol" size="5" name="forum_seq" value="" />

				<?php _e("Locked", "sforum") ?>:
				<input type="checkbox" class="sfcontrol" name="forum_status" /></p>
				
				<p><?php _e("Description", "sforum") ?>:&nbsp;&nbsp;
				<input type="text" class="sfcontrol" size="85" name="forum_desc" value="" /></p>

				<input type="submit" class="sfcontrol" name="newforum" value="<?php _e("Create Forum", "sforum") ?>" />
				<input type="submit" class="sfcontrol" name="cancel" value="<?php _e("Cancel", "sforum") ?>" />

			</form>
		</fieldset>

	</div>
<?php
	return;
}

function sfa_delete_group_form($group)
{ ?>
	<div class="wrap">
		<a id="formtop"></a>
		<h2> <?php _e("Delete Forum Group", "sforum") ?> - <?php echo(stripslashes($group->group_name)); ?></h2>

		<fieldset class="sffieldset"><legend><?php _e("Delete Forum Group", "sforum") ?></legend>
			<form action="<?php echo(SFADMINPATH); ?>&amp;panel=forums" method="post" name="sfgroupdel">

				<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_groupdelete'); ?>			
		
				<input type="hidden" name="group_id" value="<?php echo($group->group_id); ?>" />
				<input type="hidden" name="cgroup_seq" value="<?php echo($group->group_seq); ?>" />
		
				<p><?php _e("Click <strong>Confirm Deletion</strong> to completely remove this forum group.<br />This will remove ALL Forums, Topics and Posts contained in this Group.<br /><br />Please note that this action <strong>can NOT be reversed</strong>.", "sforum") ?></p>
				
				<input type="submit" class="sfcontrol" name="deletegroup" value="<?php _e("Confirm Deletion", "sforum") ?>" />
				<input type="submit" class="sfcontrol" name="cancel" value="<?php _e("Cancel", "sforum") ?>" />
			</form>
		</fieldset>

	</div>
<?php
	return;
}
				
function sfa_delete_forum_form($forum)
{ ?>
	<div class="wrap">
		<a id="formtop"></a>
		<h2> <?php _e("Delete Forum", "sforum") ?> - <?php echo(stripslashes($forum->forum_name)); ?></h2>

		<fieldset class="sffieldset"><legend><?php _e("Delete Forum", "sforum") ?></legend>
			<form action="<?php echo(SFADMINPATH); ?>&amp;panel=forums" method="post" name="sfforumdel">

				<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-adminform_forumdelete'); ?>			
		
				<input type="hidden" name="group_id" value="<?php echo($forum->group_id); ?>" />
				<input type="hidden" name="forum_id" value="<?php echo($forum->forum_id); ?>" />
				<input type="hidden" name="cforum_seq" value="<?php echo($forum->forum_seq); ?>" />
		
				<p><?php _e("Click <strong>Confirm Deletion</strong> to completely remove this Forum.<br />This will remove ALL Topics and Posts contained in this Forum.<br /><br />Please note that this action <strong>can NOT be reversed</strong>.", "sforum") ?></p>
				
				<input type="submit" class="sfcontrol" name="deleteforum" value="<?php _e("Confirm Deletion", "sforum") ?>" />
				<input type="submit" class="sfcontrol" name="cancel" value="<?php _e("Cancel", "sforum") ?>" />
			</form>
		</fieldset>

	</div>
<?php
	return;
}

?>