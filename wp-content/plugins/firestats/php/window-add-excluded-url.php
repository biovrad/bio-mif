<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');

$editing = false;
$edit_id = -1;
$edit_url = "";
if (isset($_GET['edit']))
{
	$edit_id = $_GET['edit'];
	$res = fs_get_excluded_urls($_GET['edit']);
	if ($res === false) die(fs_db_error());
	if (count($res) < 1) die("Unknown ID");
	$row = $res[0];
	$edit_url = $row->url_pattern;
}

?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php fs_e('Exclude URL or referrer')?></h3>
	<table>
		<tr>
			<td><label for='excluded_url'><?php fs_e('Excluded url/referrer')?></label></td>
			<td><input type='text' size='30' id='excluded_url' value='<?php echo $edit_url?>'/></td>
		</tr>
		<tr>
			<td colspan='2'>
				<button class='button' onclick='FS.saveExcludedUrl(this, <?php echo $edit_id?>,$F("excluded_url"))'>
					<?php fs_e('Save')?>
				</button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>	
	</table>
</div>
