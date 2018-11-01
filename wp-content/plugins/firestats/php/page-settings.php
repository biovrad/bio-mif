<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
require_once(FS_ABS_PATH.'/php/version-check.php');
fs_ensure_authenticated();
?>

<div class="first box last full_width">
<h3><?php fs_e('Options')?></h3>

<table>
	<tr>
		<td><?php fs_e('Select language')?>:</td>
		<td style="width: 150px"><select class='full_width' id="language_code">
		<?php echo fs_get_languages_list()?>
		</select></td>
		<td>
		<button class="button" onclick="changeLanguage()"><?php fs_e('Save');?></button>
		<?php echo fs_link(FS_WIKI.'TranslateFireStats',fs_r('How to translate to a new language'), true,"_blank")?>
		</td>
	</tr>
	<tr>
		<td><?php fs_e('Select your time zone')?></td>
		<?php if (fs_mysql_newer_than("4.1.12")){?>
		<td><select class='full_width' id='firestats_user_timezone'>
		<?php echo fs_get_timezone_list()?>
		</select></td>
		<td>
		<button class="button" onclick="changeTimeZone()"><?php fs_e('Save');?></button>
		</td>
		<?php } else {
			echo "<td colspan='2'><b>".sprintf(fs_r('Time zone selection requires %s or newer'), "Mysql 4.1.13")."</b><td>";
		}?>
	</tr>
	<tr>
		<td><?php fs_e('Select WHOIS provider')?></td>
		<td><select class='full_width' id="whois_providers">
		<?php echo fs_get_whois_options()?>
		</select></td>
		<td>
		<button class="button"
			onclick="saveOption('whois_providers','whois_provider','string','records_table')">
			<?php fs_e('Save');?></button>
			<?php fs_create_wiki_help_link('WhoisProviders',800,600);?></td>
	</tr>
</table>
</div>


			<?php if (fs_is_admin() || fs_is_demo()){?>
<div class="box first  full_width">
<h3><?php fs_e('Exclude hits')?></h3>
			<?php fs_e('Purge excluded records stored in the database')?>(<b
	id="num_excluded"><?php echo fs_get_num_excluded()?></b>)
<button class="button" onclick="sendRequest('action=purgeExcludedHits')">
			<?php fs_e('Purge');?></button>

<div class="box first third">
<h3><?php fs_e('User agent')?></h3>
<div><input type="text"
	onkeypress="return FS.trapEnter(event,'FS.addBot()');"
	id="bot_wildcard" /> <input type='image' class='img_btn'
	src='<?php echo fs_url("img/add.png")?>' onclick='FS.addBot()' /> <input
	type='image' class='img_btn'
	src='<?php echo fs_url("img/delete.png")?>' onclick='FS.removeBot()' />
			<?php fs_cfg_button('more_bots_options')?></div>
<span id="more_bots_options" class="normal_font hidden"> <?php
$auto_bots_list_update = fs_get_auto_bots_list_update();
$auto_bots_list_update = $auto_bots_list_update == 'true' ? "checked=\"checked\"" : "";
?> <input type="checkbox"
	onclick="saveOption('auto_bots_list_update','auto_bots_list_update','boolean')"
	id="auto_bots_list_update" <?php echo $auto_bots_list_update?> /> <?php fs_e('Automatic update')?><br />
<button class="button"
	onclick="sendRequest('action=updateBotsList&amp;update=botlist_placeholder,num_excluded')">
<?php fs_e('Update now')?></button>
<button class="button" onclick="openImportBots()"><?php fs_e('Import')?>
</button>
<button class="button"
	onclick="window.location.href='<?php echo fs_url('php/export-bots-list.php')?>'">
<?php fs_e('Export')?></button>
</span>
<div id="botlist_placeholder"><?php echo fs_get_bot_list()?></div>
</div>
<div class="box third">
<h3><?php fs_e('IP address')?></h3>
<div><input type='image' class='img_btn'
	src='<?php echo fs_url("img/add.png")?>' onclick='FS.addExcludedIP()' />
<input type='image' class='img_btn'
	src='<?php echo fs_url("img/edit.png")?>' onclick='FS.editExcludedIP()' />
<input type='image' class='img_btn'
	src='<?php echo fs_url("img/delete.png")?>'
	onclick='FS.removeExcludedIP()' /></div>
<div id="exclude_ip_placeholder"><?php echo fs_get_excluded_ips_list()?></div>
</div>

<div class="box third">
<h3><?php fs_e('URL/Referrer')?></h3>
<div><input type='image' class='img_btn'
	src='<?php echo fs_url("img/add.png")?>' onclick='FS.addExcludedURL()' />
<input type='image' class='img_btn'
	src='<?php echo fs_url("img/edit.png")?>'
	onclick='FS.editExcludedURL()' /> <input type='image' class='img_btn'
	src='<?php echo fs_url("img/delete.png")?>'
	onclick='FS.removeExcludedURL()' /></div>
<div id="exclude_urls_placeholder"><?php echo fs_get_excluded_urls_list()?></div>
</div>

</div>
<?php }?>

<div id="configuration_area"
	class="configuration_area first">
<table class="config_table">
<?php if (fs_is_admin() || fs_is_demo()){?>
	<tr>
		<td class="config_cell" colspan="2">

		<h3><?php fs_e('Compact old data')?> <?php fs_create_wiki_help_link('ArchiveOldData')?>
		</h3>
		<?php if (fs_mysql_newer_than("4.1.13")) {?> <?php
		$method_dropbox= fs_get_archive_method_dropbox();
		$num_dropbox= fs_get_archive_dropbox();
			
		?>
		<div style="padding-left: 10px; padding-right: 10px"><?php 
		echo sprintf(fs_r('%s compact data older than %s'),$method_dropbox,$num_dropbox)?>
		&nbsp;&nbsp;&nbsp;
		<button class="button" id="fs_archive_button"
			onclick="toggleArchiveOldData()"><?php fs_e('Compact now')?></button>
		<div
			style="padding-top: 10px; padding-left: 10px; padding-right: 10px;">
		<span id="fs_archive_status"><?php echo sprintf(fs_r("%s days can be compacted, database size %s"),fs_get_num_old_days(), sprintf("%.1f MB",fs_get_database_size()/(1024*1024)))?></span>
		</div>
		<?php
}else
{
	echo "<b>".fs_r('MySQL 4.1.14 or newer is required for data compacting support')."</b>";
}?></div>
		</td>
	</tr>
	<tr>
		<td class="config_cell" colspan="2">
		<h3><?php fs_e('Automatic version check')?></h3>
		<?php
		$msg = fs_r('Automatically check if there is a new version of FireStats (recommended)');
		fs_create_checkbox('firestats_version_check_enabled',$msg,'true',true);
		?></td>
	</tr>
	<tr>
		<td class="config_cell" colspan="2">
		<h3><?php fs_e('Hits processing mode')?> <?php fs_create_wiki_help_link('HitsProcessingModes')?>
		</h3>
		<div><?php
		$disabled = FS_COMMIT_STRATEGY != FS_COMMIT_BY_OPTION;
		if (!$disabled)
		{
			fs_e('This section allow you to modify the way FireStats handles incoming hits. it is useful to fine tune FireStats for sites with very high load.');
			echo "<br/>";
			if (!fs_is_auto_commit_supported())
			{
				echo "<span class='notice'>".fs_r("MySQL 5.0 or newer is required for faster processing modes")."</span>";
			}
			echo fs_get_commit_mode_dropbox();
		}
		else
		{
			$desc = "UNKNOWN";
			switch(FS_COMMIT_STRATEGY)
			{
				case FS_COMMIT_IMMEDIATE:$desc = "FS_COMMIT_IMMEDIATE";break;
				case FS_COMMIT_MANUAL:$desc = "FS_COMMIT_MANUAL";break;
				case FS_COMMIT_AUTOMATIC:$desc = "FS_COMMIT_AUTOMATIC";break;
				case FS_COMMIT_BY_OPTION:$desc = "FS_COMMIT_BY_OPTION";break;
			}
			echo "<span class='notice'>".fs_r('Overriden by conf.php')." ($desc)</span>";
		}
		?></div>
		</td>
	</tr>
	<tr>
		<td class="config_cell" colspan="2">
		<h3><?php fs_e('IP-to-country database')?></h3>
		<ul>
			<li><?php echo sprintf(fs_r('IP-to-country database version : %s'),'<b id="ip2c_database_version">'.fs_get_current_ip2c_db_version().'</b>')?></li>
			<li><?php
			$msg = fs_r('Automatically check if there is a new version of IP-to-country database');
			fs_create_checkbox('ip-to-country-db_version_check_enabled',$msg,'true',true);
			?></li>
			<li><?php fs_e('Update IP-to-country database now (only if needed)')?>
			<button class="button"
				onclick="sendRequest('action=updateIP2CountryDB')"><?php fs_e('Update');?>
			</button>
			</li>
		</ul>
		</td>
	</tr>
	<?php }?>
</table>
<!-- config_table --></div>
<!-- configuration area -->
	<?php
	function fs_get_archive_method_dropbox()
	{
		$automatically = fs_r('Automatically');
		$manually = fs_r('Manually');
		$select = fs_r('Please select');
		$n = fs_get_system_option('archive_method');
		$res = "<select id='archive_method' onchange=\"saveSystemOption('archive_method','archive_method','string')\">";
		if ($n == null)
		{
			$res .= "<option value='ask' selected='selected'>$select</option>";
		}
		$res .= "<option value='auto'".('auto' == $n ? " selected='selected'" : "").">$automatically</option>";
		$res .= "<option value='manual'".('manual' == $n ? " selected='selected'" : "").">$manually</option>";
		$res .= "</select>";
		return $res;
	}

	function fs_get_archive_dropbox()
	{
		$selected = fs_get_archive_older_than();
		$arr = array();
		$arr[] = fs_mkPair(7, fs_r('One week'));
		$arr[] = fs_mkPair(14, fs_r('Two weeks'));
		$arr[] = fs_mkPair(30, fs_r('One month'));
		$arr[] = fs_mkPair(60, fs_r('Two months'));
		$arr[] = fs_mkPair(90, fs_r('Three months'));
		$arr[] = fs_mkPair(180, fs_r('Half a year'));
		$arr[] = fs_mkPair(365, fs_r('One year'));
		$arr[] = fs_mkPair(365*2, fs_r('Two years'));
		$onchange = "saveSystemOption('archive_older_than','archive_older_than','positive_integer','fs_archive_status')";
		return fs_create_dropbox($arr,$selected,'archive_older_than',$onchange);
	}

	function fs_get_commit_mode_dropbox($disabled = false)
	{
		$selected = fs_get_system_option('commit_strategy',FS_COMMIT_IMMEDIATE);
		$auto_supported = fs_is_auto_commit_supported();
		$arr = array();
		$arr[] = fs_mkPair(1, fs_r('Process immediately (Slow)'));
		if ($auto_supported)
		{
			$arr[] = fs_mkPair(3, fs_r('Buffer hits (Fast)'));
		}

		$onchange = "saveSystemOption('commit_strategy','commit_strategy','positive_integer')";
		return fs_create_dropbox($arr,$selected,'commit_strategy',$onchange, $disabled);
	}


	function fs_is_auto_commit_supported()
	{
		return fs_mysql_newer_or_eq_to("5.0");
	}
	?>
