<?php
if (class_exists('MailPress') && !class_exists('MailPress_autoresponder'))
{
/*
Plugin Name: MailPress_autoresponder
Plugin URI: http://www.mailpress.org/wiki/index.php?title=Add_ons:Autoresponder
Description: This is just an add-on for MailPress to manage autoresponders (based on wp-cron)
Author: Andre Renaut
Version: 5.0.1
Author URI: http://www.mailpress.org
*/

// 3.

/** for admin plugin pages */
define ('MailPress_page_autoresponders', 	'mailpress_autoresponders');

/** for admin plugin urls */
$mp_file = 'admin.php';
define ('MailPress_autoresponders', $mp_file . '?page=' . MailPress_page_autoresponders);

class MailPress_autoresponder
{
	const taxonomy = 'MailPress_autoresponder';

	const bt = 100;

	function __construct()
	{
// for taxonomy
		add_action('init', 			array(__CLASS__, 'init'), 1);

// for plugin
		add_action('plugins_loaded', 					array(__CLASS__, 'plugins_loaded'));
		add_action('MailPress_add-ons_loaded', 			array(__CLASS__, 'plugins_loaded'));

// for autoresponder (from older mailpress versions)
		add_action('mp_autoresponder_process', 			array(__CLASS__, 'process'));
		add_action('mp_process_autoresponder', 			array(__CLASS__, 'process'));

// for wp admin
		if (is_admin())
		{
		// for link on plugin page
			add_filter('plugin_action_links', 			array(__CLASS__, 'plugin_action_links'), 10, 2 );
		// for role & capabilities
			add_filter('MailPress_capabilities', 		array(__CLASS__, 'capabilities'), 1, 1);
		// for settings
			add_action('MailPress_settings_logs', 		array(__CLASS__, 'settings_logs'), 10);
		// for load admin page
			add_filter('MailPress_load_admin_page', 		array(__CLASS__, 'load_admin_page'), 10, 1);
		// for mails list
			add_action('MailPress_get_icon_mails', 		array(__CLASS__, 'get_icon_mails'), 8, 1);
		// for meta box in write page
			add_action('MailPress_update_meta_boxes_write',	array(__CLASS__, 'update_meta_boxes_write'));
			add_filter('MailPress_styles', 			array(__CLASS__, 'styles'), 8, 2);
			add_filter('MailPress_scripts', 			array(__CLASS__, 'scripts'), 8, 2);
			add_action('MailPress_add_meta_boxes_write',	array(__CLASS__, 'add_meta_boxes_write'), 8, 2);
		}

// for ajax
		add_action('mp_action_add_atrspndr', 			array(__CLASS__, 'mp_action_add_atrspndr'));
		add_action('mp_action_delete_atrspndr', 			array(__CLASS__, 'mp_action_delete_atrspndr'));
		add_action('mp_action_add_wa', 				array(__CLASS__, 'mp_action_add_wa'));
		add_action('mp_action_delete_wa', 				array(__CLASS__, 'mp_action_delete_wa'));
	}

//// Taxonomy ////

	public static function init() 
	{
		register_taxonomy(self::taxonomy, 'MailPress_autoresponder', array('update_count_callback' => array(__CLASS__, 'update_count_callback')));
	}

	public static function update_count_callback( $autoresponders )
	{
		return 0;
	}

//// Plugin ////

	public static function plugins_loaded()
	{
		MailPress::require_class('Autoresponders');
		MailPress::load_options('Autoresponders_events');

		do_action('MailPress_load_Autoresponders_events');
	}

////  Autoresponder  ////

	public static function process($args)
	{
		MailPress::no_abort_limit();

		extract($args);		// $umeta_id, $mail_order
		$meta_id = (isset($umeta_id)) ? $umeta_id : $meta_id;

		MailPress::require_class('Usermeta');
		$meta = MP_Usermeta::get_by_id($meta_id);
		$term_id 	= (!$meta) ? false : str_replace('_MailPress_autoresponder_', '', $meta->meta_key);
		if (!$term_id) return;
		
		$autoresponder = MP_Autoresponders::get($term_id);

		do_action('mp_process_autoresponder_' . $autoresponder->description['event'], $args);
	}

////  ADMIN  ////
////  ADMIN  ////
////  ADMIN  ////
////  ADMIN  ////

// for link on plugin page
	public static function plugin_action_links($links, $file)
	{
		return MailPress::plugin_links($links, $file, plugin_basename(__FILE__), 'logs');
	}

// for role & capabilities
	public static function capabilities($capabilities)
	{
		$capabilities['MailPress_manage_autoresponders'] = array(	'name'	=> __('Autoresponders', MP_TXTDOM),
												'group'	=> 'mails',
												'menu'	=> 15,

												'parent'	=> false,
												'page_title'=> __('MailPress Autoresponders', MP_TXTDOM),
												'menu_title'=> '&nbsp;' . __('Autoresponders', MP_TXTDOM),
												'page'	=> MailPress_page_autoresponders,
												'func'	=> array('MP_AdminPage', 'body')
									);
		return $capabilities;
	}

// for settings
	public static function settings_logs($logs)
	{
		MP_AdminPage::logs_sub_form('autoresponder', $logs, __('Autoresponder', MP_TXTDOM), __('Autoresponder log', MP_TXTDOM), __('(for <b>ALL</b> Autoresponders through MailPress)', MP_TXTDOM), __('Number of Autoresponder log files : ', MP_TXTDOM));
	}

// for load admin page
	public static function load_admin_page($hub)
	{
		$hub[MailPress_page_autoresponders] = 'autoresponders';
		return $hub;
	}

//� for ajax
	public static function mp_action_add_atrspndr() 
	{
		if (!current_user_can('MailPress_manage_autoresponders')) die('-1');

		if ( '' === trim($_POST['name']) ) 
		{
			$x = new WP_Ajax_Response( array(	'what' => 'autoresponder', 
									'id' => new WP_Error( 'autoresponder_name', __('You did not enter a valid autoresponder name.', MP_TXTDOM) )
								   ) );
			$x->send();
		}

		MailPress::require_class('Autoresponders');
		if ( MP_Autoresponders::exists( trim( $_POST['name'] ) ) ) 
		{
			$x = new WP_Ajax_Response( array(	'what' => 'autoresponder', 
									'id' => new WP_Error( __CLASS__ . '::exists', __('The autoresponder you are trying to create already exists.', MP_TXTDOM), array( 'form-field' => 'name' ) ), 
								  ) );
			$x->send();
		}
	
		$autoresponder = MP_Autoresponders::insert( $_POST, true );

		if ( is_wp_error($autoresponder) ) 
		{
			$x = new WP_Ajax_Response( array(	'what' => 'autoresponder', 
									'id' => $autoresponder
								  ) );
			$x->send();
		}

		if ( !$autoresponder || (!$autoresponder = MP_Autoresponders::get( $autoresponder )) ) 	MailPress::mp_die('0');

		$autoresponder_full_name 	= $autoresponder->name;

		include (MP_ABSPATH . 'mp-admin/autoresponders.php');
		$x = new WP_Ajax_Response( array(	'what' => 'autoresponder', 
								'id' => $autoresponder->term_id, 
								'data' => MP_AdminPage::get_row( $autoresponder, array() ), 
								'supplemental' => array('name' => $autoresponder_full_name, 'show-link' => sprintf(__( 'Autoresponder <a href="#%s">%s</a> added' , MP_TXTDOM), "autoresponder-$autoresponder->term_id", $autoresponder_full_name))
							  ) );
		$x->send();
		break;
	}

	public static function mp_action_delete_atrspndr() 
	{
		$id = isset($_POST['id'])? (int) $_POST['id'] : 0;
		MailPress::require_class('Autoresponders');
		MailPress::mp_die( MP_Autoresponders::delete($id) ? '1' : '0' );
	}

// for mails list
	public static function get_icon_mails($mail_id)
	{
		MailPress::require_class('Autoresponders');
		if (!MP_Autoresponders::object_have_relations($mail_id)) return;
?>
			<img class='attach' alt="<?php _e('Autoresponder', MP_TXTDOM); ?>" title="<?php _e('Autoresponder', MP_TXTDOM); ?>" src='<?php echo get_option('siteurl') . '/' . MP_PATH; ?>mp-admin/images/autoresponder.png' />
<?php
	}
		
// for meta box in write page
	public static function meta_box_write_parms()
	{
		$x['table_body_id'] = 'wa-list';				// the-list
		$x['ajax_response'] = 'wa-response'; 			// ajar-response
		$x['table_list_id'] = 'wa-list-table';			// list-table

		$x['tr_prefix_id']  = 'wa';

		return $x;
	}

	public static function update_meta_boxes_write()
	{
	}

	public static function styles($styles, $screen) 
	{
		if ($screen != MailPress_page_write) return $styles;

		wp_register_style ( MailPress_page_autoresponders, '/' . MP_PATH . 'mp-admin/css/write_autoresponders.css', array(), false, 1);

		$styles[] = MailPress_page_autoresponders;

		return $styles;
	}

	public static function scripts($scripts, $screen) 
	{
		if ($screen != MailPress_page_write) return $scripts;

		wp_register_script( MailPress_page_autoresponders, '/' . MP_PATH . 'mp-admin/js/write_autoresponders.js', array('mp-lists'), false, 1);
		wp_localize_script( MailPress_page_autoresponders, 	'adminautorespondersL10n',	array_merge(	array('pending' => __('%i% pending'), 'screen' => MP_AdminPage::screen),
																			self::meta_box_write_parms(),
																			array('l10n_print_after' => 'try{convertEntities(adminautorespondersL10n);}catch(e){};')
																)
		);
		$scripts[] = MailPress_page_autoresponders;

		return $scripts;
	}

	public static function add_meta_boxes_write($mail_id, $mp_screen)
	{
		add_meta_box('write_autoresponder', __('Autoresponders', MP_TXTDOM), array(__CLASS__, 'meta_box'), MP_AdminPage::screen, 'normal', 'core');
	}
/**/
	public static function meta_box($mail)
	{
		MP_AdminPage::require_class('Autoresponders');
		$parms = self::meta_box_write_parms();
?>
<div id='postcustomstuff'>
	<div id='<?php echo $parms['ajax_response'] ?>'></div>
<?php
        	$id = (isset($mail->id)) ? $mail->id : 0;
		$metadata = MP_Autoresponders::get_object_terms($id);
		$count = 0;
		if ( !$metadata ) : $metadata = array(); 
?>
	<table id='<?php echo $parms['table_list_id'] ?>' style='display: none;'>
		<thead>
			<tr>
				<th class='left'><?php _e( 'Autoresponder', MP_TXTDOM ); ?></th>
				<th><?php _e( 'Schedule', MP_TXTDOM ); ?></th>
			</tr>
		</thead>
		<tbody id='<?php echo $parms['table_body_id'] ?>' class='list:<?php echo $parms['tr_prefix_id'] ?>'>
			<tr><td></td></tr>
		</tbody>
	</table>
<?php else : ?>
	<table id='<?php echo $parms['table_list_id'] ?>'>
		<thead>
			<tr>
				<th class='left'><?php _e( 'Autoresponder', MP_TXTDOM ) ?></th>
				<th><?php _e( 'Schedule', MP_TXTDOM ) ?></th>
			</tr>
		</thead>
		<tbody id='<?php echo $parms['table_body_id'] ?>' class='list:<?php echo $parms['tr_prefix_id'] ?>'>
<?php foreach ( $metadata as $entry ) echo self::meta_box_autoresponder_row( $entry, $count ); ?>
		</tbody>
	</table>
<?php endif; ?>
<?php
	$autoresponders = MP_Autoresponders::get_all();
	foreach( $autoresponders as $autoresponder )
	{
		$_autoresponders[$autoresponder->term_id] = $autoresponder->name;
	}
	if (empty($_autoresponders)) :
?>
	<p>
		<strong>
			<?php _e( 'No autoresponder', MP_TXTDOM) ?>
		</strong>
	</p>
<?php else : ?>
	<p>
		<strong>
			<?php _e( 'Link to :', MP_TXTDOM) ?>
		</strong>
	</p>
	<table id='add_<?php echo $parms['tr_prefix_id']; ?>'>
		<thead>
			<tr>
				<th class='left'><label for='autoresponderselect'><?php _e( 'Autoresponder', MP_TXTDOM ) ?></label></th>
				<th><label for='metavalue'><?php _e( 'Schedule', MP_TXTDOM ) ?></label></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td id='newarleft' class='left'>
					<select id='autoresponderselect' name='autoresponderselect' tabindex='7'>
<?php MP_AdminPage::select_option($_autoresponders, false); ?>
					</select>
				</td>
				<td style='vertical-align:top;'>
					<table style='border:none;margin:8px 0 8px 8px;width:95%;'>
						<tbody>
							<tr>
								<td class='arschedule'>
									<?php _e('Month', MP_TXTDOM);?><br />
									<select style='width:auto;margin:0;padding:0;' name='autoresponder[schedule][MM]' >
<?php MP_AdminPage::select_number(0, 12, (isset($month)) ? $month : 0); ?>
									</select>
								</td>
								<td class='arschedule'>
									<?php _e('Day', MP_TXTDOM);?><br />
									<select style='width:auto;margin:0;padding:0;' name='autoresponder[schedule][DD]' >
<?php MP_AdminPage::select_number(0, 31, (isset($days)) ? $days : 0); ?>
									</select>
								</td>
								<td class='arschedule'>
									<?php _e('Hour', MP_TXTDOM);?><br />
									<select style='width:auto;margin:0;padding:0;' name='autoresponder[schedule][HH]' >
<?php MP_AdminPage::select_number(0, 23, (isset($hours)) ? $hours : 0); ?>
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			<tr>
				<td colspan='2' class='submit'>
					<input type='submit' id='addmetasub' name='addwrite_autoresponder' class='add:<?php echo $parms['table_body_id']; ?>:add_<?php echo $parms['tr_prefix_id']; ?>' tabindex='9' value="<?php _e( 'Add', MP_TXTDOM ) ?>" />
					<?php wp_nonce_field( 'add-write-autoresponder', '_ajax_nonce', false ); ?>
				</td>
			</tr>
		</tbody>
	</table>
<?php endif; ?>
</div>
<?php
	}
	//� for ajax
	public static function meta_box_autoresponder_row( $entry, &$count ) 
	{
		MailPress::require_class('Autoresponders');
		$parms = self::meta_box_write_parms();

		static $update_nonce = false;
		if ( !$update_nonce ) $update_nonce = wp_create_nonce( 'add-write-autoresponder' );

		$r = '';
		++ $count;

		if ( $count % 2 )	$style = 'alternate';
		else			$style = '';

		$entry['meta_id'] 	= (int) $entry['meta_id'];

		$delete_nonce 		= wp_create_nonce( 'delete-write-autoresponder_' . $entry['meta_id'] );

		$autoresponders = MP_Autoresponders::get_all();
		foreach( $autoresponders as $autoresponder )
		{
			$_autoresponders[$autoresponder->term_id] = $autoresponder->name;
		}
		$r .= "
			<tr id='{$parms['tr_prefix_id']}-{$entry['meta_id']}' class='$style'>
				<td class='left'>
					<select id='write_autoresponder_{$entry['meta_id']}_key' name='write_autoresponder[{$entry['meta_id']}][key]' tabindex='7'>
" . MailPress::select_option($_autoresponders, $entry['term_id'], false) . "
					</select>
					<div class='submit'>
						<input name='delete_wa-{$entry['meta_id']}' type='submit' class='delete:{$parms['table_body_id']}:{$parms['tr_prefix_id']}-{$entry['meta_id']}::_ajax_nonce=$delete_nonce delete_wa' tabindex='6' value='".attribute_escape(__( 'Delete' ))."' />
						<input name='update_wa-{$entry['meta_id']}' type='submit' tabindex='6' value='".attribute_escape(__( 'Update' ))."' class='add:{$parms['table_body_id']}:{$parms['tr_prefix_id']}-{$entry['meta_id']}::_ajax_nonce=$update_nonce update_wa' />
					" . wp_nonce_field( 'change-write_autoresponder', '_ajax_nonce', false, false ) . "
					</div>
				</td>
				<td style='vertical-align:top;'>
					<table style='border:none;margin:8px 0 8px 8px;width:95%;'>
						<tbody>
							<tr>
								<td class='arschedule'>
									" . __('Month', MP_TXTDOM) . "<br />
									<select style='width:auto;margin:0;padding:0;' name='write_autoresponder[" . $entry['meta_id'] . "][value][MM]' >
" . MailPress::select_number(0, 12, substr($entry['schedule'], 0, 2), 1, false) . "
									</select>
								</td>
								<td class='arschedule'>
									" . __('Day', MP_TXTDOM) . "<br />
									<select style='width:auto;margin:0;padding:0;' name='write_autoresponder[" . $entry['meta_id'] . "][value][DD]' >
" . MailPress::select_number(0, 31, substr($entry['schedule'], 2, 2), 1, false) . "
									</select>
								</td>
								<td class='arschedule'>
									" . __('Hour', MP_TXTDOM) . "<br />
									<select style='width:auto;margin:0;padding:0;' name='write_autoresponder[" . $entry['meta_id'] . "][value][HH]' >
" . MailPress::select_number(0, 23, substr($entry['schedule'], 4, 2), 1, false) . "
									</select>
								</td>
							</tr>
						</tbody>
					</table>
				</td>
			</tr>
			";
		return $r;
	}

// for ajax	

	public static function mp_action_add_wa()
	{
		if ( !current_user_can( 'MailPress_manage_autoresponders') )	die('-1');

		$c = 0;
		$object_id = (int) $_POST['mail_id'];
		if ($object_id === 0) MailPress::mp_die();

		if ( isset($_POST['autoresponderselect']) || isset($_POST['autoresponder']['schedule']) ) 
		{
			if ( !$meta_id = self::add_meta( $object_id ) ) 	MailPress::mp_die();

			$response = array('position' 	=> 1);
		}
		else
		{
			$meta_id   = (int) array_pop(array_keys($_POST['write_autoresponder']));
			$key   = '_MailPress_autoresponder_' . $_POST['write_autoresponder'][$meta_id]['key'];
			if (isset($_POST['write_autoresponder'][$meta_id]['value'])) foreach ($_POST['write_autoresponder'][$meta_id]['value'] as $k => $v) if ($v <10) $_POST['write_autoresponder'][$meta_id]['value'][$k] = '0' . $v;
			$value = implode('', $_POST['write_autoresponder'][$meta_id]['value']);

			MailPress::require_class('Mailmeta');
			if ( !$meta = MP_Mailmeta::get_by_id( $meta_id ) )		MailPress::mp_die();
			if ( !MP_Mailmeta::update_by_id($meta_id , $key, $value) )	MailPress::mp_die(1);

			$response = array('old_id' 	=> $meta_id, 'position' 	=> 0);
		}

		MailPress::require_class('Mailmeta');
		$meta = MP_Mailmeta::get_by_id( $meta_id );
		$object_id = (int) $meta->mp_mail_id;
		$meta = get_object_vars( $meta );

		MailPress::require_class('Autoresponders');

		$response = array_merge($response, array('what' => 'write-autoresponder', 'id' => $meta_id, 'data' => self::meta_box_autoresponder_row( MP_Autoresponders::get_term_meta_id($meta_id), $c ), 'supplemental' => array('mail_id' => $object_id) ) );

		$x = new WP_Ajax_Response( $response );

		$x->send();

	}

	public static function add_meta($mail_id)
	{
		$mail_id = (int) $mail_id;
		if (isset($_POST['autoresponder']['schedule'])) foreach ($_POST['autoresponder']['schedule'] as $k => $v) if ($v <10) $_POST['autoresponder']['schedule'][$k] = '0' . $v;

		$metakey 	= isset($_POST['autoresponderselect']) ? '_MailPress_autoresponder_' . trim( $_POST['autoresponderselect'] ) : '';
		$metavalue 	= isset($_POST['autoresponder']['schedule']) ? implode('', $_POST['autoresponder']['schedule']) : '';

		if ( !empty($metavalue)  && !empty ($metakey) ) 
		{
			MailPress::require_class('Mailmeta');
			return MP_Mailmeta::add( $mail_id, $metakey, $metavalue );
		}
		return false;
	}

	public static function mp_action_delete_wa()
	{
		if ( !current_user_can( 'MailPress_manage_autoresponders') )	MailPress::mp_die('-1');

		$id = isset($_POST['id'])? (int) $_POST['id'] : 0;

		MailPress::require_class('Mailmeta');
		if ( !$meta = MP_Mailmeta::get_by_id( $id ) ) 				MailPress::mp_die('1');
		if ( MP_Mailmeta::delete_by_id( $meta->meta_id ) )			MailPress::mp_die('1');
		MailPress::mp_die('0');
	}
}
new MailPress_autoresponder();
}