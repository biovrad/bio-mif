<?php /*
Plugin Name: Video Embedder
Plugin URI: http://www.gate303.net/2007/12/17/video-embedder/
Description: Этот плагин позволяет правильно и легко встроить в ваши записи видео-ролики со всех популярных видео-сайтов.  Для настройки и справки по плагину зайдите в <a href="options-general.php?page=video-embedder.php">Параметры\Video Embedder</a>. Обновления русской версия плагина вы можете найти на <a href="http://www.wordpressplugins.ru/media/video-embedder.html">WordpressPlugins.ru</a>
Version: 1.6
Author: Kristoffer Forsgren
Author URI: http://www.gate303.net/

Copyright 2007  Kristoffer Forsgren */

defaultSettings();
add_filter('the_content', 'videoembedder_embed');
add_action('admin_menu', 'videoembedder_add_pages');

function videoembedder_add_pages() {
    add_options_page('Video Embedder Options', 'Video Embedder', 8, basename(__FILE__), 'videoembedder_options_page');	
}

function videoembedder_options_page() {
	if ($_POST){
		$options = array (
			"video_width"		=> $_POST["video_width"],
			"video_height"		=> $_POST["video_height"],
			"rutube_tag"		=> $_POST["rutube_tag"],
			"smotri_tag"		=> $_POST["smotri_tag"],
			"youtube_tag"		=> $_POST["youtube_tag"],
			"google_tag"		=> $_POST["google_tag"],
			"metacafe_tag"		=> $_POST["metacafe_tag"],
			"liveleak_tag"		=> $_POST["liveleak_tag"],
			"revver_tag"		=> $_POST["revver_tag"],
			"ifilm_tag"		=> $_POST["ifilm_tag"],
			"myspace_tag"		=> $_POST["myspace_tag"],
			"bliptv_tag"		=> $_POST["bliptv_tag"],
			"college_tag"		=> $_POST["college_tag"],
			"videojug_tag"		=> $_POST["videojug_tag"],
			"godtube_tag"		=> $_POST["godtube_tag"],
			"veoh_tag"		=> $_POST["veoh_tag"],
			"break_tag"		=> $_POST["break_tag"],
			"dailymotion_tag"	=> $_POST["dailymotion_tag"],
			"movieweb_tag"	=> $_POST["movieweb_tag"],
			"jaycut_tag"	=> $_POST["jaycut_tag"],
			"myvideo_tag"	=> $_POST["myvideo_tag"],
			"vimeo_tag"	=> $_POST["vimeo_tag"],
			"gtrailers_tag"	=> $_POST["gtrailers_tag"],
			"viddler_tag"	=> $_POST["viddler_tag"],
			"snotr_tag"	=> $_POST["snotr_tag"],

			"quicktime_tag"	=> $_POST["quicktime_tag"],
			"windowsmedia_tag"	=> $_POST["windowsmedia_tag"],
		);

		$updated=false;
		update_option('videoembedder_options', $options);
		defaultSettings();
	}

	$videoembedder_options = get_option(videoembedder_options);

	$video_height = $videoembedder_options["video_height"];
	$video_width = $videoembedder_options["video_width"];
	$rutube_tag = $videoembedder_options["rutube_tag"];
	$smotri_tag = $videoembedder_options["smotri_tag"];
	$youtube_tag = $videoembedder_options["youtube_tag"];
	$google_tag = $videoembedder_options["google_tag"];
	$metacafe_tag = $videoembedder_options["metacafe_tag"];
	$liveleak_tag = $videoembedder_options["liveleak_tag"];
	$revver_tag = $videoembedder_options["revver_tag"];
	$ifilm_tag = $videoembedder_options["ifilm_tag"];
	$myspace_tag = $videoembedder_options["myspace_tag"];
	$bliptv_tag = $videoembedder_options["bliptv_tag"];
	$college_tag = $videoembedder_options["college_tag"];
	$videojug_tag = $videoembedder_options["videojug_tag"];
	$godtube_tag = $videoembedder_options["godtube_tag"];
	$veoh_tag = $videoembedder_options["veoh_tag"];
	$break_tag = $videoembedder_options["break_tag"];
	$dailymotion_tag = $videoembedder_options["dailymotion_tag"];
	$movieweb_tag = $videoembedder_options["movieweb_tag"];
	$jaycut_tag = $videoembedder_options["jaycut_tag"];
	$myvideo_tag = $videoembedder_options["myvideo_tag"];
	$vimeo_tag = $videoembedder_options["vimeo_tag"];
	$gtrailers_tag = $videoembedder_options["gtrailers_tag"];
	$viddler_tag = $videoembedder_options["viddler_tag"];
	$snotr_tag = $videoembedder_options["snotr_tag"];

	$quicktime_tag = $videoembedder_options["quicktime_tag"];
	$windowsmedia_tag = $videoembedder_options["windowsmedia_tag"];

	echo '<div class="wrap"><h2>Настройки Video Embedder</h2>';
	echo "<form name='form' method='post' action=''>
	<table width='100%' border='0' cellspacing='0' cellpadding='0'>
		<tr>
			<th width='20%'><strong>Ширина видео:</strong></th>
			<td width='80%'><input name='video_width' type='text' id='video_width' value='$video_width'> (По умолчанию: 425)</td>
		</tr>
		<tr>
			<th><strong>Высота видео:</strong></th>
			<td><input name='video_height' type='text' id='video_height' value='$video_height'> (По умолчанию: 355)</td>
		</tr>
		<tr>
			<th><strong>RuTube тег:</strong></th>
			<td><input name='rutube_tag' type='text' id='rutube_tag' value='$rutube_tag'> Использование: [$rutube_tag]video_id[/$rutube_tag]</td>
		</tr>
				<tr>
			<th><strong>Smotri.com тег:</strong></th>
			<td><input name='smotri_tag' type='text' id='smotri_tag' value='$smotri_tag'> Использование: [$smotri_tag]video_id[/$smotri_tag]</td>
		</tr>
		<tr>
			<th><strong>Youtube тег:</strong></th>
			<td><input name='youtube_tag' type='text' id='youtube_tag' value='$youtube_tag'> Использование: [$youtube_tag]video_id[/$youtube_tag]</td>
		</tr>
		<tr>
			<th><strong>Google Video тег:</strong></th>
			<td><input name='google_tag' type='text' id='google_tag' value='$google_tag'> Использование: [$google_tag]video_id[/$google_tag]</td>
		</tr>
		<tr>
			<th><strong>Metacafe тег:</strong></th>
			<td><input name='metacafe_tag' type='text' id='metacafe_tag' value='$metacafe_tag'> Использование: [$metacafe_tag]video_id[/$metacafe_tag]</td>
		</tr>
		<tr>
			<th><strong>Liveleak тег:</strong></th>
			<td><input name='liveleak_tag' type='text' id='liveleak_tag' value='$liveleak_tag'> Использование: [$liveleak_tag]video_id[/$liveleak_tag]</td>
		</tr>
		<tr>
			<th><strong>Revver тег:</strong></th>
			<td><input name='revver_tag' type='text' id='revver_tag' value='$revver_tag'> Использование: [$revver_tag]video_id[/$revver_tag]</td>
		</tr>
		<tr>
			<th><strong>IFILM тег:</strong></th>
			<td><input name='ifilm_tag' type='text' id='ifilm_tag' value='$ifilm_tag'> Использование: [$ifilm_tag]video_id[/$ifilm_tag]</td>
		</tr>
		<tr>
			<th><strong>Myspace тег:</strong></th>
			<td><input name='myspace_tag' type='text' id='myspace_tag' value='$myspace_tag'> Использование: [$myspace_tag]video_id[/$myspace_tag]</td>
		</tr>
		<tr>
			<th><strong>Blip.tv тег:</strong></th>
			<td><input name='bliptv_tag' type='text' id='bliptv_tag' value='$bliptv_tag'> Использование: [$bliptv_tag]video_id[/$bliptv_tag]</td>
		</tr>
		<tr>
			<th><strong>CollegeHumor тег:</strong></th>
			<td><input name='college_tag' type='text' id='college_tag' value='$college_tag'> Использование: [$college_tag]video_id[/$college_tag]</td>
		</tr>
		<tr>
			<th><strong>Videojug тег:</strong></th>
			<td><input name='videojug_tag' type='text' id='videojug_tag' value='$videojug_tag'> Использование: [$videojug_tag]video_id[/$videojug_tag]</td>
		</tr>
		<tr>
			<th><strong>Godtube тег:</strong></th>
			<td><input name='godtube_tag' type='text' id='godtube_tag' value='$godtube_tag'> Использование: [$godtube_tag]video_id[/$godtube_tag]</td>
		</tr>
		<tr>
			<th><strong>Veoh тег:</strong></th>
			<td><input name='veoh_tag' type='text' id='veoh_tag' value='$veoh_tag'> Использование: [$veoh_tag]video_id[/$veoh_tag]</td>
		</tr>
		<tr>
			<th><strong>Break тег:</strong></th>
			<td><input name='break_tag' type='text' id='break_tag' value='$break_tag'> Использование: [$break_tag]video_id[/$break_tag]</td>
		</tr>
		<tr>
			<th><strong>Dailymotion тег:</strong></th>
			<td><input name='dailymotion_tag' type='text' id='dailymotion_tag' value='$dailymotion_tag'> Использование: [$dailymotion_tag]video_id[/$dailymotion_tag]</td>
		</tr>
		<tr>
			<th><strong>Movieweb тег:</strong></th>
			<td><input name='movieweb_tag' type='text' id='movieweb_tag' value='$movieweb_tag'> Использование: [$movieweb_tag]video_id[/$movieweb_tag]</td>
		</tr>
		<tr>
			<th><strong>Jaycut тег:</strong></th>
			<td><input name='jaycut_tag' type='text' id='jaycut_tag' value='$jaycut_tag'> Использование: [$jaycut_tag]video_id[/$jaycut_tag]</td>
		</tr>
		<tr>
			<th><strong>Myvideo тег:</strong></th>
			<td><input name='myvideo_tag' type='text' id='myvideo_tag' value='$myvideo_tag'> Использование: [$myvideo_tag]video_id[/$myvideo_tag]</td>
		</tr>
		<tr>
			<th><strong>Vimeo тег:</strong></th>
			<td><input name='vimeo_tag' type='text' id='vimeo_tag' value='$vimeo_tag'> Использование: [$vimeo_tag]video_id[/$vimeo_tag]</td>
		</tr>
		<tr>
			<th><strong>Gametrailers тег:</strong></th>
			<td><input name='gtrailers_tag' type='text' id='gtrailers_tag' value='$gtrailers_tag'> Использование: [$gtrailers_tag]video_id[/$gtrailers_tag]</td>
		</tr>
		<tr>
			<th><strong>Viddler тег:</strong></th>
			<td><input name='viddler_tag' type='text' id='viddler_tag' value='$viddler_tag'> Использование: [$viddler_tag]video_id[/$viddler_tag]</td>
		</tr>
		<tr>
			<th><strong>Snotr тег:</strong></th>
			<td><input name='snotr_tag' type='text' id='snotr_tag' value='$snotr_tag'> Использование: [$snotr_tag]video_id[/$snotr_tag]</td>
		</tr>
		<!-- local media -->
		<tr>
			<th><strong>Quicktime тег:</strong></th>
			<td><input name='quicktime_tag' type='text' id='quicktime_tag' value='$quicktime_tag'> Использование: [$quicktime_tag]URL[/$quicktime_tag]</td>
		</tr>
		<tr>
			<th><strong>Windows Media Player тег:</strong></th>
			<td><input name='windowsmedia_tag' type='text' id='windowsmedia_tag' value='$windowsmedia_tag'> Использование: [$windowsmedia_tag]URL[/$windowsmedia_tag]</td>
		</tr>
	</table>
	<input type='submit' name='Submit' value='Сохранить'>";
	if ($updated==true) echo ' Настройки обновлены';
	echo '</form></div>';

	echo '<div class="wrap"><h2>Справка</h2>';
	
	echo '<h3>RuTube помощь</h3>';
	echo '<p>Для вставки видео-ролика RuTube используйте выделенную красным часть ссылки: http://video.rutube.ru/<strong style="color:red;">5218b8aea4968b29ea8c69213b690131</strong>. Брать эту ссылку в меню <strong>Коды ролики\код плейера</strong> на странице ролика (так как иногда в ссылке нет параметра ?v=, а именно он нужен для вставки видео-ролика).</p>';
	echo '<p>Наберите ['.$rutube_tag.']<strong style="color:red;">5218b8aea4968b29ea8c69213b690131</strong>[/'.$rutube_tag.'] в редакторе, чтобы встроить видео.</p>';
	
	echo '<h3>Smotri.com помощь</h3>';
	echo '<p>Для вставки видео-ролика Smotri.com используйте выделенную красным часть ссылки: http://smotri.com/video/view/?id=v<strong style="color:red;">473798e854</strong>. </p>';
	echo '<p>Наберите ['.$smotri_tag.']<strong style="color:red;">473798e854</strong>[/'.$smotri_tag.'] в редакторе, чтобы встроить видео.</p>';
	
	echo '<h3>Youtube помощь</h3>';
	echo '<p>Для вставки видео-ролика Youtube используйте выделенную красным часть ссылки: http://www.youtube.com/watch?v=<strong style="color:red;">zORv8wwiadQ</strong></p>';
	echo '<p>Наберите ['.$youtube_tag.']<strong style="color:red;">zORv8wwiadQ</strong>[/'.$youtube_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Google Video помощь</h3>';
	echo '<p>Для вставки видео-ролика Google Video используйте выделенную красным часть ссылки: http://video.google.com/videoplay?docid=<strong style="color:red;">6063985264803214006</strong></p>';
	echo '<p>Наберите ['.$google_tag.']<strong style="color:red;">6063985264803214006</strong>[/'.$google_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Metacafe помощь</h3>';
	echo '<p>Для вставки видео-ролика Metacafeиспользуйте выделенную красным часть ссылки: http://www.metacafe.com/watch/<strong style="color:red;">975366/secrets_of_google_earth</strong>/ (the trailing slash should not be included)</p>';
	echo '<p>Наберите ['.$metacafe_tag.']<strong style="color:red;">975366/secrets_of_google_earth</strong>[/'.$metacafe_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Liveleak помощь</h3>';
	echo '<p>Для вставки видео-ролика Liveleak используйте выделенную красным часть ссылки: http://www.liveleak.com/view?i=<strong style="color:red;">d62_1594640234</strong></p>';
	echo '<p>Наберите ['.$liveleak_tag.']<strong style="color:red;">d62_1594640234</strong>[/'.$liveleak_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Revver помощь</h3>';
	echo '<p>Для вставки видео-ролика Revver используйте выделенную красным часть ссылки: http://revver.com/video/<strong style="color:red;">527514</strong>/ (the trailing slash should not be included)</p>';
	echo '<p>Наберите ['.$revver_tag.']<strong style="color:red;">527514</strong>[/'.$revver_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>IFILM помощь</h3>';
	echo '<p>Для вставки видео-ролика IFILM используйте выделенную красным часть ссылки: http://www.ifilm.com/video/<strong style="color:red;">3521648</strong></p>';
	echo '<p>Наберите ['.$ifilm_tag.']<strong style="color:red;">3521648</strong>[/'.$ifilm_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Myspace помощь</h3>';
	echo '<p>Для вставки видео-ролика Myspace используйте выделенную красным часть ссылки: http://vids.myspace.com/index.cfm?fuseaction=vids.individual&videoid=<strong style="color:red;">56884863</strong></p>';
	echo '<p>Наберите ['.$myspace_tag.']<strong style="color:red;">56884863</strong>[/'.$myspace_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Blip.tv помощь</h3>';
	echo '<p>Для вставки видео-ролика Blip.tv используйте выделенную красным часть ссылки: http://blip.tv/file/<strong style="color:red;">254683</strong></p>';
	echo '<p>Наберите ['.$bliptv_tag.']<strong style="color:red;">254683</strong>[/'.$bliptv_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>CollegeHumor помощь</h3>';
	echo '<p>Для вставки видео-ролика CollegeHumor используйте выделенную красным часть ссылки: http://www.collegehumor.com/video:<strong style="color:red;">3567863</strong></p>';
	echo '<p>Наберите ['.$college_tag.']<strong style="color:red;">3567863</strong>[/'.$college_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Videojug помощь</h3>';
	echo '<p>Для вставки видео-ролика Videojug используйте выделенную красным часть ссылки:: &lt;object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0" width="400" height="345" align="middle"&gt;&lt;param name="movie" value="http://www.videojug.com/film/player?id=<strong style="color:red;">3ff6e533-5eaf-4ff8-540e-02334f7ac808</strong>" /&gt;&lt;embed src (...)</p>';
	echo '<p>Наберите ['.$videojug_tag.']<strong style="color:red;">3ff6e533-5eaf-4ff8-540e-02334f7ac808</strong>[/'.$videojug_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Godtube помощь</h3>';
	echo '<p>Для вставки видео-ролика Godtube используйте выделенную красным часть ссылки: http://www.godtube.com/view_video.php?viewkey=<strong style="color:red;">83abb6308b8842ca6f1f</strong></p>';
	echo '<p>Наберите ['.$godtube_tag.']<strong style="color:red;">83abb6308b8842ca6f1f</strong>[/'.$godtube_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Veoh помощь</h3>';
	echo '<p>Для вставки видео-ролика Veoh используйте выделенную красным часть ссылки: http://www.veoh.com/videos/<strong style="color:red;">v1683095mYFrF3Xc</strong></p>';
	echo '<p>Наберите ['.$veoh_tag.']<strong style="color:red;">v1683095mYFrF3Xc</strong>[/'.$veoh_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Break помощь</h3>';
	echo '<p>Для вставки видео-ролика Break используйте выделенную красным часть ссылки: &lt;object width="464" height="392"&gt;&lt;param name="movie" value="http://embed.break.com/<strong style="color:red;">NDExNjU2</strong>"&gt;&lt;/para...</p>';
	echo '<p>Наберите ['.$break_tag.']<strong style="color:red;">NDExNjU2</strong>[/'.$break_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Dailymotion помощь</h3>';
	echo '<p>Для вставки видео-ролика Dailymotion используйте выделенную красным часть ссылки: http://www.dailymotion.com/video/<strong style="color:red;">xoh8j</strong>_monty-python-dead-parrot-sketch_family</p>';
	echo '<p>Наберите ['.$dailymotion_tag.']<strong style="color:red;">xoh8j</strong>[/'.$dailymotion_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Movieweb помощь</h3>';
	echo '<p>Для вставки видео-ролика Movieweb используйте выделенную красным часть ссылки: http://www.movieweb.com/video/<strong style="color:red;">V07L3flnvxMUWY</strong></p>';
	echo '<p>Наберите ['.$movieweb_tag.']<strong style="color:red;">V07L3flnvxMUWY</strong>[/'.$movieweb_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Jaycut помощь</h3>';
	echo '<p>Для вставки видео-ролика Jaycut используйте выделенную красным часть ссылки: http://jaycut.se/mix/<strong style="color:red;">2493</strong>/preview</p>';
	echo '<p>Наберите ['.$jaycut_tag.']<strong style="color:red;">2493</strong>[/'.$jaycut_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Myvideo помощь</h3>';
	echo '<p>Для вставки видео-ролика Myvideo используйте выделенную красным часть ссылки: http://www.myvideo.de/watch/<strong style="color:red;">3033737</strong></p>';
	echo '<p>Наберите ['.$myvideo_tag.']<strong style="color:red;">3033737</strong>[/'.$myvideo_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Vimeo помощь</h3>';
	echo '<p>Для вставки видео-ролика Vimeo используйте выделенную красным часть ссылки: http://www.vimeo.com/<strong style="color:red;">367351</strong></p>';
	echo '<p>Наберите ['.$vimeo_tag.']<strong style="color:red;">367351</strong>[/'.$vimeo_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Gametrailers помощь</h3>';
	echo '<p>Для вставки видео-ролика Gametrailers используйте выделенную красным часть ссылки: http://www.gametrailers.com/player/<strong style="color:red;">32532</strong>.html</p>';
	echo '<p>Наберите ['.$gtrailers_tag.']<strong style="color:red;">32532</strong>[/'.$gtrailers_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Viddler помощь</h3>';
	echo '<p>Для вставки видео-ролика Viddler используйте выделенную красным часть ссылки: &lt;object classid="clsid:D27CDB6E-AE6D-11cf-96B8-444553540000" width="437" height="370" id="viddler"&gt;&lt;param name="movie" value="http://www.viddler.com/player/<strong style="color:red;">6708b741</strong>/" /&gt;&lt;param name</p>';
	echo '<p>Наберите ['.$viddler_tag.']<strong style="color:red;">6708b741</strong>[/'.$viddler_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Snotr помощь</h3>';
	echo '<p>Для вставки видео-ролика Snotr используйте выделенную красным часть ссылки: http://www.snotr.com/video/<strong style="color:red;">1046</strong></p>';
	echo '<p>Наберите ['.$snotr_tag.']<strong style="color:red;">1046</strong>[/'.$snotr_tag.'] в редакторе, чтобы встроить видео.</p>';

	// Local media
	echo '<h3>Quicktime помощь</h3>';
	echo '<p>Для вставки видео-ролика Quicktime просто заключите ссылку ролика в соответствующие теги</p>';
	echo '<p>Наберите ['.$quicktime_tag.']<strong style="color:red;">http://www.yoursite.com/path/file.mov</strong>[/'.$quicktime_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h3>Windows Media Player помощь</h3>';
	echo '<p>Для вставки видео-ролика Windows Media просто заключите ссылку ролика в соответствующие теги</p>';
	echo '<p>Наберите ['.$windowsmedia_tag.']<strong style="color:red;">http://www.yoursite.com/path/file.wmv</strong>[/'.$windowsmedia_tag.'] в редакторе, чтобы встроить видео.</p>';

	echo '<h2>Нужна дополнительная помощь?</h2>';
	echo '<p>Посетите <a href="http://www.gate303.net/2007/12/17/video-embedder/">домашнюю страницу</a> плагина, чтобы получить более подробную справку. Или зайдите на страницу русской версии плагина на <a href="http://www.wordpressplugins.ru/media/video-embedder.html">WordpressPlugins.ru</a></p>';
	echo '<p>Video Embedder версия '.$videoembedder_options["version"].'</p>';
	echo '</div>';
}

function videoembedder_embed($content)
{
	
	$tags = get_option(videoembedder_options);

	// Rutube
	$tag = $tags["rutube_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://video.rutube.ru/".$video);					
		$content = str_replace($replace, $new, $content);
	}
	
	// Smotri
	$tag = $tags["smotri_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://pics.smotri.com/scrubber_custom8.swf?file=v".$video."&bufferTime=3&autoStart=false&str_lang=rus&xmlsource=http%3A%2F%2Fpics.smotri.com%2Fcskins%2Floadup%2Fskin_color_green.xml&xmldatasource=http%3A%2F%2Fpics.smotri.com%2Fskin_ng.xml");					
		$content = str_replace($replace, $new, $content);
	}
	
	// Youtube
	$tag = $tags["youtube_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.youtube.com/v/".$video."&amp;rel=1");					
		$content = str_replace($replace, $new, $content);
	}

	// Google
	$tag = $tags["google_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://video.google.com/googleplayer.swf?docId=".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Metacafe
	$tag = $tags["metacafe_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.metacafe.com/fplayer/".$video.".swf");
		$content = str_replace($replace, $new, $content);
	}

	// Liveleak
	$tag = $tags["liveleak_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.liveleak.com/player.swf?autostart=false&amp;token=".$video);					
		$content = str_replace($replace, $new, $content);
	}

	// Revver
	$tag = $tags["revver_tag"];
	$height = $tags["video_height"];
	$width = $tags["video_width"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = '<script src="http://flash.revver.com/player/1.0/player.js?mediaId:'.$video.';width:'.$width.';height:'.$height.'" type="text/javascript"></script>';					
		$content = str_replace($replace, $new, $content);
	}

	// IFILM
	$tag = $tags["ifilm_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.ifilm.com/efp?flvbaseclip=".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Myspace
	$tag = $tags["myspace_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://lads.myspace.com/videos/vplayer.swf?m=".$video."&amp;v=2&amp;type=video");
		$content = str_replace($replace, $new, $content);
	}

	// Blip.tv
	$tag = $tags["bliptv_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://blip.tv/scripts/flash/showplayer.swf?autostart=false&#038;file=http%3A%2F%2Fcreationsnet%2Eblip%2Etv%2Ffile%2F".$video."%2F%3Fskin%3Drss%26sort%3Ddate&#038;fullscreenpage=http%3A%2F%2Fblip%2Etv%2Ffullscreen%2Ehtml&#038;fsreturnpage=http%3A%2F%2Fblip%2Etv%2Fexitfullscreen%2Ehtml&#038;showfsbutton=true&#038;brandlink=http%3A%2F%2Fcreationsnet%2Eblip%2Etv%2F&#038;brandname=cre%2Eations%2Enet&#038;showguidebutton=false&#038;showplayerpath=http%3A%2F%2Fblip%2Etv%2Fscripts%2Fflash%2Fshowplayer%2Eswf");
		$content = str_replace($replace, $new, $content);
	}

	// Collegehumor
	$tag = $tags["college_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.collegehumor.com/moogaloop/moogaloop.swf?clip_id=".$video."&amp;fullscreen=1");
		$content = str_replace($replace, $new, $content);
	}

	// Videojug
	$tag = $tags["videojug_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.videojug.com/film/player?id=".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Godtube
	$tag = $tags["godtube_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://godtube.com/flvplayer.swf?viewkey=".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Veoh
	$tag = $tags["veoh_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.veoh.com/videodetails2.swf?player=videodetailsembedded&amp;type=v&amp;permalinkId=".$video."&amp;id=anonymous");
		$content = str_replace($replace, $new, $content);
	}

	// Break
	$tag = $tags["break_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://embed.break.com/".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Dailymotion
	$tag = $tags["dailymotion_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.dailymotion.com/swf/".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Movieweb
	$tag = $tags["movieweb_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.movieweb.com/v/".$video);
		$content = str_replace($replace, $new, $content);
	}

	// jaycut
	$tag = $tags["jaycut_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://jaycut.se/flash/preview.swf?file=http://jaycut.se/mixes/send_preview/".$video."&amp;type=flv&amp;autostart=false");
		$content = str_replace($replace, $new, $content);
	}

	// Myvideo
	$tag = $tags["myvideo_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.myvideo.de/movie/".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Vimeo
	$tag = $tags["vimeo_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.vimeo.com/moogaloop.swf?clip_id=".$video."&amp;server=www.vimeo.com&amp;fullscreen=1&amp;show_title=0&amp;show_byline=0&amp;show_portrait=0&amp;color=");
		$content = str_replace($replace, $new, $content);
	}

	// Gametrailers
	$tag = $tags["gtrailers_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.gametrailers.com/remote_wrap.php?mid=".$video);
		$content = str_replace($replace, $new, $content);
	}

	// Viddler
	$tag = $tags["viddler_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://www.viddler.com/player/".$video."/");
		$content = str_replace($replace, $new, $content);
	}

	// Snotr
	$tag = $tags["snotr_tag"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new = buildEmbed("http://videos.snotr.com/player.swf?video=".$video."&amp;embedded=true&amp;autoplay=false");
		$content = str_replace($replace, $new, $content);
	}

	// Quicktime
	$tag = $tags["quicktime_tag"];
	$height = $tags["video_height"];
	$width = $tags["video_width"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new='<object classid="clsid:02BF25D5-8C17-4B23-BC80-D3488ABDDC6B" codebase="http://www.apple.com/qtactivex/qtplugin.cab" width="'.$width.'" height="'.$height.'">';
		$new.='<param name="src" value="'.$video.'" />';
		$new.='<param name="controller" value="true" />';
		$new.='<param name="autoplay" value="false" />';
		$new.='<param name="scale" value="aspect" />';
		$new.='<object type="video/quicktime" data="'.$video.'" width="'.$width.'" height="'.$height.'">'."\n";
		$new.='<param name="autoplay" value="false" />';
	 	$new.='<param name="controller" value="true" />';
		$new.='<param name="scale" value="aspect" />';
		$new.='</object>';
		$new.='</object>';
		$content = str_replace($replace, $new, $content);
	}

	// Windows media player
	$tag = $tags["windowsmedia_tag"];
	$height = $tags["video_height"];
	$width = $tags["video_width"];
	preg_match_all('/\['.$tag.'\](.*?)\[\/'.$tag.'\]/is', $content, $videocode);
	for ($i=0; $i < count($videocode['0']); $i++)
	{
		$video =  $videocode['1'][$i];
		$replace = $videocode['0'][$i];
		$new='<object classid="CLSID:6BF52A52-394A-11d3-B153-00C04F79FAA6" id="player" width="'.$width.'" height="'.$height.'">'."\n";
		$new.='<param name="url" value="'.$video.'" />'."\n";
		$new.='<param name="src" value="'.$video.'" />'."\n";
		$new.='<param name="showcontrols" value="true" />'."\n";
		$new.='<param name="autostart" value="false" />'."\n";
		$new.='<param name="stretchtofit" value="true" />'."\n";
		$new.='<!--[if !IE]>-->'."\n";
		$new.='<object type="video/x-ms-wmv" data="'.$video.'" width="'.$width.'" height="'.$height.'">'."\n";
		$new.='<param name="src" value="'.$video.'" />'."\n";
		$new.='<param name="autostart" value="false" />'."\n";
		$new.='<param name="controller" value="false" />'."\n";
		$new.='<param name="stretchtofit" value="true" />'."\n";
		$new.='</object>'."\n";
		$new.='<!--<![endif]-->'."\n";
		$new.='</object>'."\n";

		$content = str_replace($replace, $new, $content);
	}

	return $content;
}

function buildEmbed($code)
{
	$options = get_option(videoembedder_options);
	$width = $options["video_width"];
	$height = $options["video_height"];
	$object = '<object type="application/x-shockwave-flash" width="'.$width.'" height="'.$height.'" data="'.$code.'">';
	$object .= '<param name="movie" value="'.$code.'" />';
	$object .= '<param name="wmode" value="transparent" />';
	$object .= '<param name="quality" value="high" />';
	$object .= '</object>';
	return $object;
}

function defaultSettings() {
	if(get_option('videoembedder_version') != ""){
			importOldSettings();
	}
	
	$option = get_option('videoembedder_options');
	if($option["version"] != "1.5")		$option["version"] = "1.6";
	if($option["video_width"]=="")		$option["video_width"] = "425";
	if($option["video_height"]=="")		$option["video_height"] = "355";
	if($option["rutube_tag"]=="")		$option["rutube_tag"] = "rutube";
	if($option["smotri_tag"]=="")		$option["smotri_tag"] = "smotri";
	if($option["youtube_tag"]=="")		$option["youtube_tag"] = "youtube";
	if($option["google_tag"]=="")		$option["google_tag"] = "google";
	if($option["metacafe_tag"]=="")		$option["metacafe_tag"] = "metacafe";
	if($option["liveleak_tag"]=="")		$option["liveleak_tag"] = "liveleak";
	if($option["revver_tag"]=="")		$option["revver_tag"] = "revver";
	if($option["ifilm_tag"]=="")		$option["ifilm_tag"] = "ifilm";
	if($option["myspace_tag"]=="")		$option["myspace_tag"] = "myspace";
	if($option["bliptv_tag"]=="")		$option["bliptv_tag"] = "bliptv";
	if($option["college_tag"]=="")		$option["college_tag"] = "college";
	if($option["videojug_tag"]=="")		$option["videojug_tag"] = "videojug";
	if($option["godtube_tag"]=="")		$option["godtube_tag"] = "godtube";
	if($option["veoh_tag"]=="")		$option["veoh_tag"] = "veoh";
	if($option["break_tag"]=="")		$option["break_tag"] = "break";
	if($option["dailymotion_tag"]=="")	$option["dailymotion_tag"] = "daily";
	if($option["movieweb_tag"]=="")		$option["movieweb_tag"] = "movieweb";
	if($option["jaycut_tag"]=="")		$option["jaycut_tag"] = "jaycut";
	if($option["myvideo_tag"]=="")		$option["myvideo_tag"] = "myvideo";
	if($option["vimeo_tag"]=="")		$option["vimeo_tag"] = "vimeo";
	if($option["gtrailers_tag"]=="")	$option["gtrailers_tag"] = "gtrailer";
	if($option["viddler_tag"]=="")	$option["viddler_tag"] = "viddler";
	if($option["snotr_tag"]=="")	$option["snotr_tag"] = "snotr";
	//Local
	if($option["quicktime_tag"]=="")	$option["quicktime_tag"] = "quicktime";
	if($option["windowsmedia_tag"]=="")	$option["windowsmedia_tag"] = "windowsmedia";
	update_option('videoembedder_options', $option);
}

function importOldSettings(){
	$old_options=array(
		"video_width" => get_option('videoembedder_video_width'),
		"video_height" => get_option('videoembedder_video_height'),
		"youtube_tag" => get_option('videoembedder_youtube_tag'),
		"google_tag" => get_option('videoembedder_google_tag'),
		"metacafe_tag" => get_option('videoembedder_metacafe_tag'),
		"liveleak_tag" => get_option('videoembedder_liveleak_tag'),
		"revver_tag" => get_option('videoembedder_revver_tag'),
		"ifilm_tag" => get_option('videoembedder_ifilm_tag'),
		"myspace_tag" => get_option('videoembedder_myspace_tag'),
		"bliptv_tag" => get_option('videoembedder_bliptv_tag'),
		"college_tag" => get_option('videoembedder_college_tag'),
		"videojug_tag" => get_option('videoembedder_videojug_tag'),
		"godtube_tag" => get_option('videoembedder_godtube_tag'),
		"veoh_tag" => get_option('videoembedder_veoh_tag'),
		"break_tag" => get_option('videoembedder_break_tag'),
	);
	update_option('videoembedder_options', $old_options);

	delete_option('videoembedder_version');
	delete_option('videoembedder_video_width');
	delete_option('videoembedder_video_height');
	delete_option('videoembedder_youtube_tag');
	delete_option('videoembedder_google_tag');
	delete_option('videoembedder_metacafe_tag');
	delete_option('videoembedder_liveleak_tag');
	delete_option('videoembedder_revver_tag');
	delete_option('videoembedder_ifilm_tag');
	delete_option('videoembedder_myspace_tag');
	delete_option('videoembedder_bliptv_tag');
	delete_option('videoembedder_college_tag');
	delete_option('videoembedder_videojug_tag');
	delete_option('videoembedder_godtube_tag');
	delete_option('videoembedder_veoh_tag');
	delete_option('videoembedder_break_tag');
}
?>