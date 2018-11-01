<?php

// Edit this line to match your FireStats directory.
// $_SERVER["DOCUMENT_ROOT"] represents the document root on Apache.
define('FS_PATH',$_SERVER["DOCUMENT_ROOT"].'/firestats'); 

// This is the site ID of your MediaWiki site inside FireStats sites table.
// You need to add a MediaWiki site in the Sites tab in FireStats and then change the
// value here to match the ID.
// this will allow FireStats to show the statistics of your MediaWiki site seperated from your other sites.
//
// Note: This is optional, the default value will also work.
define('FS_SITE_ID',1);


if (!file_exists(FS_PATH.'/php/db-hit.php'))
{
	echo "FireStats not found";
	return;
}
require_once(FS_PATH.'/php/db-hit.php');
$wgHooks['LogPageLogHeader'][] = 'fs_mediawiki_hit';

function fs_mediawiki_hit($wiki_object)
{
	if (isset($_REQUEST['gen']) || isset($_REQUEST['ctype'])) 
	{
		return true;
	}
	fs_add_site_hit(FS_SITE_ID);
	return true;
}

?>
