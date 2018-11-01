<?php
header('Content-type: text/html; charset=utf-8');
require_once(dirname(__FILE__).'/init.php');
require_once(FS_ABS_PATH.'/php/utils.php');
require_once(FS_ABS_PATH.'/php/db-sql.php');
require_once(FS_ABS_PATH.'/php/html-utils.php');
fs_ensure_authenticated(); 
?>
<div id="fs_sites_div">

<!-- Use table for base layout -->
<table>
<tr style="vertical-align: top"><td>

<div id="fs_sites_table_holder" class="fwrap">
	<h2><?php fs_e('Manage sites')?></h2>
	<?php fs_e("Search") ?> : <input 	type="text" 
			onkeypress="return FS.sitesSearchKeyPressed(event)" 
				id="sites_table_name_filter_text" 
				size="10" value="<?php echo fs_get_option("sites_table_name_filter", "");?>"/>
				
	<input type='image' title="<?php fs_e('Clear')?>" class='img_btn' src="<?php echo fs_url("img/clear.png")?>"
		id='clear_sites_table_name_filter_text' onclick="$('sites_table_name_filter_text').value = '';FS.updateSiteNameFilter();"
	/>				
				 
	<div id="fs_sites_table">
		<?php echo fs_get_sites_manage_table()?>
	</div>
</div>

</td><td>

<div id="fs_sites_tab_help" class="fwrap">
	<h2><?php fs_e('Help');fs_create_wiki_help_link('MultipleSites', 800,800)?></h2>
	<b><?php fs_e('Warning, you can really mess things up from here, be careful!')?></b><br/>
	<br/>
	<?php fs_e('FireStats can collect statistics from multiple sites (on the same server).')?><br/>
	<ul>
		<li><?php fs_e('The site need to be registered in the sites table')?></li>
		<li><?php fs_e('The site should be configured to use its ID when reporting a hit, click on the help button next to the site you created for more information')?></li>
		<li>
			<?php 
				echo sprintf(fs_r("Click %s for more information"),
				sprintf("<a target='_blank' href='%s'>%s</a>",FS_WIKI."MultipleSites",fs_r('here')))?>
		</li>
	</ul>
</div>

</td></tr></table>

</div>