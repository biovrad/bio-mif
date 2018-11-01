<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');

$edit_mode = isset($_GET['user_id']);
if ($edit_mode)
{
	$user_id = htmlentities($_GET['user_id']);
	$user = fs_get_user($user_id);
	if ($user === false) die(fs_db_error());
	if ($user === null) die(fs_r("No such user"));
}
	
$arr = array();
$arr[] = fs_mkPair(1, fs_r('Administrator'));
$arr[] = fs_mkPair(2, fs_r('User'));

$site_access_arr= array();
$site_access_arr[] = fs_mkPair(1, fs_r('All sites'));
$site_access_arr[] = fs_mkPair(2, fs_r('Specific sites'));
?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php echo $edit_mode ? fs_r('Edit user') : fs_r("Create a new user")?></h3>
	<table>
		<tr>
			<td><label for='new_username'><?php fs_e('User name')?></label></td>
			<td><input type='text' size='30' id='new_username' value='<?php echo $edit_mode ? $user->username : ""?>'/></td>
		</tr>
		<tr>
			<td><label for='new_email'><?php fs_e('Email')?></label></td>
			<td><input type='text' size='30' id='new_email' value='<?php echo $edit_mode ? $user->email : ""?>' /></td>
		</tr>
		<tr>
			<td><label for='new_password'><?php echo $edit_mode ? fs_r('New password (Optional)') : fs_r('Password')?></label></td>
			<td><input type='password' size='30' id='new_password' value='' /></td>
		</tr>
		<tr>
			<td><label for='new_password_verify'><?php echo $edit_mode ? fs_r('Verify new password') : fs_r('Verify password')?></label></td>
			<td><input type='password' size='30' id='new_password_verify' value='' /></td>
		</tr>
		<tr>
			<td><label for='new_security_level'><?php fs_e('Security level')?></label></td>
			<td>
			<?php
			echo fs_create_dropbox($arr,$edit_mode ? $user->security_level : 2,'new_security_level',"FS.updateUserEditCombo()")?>
			</td>
		</tr>
		<tr>
			<td><label for='site_access_type_combo'><?php fs_e('User can access statistics from')?></label></td>
			<td><?php
				$all_sites_access = $edit_mode && fs_user_allowed_to_access_site($user, -1);
				$selected = $all_sites_access ? "1" : "2"; 
				$on_combo_select = "var d = $('site_access_type_combo').selectedIndex == 0 ? 'none' : 'block';$('allowed_sites_list_label').style.display = d;$('allowed_sites_list').style.display = d;";
				echo fs_create_dropbox($site_access_arr,$selected,'site_access_type_combo', $on_combo_select);
				?>
			</td>
		</tr>
		<tr>	
			<td><label id='allowed_sites_list_label' for='allowed_sites_list' style="display:<?php echo $all_sites_access ? 'none': 'block'?>"><?php fs_e('Comma separated list of allowed site ids (example : 1,4,6)')?></label></td>
			<td>
				<input type='text' size='30' id='allowed_sites_list' value='<?php echo $edit_mode ? fs_get_user_sites_list($user_id, false) : ""?>' style="display:<?php echo $all_sites_access ? 'none': 'block'?>"/>
			</td>
		</tr>
		<tr>
			<td colspan='2'>
				<?php
				if ($edit_mode)
				{
					$save_action = "FS.updateUser($user_id,this)";
				}
				else
				{
					$save_action = "FS.createUser(this)";
				}
				?>
				<button id='create_user' class='button' onclick='<?php echo $save_action?>'><?php fs_e('Save')?></button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>
	</table>
</div>
