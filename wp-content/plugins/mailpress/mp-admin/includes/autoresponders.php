<?php
$url_parms = MP_AdminPage::get_url_parms();

$h2 = __('Autoresponders', MP_TXTDOM);

//
// MANAGING PAGINATION
//

if( !isset($_per_page) || $_per_page <= 0 ) $_per_page = 20;
$url_parms['apage'] = isset($url_parms['apage']) ? $url_parms['apage'] : 1;

$total = ( isset($url_parms['s']) ) ? count(MP_Autoresponders::get_all(array('hide_empty' => 0, 'search' => $url_parms['s']))) : wp_count_terms(MP_AdminPage::taxonomy);

$page_links = paginate_links	(array(	'base' => add_query_arg( 'apage', '%#%' ),
							'format' => '',
							'total' => ceil( $total / $_per_page),
							'current' => $url_parms['apage']
						)
					);
if ($url_parms['apage'] <= 1) unset($url_parms['apage']);

//
// MANAGING MESSAGE
//

$messages[1] = __('Autoresponder added.', MP_TXTDOM);
$messages[2] = __('Autoresponder updated.', MP_TXTDOM);
$messages[3] = __('Autoresponder deleted.', MP_TXTDOM);
$messages[4] = __('Autoresponders deleted.', MP_TXTDOM);
$messages[91] = __('Autoresponder not added.', MP_TXTDOM);
$messages[92] = __('Autoresponder not updated.', MP_TXTDOM);

if (isset($_GET['message']))
{
	$message = $messages[$_GET['message']];
	$_SERVER['REQUEST_URI'] = remove_query_arg(array('message'), $_SERVER['REQUEST_URI']);
}

//
// MANAGING CONTENT
//

$bulk_actions[''] 	= __('Bulk Actions');
$bulk_actions['delete']	= __('Delete', MP_TXTDOM);

$mp_autoresponder_registered_events = MP_Autoresponders_events::get_all();

global $action;
wp_reset_vars(array('action'));
if ('edit' == $action) 
{
	$action = 'edited';
	$cancel = "<input type='submit' class='button' name='cancel' value=\"" . __('Cancel', MP_TXTDOM) . "\" />\n";

	$id = (int) $_GET['id'];
	$autoresponder = MP_Autoresponders::get($id);

	$h3 = __('Update the autoresponder', MP_TXTDOM);

//	$disabled = " disabled='disabled'";
	$disabled = '';
		
	$hidden = "<input type='hidden' name='id'   value=\"" . $id . "\" />\n";
	$hidden .="<input name='name' type='hidden' value=\"" . esc_attr($autoresponder->name) . "\"/>";
	
	$_mails = MP_Autoresponders::get_term_objects($id);
}
else 
{
	$action = MP_AdminPage::add_form_id;
	$cancel = '';

	$autoresponder = new stdClass();

	$h3 = __('Add an autoresponder', MP_TXTDOM);

	$disabled = '';
	$hidden = '';

	$_mails = false;
}

?>
<div class='wrap nosubsub'>
	<div id='icon-mailpress-tools' class='icon32'><br /></div>
	<h2>
		<?php echo esc_html( $h2 ); ?> 
<?php if ( isset($url_parms['s']) ) printf( '<span class="subtitle">' . __('Search results for &#8220;%s&#8221;') . '</span>', esc_attr( $url_parms['s'] ) ); ?>
	</h2>
<?php if (isset($message)) MP_AdminPage::message($message, ($_GET['message'] < 90)); ?>
	<form class='search-form topmargin' action='' method='get'>
		<input type='hidden' name='page' value='<?php echo MP_AdminPage::screen; ?>' />

		<p class='search-box'>
			<input type='text' name='s' value="<?php if (isset($url_parms['s'])) echo esc_attr( $url_parms['s'] ); ?>" class="search-input" />
			<input type='submit' value="<?php _e( 'Search', MP_TXTDOM ); ?>" class='button' />
		</p>

	</form>
	<br class='clear' />
	<div id='col-container'>
		<div id='col-right'>
			<div class='col-wrap'>
				<form id='posts-filter' action='' method='get'>
					<input type='hidden' name='page' value='<?php echo MP_AdminPage::screen; ?>' />
<?php MP_AdminPage::post_url_parms($url_parms); ?>
					<div class='tablenav'>
<?php 	if ( $page_links ) echo "						<div class='tablenav-pages'>$page_links</div>"; ?>
						<div class='alignleft actions'>
<?php	MP_AdminPage::get_bulk_actions($bulk_actions); ?>
						</div>
						<br class='clear' />
					</div>
					<div class='clear'></div>
					<table class='widefat'>
						<thead>
							<tr>
<?php MP_AdminPage::columns_list(); ?>
							</tr>
						</thead>
						<tfoot>
							<tr>
<?php MP_AdminPage::columns_list(false); ?>
							</tr>
						</tfoot>
						<tbody id='<?php echo MP_AdminPage::list_id; ?>' class='list:<?php echo MP_AdminPage::tr_prefix_id; ?>'>
<?php MP_AdminPage::get_list( (isset($url_parms['apage'])) ? $url_parms['apage'] : 1, $_per_page ); ?>
						</tbody>
					</table>
					<div class='tablenav'>
<?php 	if ( $page_links ) echo "						<div class='tablenav-pages'>$page_links</div>\n"; ?>
						<div class='alignleft actions'>
<?php	MP_AdminPage::get_bulk_actions($bulk_actions, 'action2'); ?>
						</div>
						<br class='clear' />
					</div>
					<br class='clear' />
				</form>
			</div>
		</div><!-- /col-right -->
		<div id='col-left'>
			<div class='col-wrap'>
				<div class='form-wrap'>
					<h3><?php echo $h3; ?></h3>
					<div id='ajax-response'></div>
					<form name='<?php echo $action; ?>'  id='<?php echo $action; ?>'  method='post' action='' class='<?php echo $action; ?>:<?php echo MP_AdminPage::list_id; ?>: validate'>
						<input type='hidden' name='action'   value='<?php echo $action; ?>' />
						<input type='hidden' name='formname' value='autoresponder_form' />
						<?php echo $hidden; ?>
						<?php wp_nonce_field('update-' . MP_AdminPage::tr_prefix_id); ?>
						<div class="form-field form-required" style='margin:0;padding:0;'>
							<label for='autoresponder_name'><?php _e('Name', MP_TXTDOM); ?></label>
							<input name='name' id='autoresponder_name' type='text'<?php echo $disabled; ?> value="<?php if (isset($autoresponder->name)) echo attribute_escape($autoresponder->name); ?>" size='40' aria-required='true' />
							<p><?php _e('The name is used to identify the autoresponder almost everywhere.', MP_TXTDOM); ?></p>
						</div>
						<div class="form-field" style='margin:0;padding:0;'>
							<label for='autoresponder_slug'><?php _e('Slug', MP_TXTDOM) ?></label>
							<input name='slug' id='autoresponder_slug' type='text' value="<?php if (isset($autoresponder->slug)) echo attribute_escape($autoresponder->slug); ?>" size='40' />
							<p><?php _e('The &#8220;slug&#8221; is a unique id for the autoresponder (not so friendly !).', MP_TXTDOM); ?></p>
						</div>
						<div class="form-field" style='margin:0;padding:0;'>
							<label for='autoresponder_description'><?php _e('Description', MP_TXTDOM) ?></label>
							<input type="text" id='autoresponder_description' name='description[desc]' value="<?php if (isset($autoresponder->description)) echo htmlentities(stripslashes($autoresponder->description['desc']),ENT_QUOTES); ?>" size="40"/>
							<p><?php _e('The description is not prominent by default.', MP_TXTDOM); ?></p>
						</div>
						<div class="form-field" style='margin:0;padding:0;'>
							<label for='autoresponder_active'><?php _e('Active', MP_TXTDOM) ?></label>
							<input type="checkbox" id='autoresponder_active' name='description[active]'<?php if (isset($autoresponder->description['active'])) echo " checked='checked'"; ?> style='width:auto;'/>
							<p><?php _e("If unchecked during a certain period of time, All mails that should have been sent on time will be cancelled. Following mails (if any) will be lost as well.", MP_TXTDOM); ?></p>
						</div>
						<div class="form-field" style='margin:0;padding:0;'>
							<label for='autoresponder_event'><?php _e('Event', MP_TXTDOM) ?></label>
							<select id='autoresponder_event' name='description[event]'>
<?php MP_AdminPage::select_option($mp_autoresponder_registered_events, (isset($autoresponder->description['event'])) ? $autoresponder->description['event'] : '' ); ?>
							</select>
						</div>
						<div id='autoresponder_events_specs'>
<?php foreach ($mp_autoresponder_registered_events as $key => $event) : ?>
							<div id='autoresponder_<?php echo $key; ?>_settings' class='autoresponder_settings <?php if (!isset($autoresponder->description['event']) || $key != $autoresponder->description['event']) echo " hidden"; ?>'>
<?php do_action('MailPress_autoresponder_' . $key . '_settings_form',	(isset($autoresponder->description['settings'])) ? $autoresponder->description['settings'] : 0 ); ?>
							</div>
<?php endforeach; ?>
						</div>
<?php if ($_mails) : ?>
						<div class="form-field" style='margin:0;padding:0;'>
							<label for='autoresponder_mails'><?php _e('Mails', MP_TXTDOM) ?></label>
							<table class="widefat" id='autoresponder_mails' style='width:100%;'>
								<thead>
									<tr>
										<th><?php _e('mail', MP_TXTDOM); ?></th>
										<th><?php _e('subject', MP_TXTDOM); ?></th>
										<th><?php _e('m/d/h', MP_TXTDOM); ?></th>
									</tr>
								</thead>
								<tbody>
<?php 	foreach($_mails as $_mail) 
		{ 
			$id = $_mail['mail_id'];
			MP_AdminPage::require_class('Mails');
			$mail 	= MP_Mails::get( $id );
			$subject_display = htmlspecialchars($mail->subject,ENT_QUOTES);
			if ( strlen($subject_display) > 40 )	$subject_display = substr($subject_display, 0, 39) . '...';
			if ( '' == $mail->subject)  			$subject_display = $mail->subject = htmlspecialchars(__('(no subject)', MP_TXTDOM),ENT_QUOTES);

			$edit_url    	= clean_url(MailPress_edit . "&id=$id");
			$actions['edit']    = "<a href='$edit_url'   title='" . sprintf( __('Edit "%1$s"', MP_TXTDOM) , $subject_display ) . "'>" . $_mail['mail_id'] . '</a>';

			$view_url		= clean_url(add_query_arg( array('action' => 'iview', 'id' => $id, 'KeepThis' => 'true', 'TB_iframe' => 'true', 'width' => '600', 'height' => '400'), MP_Action_url ));
			$actions['view'] = "<a href='$view_url' class='thickbox'  title='" . sprintf( __('View "%1$s"', MP_TXTDOM) , $subject_display ) . "'>" . $subject_display . '</a>';
?>
									<tr>
										<td>
											<?php echo $actions['edit']; ?>
										</td>
										<td>
											<?php echo $actions['view']; ?>
										</td>
										<td>
											<?php echo substr($_mail['schedule'],0,2) . '/' . substr($_mail['schedule'],2,2) . '/' . substr($_mail['schedule'],4,2) ; ?>
										</td>
									</tr>
<?php 	} ?>
								</tbody>
							</table>
							<p></p>
						</div>
<?php endif; ?>
						<p class='submit'>
							<input type='submit' class='button' name='submit' id='autoresponder_submit' value="<?php echo $h3; ?>" />
							<?php echo $cancel; ?>
						</p>
					</form>
				</div>
			</div>
		</div><!-- /col-left -->
	</div><!-- /col-container -->
</div><!-- /wrap -->