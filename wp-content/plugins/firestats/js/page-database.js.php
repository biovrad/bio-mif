<?php
@header('Content-type: text/javascript; charset=utf-8');
require_once(dirname(dirname(__FILE__)).'/php/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
?>
FS.testDBConnection = function()
{
    var host    = encodeURIComponent($F('text_database_host'));
    var user    = encodeURIComponent($F('text_database_user'));
    var pass    = encodeURIComponent($F('text_database_pass'));
    var dbname  = encodeURIComponent($F('text_database_name'));
    var prefix  = encodeURIComponent($F('text_database_prefix'));
    var params = 'action=testDBConnection&host=' + host + "&user=" + user + "&pass="+pass+"&dbname="+dbname+"&table_prefix="+prefix;
    sendRequest(params);
}

FS.installDBTables = function()
{
    var host    = encodeURIComponent($F('text_database_host'));
    var user    = encodeURIComponent($F('text_database_user'));
    var pass    = encodeURIComponent($F('text_database_pass'));
    var dbname  = encodeURIComponent($F('text_database_name'));
    var prefix  = encodeURIComponent($F('text_database_prefix'));
    var params = 'action=installDBTables&host=' + host + "&user=" + user + "&pass="+pass+"&dbname="+dbname+"&table_prefix="+prefix;
    sendRequest(params);
}


FS.attachToDatabase = function()
{
    var host    = encodeURIComponent($F('text_database_host'));
    var user    = encodeURIComponent($F('text_database_user'));
    var pass    = encodeURIComponent($F('text_database_pass'));
    var dbname  = encodeURIComponent($F('text_database_name'));
    var prefix  = encodeURIComponent($F('text_database_prefix'));
    var params = 'action=attachToDatabase&host=' + host + "&user=" + user + "&pass="+pass+"&dbname="+dbname+"&table_prefix="+prefix;
    sendRequest(params);
}

FS.createNewDatabase = function()
{
    var user        = encodeURIComponent($F('text_database_firestats_user'));
    var pass        = encodeURIComponent($F('text_database_firestats_pass'));
    var host        = encodeURIComponent($F('text_database_host'));
    var admin_user  = encodeURIComponent($F('text_database_user'));
    var admin_pass  = encodeURIComponent($F('text_database_pass'));
    var dbname      = encodeURIComponent($F('text_database_name'));
    var prefix      = encodeURIComponent($F('text_database_prefix'));
    var params      =   'action=createNewDatabase&host=' + host +
                        "&user=" + user + "&pass=" + pass +
                        "&dbname=" + dbname + "&table_prefix="+prefix +
                        "&admin_user=" + admin_user + "&admin_pass=" + admin_pass;
    sendRequest(params);

}

FS.useWordpressDB = function()
{
    var params = 'action=useWordpressDB';
    sendRequest(params);
}

FS.upgradeDatabase = function()
{
	$('upgrade_db').disabled = true;
	$('upgrade_db').innerHTML = '<?php fs_e('Upgrading, do not interrupt')?>';
    var params = 'action=upgradeDatabase';
    sendRequest(params, function(response)
    {
    	if (response.status == 'error')
    	{
	   		$('upgrade_db').disabled = false;
   			$('upgrade_db').innerHTML = '<?php fs_e('Try again')?>';
    	}
    });
}
