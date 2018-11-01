<?php
/*
Plugin Name: Управление версиями
Plugin URI: http://www.chanishvili.org/rc-revmngr/
Description: Управление ревизиями для WordPress 2.6 и старше
Version: 0.3 beta
Author: Роланд «Сволочь» Чанишвили
Author URI: http://www.chanishvili.org/
*/

add_action('admin_menu','rcrm_hook');
function rcrm_hook(){
	if (function_exists('add_options_page')) {
		add_options_page('Управление версиями',
		'Управление версиями',
		10, /* для Одминов онли! */
		basename(__FILE__),
		'rcrm_show_plugin_page');
	}
}

function rcrm_show_plugin_page()
{
	global $wpdb;
	
	if(!empty($_POST['clear'])){
		if($wpdb->query("DELETE FROM $wpdb->posts WHERE post_parent = 2".$_POST['clear'].";")) echo '<div class="updated"><p>Версии поста «<strong>'.$_POST['txt'].'</strong>» удалены.</p></div>';
		else echo '<div class="error"><p>Ошибка удаления версии поста «<strong>'.$_POST['txt'].'</strong>»!</p></div>';
	}elseif(!empty($_POST['clear-all'])){
		if($wpdb->query("DELETE FROM $wpdb->posts WHERE post_status='inherit' AND post_type='revision';")) echo '<div class="updated"><p>Все версии и автосохранения удалены.</p></div>';
		else echo '<div class="error"><p>Ошибка удаления всех версий и автосохранений!</p></div>';
	}elseif(!empty($_POST['save'])){
		$f = file_get_contents(ABSPATH.'/wp-config.php');

		$revcount = $_POST['revision']>1 ? $_POST['revcount'] : $_POST['revision'] ;
		if(strpos($f,'WP_POST_REVISIONS')===false) $f = str_replace("<?php","<?php\r\ndefine('WP_POST_REVISIONS', $revcount );", $f);
		else $f = preg_replace('/WP_POST_REVISIONS\s*([\'"])\s*,([^)]*)/s', 'WP_POST_REVISIONS$1,'.$revcount, $f, 1);
		
		if(strpos($f,'AUTOSAVE_INTERVAL')===false) $f = str_replace("<?php","<?php\r\ndefine('AUTOSAVE_INTERVAL', ".$_POST['count']." );", $f);
		else $f = preg_replace('/AUTOSAVE_INTERVAL\s*([\'"])\s*,([^)]*)/s', 'AUTOSAVE_INTERVAL$1,'.$_POST['count'], $f, 1);

		if(@file_put_contents(ABSPATH .'/wp-config.php',$f)) echo '<div class="updated"><p>Настройки сохранены, <a href="/wp-admin/options-general.php?page=rc_revmngr.php">применяем настроки...</a></p></div> <script>document.location.href="/wp-admin/options-general.php?page=rc_revmngr.php";</script>';
		else echo '<div class="error"><p>Не могу сохранить <strong>wp-config.php</strong>. Проверьте права доступа.</p></div>';
	}

	if(constant('WP_POST_REVISIONS')) $rev = constant('WP_POST_REVISIONS');
	else $rev = 0;

	$si = constant('AUTOSAVE_INTERVAL');

	$post = $wpdb->get_results("SELECT `post_title`, `post_parent`, COUNT(`post_title`) count FROM `$wpdb->posts` WHERE `post_status` = 'inherit' AND `post_type` = 'revision' GROUP BY `post_parent`;");
	if($post!==NULL){
		$list = '';
		foreach($post as $p){
			if(!empty($list)) $list .= ', ';
			$list .= '<a title="Удалить версии «'.$p->post_title.'»" href="#'.$p->post_parent.'" onClick="Go('.$p->post_parent.',\''.$p->post_title.'\');">'.$p->post_title.' ('.$p->count.')</a>';
		}
		$list .= '.<br /><br /><strong><input type="submit" value="Удалить все сохраненные версии" name="clear-all" class="button" onclick="return confirm(\'Действительно удалить ВСЕ версии?\')"/>';
	} else $list = 'Версионных постов нет.';

?>
<script>
function Go(val,txt) {
	if (confirm('Действительно удалить версии записи «'+txt+'» ?')) {
var frm=document.myform;
	frm.clear.value = val;
	frm.txt.value = txt;
	frm.submit();
	}
}
</script>
   
   <?php if(!is_writable(ABSPATH.'/wp-config.php')) echo '<div class="error"><p>Файл /wp-config.php защищен от записи! Изменения <strong>не будут сохранены</strong>!</p></div>'; ?>

<div class='wrap'>
<h2>Управление версиями</h2>
<form name='myform' method='post'>
<input type='hidden' name='clear'>
<input type='hidden' name='txt'>
  <fieldset>
  <table class="form-table">
	<tr>
		<th><label for="revision">Настройки версий</label></th>
		<td>
		<label><input name='revision' type='radio' value='0'  <?=(0==$rev ? 'checked' : '')?>/> Выключить поддержку версий</label><br />
		<label><input name='revision' type='radio' value='1' <?=(1==$rev ? 'checked' : '')?>/> Хранить все версии</label><br />
		<label><input name='revision' type='radio' value='2'  <?=(1<$rev ? 'checked' : '')?>/> Хранить не более </label><input type="text" value="<?=$rev?>" id="revcount" name="revcount" size="3" style="text-align: center;"/><label> версий</label><br /></td>
   </tr>
	<tr>
		<th><label for="count">Настройки автосохранения</label></th>
		<td>
		<label>Автоматически сохранять черновик каждые <input type="text" value="<?=$si?>" id="count" name="count" size="3" style="text-align: center;"/> секунд</label></td>
   </tr>
	<tr>
		<th><label>Посты с версиями</label></th>
		<td><?=$list?></td>
  </tr>
   </table>
	<p class="submit"><input type="submit" value="Сохранить настроки в wp-config.php" name="save" class="button" /></p>
  </fieldset>
</form>

<p><strong>Самая свежая версия плагина доступна на <a href='http://www.chanishvili.org/avtomaticheskoe-upravlenie-versiyami-reviziyami-v-wordpress-26/' targe='_blank' >www.chanishvili.org</a>.</strong></p>
</div> <!-- wrap -->
<?php } ?>