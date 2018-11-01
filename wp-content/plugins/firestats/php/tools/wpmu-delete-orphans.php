<?php
die("Access denied, comment line 2 in " . __FILE__ . " to access this function, don't forget to comment it back when you are done.");
require_once(dirname(dirname(__FILE__)).'/init.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
$table_prefix = "wp_"; // edit if you have something else in wp-config.php.

$fsdb = &fs_get_db_conn();
$sites_table = fs_sites_table();
$blogs_table = $table_prefix . "blogs";
$res = $fsdb->get_results("select id from $sites_table where id not in (select blog_id from $blogs_table)");
if ($res === false)
{
	die(fs_db_error());
}
else
{
	if (count($res) > 0)
	{
		foreach($res as $r)
		{
			$id = $r->id;
			fs_println("Deleting statistics for blog #$id");
			$res1 = fs_delete_site($id,'delete',null);
			if ($res1 !== true)
			{
				fs_println("Error deleting site: $res1");
			}
		}
		fs_println("done");
	}
	else
	{
		fs_println("No orphans sites to delete");
	}
}
?>
