<?php
/*
Plugin Name: belavir (php MD5)
Plugin URI: http://www.portal.kharkov.ua/soft/belavir.sphp
Description: Проверка целостности файлов
Author: Yuri 'Bela' Belotitski
Version: 1.0 @ 27.06.2008 09:12
Author URI: http://www.portal.khakrov.ua/
*/

$md5_file = ABSPATH . 'wp-content/uploads/my-md5.txt'; // укажите имя файла


function write_md5(){
	global $md5_file;
	$md5 = dir_md5();
	$mdf = @fopen($md5_file, 'w');
	foreach ($md5 as $key=>$val) @fwrite($mdf, "$val\t$key\n");
}

function dashboard_files_checker(){
	global $md5_file;

	echo '<h3>Измененные файлы</h3>';

	if ( (current_user_can('edit_themes') and $_POST['updatemd5']) or (!file_exists($md5_file)) ) write_md5();

	$md5 = dir_md5();
	if (file_exists($md5_file)) {
		$mdf = file($md5_file);

		if ( !$mdf ) {
			write_md5();
			$mdf = file($md5_file);
		}

		$i = 1;
		while (list($ln, $line) = each($mdf))
		{
			list($md, $ff) = explode("\t", trim($line));
				if ($md != $md5[$ff]) {
					$ff = str_replace(ABSPATH, '/', $ff);
					$ff = str_replace('//', '/', $ff);
					echo "<br />$i. $ff - <font color='red'>изменен</font>";
					$i++;
				}
		}
		if ( $i == 1 ) echo "<br /><font color='green'>Всё ок!</font>";
	}

	if (current_user_can('edit_themes'))
		echo '<br /><br /><form method="post" action="index.php">
			<input type="submit" id="updatemd5" name="updatemd5" value="Сбросить/обновить хэш файлов"></form>';
}

function dir_md5() {
	global $md5;
	xdir(ABSPATH, 0);
	xdir(ABSPATH . 'wp-includes', 1);
	xdir(ABSPATH . 'wp-admin', 1);
	xdir(ABSPATH . 'wp-content/themes', 1);
	xdir(ABSPATH . 'wp-content/plugins', 1);
	return $md5;
}

function xdir($path, $recurs) {
	global $md5;
	if ($dir = @opendir($path)) {
		while($file = readdir($dir)) {
			if ($file == '.' or $file == '..') continue;
			$file = $path . '/' . $file;
			if (is_dir($file) && $recurs)  {
				xdir($file, 1);
			}
			if (is_file($file) && strstr($file, '.php')) {
				$md5[$file] = md5(join ('', file($file)));
			}
		}
		closedir($dir);
 	}
}

add_action('activity_box_end', 'dashboard_files_checker');

?>