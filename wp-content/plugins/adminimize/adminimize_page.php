<?php
/**
 * options-page in wp-admin
 */
function _mw_adminimize_options() {
	global $wpdb, $_wp_admin_css_colors;
	
	_mw_adminimize_user_info == '';
	
	// update options
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_insert') && $_POST['_mw_adminimize_save'] ) {
		
		if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
			check_admin_referer('mw_adminimize_nonce');

			_mw_adminimize_update();
			
		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}

	// deinstall options
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_deinstall') &&  ($_POST['_mw_adminimize_deinstall_yes'] != '_mw_adminimize_deinstall') ) {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_deinstall_yes') . '</p></div>';
			wp_die($myErrors);
	}
	
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_deinstall') && $_POST['_mw_adminimize_deinstall'] && ($_POST['_mw_adminimize_deinstall_yes'] == '_mw_adminimize_deinstall') ) {

		if ( function_exists('current_user_can') && current_user_can('edit_plugins') ) {
			check_admin_referer('mw_adminimize_nonce');
			
			_mw_adminimize_deinstall();
			
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="updated fade"><p>' . $myErrors->get_error('_mw_adminimize_deinstall') . '</p></div>';
			echo $myErrors;
		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}
	
	if ( ($_POST['_mw_adminimize_action'] == '_mw_adminimize_set_theme') && $_POST['_mw_adminimize_save'] ) {
		if ( function_exists('current_user_can') && current_user_can('edit_users') ) {
			check_admin_referer('mw_adminimize_nonce');
			
			_mw_adminimize_set_theme();
			
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="updated fade"><p>' . $myErrors->get_error('_mw_adminimize_set_theme') . '</p></div>';
			echo $myErrors;
		} else {
			$myErrors = new _mw_adminimize_message_class();
			$myErrors = '<div id="message" class="error"><p>' . $myErrors->get_error('_mw_adminimize_access_denied') . '</p></div>';
			wp_die($myErrors);
		}
	}

?>
	<div class="wrap">
		<h2><?php _e('Adminimize', 'adminimize'); ?></h2>
		<br class="clear" />
		<div id="poststuff" class="dlm">
			<div class="postbox closed" >
				<h3><?php _e('Backend options', 'adminimize'); ?></h3>
				<div class="inside">
		
				<form name="backend_option" method="post" id="_mw_adminimize_options" action="?page=<?php echo $_GET['page'];?>" >
					<?php wp_nonce_field('mw_adminimize_nonce'); ?>
					<br class="clear" />
					<table summary="config" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Backend options', 'adminimize'); ?></th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
							<tr valign="top">
								<td><?php _e('Sidebar Width', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_sidebar_wight = _mw_adminimize_getOptionValue('_mw_adminimize_sidebar_wight'); ?>
									<select name="_mw_adminimize_sidebar_wight">
										<option value="0"<?php if ($_mw_adminimize_sidebar_wight == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="300"<?php if ($_mw_adminimize_sidebar_wight == '300') { echo ' selected="selected"'; } ?>>300px</option>
										<option value="400"<?php if ($_mw_adminimize_sidebar_wight == '400') { echo ' selected="selected"'; } ?>>400px</option>
										<option value="20"<?php if ($_mw_adminimize_sidebar_wight == '20') { echo ' selected="selected"'; } ?>>20%</option>
										<option value="30"<?php if ($_mw_adminimize_sidebar_wight == '30') { echo ' selected="selected"'; } ?>>30%</option>
									</select> <?php _e('The sidebar on the right side in the area <em>Edit</em> is configurable. Default is 200 pixel in the WordPress Theme <em>Classic</em> and <em>Fresh</em>', 'adminimize'); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('User-Info', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_user_info = _mw_adminimize_getOptionValue('_mw_adminimize_user_info'); ?>
									<select name="_mw_adminimize_user_info">
										<option value="0"<?php if ($_mw_adminimize_user_info == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="1"<?php if ($_mw_adminimize_user_info == '1') { echo ' selected="selected"'; } ?>><?php _e('Hide', 'adminimize'); ?></option>
										<option value="2"<?php if ($_mw_adminimize_user_info == '2') { echo ' selected="selected"'; } ?>><?php _e('Only logout', 'adminimize'); ?></option>
										<option value="3"<?php if ($_mw_adminimize_user_info == '3') { echo ' selected="selected"'; } ?>><?php _e('User &amp; Logout', 'adminimize'); ?></option>
									</select> <?php _e('The &quot;User-Info-area&quot; is on the top right side of the backend. You can hide or reduced show.', 'adminimize'); ?>
								</td>
							</tr>
							<?php if ( ($_mw_adminimize_user_info == '') || ($_mw_adminimize_user_info == '1') || ($_mw_adminimize_user_info == '0') ) $disabled_item = ' disabled="disabled"' ?>
							<tr valign="top">
								<td><?php _e('Change User-Info, redirect to', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_ui_redirect = _mw_adminimize_getOptionValue('_mw_adminimize_ui_redirect'); ?>
									<select name="_mw_adminimize_ui_redirect" <?php echo $disabled_item ?>>
										<option value="0"<?php if ($_mw_adminimize_ui_redirect == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="1"<?php if ($_mw_adminimize_ui_redirect == '1') { echo ' selected="selected"'; } ?>><?php _e('Frontpage of the Blog', 'adminimize'); ?> 
									</select> <?php _e('When the &quot;User-Info-area&quot; change it, then it is possible to change the redirect.', 'adminimize'); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Footer', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_footer = _mw_adminimize_getOptionValue('_mw_adminimize_footer'); ?>
									<select name="_mw_adminimize_footer">
										<option value="0"<?php if ($_mw_adminimize_footer == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="1"<?php if ($_mw_adminimize_footer == '1') { echo ' selected="selected"'; } ?>><?php _e('Hide', 'adminimize'); ?></option>
									</select> <?php _e('The Footer-area kann hide, include all links and details.', 'adminimize'); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('WriteScroll', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_writescroll = _mw_adminimize_getOptionValue('_mw_adminimize_writescroll'); ?>
									<select name="_mw_adminimize_writescroll">
										<option value="0"<?php if ($_mw_adminimize_writescroll == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="1"<?php if ($_mw_adminimize_writescroll == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', 'adminimize'); ?></option>
									</select> <?php _e('With the WriteScroll option active, these pages will automatically scroll to an optimal position for editing, when you visit Write Post or Write Page.', 'adminimize'); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Timestamp', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_timestamp = _mw_adminimize_getOptionValue('_mw_adminimize_timestamp'); ?>
									<select name="_mw_adminimize_timestamp">
										<option value="0"<?php if ($_mw_adminimize_timestamp == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="1"<?php if ($_mw_adminimize_timestamp == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', 'adminimize'); ?></option>
									</select> <?php _e('Opens the post timestamp editing fields without you having to click the "Edit" link every time.', 'adminimize'); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Thickbox FullScreen', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_tb_window = _mw_adminimize_getOptionValue('_mw_adminimize_tb_window'); ?>
									<select name="_mw_adminimize_tb_window">
										<option value="0"<?php if ($_mw_adminimize_tb_window == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="1"<?php if ($_mw_adminimize_tb_window == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', 'adminimize'); ?></option>
									</select> <?php _e('All Thickbox-function use the full area of the browser. Thickbox is for examble in upload media-files.', 'adminimize'); ?>
								</td>
							</tr>
							<tr valign="top">
								<td><?php _e('Advice in Footer', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_advice = _mw_adminimize_getOptionValue('_mw_adminimize_advice'); ?>
									<select name="_mw_adminimize_advice">
										<option value="0"<?php if ($_mw_adminimize_advice == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?></option>
										<option value="1"<?php if ($_mw_adminimize_advice == '1') { echo ' selected="selected"'; } ?>><?php _e('Activate', 'adminimize'); ?></option>
									</select>
									<textarea style="width: 85%;" class="code" rows="1" cols="60" name="_mw_adminimize_advice_txt" id="_mw_adminimize_advice_txt" ><?php echo htmlspecialchars(stripslashes(_mw_adminimize_getOptionValue('_mw_adminimize_advice_txt'))); ?></textarea><br /><?php _e('In the Footer kann you display a advice for change the Default-design, (x)HTML is possible.', 'adminimize'); ?>
								</td>
							</tr>
							<?php
							// when remove dashboard
							$disabled_menu        = _mw_adminimize_getOptionValue('mw_adminimize_disabled_menu_items');
							$disabled_submenu     = _mw_adminimize_getOptionValue('mw_adminimize_disabled_submenu_items');
							$disabled_menu_adm    = _mw_adminimize_getOptionValue('mw_adminimize_disabled_menu_adm_items');
							$disabled_submenu_adm = _mw_adminimize_getOptionValue('mw_adminimize_disabled_submenu_adm_items');
		
							if ($disabled_menu != '') {
								if ( in_array('index.php', $disabled_menu) ||
										 in_array('index.php', $disabled_submenu) ||
										 in_array('index.php', $disabled_menu_adm) ||
										 in_array('index.php', $disabled_submenu_adm)
									 ) {
							?>
							<tr valign="top" class="form-invalid">
								<td><?php _e('Dashboard deaktivate, redirect to', 'adminimize'); ?></td>
								<td>
									<?php $_mw_adminimize_db_redirect = _mw_adminimize_getOptionValue('_mw_adminimize_db_redirect'); ?>
									<select name="_mw_adminimize_db_redirect">
										<option value="0"<?php if ($_mw_adminimize_db_redirect == '0') { echo ' selected="selected"'; } ?>><?php _e('Default', 'adminimize'); ?> (profile.php)</option>
										<option value="1"<?php if ($_mw_adminimize_db_redirect == '1') { echo ' selected="selected"'; } ?>><?php _e('Manage Posts', 'adminimize'); ?> (edit.php)</option>
										<option value="2"<?php if ($_mw_adminimize_db_redirect == '2') { echo ' selected="selected"'; } ?>><?php _e('Manage Pages', 'adminimize'); ?> (edit-pages.php)</option>
										<option value="3"<?php if ($_mw_adminimize_db_redirect == '3') { echo ' selected="selected"'; } ?>><?php _e('Write Post', 'adminimize'); ?> (post-new.php)</option>
										<option value="4"<?php if ($_mw_adminimize_db_redirect == '4') { echo ' selected="selected"'; } ?>><?php _e('Write Page', 'adminimize'); ?> (page-new.php)</option>
										<option value="5"<?php if ($_mw_adminimize_db_redirect == '5') { echo ' selected="selected"'; } ?>><?php _e('Comments', 'adminimize'); ?> (edit-comments.php)</option>
										</select> <?php _e('You have deaktivate the Dashboard, please select a page for redirect?', 'adminimize'); ?>
								</td>
							</tr>
							<?php
								}
							}
							?>
						</tbody>
					</table>
					<p id="submitbutton">
						<input class="button" type="submit" name="_mw_adminimize_save" value="<?php _e('Update options', 'adminimize'); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
					
				</div>
			</div>
		</div>
			
		<div id="poststuff" class="dlm">
			<div class="postbox closed" >
				<h3 id="config_menu"><?php _e('Menu Options', 'adminimize'); ?></h3>
				<div class="inside">
					<br class="clear" />
					<table summary="config_menu" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Menu options - Menu, <span style=\"font-weight: 400;\">Submenu</span>', 'adminimize'); ?></th>
								<th><?php _e('Deactivate &lt; User Level 10 (Admin)', 'adminimize'); ?></th>
								<th><?php _e('Deactivate for Admin\'s', 'adminimize'); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php
							$menu = _mw_adminimize_getOptionValue('mw_adminimize_default_menu');
							$submenu = _mw_adminimize_getOptionValue('mw_adminimize_default_submenu');

							$disabled_metaboxes_post = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_post_items');
							$disabled_metaboxes_page = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_page_items');
							$disabled_metaboxes_post_adm = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_post_adm_items');
							$disabled_metaboxes_page_adm = _mw_adminimize_getOptionValue('mw_adminimize_disabled_metaboxes_page_adm_items');
						
							$metaboxes = array(
								'#pageslugdiv',
								'#tagsdiv,#tagsdivsb',
								'#categorydiv,#categorydivsb',
								'#category-add-toggle',
								'#postexcerpt',
								'#trackbacksdiv',
								'#postcustom',
								'#commentstatusdiv',
								'#passworddiv',
								'#authordiv',
								'#revisionsdiv',
								'.side-info',
								'#notice',
								'#post-body h2',
								'media_buttons'
							);
							
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes, '#suggestedtags');
							if (function_exists('tc_post'))
								array_push($metaboxes, '#textcontroldiv');
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes, '#htmlspecialchars');
							
							$metaboxes_names = array(
								__('Permalink', 'adminimize'),
								__('Tags', 'adminimize'),
								__('Categories', 'adminimize'),
								__('Add New Category', 'adminimize'),
								__('Excerpt', 'adminimize'),
								__('Trackbacks', 'adminimize'),
								__('Custom Fields', 'adminimize'),
								__('Comments &amp; Pings', 'adminimize'),
								__('Password Protect This Post', 'adminimize'),
								__('Post Author', 'adminimize'),
								__('Post Revisions', 'adminimize'),
								__('Related, Shortcuts', 'adminimize'),
								__('Messenges', 'adminimize'),
								__('h2: Advanced Options', 'adminimize'),
								__('Media Buttons (all)', 'adminimize')
							);
							
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes_names, __('Suggested tags from'));
							if (function_exists('tc_post'))
								array_push($metaboxes_names, __('Text Control'));
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes_names, __('HTML Special Characters'));
								
							$metaboxes_page = array(
								'#pageslugdiv',
								'#pagepostcustom, #pagecustomdiv',
								'#pagecommentstatusdiv',
								'#pagepassworddiv',
								'#pageparentdiv',
								'#pagetemplatediv',
								'#pageorderdiv',
								'#pageauthordiv',
								'#revisionsdiv',
								'.side-info',
								'#notice',
								'#post-body h2',
								'media_buttons'
							);
							
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes_page, '#suggestedtags');
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes_page, '#htmlspecialchars');
								
							$metaboxes_names_page = array(
								__('Permalink', 'adminimize'),
								__('Custom Fields', 'adminimize'),
								__('Comments &amp; Pings', 'adminimize'),
								__('Password Protect This Page', 'adminimize'),
								__('Page Parent', 'adminimize'),
								__('Page Template', 'adminimize'),
								__('Page Order', 'adminimize'),
								__('Page Author', 'adminimize'),
								__('Page Revisions', 'adminimize'),
								__('Related', 'adminimize'),
								__('Messenges', 'adminimize'),
								__('h2: Advanced Options', 'adminimize'),
								__('Media Buttons (all)', 'adminimize')
							);
		
							if (class_exists('SimpleTagsAdmin'))
								array_push($metaboxes_names_page, __('Suggested tags from', 'adminimize'));
							if (class_exists('HTMLSpecialCharactersHelper'))
								array_push($metaboxes_names_page, __('HTML Special Characters'));
							
							// print menu, submenu
							if ($menu != '') {
								
								$i = 0;
								$x = 0;
								foreach ($menu as $item) {
								
									// menu items
									// items disabled for user
									if ( $item[2] == 'examble.php' ) {
										$disabled_item = ' disabled="disabled"';
									} else {
										$disabled_item = '';
									}
									
									// checkbox checked
									if ( in_array($item[2], $disabled_menu) ) {
										$checked = ' checked="checked"';
									} else {
										$checked = '';
									}
		
									// checkbox checked for admin
									if ( in_array($item[2], $disabled_menu_adm) ) {
										$checked_adm = ' checked="checked"';
									} else {
										$checked_adm = '';
									}
									
									echo '<tr class="form-invalid">' . "\n";
									echo '<th>' . $item[0] . ' <span style="color:#ccc; font-weight: 400;">(' . $item[2] . ')</span> </th>';
									echo '<td class="num"><input id="check_menu'. $x .'" type="checkbox"' . $disabled_item . $checked . ' name="mw_adminimize_disabled_menu_items[]" value="' . $item[2] . '"/></td>' . "\n";
									echo '<td class="num"><input id="check_menuadm'. $x .'" type="checkbox"' . $disabled_item . $checked_adm . ' name="mw_adminimize_disabled_menu_adm_items[]" value="' . $item[2] . '"/></td>' . "\n";
									echo '</tr>';
									$x++;
									if ( !isset($submenu[$item[2]]) )
										continue;
		
									// submenu items
									foreach ( $submenu[ $item[2] ] as $subitem ) {
									
										// submenu items
										// items disabled for adm
										if ( $subitem[2] == 'adminimize/adminimize.php' ) {
											$disabled_subitem_adm = ' disabled="disabled"';
										} else {
											$disabled_subitem_adm = '';
										}
										
										echo '<tr>' . "\n";
										
										$checked     = (in_array($subitem[2], $disabled_submenu)) ? ' checked="checked"' : '';
										$checked_adm = (in_array($subitem[2], $disabled_submenu_adm)) ? ' checked="checked"' : '';
										
										echo '<td> &mdash; ' . $subitem[0] . ' <span style="color:#ccc; font-weight: 400;">(' . $subitem[2] . ')</span> </td>' . "\n";
										echo '<td class="num"><input id="check_menu'. $x .'" type="checkbox"' . $checked . ' name="mw_adminimize_disabled_submenu_items[]" value="' . $subitem[2] . '" /></td>' . "\n";
										echo '<td class="num"><input id="check_menuadm'. $x .'" type="checkbox"' . $disabled_subitem_adm . $checked_adm . ' name="mw_adminimize_disabled_submenu_adm_items[]" value="' . $subitem[2] . '" /></td>' . "\n";
										echo '</tr>' . "\n";
										$x++;
									}
									$i++;
									$x++;
								}
									echo '<tr>' . "\n";
									echo '<th>' . __('All items', 'adminimize') . '</th>';
									echo '<td class="num"><input type="checkbox" id="ctoggleCheckboxes_menu" onClick="toggleCheckboxes_menu();"><a id="atoggleCheckboxes_menu" href="javascript:toggleCheckboxes_menu();"> ' . __('All', 'adminimize') . '</a></td>';
									echo '<td class="num"><input type="checkbox" id="ctoggleCheckboxes_menuadm" onClick="toggleCheckboxes_menuadm();"><a id="atoggleCheckboxes_menuadm" href="javascript:toggleCheckboxes_menuadm();"> ' . __('All', 'adminimize') . '</a></td>';
									echo '</tr>' . "\n";
									
							} else {
								$myErrors = new _mw_adminimize_message_class();
								$myErrors = '<tr><td style="color: red;">' . $myErrors->get_error('_mw_adminimize_get_option') . '</td></tr>';
								echo $myErrors;
							} ?>
						</tbody>
					</table>
					<p id="submitbutton">
						<input class="button" type="submit" name="_mw_adminimize_save" value="<?php _e('Update options', 'adminimize'); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
					
				</div>
			</div>
		</div>

		<div id="poststuff" class="dlm">
			<div class="postbox closed" >
				<h3 id="config_edit"><?php _e('Write options', 'adminimize'); ?></h3>
				<div class="inside">
					<br class="clear" />
					<table summary="config_edit" class="widefat">
						<thead>
							<tr>
								<th><?php _e('Write options - Post', 'adminimize'); ?></th>
								<th><?php _e('Write options - Page', 'adminimize'); ?></th>
							</tr>
						</thead>
						
						<tbody>
							<tr valign="top">
								<td>
									<table summary="config_edit_post" class="widefat">
										<thead>
											<tr>
												<th>&nbsp;</th>
												<th><?php _e('Deactivate for &lt; Admin\'s (level 1-9)', 'adminimize'); ?></th>
												<th><?php _e('Deactivate for Admin\'s (level 10)', 'adminimize'); ?></th>
											</tr>
										</thead>
										
										<tbody>
										<?php
											$x = 0;
											foreach ($metaboxes as $index => $metabox) {
												$checked = (in_array($metabox, $disabled_metaboxes_post)) ? ' checked="checked"' : '';
												$checked_adm = (in_array($metabox, $disabled_metaboxes_post_adm)) ? ' checked="checked"' : '';
												
												echo '<tr>' . "\n";
												echo '<td>' . $metaboxes_names[$index] . ' <span style="color:#ccc; font-weight: 400;">(' . $metabox . ')</span> </td>' . "\n";
												echo '<td class="num"><input id="check_post'. $x .'" type="checkbox"' . $checked . ' name="mw_adminimize_disabled_metaboxes_post_items[]" value="' . $metabox . '" /></td>' . "\n";
												echo '<td class="num"><input id="check_postadm'. $x .'" type="checkbox"' . $checked_adm . ' name="mw_adminimize_disabled_metaboxes_post_adm_items[]" value="' . $metabox . '" /></td>' . "\n";
												echo '</tr>' . "\n";
												$x++;
											}
										?>
											<tr>
												<th><?php _e('All items', 'adminimize'); ?></th>
												<?php
													echo '<td class="num"><input type="checkbox" id="ctoggleCheckboxes_post" onClick="toggleCheckboxes_post();"><a id="atoggleCheckboxes_post" href="javascript:toggleCheckboxes_post();"> ' . __('All', 'adminimize') . '</a></td>';
													echo '<td class="num"><input type="checkbox" id="ctoggleCheckboxes_postadm" onClick="toggleCheckboxes_postadm();"><a id="atoggleCheckboxes_postadm" href="javascript:toggleCheckboxes_postadm();"> ' . __('All', 'adminimize') . '</a></td>';
												?>
											</tr>
										</tbody>
									</table>
								</td>
								<td>
									<table summary="config_edit_page" class="widefat">
										<thead>
											<tr>
												<th>&nbsp;</th>
												<th><?php _e('Deactivate for &lt; Admin\'s (level 1-9)', 'adminimize'); ?></th>
												<th><?php _e('Deactivate for Admin\'s (level 10)', 'adminimize'); ?></th>
											</tr>
										</thead>
										
										<tbody>
										<?php
											$x = 0;
											foreach ($metaboxes_page as $index => $metabox) {
												$checked = (in_array($metabox, $disabled_metaboxes_page)) ? ' checked="checked"' : '';
												$checked_adm = (in_array($metabox, $disabled_metaboxes_page_adm)) ? ' checked="checked"' : '';
												
												echo '<tr>' . "\n";
												echo '<td>' . $metaboxes_names_page[$index] . ' <span style="color:#ccc; font-weight: 400;">(' . $metabox . ')</span> </td>' . "\n";
												echo '<td class="num"><input id="check_page'. $x .'" type="checkbox"' . $checked . ' name="mw_adminimize_disabled_metaboxes_page_items[]" value="' . $metabox . '" /></td>' . "\n";
												echo '<td class="num"><input id="check_pageadm'. $x .'" type="checkbox"' . $checked_adm . ' name="mw_adminimize_disabled_metaboxes_page_adm_items[]" value="' . $metabox . '" /></td>' . "\n";
												echo '</tr>' . "\n";
												$x++;
											}
										?>
											<tr>
												<th><?php _e('All items', 'adminimize'); ?></th>
												<?php
													echo '<td class="num"><input type="checkbox" id="ctoggleCheckboxes_page" onClick="toggleCheckboxes_page();"><a id="atoggleCheckboxes_page" href="javascript:toggleCheckboxes_page();"> ' . __('All', 'adminimize') . '</a></td>';
													echo '<td class="num"><input type="checkbox" id="ctoggleCheckboxes_pageadm" onClick="toggleCheckboxes_pageadm();"><a id="atoggleCheckboxes_pageadm" href="javascript:toggleCheckboxes_pageadm();"> ' . __('All', 'adminimize') . '</a></td>';
												?>
											</tr>
										</tbody>
									</table>
								</td>
							</tr>
						</tbody>
					</table>
					
					<p id="submitbutton">
						<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_insert" />
						<input class="button" type="submit" name="_mw_adminimize_save" value="<?php _e('Update options', 'adminimize'); ?> &raquo;" /><input type="hidden" name="page_options" value="'dofollow_timeout'" />
					</p>
				</form>

				</div>
			</div>
		</div>
		
		<div id="poststuff" class="dlm">
			<div class="postbox closed" >
				<h3 id="set_theme"><?php _e('Set Theme', 'adminimize') ?></h3>
				<div class="inside">
					<br class="clear" />
					<form name="set_theme" method="post" id="_mw_adminimize_set_theme" action="?page=<?php echo $_GET['page'];?>" >
						<?php wp_nonce_field('mw_adminimize_nonce'); ?>
						<table class="widefat">
							<thead>
								<tr class="thead">
									<th>&nbsp;</th>
									<th class="num"><?php _e('User-ID') ?></th>
									<th><?php _e('Username') ?></th>
									<th><?php _e('Display name publicly as') ?></th>
									<th><?php _e('Admin Color Scheme') ?></th>
									<th><?php _e('User Level') ?></th>
									<th><?php _e('Role') ?></th>
								</tr>
							</thead>
							<tbody id="users" class="list:user user-list">
								<?php
								$wp_user_search = $wpdb->get_results("SELECT ID, user_login, display_name FROM $wpdb->users ORDER BY ID");
								
								$style = '';
								foreach ( $wp_user_search as $userid ) {
									$user_id       = (int) $userid->ID;
									$user_login    = stripslashes($userid->user_login);
									$display_name  = stripslashes($userid->display_name);
									$current_color = get_user_option('admin_color', $user_id);
									$user_level    = (int) get_user_option($table_prefix . 'user_level', $user_id);
									$user_object   = new WP_User($user_id);
									$roles         = $user_object->roles;
									$role          = array_shift($roles);
								
									$style = ( ' class="alternate"' == $style ) ? '' : ' class="alternate"';
									$return  = '';
									$return .= '<tr>';
									$return .= '<td><input type="checkbox" name="mw_adminimize_theme_items[]" value="' . $user_id . '" /></td>';
									$return .= '<td class="num">'. $user_id .'</td>';
									$return .= '<td>'. $user_login .'</td>';
									$return .= '<td>'. $display_name .'</td>';
									$return .= '<td>'. $current_color . '</td>';
									$return .= '<td class="num">'. $user_level . '</td>';
									$return .= '<td>'. $role . '</td>';
									$return .= '</tr>';
			
									print($return);
								}
								?>
									<tr valign="top">
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
										<td>
											<select name="_mw_adminimize_set_theme">
												<?php foreach ( $_wp_admin_css_colors as $color => $color_info ): ?>
													<option value="<?php echo $color; ?>"><?php echo $color_info->name . ' (' . $color . ')' ?></option>
												<?php endforeach; ?>
												</select>
										</td>
										<td>&nbsp;</td>
										<td>&nbsp;</td>
									</tr>
							</tbody>
						</table>
						<p id="submitbutton">
							<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_set_theme" />
							<input type="submit" name="_mw_adminimize_save" value="<?php _e('Set Theme', 'adminimize'); ?> &raquo;" class="button" />
						</p>
					</form>

				</div>
			</div>
		</div>

		<div id="poststuff" class="dlm">
			<div class="postbox closed" >
				<h3 id="uninstall"><?php _e('Deinstall options', 'adminimize') ?></h3>
				<div class="inside">
					
					<p><?php _e('Use this option for clean your database from all entries of this plugin. When you deactivate the plugin, the deinstall of the plugin <strong>clean not</strong> all entries in the database.', 'adminimize'); ?></p>
					<form name="deinstall_options" method="post" id="_mw_adminimize_options_deinstall" action="?page=<?php echo $_GET['page'];?>">
						<?php wp_nonce_field('mw_adminimize_nonce'); ?>
						<p id="submitbutton">
							<input type="submit" name="_mw_adminimize_deinstall" value="<?php _e('Delete options', 'adminimize'); ?> &raquo;" class="button-secondary" /> 
							<input type="checkbox" name="_mw_adminimize_deinstall_yes" value="_mw_adminimize_deinstall" />
							<input type="hidden" name="_mw_adminimize_action" value="_mw_adminimize_deinstall" />
						</p>
					</form>

				</div>
			</div>
		</div>

		<div id="poststuff" class="dlm">
			<div class="postbox closed" >
				<h3 id="uninstall"><?php _e('About the plugin', 'adminimize') ?></h3>
				<div class="inside">

					<p><?php _e('Further information: Visit the <a href="http://bueltge.de/wordpress-admin-theme-adminimize/674/">plugin homepage</a> for further information or to grab the latest version of this plugin.', 'adminimize'); ?><br />&copy; Copyright 2008 - <?php echo date("Y"); ?> <a href="http://bueltge.de">Frank B&uuml;ltge</a> | <?php _e('You want to thank me? Visit my <a href="http://bueltge.de/wunschliste/">wishlist</a>.', 'adminimize'); ?></p>
					<p class="textright"><?php echo $wpdb->num_queries; ?>q, <?php timer_stop(1); ?>s</p>
				</div>
			</div>
		</div>

		<script type="text/javascript">
		<!--
		jQuery('.postbox h3').prepend('<a class="togbox">+</a> ');
		//jQuery('.togbox').click( function() { jQuery(jQuery(this).parent().parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox h3').click( function() { jQuery(jQuery(this).parent().get(0)).toggleClass('closed'); } );
		jQuery('.postbox.close-me').each(function(){
			jQuery(this).addClass("closed");
		});
		//-->
		</script>
		
	</div>
<?php
}
?>