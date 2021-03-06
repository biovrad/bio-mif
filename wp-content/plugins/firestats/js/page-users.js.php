<?php
@header('Content-type: text/javascript; charset=utf-8');
require_once(dirname(dirname(__FILE__)).'/php/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
?>

FS.newUserDialog = function()
{
	FS.createWindowUrl(400,380, 'center','center','<?php echo fs_js_url("php/window-edit-user.php")?>');
}

FS.deleteUserDialog = function(id)
{
	var url = "<?php echo fs_js_url("php/window-delete-user.php",'user_id=')?>"+id;
	FS.createWindowUrl(200,150,'center','center',url);
}

FS.editUserDialog = function(id)
{
	var url = "<?php echo fs_js_url("php/window-edit-user.php",'user_id=')?>"+id;
	FS.createWindowUrl(400,380,'center','center',url);
}

FS.createUser = function(button)
{
	var user = $F('new_username');
	var email = $F('new_email');
	var pass1 = $F('new_password');
	var pass2 = $F('new_password_verify');
	var security_level = $F('new_security_level');
	var can_access_all_sites = $F('site_access_type_combo') == 1 ? 'true' : 'false';
	var allowed_sites_list = $F('allowed_sites_list');
	var request = 'action=createUser&username='+user+'&email='+email+'&pass1='+pass1+'&pass2='+pass2
				  + '&security_level=' + security_level 
				  + "&can_access_all_sites=" + can_access_all_sites
				  + "&allowed_sites_list=" + allowed_sites_list;
	sendRequest(request, function(response)
	{
		if (response.status == 'ok')
		{
			sendRequest('action=updateFields&update=fs_users_table');
			closeParentWindow(button);
		}
	});
}

FS.updateUser = function(id,button)
{
	var user = $F('new_username');
	var email = $F('new_email');
	var pass1 = $F('new_password');
	var pass2 = $F('new_password_verify');
	var security_level = $F('new_security_level');
	var can_access_all_sites = $F('site_access_type_combo') == 1 ? 'true' : 'false';
	var allowed_sites_list = $F('allowed_sites_list');
	var request = 'action=updateUser&id=' + id + '&username=' + user + '&email=' 
				  + email + '&pass1=' + pass1 + '&pass2=' + pass2 
				  + '&security_level=' + security_level + "&can_access_all_sites=" + can_access_all_sites
				  + "&allowed_sites_list=" + allowed_sites_list
				  + "&update=sites_filter_span";
	sendRequest(request, function(response)
	{
		if (response.status == 'ok')
		{
			sendRequest('action=updateFields&update=fs_users_table');
			closeParentWindow(button);
		}
	});
}



FS.deleteUser = function(id,button)
{
	sendRequest('action=deleteUser&id='+id,function(response)
	{
		if (response.status == 'ok')
		{
			sendRequest('action=updateFields&update=fs_users_table');
			closeParentWindow(button);
		}
	});
}


FS.updateUserEditCombo = function()
{
	var i = $("new_security_level").selectedIndex;
	if(i == 0)
	{
		$("allowed_sites_list").style.display = "none";
		$("allowed_sites_list_label").style.display = "none";
		$("site_access_type_combo").selectedIndex = 0;
	}
	else
	{
		$("allowed_sites_list").style.display = "block";
		$("allowed_sites_list_label").style.display = "block";
		$("site_access_type_combo").selectedIndex = 1;
	};
}