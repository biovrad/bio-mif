<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');

$editing = false;
$range = false;
$edit_start = "";
$edit_end = "";
$edit_id = -1;
if (isset($_GET['edit']))
{
	$edit_id = $_GET['edit'];
	$res = fs_get_excluded_ips($_GET['edit']);
	if ($res === false) die(fs_db_error());
	if (count($res) < 1) die("Unknown ID");
	$row = $res[0];
	$start_ip = fs_ip_to_string($row->start_ip1,$row->start_ip2);
	$end_ip = fs_ip_to_string($row->end_ip1,$row->end_ip2);
	if (!strcmp($start_ip,$end_ip))
	{
		$edit_start = $start_ip;
	}
	else
	{
		$range = true;
		$edit_start = $start_ip;
		$edit_end = $end_ip;
	}
}

?>
<div class='<?php echo fs_lang_dir()?>'>
	<h3><?php fs_e('Exclude IP address')?></h3>
	<table>
		<tr>
			<td>
				<input 	id="radio_exclude_single" 
						type="radio" 
						name="exclude_type" 
						value="single" 
						<?php echo $range ? "" : "checked='checked'"?>
						onclick="$('signle_address_div').style.display = 'block';$('ip_range_div').style.display = 'none';$('single_address_input').value = $F('range_start_address')">
				<label for="radio_exclude_single"><?php fs_e("Single")?></label>
				<input 	id="radio_exclude_range" 
						type="radio" 
						name="exclude_type" 
						value="range"
						<?php echo $range ? "checked='checked'" : ""?>
						onclick="$('signle_address_div').style.display = 'none';$('ip_range_div').style.display = 'block';$('range_start_address').value = $F('single_address_input')">
				<label for="radio_exclude_range"><?php fs_e("Range")?></label>
			</td>
		</tr>
		<tr>
			<td>
				<div id="signle_address_div" style="display: <?php echo $range ? "none" : "block"?>">
					<table>
						<tr>
							<td>
								<label for="single_address_input"><?php fs_e("IP Address")?></label>
							</td>
							<td>
								<input id="single_address_input" type="text" value="<?php echo $edit_start?>" size="30">
							</td>
						</tr>
					</table>
				</div>
				
				<div id="ip_range_div" style="display: <?php echo $range ? "block" : "none"?>">
					<table>
						<tr>
							<td>
								<label for="range_start_address"><?php fs_e("Range start")?></label>
							</td>
							<td>
								<input id="range_start_address" type="text" value="<?php echo $edit_start?>" size="30"><br/>
							</td>
						</tr>
						<tr>
							<td>
								<label for="range_end_address"><?php fs_e("Range end")?></label>
							</td>
							<td>
								<input id="range_end_address" type="text" value="<?php echo $edit_end?>" size="30">
							</td>
						</tr>
					</table>
				</div>
			</td>
		</tr>
		<tr>
			<td colspan='2'>
				<button class='button' onclick='$F("radio_exclude_single") == "single" ? 
													FS.saveExcludedIP(this, <?php echo $edit_id?>,$F("single_address_input")) : 
													FS.saveExcludedIP(this, <?php echo $edit_id?>, $F("range_start_address"),$F("range_end_address"))'>
					<?php fs_e('Save')?>
				</button>
				<button class='button' onclick='closeParentWindow(this)'><?php fs_e('Close')?></button>
			</td>
		</tr>
	</table>
</div>
