<?php if (!defined('FS_VERSION')) die("direct call is not premitted")?>
<script type="text/javascript">
//<![CDATA[
FS.updateSitesFilter = function()
{
	sendRequest("action=updateSitesFilter&sites_filter="+$F("sites_filter"));
}

FS.applyFilters = function()
{
	saveOptions('firestats_ht_ip_filter,firestats_ht_url_filter,firestats_ht_referrer_filter,firestats_ht_useragent_filter','records_table','regexp');
}

FS.saveAutoRefreshInterval = function()
{
    saveOption('auto_refresh_interval','firestats_auto_refresh_interval','positive_integer');
}


FS.saveNumEntries = function()
{
    saveOption('firestats_num_to_show','firestats_num_entries_to_show','positive_integer','records_table');
}

FS.autoRefreshTimerID;
FS.timeLeftToRefresh;

FS.updateRefreshTimer = function()
{
    if (timeLeftToRefresh <= 0)
    {
        FS.updateAllStats();
        FS.toggleAutoRefresh();
    }
    else
    {
        var min = parseInt(timeLeftToRefresh / 60);
        var sec = timeLeftToRefresh - min * 60;
        if (sec < 10) sec = '0' + sec;
        FS.autoRefreshTimerID = setTimeout("FS.updateRefreshTimer()", 1000);
        var b = $('refresh_button');
        if (b)
        {
            b.innerHTML = "<?php fs_e('Refresh statistics')?>" + " ("+min+":"+sec+")";
        }
    }
    timeLeftToRefresh--;
} 


FS.toggleAutoRefresh = function()
{
    if (!$('auto_refresh_checkbox'))
    {
        return;
    }

    var on = $F('auto_refresh_checkbox');
    if (on == 'on')
    {
        if (FS.autoRefreshTimerID) clearTimeout(FS.autoRefreshTimerID);
        FS.autoRefreshTimerID = setTimeout("FS.updateRefreshTimer()", 0);
        timeLeftToRefresh = $F('auto_refresh_interval');
        var n = parseInt(timeLeftToRefresh);
        if(!n || n <= 0)
        {
            showError("<?php print fs_r("Not a positive number : ") ?>" + timeLeftToRefresh);
            $('auto_refresh_checkbox').checked = false;
        }
        else
        {
            timeLeftToRefresh = n * 60;
        }
    }
    else
    {
        clearTimeout(FS.autoRefreshTimerID);
        $('refresh_button').innerHTML = "<?php fs_e('Refresh statistics')?>";
    }

}

FS.fs_search_terms_date_type_changed = function(save)
{
	if (save) saveOptions('search_terms_date_type');
	var is_range = $F('search_terms_date_type') == 'time_range';
	$('time_range').style.display = is_range ? 'inline' : 'none';	
}

FS.fs_get_search_terms_engines_breakdown = function(search_term_id)
{
	var search_term = search_term_id.substring("search_term_".length);
	var pid = "parent_search_term_"+ search_term;
	var parent = $(pid);
	var clazzName = parent.className;
	if (clazzName == 'liOpen')
	{
		sendRequest("action=searchTermsBreakdown&id="+search_term_id+"&search_term="+search_term);
	}
}

FS.updateLastDayStatsMode = function()
{
	var mode = $F('last_day_stats_mode');
	var update = "last_day_page_views_label,last_day_visits_label,stats_total_count_last_day,stats_total_unique_last_day";
	saveOption('last_day_stats_mode','last_day_stats_mode','string',update)
}
FS.hitsTableDateSelected = function(calendar, date)
{
	if (calendar.dateClicked) 
	{
		var input_field = $("hits_table_date_selector");
		input_field.value = date;
		calendar.callCloseHandler(); // this calls "onClose" (see above)
		sendRequest('action=changeHitsTablePage&type=date&date=' + date);
	}
}
//]]>
</script>

<div
	id="stats_area" class="stats_area">
<div class="fwrap">
<button class="button" id="refresh_button"
	onclick="FS.updateAllStats();FS.toggleAutoRefresh()"><?php fs_e('Refresh statistics');?></button>
<?php fs_cfg_button('refresh_button_config')?> 
<span id="sites_filter_span"> <?php echo fs_get_sites_list();?> </span>
<br />
<span id="refresh_button_config" class="normal_font hidden"> <?php
$auto_refresh_enabled = fs_get_option('firestats_auto_refresh_enabled','true');
$auto_refresh_interval  = fs_get_option('firestats_auto_refresh_interval','5');
$auto_refresh_checked = $auto_refresh_enabled == 'true' ? "checked=\"checked\"" : "";
?> <input type="checkbox"
	onclick="saveOption('auto_refresh_checkbox','firestats_auto_refresh_enabled','boolean');FS.toggleAutoRefresh()"
	id="auto_refresh_checkbox" <?php echo $auto_refresh_checked?> /> <label
	for='auto_refresh_checkbox'><?php fs_e('Auto refresh every')?></label>
<input type="text"
	onkeypress="return FS.trapEnter(event, 'FS.saveAutoRefreshInterval();');"
	id="auto_refresh_interval" size="1"
	value="<?php echo $auto_refresh_interval?>" /> <?php fs_e('minutes')?>
<button class="button" onclick="FS.saveAutoRefreshInterval()"><?php fs_e('Apply');?></button>
</span></div>
<!-- fwrap -->

<div class="fwrap">


<h2><?php fs_e('Status');fs_create_anchor('Status')?> <?php fs_cfg_button('status_config_panel')?>
<span id="status_config_panel" class="normal_font hidden">
<?php
$items = array();
$items[] = fs_mkPair("24h", fs_r('Last 24 hours'));
$items[] = fs_mkPair("midnight", fs_r('Since midnight'));
echo fs_create_dropbox($items, fs_get_option("last_day_stats_mode","24h"), "last_day_stats_mode", 'FS.updateLastDayStatsMode()');
?> 
</span></h2>
<table id="status_table">
	<tr>
		<td width="20%"><?php fs_e('Page views')?>
		<p id="stats_total_count">--</p>
		</td>
		<td width="20%"><?php fs_e('Visits')?>
		<p id="stats_total_unique">--</p>
		</td>
		<td width="20%"><span id='last_day_page_views_label'><?php echo fs_get_last_day_page_views_label()?></span>
		<p id="stats_total_count_last_day">--</p>
		</td>
		<td width="20%"><span id='last_day_visits_label'><?php echo fs_get_last_day_visits_label()?></span>
		<p id="stats_total_unique_last_day">--</p>
		</td>
		<td width="20%"><span><?php fs_e("RSS Subscribers")?></span>
		<p id="rss_subscribers">
		<?php
		if (fs_plugin_installed("RSS Subscribers")) echo "--";
		else echo fs_create_wiki_help_link("FireStatsPRO");
		 ?>
		</p>
		</td>
	</tr>
</table>
</div>
<!-- warp -->

<div class="fwrap">
<h2><?php fs_e('Recent referrers');fs_create_anchor('RecentReferrers')?>
<?php fs_cfg_button('recent_referers_id')?> <span
	id="recent_referers_id" class="normal_font hidden"> <?php
	$max = fs_get_num_textfield('firestats_num_max_recent_referers','fs_recent_referers',fs_get_max_referers_num(),4);
	$days = fs_get_num_textfield('firestats_recent_referers_days_ago','fs_recent_referers',fs_get_recent_referers_days_ago(),4);
	$order = fs_get_ref_order_by_dropbox('recent_referrers_order_by',"saveOptions('recent_referrers_order_by','fs_recent_referers')");
	$show_at_most_X_for_the_last_Y_days = sprintf(fs_r("Show at most %s items for the last %s days, sorted by %s"),$max,$days,$order);
	?> <span> <?php echo $show_at_most_X_for_the_last_Y_days?>
<button class="button"
	onclick="saveOptions('firestats_num_max_recent_referers,firestats_recent_referers_days_ago,recent_referrers_order_by','fs_recent_referers')"><?php fs_e('Apply');?></button>
</span> </span></h2>
<div id="fs_recent_referers">
<div>--</div>
</div>
</div>
<!-- warp -->

<div class="fwrap">
<h2><?php fs_e('Search terms');fs_create_anchor('SearchTerms')?> <?php fs_cfg_button('search_terms_id')?>
<span id="search_terms_id" class="normal_font hidden"> <?php
$max = fs_get_num_textfield('num_max_search_terms','fs_search_terms',fs_get_max_search_terms(),4);
$dropbox = fs_get_time_range_dropbox('search_terms_date_type','FS.fs_search_terms_date_type_changed(false)');
$show_at_most_X_for_Y = sprintf(fs_r("Show at most %s search terms for %s"), $max, $dropbox);
?> <br />
<?php echo $show_at_most_X_for_Y?>
<button class="button"
	onclick="saveOptions('num_max_search_terms,search_terms_date_type,search_terms_start,search_terms_end','fs_search_terms')">
<?php fs_e('Apply');?></button>
<span id="time_range"> <br />
	<?php fs_e('Start date:');fs_create_date_selector_text("search_terms_start")?>
	<?php fs_e('End date:');fs_create_date_selector_text("search_terms_end")?> </span>
	<script type="text/javascript">
				FS.fs_search_terms_date_type_changed(false);
	</script>
</span>
</h2>
<div id="fs_search_terms" class="tree_container">
<div id="search_terms_tree_id">--</div>
</div>
</div>
<!-- warp -->


<div class="fwrap">
<h2><?php fs_e('Recent popular pages');fs_create_anchor('PopularPages')?>
<?php fs_cfg_button('recent_popular_config')?> <span
	id="recent_popular_config" class="normal_font hidden"> <?php
	$max = fs_get_num_textfield('firestats_num_max_recent_popular','popular_pages',fs_get_max_popular_num(),4);
	$days = fs_get_num_textfield('firestats_recent_popular_pages_days_ago','popular_pages',fs_get_recent_popular_pages_days_ago(),4);
	$show_at_most_X_for_the_last_Y_days = sprintf(fs_r("Show at most %s items for the last %s days"),$max,$days);
	?> <span> <?php echo $show_at_most_X_for_the_last_Y_days?>
<button class="button"
	onclick="saveOptions('firestats_num_max_recent_popular,firestats_recent_popular_pages_days_ago','popular_pages')"><?php fs_e('Apply');?></button>
</span> </span></h2>
<div id="popular_pages" class="tree_container">--</div>
</div>
<!-- warp -->

<div class="fwrap">
<h2><?php fs_e('Browsers');fs_create_anchor('Browsers')?> <span> <?php fs_cfg_button('browsers_config')?>
<span id="browsers_config" class="normal_font hidden"> <?php
$days = fs_get_num_textfield('firestats_browsers_tree_days_ago','fs_browsers_tree',fs_browsers_tree_days_ago(),4);
$show_for_the_last_Y_days = sprintf(fs_r("Show items for the last %s days"),$days);
?> <span> <?php echo $show_for_the_last_Y_days?>
<button class="button"
	onclick="saveOptions('firestats_browsers_tree_days_ago','fs_browsers_tree')"><?php fs_e('Apply');?></button>
</span> </span> </span></h2>
<div id="fs_browsers_tree" class="tree_container">
<div id="browsers_tree_id">--</div>
</div>
</div>
<!-- warp -->

<div class="fwrap">
<h2><?php fs_e('Operating systems');fs_create_anchor('OperatingSystems')?>
<span> <?php fs_cfg_button('os_config')?> <span id="os_config"
	class="normal_font hidden"> <?php
	$days = fs_get_num_textfield('firestats_os_tree_days_ago','fs_os_tree',fs_os_tree_days_ago(),4);
	$show_for_the_last_Y_days = sprintf(fs_r("Show items for the last %s days"),$days);
	?> <span> <?php echo $show_for_the_last_Y_days?>
<button class="button"
	onclick="saveOptions('firestats_os_tree_days_ago','fs_os_tree')"><?php fs_e('Apply');?></button>
</span> </span> </span></h2>
<div id="fs_os_tree" class="tree_container">
<div id="os_tree_id">--</div>
</div>
</div>
<!-- warp -->

<div class="fwrap">
<h2><?php fs_e('Countries');fs_create_anchor('Countries')?> <?php fs_cfg_button('countries_config')?>
<span id="countries_config" class="normal_font hidden"> <?php
$max = fs_get_num_textfield('firestats_max_countries_in_list','countries_list',fs_get_max_countries_num(),4);
$days = fs_get_num_textfield('firestats_countries_list_days_ago','countries_list',fs_countries_list_days_ago(),4);
$show_at_most_X_for_the_last_Y_days = sprintf(fs_r("Show at most %s items for the last %s days"),$max,$days);
?> <span> <?php echo $show_at_most_X_for_the_last_Y_days?>
<button class="button"
	onclick="saveOptions('firestats_countries_list_days_ago,firestats_max_countries_in_list','countries_list')"><?php fs_e('Apply');?></button>
</span> </span></h2>
<div id="countries_list">--</div>
</div>
<!-- warp -->

<div class="fwrap">
<h2><?php fs_e('Hits table');fs_create_anchor('HitsTable')?> <?php fs_cfg_button('records_table_config')?>
<span id="records_table_config" class="normal_font hidden"> <span> <?php fs_e('Number of hits to show')?>
<input type="text"
	onkeypress="return FS.trapEnter(event, 'FS.saveNumEntries()');" size="4"
	id="firestats_num_to_show"
	value="<?php echo fs_get_num_hits_in_table()?>" />
<button class="button" onclick="FS.saveNumEntries()"><?php fs_e('Save');?></button>
</span> </span></h2>
<div style="border: 1px solid #f0f0fa">

<table>
	<tr>
		<td>
			<?php 
				global $FS_LANG_DIR;
				$next_url = $FS_LANG_DIR == "ltr" ? fs_url("img/prev.png") : fs_url("img/next.png");
				$prev_url = $FS_LANG_DIR == "rtl" ? fs_url("img/prev.png") : fs_url("img/next.png");
			?>
			<input id='prev_hits_table_page' type='image' class='img_btn' title='<?php fs_e('Previous page') ?>' src='<?php echo $next_url?>' onclick='sendRequest("action=changeHitsTablePage&type=prev")'/>
			<?php echo fs_get_date_selector_button("hits_table_date_selector","FS.hitsTableDateSelected") ?>
			<input id='next_hits_table_page' type='image' class='img_btn' title='<?php fs_e('Next page') ?>' src='<?php echo $prev_url?>' onclick='sendRequest("action=changeHitsTablePage&type=next")'/>
		</td>
		<td id='hits_table_page_number_indicator'>
		<?php echo fs_get_hits_table_page_number_indicator() ?>
		</td>
	</tr>
</table>

<table>
	<tr>
		<th></th>
		<th><?php fs_e('IP')?></th>
		<th><?php fs_e('URL')?></th>
		<th><?php fs_e('Referrer')?></th>
		<th><?php fs_e('User agent')?></th>
		<th></th>
	</tr>
	<tr>
		<td><span class="bold"><?php fs_e('Filter')?>:</span></td>
		<td><input type="text" id="firestats_ht_ip_filter"
			style="width: 120px"
			onkeypress="return FS.trapEnter(event, 'FS.applyFilters()');"
			value="<?php echo fs_get_option('firestats_ht_ip_filter')?>" /></td>
		<td><input type="text" id="firestats_ht_url_filter"
			style="width: 190px"
			onkeypress="return FS.trapEnter(event, 'FS.applyFilters()');"
			value="<?php echo fs_get_option('firestats_ht_url_filter')?>" /></td>
		<td><input type="text" id="firestats_ht_referrer_filter"
			style="width: 190px"
			onkeypress="return FS.trapEnter(event, 'FS.applyFilters()');"
			value="<?php echo fs_get_option('firestats_ht_referrer_filter')?>" />
		</td>
		<td><input type="text" id="firestats_ht_useragent_filter"
			style="width: 190px"
			onkeypress="return FS.trapEnter(event, 'FS.applyFilters()');"
			value="<?php echo fs_get_option('firestats_ht_useragent_filter')?>" />
		</td>
		<td>
		<button class="button"
			onclick="clearOptions('firestats_ht_ip_filter,firestats_ht_url_filter,firestats_ht_referrer_filter,firestats_ht_useragent_filter',true,'records_table')">
			<?php fs_e('Clear');?></button>
		<button class="button" onclick="FS.applyFilters()"><?php fs_e('Apply');?>
		</button>
		</td>
	</tr>
</table>


</div>
<div id="records_table">--</div>


<table>
	<tr>
		<td>
			<input type='image' class='img_btn' title='<?php fs_e('Previous page') ?>' src='<?php echo $next_url?>' onclick='sendRequest("action=changeHitsTablePage&type=prev")'/>
			<input type='image' class='img_btn' title='<?php fs_e('Next page') ?>' src='<?php echo $prev_url?>' onclick='sendRequest("action=changeHitsTablePage&type=next")'/>
		</td>
	</tr>
</table>


</div>
<!-- warp --></div>
<!-- stats_area -->
			<?php

			function fs_get_ref_order_by_dropbox($key,$onchange)
			{
				$arr = array();
				$arr[] = fs_mkPair(ORDER_BY_FIRST_SEEN, fs_r('Newest first'));
				$arr[] = fs_mkPair(ORDER_BY_HIGH_COUNT_FIRST, fs_r('Most hits first'));
				$selected = fs_get_option($key, ORDER_BY_FIRST_SEEN);
				return fs_create_dropbox($arr, $selected, $key, $onchange);
			}


			function fs_get_time_range_dropbox($key,$onchange)
			{
				$arr = array();
				$arr[] = fs_mkPair(1, fs_r('the last 24 hours'));
				$arr[] = fs_mkPair(7, fs_r('the last week'));
				$arr[] = fs_mkPair(30, fs_r('the last month'));
				$arr[] = fs_mkPair(90, fs_r('the three months'));
				$arr[] = fs_mkPair(180, fs_r('the last six months'));
				$arr[] = fs_mkPair(365, fs_r('the last year'));
				$arr[] = fs_mkPair('ever', fs_r('all time'));
				$arr[] = fs_mkPair('time_range', fs_r('time range'));
				$selected = fs_get_option($key, 90);
				return fs_create_dropbox($arr, $selected, $key, $onchange);
			}

			?>
