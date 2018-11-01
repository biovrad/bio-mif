<?php
@header('Content-type: text/javascript; charset=utf-8');
require_once(dirname(dirname(__FILE__)).'/php/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
?>
//<![CDATA[
FS.addBot = function()
{
    var bot = $F('bot_wildcard');
    $('bot_wildcard').value = '';
    var params = 'action=' + 'addBot' + '&wildcard=' + bot;
    sendRequest(params);
}

FS.removeBot = function()
{
	var selected = FS.getMultipleSelectionIndex('botlist').join(',');
    if (selected.length == 0)
    {
        showError("<?php fs_e('You need to select one or more bots')?>");
    }
    else
    {
        var params = 'action=' + 'removeBot' + '&bot_ids=' +selected;
        sendRequest(params);
    }
}

FS.addExcludedURL = function()
{
	var url = "<?php echo fs_js_url("php/window-add-excluded-url.php")?>";
	FS.createWindowUrl(400,270,'center','center',url);
}

FS.removeExcludedURL = function()
{
	var selected = FS.getMultipleSelectionIndex('exclude_urls_table').join(',');
    if (selected.length == 0)
    {
        showError("<?php fs_e('You need to select one or more urls')?>");
    }
    else
    {
		var params = 'action=' + 'removeExcludedUrl' + '&ids=' +selected;
		sendRequest(params);
    }
}

FS.editExcludedURL = function()
{
    var index = $('exclude_urls_table').selectedIndex;
    if (index == -1)
    {
        showError("<?php fs_e('You need to select a record from the table')?>");
    }
    else
    {
		var id = $F('exclude_urls_table');
		var url = "<?php echo fs_js_url("php/window-add-excluded-url.php").'&edit='?>" + id;
		FS.createWindowUrl(400,270,'center','center',url);
    }
}

FS.addExcludedIP = function()
{
	var url = "<?php echo fs_js_url("php/window-add-excluded-ip.php")?>";
	FS.createWindowUrl(400,270,'center','center',url);
}

FS.removeExcludedIP = function()
{
	var selected = FS.getMultipleSelectionIndex('exclude_ip_table').join(',');
    if (selected.length == 0)
    {
        showError("<?php fs_e('You need to select one or more IP addresses')?>");
    }
    else
    {
        var params = 'action=' + 'removeExcludedIP' + '&ids=' +selected;
        sendRequest(params);
    }
}

FS.editExcludedIP = function()
{
    var index = $('exclude_ip_table').selectedIndex;
    if (index == -1)
    {
        showError("<?php fs_e('You need to select a record from the table')?>");
    }
    else
    {
		var id = $F('exclude_ip_table');
		var url = "<?php echo fs_js_url("php/window-add-excluded-ip.php").'&edit='?>" + id;
		FS.createWindowUrl(400,270,'center','center',url);
    }
}

FS.saveExcludedIP = function(parentWindow, edit_id, start_ip, end_ip)
{
	var params = 'action=addOrEditExcludedIP&start_ip=' +start_ip + (end_ip != undefined ? "&end_ip=" + end_ip : "") + 
				 (edit_id != -1 ? "&edit_id="+edit_id : "");
	sendRequest(params, function(response)
    {
    	if (response.status != 'error')
    	{
			closeParentWindow(parentWindow);    	
    	}
    });
}

FS.saveExcludedUrl = function(parentWindow, edit_id, url)
{
	var params;
	if (edit_id != "-1")
	{
		params = 'action=editExcludedUrl&url=' + url +"&edit_id="+edit_id;
	}
	else
	{
		params = 'action=addExcludedUrl&url=' + url;
	}
	
	sendRequest(params, function(response)
    {
    	if (response.status != 'error')
    	{
			closeParentWindow(parentWindow);    	
    	}
    });
}


function changeLanguage()
{
    sendRequest('action=changeLanguage&language=' + $F('language_code'));
}

function changeTimeZone()
{
    saveOption('firestats_user_timezone','firestats_user_timezone','string','records_table');
}


function toggleArchiveOldData()
{
	if (!FS.archivingOldData)
	{
		FS.archiveOldData();
	}
	else
	{
		FS.archiveCleanup();
	}
}

function openImportBots() 
{
	FS.openWindow('<?php echo fs_url('bridge.php').fs_get_request_suffix("file_id=import_bots")?>',300,300);
}
//]]>