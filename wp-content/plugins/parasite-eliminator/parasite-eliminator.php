<?php
/*
Plugin Name: Novikov's Parasite Eliminator
Plugin URI: http://parasite-eliminator.ru 
Description: Боремся с&nbsp;паразитами в&nbsp;блоге. Этот плагин ищет комментарии, содержащие спамерские ссылки из&nbsp;черного списка, и&nbsp;скрывает&nbsp;их, отправляя&nbsp;модератору. Плагин&nbsp;может фильтровать ссылки и&nbsp;по&nbsp;белому списку. Если&nbsp;комментатор в&nbsp;белом списке&nbsp;&mdash; он&nbsp;получит свою честную&nbsp;ссылку. 
Version: beta 0.849
Author: Алексей Новиков
Author URI: http://drNovikov.ru/
*/

/*  Copyright 2008  Alexey Novikov  (email: eliminator@parasite-eliminator.ru)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Тут указываем версии 
define('VERSION', '0.849');  // Версия плагина
define('DB_VERSION', '0.74'); // Версия структуры данных
define('STATUS', 'beta'); // Статус версии

// Некоторые значения по умолчанию
define('MAIN_SERVER', 'http://parasite-eliminator.ru/updates/update.txt'); // Основной сервер обновлений
define('BACKUP_SERVER', 'http://parasite-eliminator.narod.ru/updates/update.txt'); // Запасной сервер обновлений
define('SPAMMERS_FUCK_OFF_TEXT', '<p style="color: #FF0000; font-weight: bold;">Привет спамерам! Этот блог защищен плагином <a href="http://parasite-eliminator.ru">Parasite Eliminator</a>, спамить&nbsp;его&nbsp;бесполезно.</p>'); // Приветственное обращение к спамерам

// Добавляем фильтр на вывод URL комментатора
add_filter('get_comment_author_url', 'npe_filter_comment_author_url');

// Добавляем фильтр на вывод текста спамерского комментария
add_filter('comment_text', 'npe_spammer_fuck_off');

// Добавляем при каждом выводе подвала страницы проверку необходимости обновления баз
add_action('get_footer', 'npe_update_sheduler');

// Добавляем в админку страницы для управления плагином
add_action('admin_menu','npe_create_admin_pages');

// Подключаем проверку при добавлении комментариев
add_action('comment_post', 'npe_check_comments', 10, 1); 

// Добавляем в дешборд кнопку для старта полной ручной проверки
add_action('activity_box_end', 'npe_dashboard', 10, 1);

// TODO При изменении статуса комментария на approved снимаем с него пометку BL (пока до следующей проверки)
add_action('wp_set_comment_status', 'npe_set_comment_status', 10, 2);

// Регистрируем хуки для активации и деактивации
register_activation_hook(__FILE__,'npe_install_plugin');
register_deactivation_hook(__FILE__,'npe_uninstall_plugin');


////////////////////////// Функции установки и удаления /////////////////////////

function npe_install_plugin() {
// Эта функция вызывается при активации плагина и создает таблицы, опции и т.п.
  
  npe_add_comments_properties();
  npe_create_tables();
  
  add_option("npe_version", VERSION); // Сохраняем при установке в опциях версию плагина
  add_option("npe_db_version", DB_VERSION);  // Сохраняем при установке в опциях версию структуры данных
  
  add_option("npe_use_whitelist", 1);   // Проверять ли комментарии по белому списку
  add_option("npe_only_white_comment_author_url", '1');   // Выводить ссылки на комментаторов только из белого списка
  
  add_option("npe_use_blacklist", '1');   // Проверять ли комментарии по черному списку
  add_option("npe_approve_blacklisted", '0');   // Как помечать комментарии из черного списка
  
  add_option("npe_autoupdate_every_day", '1');   // Обновлять списки с сервера автоматически каждый день
  add_option("npe_main_server_url", MAIN_SERVER);   // Основной сервер
  add_option("npe_backup_server_url", BACKUP_SERVER);   // Запасной сервер
  add_option("npe_last_update", '');   // Время последнего успешного обновления
  add_option("npe_next_update", time() + 120);   // Время следующей попытки автоматического обновления, первый раз через 2 минуты после установки плагина
  
  add_option("npe_black_counter", '0');   // Cчетчик отловленных по черному списку комментариев
  add_option("npe_white_counter", '0');   // Cчетчик отловленных по белому списку комментариев
  
  add_option("npe_spammers_fuck_off", 1); // Выводить спамерам приветик после сохранения комментария
  add_option("npe_spammers_fuck_off_text", SPAMMERS_FUCK_OFF_TEXT); // Текст, который будем выводить для спамеров
  
  add_option("npe_current_version", 0);   // Тут будем хранить номер последнего обновления
  
  add_option("npe_user_blacklist_include", '');   // Тут будем хранить пользовательский черный список
  add_option("npe_user_blacklist_exclude", '');   // Тут будем хранить пользовательские исключения из черного списка
  
  add_option("npe_user_whitelist_include", '');   // Тут будем хранить пользовательский белый список
  add_option("npe_user_whitelist_exclude", '');   // Тут будем хранить пользовательские исключения из белого списка
  
  add_option("npe_update_from_server_on_admin_save", '1');  // Обновлять ли списки с сервера при сохранении настроек?
  add_option("npe_total_scan_on_admin_save", '1');  // Проводить ли тотальную проверку комментариев при сохранении настроек?
  
  add_option("npe_last_main_server_update_was_ok", '1');  // Последнее обновление с основного сервера прошло нормально?
  add_option("npe_last_backup_server_update_was_ok", '1');  // Последнее обновление с запасного сервера прошло нормально?
  
  // Обновляемся с сервера и проверяем все комментарии
  npe_perform_update();
  npe_check_comments();
}

function npe_uninstall_plugin() {
// Эта функция вызывается при деактивации плагина и удаляет таблицы, опции и т.п.

  npe_remove_comments_properties();
  npe_drop_tables();

  delete_option("npe_version");
  delete_option("npe_db_version");

  delete_option("npe_use_whitelist");
  delete_option("npe_only_white_comment_author_url");
 
  delete_option("npe_use_blacklist");
  delete_option("npe_approve_blacklisted");

  delete_option("npe_autoupdate_every_day");
  delete_option("npe_main_server_url");
  delete_option("npe_backup_server_url");
  delete_option("npe_last_update");
  delete_option("npe_next_update");
  
  delete_option("npe_black_counter");
  delete_option("npe_white_counter");
  
  delete_option("npe_spammers_fuck_off"); 
  delete_option("npe_spammers_fuck_off_text");
  
  delete_option("npe_current_version");
  
  delete_option("npe_user_blacklist_include");
  delete_option("npe_user_blacklist_exclude");
  
  delete_option("npe_user_whitelist_include");
  delete_option("npe_user_whitelist_exclude");
  
  delete_option("npe_update_from_server_on_admin_save");
  delete_option("npe_total_scan_on_admin_save");
  
  delete_option("npe_last_main_server_update_was_ok");
  delete_option("npe_last_backup_server_update_was_ok");
}

function npe_add_comments_properties() {
// Эта функция добавляет в таблицу comments поля для помечания 
// комментариев как whitelisted или blacklisted, если таких полей нет
  if ( !npe_field_exists('comments', 'npe_whitelist') ) {
    global $wpdb;
    $query = ' ALTER TABLE `' . $wpdb->prefix . 'comments` 
                ADD `npe_whitelist` TINYINT ( 1 ) , 
                ADD `npe_blacklist` TINYINT ( 1 ) ';
    return $wpdb->query( $query );
  }
}

function npe_remove_comments_properties() {
// Эта функция удаляет из таблицы comments поля для помечания 
// комментариев как whitelisted или blacklisted, если такие поля есть
  if ( npe_field_exists('comments', 'npe_whitelist') ) {
    global $wpdb;
    $query = ' ALTER TABLE `' . $wpdb->prefix . 'comments`  
                DROP `npe_whitelist` , 
                DROP `npe_blacklist` ; ';
    return $wpdb->query( $query );
  }
}

function npe_create_tables() {
// Эта функция создает в базе таблицы для белого и черного списков,
// предварительно убеждаясь, что таких таблиц нет
  global $wpdb;
  
  // Сперва получаем charset и collate, иначе будут проблемы с LIKE 
  if ( ! empty($wpdb->charset) )
		$charset_collate =  ' DEFAULT CHARACTER SET ' . $wpdb->charset;
  if ( ! empty($wpdb->collate) )
		$charset_collate .= ' COLLATE ' . $wpdb->collate;

  $query = ' CREATE TABLE IF NOT EXISTS`' . $wpdb->prefix . 'npe_blacklist` (
                `url` VARCHAR( 200 ) NOT NULL ,
                `status` TINYINT ( 1 ) NOT NULL , 
                PRIMARY KEY ( `url` ))
                ' . $charset_collate . ' ; ';
  $wpdb->query( $query );
                
  $query = ' CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'npe_whitelist` (
                `url` VARCHAR( 200 ) NOT NULL ,
                `status` TINYINT ( 1 ) NOT NULL , 
                PRIMARY KEY ( `url` ))
                ' . $charset_collate . ' ; ';
  $wpdb->query( $query );
  
  $query = ' CREATE TABLE IF NOT EXISTS`' . $wpdb->prefix . 'npe_server_blacklist` (
                `url` VARCHAR( 200 ) NOT NULL ,
                `status` TINYINT ( 1 ) NOT NULL , 
                PRIMARY KEY ( `url` ))
                ' . $charset_collate . ' ; ';
  $wpdb->query( $query );
  
  $query = ' CREATE TABLE IF NOT EXISTS `' . $wpdb->prefix . 'npe_server_whitelist` (
                `url` VARCHAR( 200 ) NOT NULL ,
                `status` TINYINT ( 1 ) NOT NULL , 
                PRIMARY KEY ( `url` ))
                ' . $charset_collate . ' ; ';
  $wpdb->query( $query );
}

function npe_drop_tables() {
// Эта функция удаляет из базы таблицы для белого и черного списков,
// предварительно убеждаясь, что такие таблицы есть
  global $wpdb;
  
  $query = ' DROP TABLE IF EXISTS `' . $wpdb->prefix . 'npe_blacklist` ; ';
  $wpdb->query( $query );
  
  $query = ' DROP TABLE IF EXISTS `' . $wpdb->prefix . 'npe_whitelist` ; ';
  $wpdb->query( $query );
  
  $query = ' DROP TABLE IF EXISTS `' . $wpdb->prefix . 'npe_server_blacklist` ; ';
  $wpdb->query( $query );
  
  $query = ' DROP TABLE IF EXISTS `' . $wpdb->prefix . 'npe_server_whitelist` ; ';
  $wpdb->query( $query );
}


////////////////////////// Функции админки  /////////////////////////

function npe_create_admin_pages() {
// Эта функция добавляет в админку страницы настроек и управления списками
  npe_options_page();
  npe_updates_page();
}

function npe_dashboard() {
// Эта функция добавляет в дешборд кнопку ручного сканирования
?>
  <div>
  <h3>Parasite Eliminator</h3>
  <p><?php echo npe_print_parasites_count(npe_how_much_spam_trashed()); ?><br /><br /></p>
  <p><?php if ( !get_option("npe_last_update") ) { echo 'Обновления с&nbsp;сервера пока не&nbsp;были&nbsp;получены'; } else { echo 'Последнее обновление с&nbsp;сервера получено ' . date ('d.m.Y в&\nb\sp;H:i', get_option("npe_last_update")) . ' (Текущая версия базы: ' . get_option("npe_current_version") . ')' ; } ?></p>
  <p>
  <br />
  <form method="post" action="options-general.php?page=npe_options">
  <input type="submit" style="padding: 8px; font-family: Arial; font-weight: bold; font-size: 20px;" value="Проверить все комментарии" name="total_scan" tabindex="50"/>
  </form>
  </p>
  </div>
<?php
}

function npe_options_page() {
// Эта функция добавляет в админку страницу настроек плагина
  add_options_page(
                    'Настройки Parasite Eliminator',  // Заголовок окна админки
                    'Parasite Eliminator',            // Пункт меню админки
                    'manage_options',                 // Право для управления
                    'npe_options',                    // Файл плагина
                    'npe_print_options_page'          // Функция, которая будет вызвана
                   );
}

function npe_updates_page() {
// Эта функция добавляет в админку страницу настроек плагина
  add_options_page(
                    'Черные и белые списки Parasite Eliminator',  // Заголовок окна админки
                    'Черные и белые списки',                      // Пункт меню админки
                    'manage_options',                             // Право для управления
                    'npe_updates',                                // Файл плагина
                    'npe_print_updates_page'                      // Функция, которая будет вызвана
                   );  
}

function npe_update_lists() {
// Эта функция обновляет списки, сохраняемые пользователем, и вызывает пересборку общих списков

  // Проверяем, пришла ли форма полностью
  if ($_REQUEST['hidden']) {
   
    // Очищаем введенные пользователем списки
    $npe_user_blacklist_include = npe_filter_user_list($_REQUEST['npe_user_blacklist_include']);
    $npe_user_blacklist_exclude = npe_filter_user_list($_REQUEST['npe_user_blacklist_exclude']);
    $npe_user_whitelist_include = npe_filter_user_list($_REQUEST['npe_user_whitelist_include']);
    $npe_user_whitelist_exclude = npe_filter_user_list($_REQUEST['npe_user_whitelist_exclude']);

    // Сохраняем опции в настройках    
    update_option("npe_user_blacklist_include", $npe_user_blacklist_include);
    update_option("npe_user_blacklist_exclude", $npe_user_blacklist_exclude);
    update_option("npe_user_whitelist_include", $npe_user_whitelist_include);
    update_option("npe_user_whitelist_exclude", $npe_user_whitelist_exclude);
    
    $npe_update_from_server_on_admin_save = 0 + $_REQUEST['npe_update_from_server_on_admin_save'];
    update_option("npe_update_from_server_on_admin_save", $npe_update_from_server_on_admin_save);
    
    $npe_total_scan_on_admin_save = 0 + $_REQUEST['total_scan'];
    update_option("npe_total_scan_on_admin_save", $npe_total_scan_on_admin_save);     

    if ( $npe_update_from_server_on_admin_save ) {  // Если решено получить свежие списки с сервера при сохранении
      // Сперва собираем списки, на случай, если потом обновление с серверов повиснет
      npe_merge_lists();

      // Выводим сообщение об обновлении списков
      echo '<div id="message2" class="updated fade"><p>Списки обновлены</p></div>';
      // Получаем обновление с сервера, если все ок, то снова собираем списки и выводим сообщение
      if ( !$failure = npe_update_from_server() ) {
        npe_merge_lists();
        echo '<div id="message3" class="updated fade"><p>Свежие списки с сервера получены</p></div>';
      } else {  // Если обновиться с сервера не удалось -- честно признаемся
        echo '<div id="message3" class="error fade"><p>' . $failure . '</p></div>';
      }

    } else {  // Если не надо получать свежие списки при сохранении
      // Сперва собираем списки, на случай, если потом
      npe_merge_lists();
      
      // Выводим сообщение об обновлении
      echo '<div id="message" class="updated fade"><p>Списки обновлены</p></div>';
    }

  // Если форма не пришла целиком -- выводим сообщение об ошибке и ничего не обновляем
  } else { echo '<div id="message" class="updated fade"><p>Не удалось обновить списки</p></div>'; }
}

function npe_print_updates_page() {
// Эта функция выводит страницу обновлений черных и белых списков

  // Если не вызывали процедуру обновления с серверов, то придется проверить соединение
  // с интернетом здесь, дабы блоггер видел, что непорядок есть, и хостера надобно пнуть
  if ( !$_REQUEST['npe_update_from_server_on_admin_save'] ) {
    if ( !npe_can_get_file_from_server() ) {
      $connection_error_messages .= '<div id="error01" class="error fade"><p>Настройки хостинга не&nbsp;позволяют обновить базу с&nbsp;нашего сервера (пусть хостер включит allow_url_fopen или поставит cURL)</p></div>';
    } elseif ( !npe_connection_is_possible() ) {
      $connection_error_messages .= '<div id="error01" class="error fade"><p>Скрипт не может соединиться с серверами в интернете (спросите у вашего хостера, что можно сделать)</p></div>';        
    }
    echo $connection_error_messages;
  }

  // Если только что сохраняли -- обновить опции 
  if ($_REQUEST['submit']) { npe_update_lists(); }

  $npe_user_blacklist_include = get_option("npe_user_blacklist_include");
  $npe_user_blacklist_exclude = get_option("npe_user_blacklist_exclude");
  
  $npe_user_whitelist_include = get_option("npe_user_whitelist_include");
  $npe_user_whitelist_exclude = get_option("npe_user_whitelist_exclude"); 
  
  $npe_update_from_server_on_admin_save = get_option("npe_update_from_server_on_admin_save");
  $npe_total_scan_on_admin_save = get_option("npe_total_scan_on_admin_save");

  if ($_REQUEST['total_scan']) {
    npe_check_comments();
?>

  <div id="message5" class="updated fade">
  <p>
    Комментарии проверены. <?php echo npe_print_parasites_count(npe_how_much_spam_trashed()); ?>
  </p>
  </div>

<?php    
  }

  ?>
  
  <div class="wrap">
  <?php //// TODO -- убрать потом сообщение о бете ?> 
  <?php npe_print_beta(); ?>
  
  <h2>Novikov's Parasite Eliminator: управление списками</h2>

  <br />
  <form method="post">
  <fieldset class="options" style="border: #DDDDDD 1px solid;">
  <legend>Черный список</legend>
  <p>Большая часть спама в&nbsp;блогах направлена на&nbsp;&laquo;раскрутку&raquo; спамерских&nbsp;сайтов. Чтобы&nbsp;сделать спам в&nbsp;блогах невыгодным и&nbsp;бесполезным, мы&nbsp;добавляем домены спамеров в&nbsp;черный список, а&nbsp;плагин находит спамерские комментарии и&nbsp;отправляет&nbsp;их на&nbsp;модерацию. Таким&nbsp;образом, блог очищается от&nbsp;мусора, экономится время&nbsp;блоггера.</p>
  <p>Если какого-то спамерского домена нет в&nbsp;нашем черном списке&nbsp;&mdash; вы&nbsp;можете добавить&nbsp;его&nbsp;вручную. Только&nbsp;не&nbsp;забудьте отправить&nbsp;его по&nbsp;адресу <a href="mailto:addtoblacklist@parasite-eliminator.ru">addtoblacklist@parasite-eliminator.ru</a>, чтобы и&nbsp;другие блоггеры получили&nbsp;его при&nbsp;следующем обновлении. Если&nbsp;же вы&nbsp;хотите, чтобы какой-то домен из&nbsp;черного списка не&nbsp;отсеивался, добавьте&nbsp;его в&nbsp;список&nbsp;исключений.<br /><br /></p>
  <div style="width: 49%; font-size: 12px; float:left; padding-right: 16px;"> 
  <label for="npe_user_blacklist_include">Добавить эти домены в черный список, каждый с новой строки</label><br />
  <textarea tabindex="10" style="width: 98%" id="npe_user_blacklist_include" name="npe_user_blacklist_include" cols="40" rows="10"><?php echo $npe_user_blacklist_include; ?></textarea>
  </div>

  <div style="width: 49%; font-size: 12px; float:left;"> 
  <label for="npe_user_blacklist_exclude">Не помечать эти домены как спамерские</label><br />
  <textarea tabindex="20" style="width: 98%" id="npe_user_blacklist_exclude" name="npe_user_blacklist_exclude" cols="40" rows="10"><?php echo $npe_user_blacklist_exclude; ?></textarea>
  </div>
  </fieldset>
  <br />


  <fieldset class="options" style="border: #DDDDDD 1px solid;">
  <legend>Белый список</legend>
  <p>Белый список предназначен для того, чтобы пропускать ссылки на&nbsp;хорошие, известные&nbsp;нам или&nbsp;вам&nbsp;блоги. Плагин позволяет скрывать ссылки с&nbsp;имен комментаторов, оставляя только&nbsp;те, которые есть в&nbsp;белом&nbsp;списке. Вы&nbsp;сами будете решать, кого наградить ссылкой. Таким образом, даже если спамера еще&nbsp;нет в&nbsp;черном списке, смысла мусорить в&nbsp;вашем блоге для&nbsp;него не&nbsp;остается: ссылка-то все равно появится только у&nbsp;нормальных комментаторов.</p>
  <p>Здесь можно добавить выбранные вами домены в&nbsp;белый список. Если&nbsp;же вы&nbsp;не&nbsp;согласны с&nbsp;тем, что какой-то домен оказался в&nbsp;белом списке с&nbsp;нашего сервера, вы&nbsp;можете исключить&nbsp;его&nbsp;индивидуально.<br /><br /></p>
   
  <div style="width: 49%; font-size: 12px; float:left; padding-right: 16px;"> 
  <label for="npe_user_whitelist_include">Добавить эти домены в белый список, каждый с новой строки</label><br />
  <textarea tabindex="30" style="width: 98%" id="npe_user_whitelist_include" name="npe_user_whitelist_include" cols="40" rows="10"><?php echo $npe_user_whitelist_include; ?></textarea>
  </div>

  <div style="width: 49%; font-size: 12px; float:left;"> 
  <label for="npe_user_whitelist_exclude">Не пропускать эти домены по белому списку</label><br />
  <textarea tabindex="40" style="width: 98%" id="npe_user_whitelist_exclude" name="npe_user_whitelist_exclude" cols="40" rows="10"><?php echo $npe_user_whitelist_exclude; ?></textarea>
  </div>
  </fieldset>
  
  <br />
  
  <input tabindex="50" id="npe_update_from_server_on_admin_save" type="checkbox" value="1" name="npe_update_from_server_on_admin_save" <?php echo npe_checked("npe_update_from_server_on_admin_save", '1'); ?> />
  <label for="npe_update_from_server_on_admin_save">Забрать свежие списки с&nbsp;сервера при&nbsp;сохранении</label><br /><br />
  
  <input tabindex="51" id="total_scan" type="checkbox" value="1" name="total_scan" <?php echo npe_checked("npe_total_scan_on_admin_save", '1'); ?> />
  <label for="total_scan">Проверить все комментарии при&nbsp;сохранении</label><br /><br />

  <input tabindex="60" type="submit" name="submit" value="Обновить списки" style="font: bold 20px Arial; padding: 8px;" />
  <input type="hidden" name="hidden" id="hidden" value="true">
  </form>
  </div>
  
  <?php
}

function npe_print_options_page() {
// Эта функция выводит страницу настроек плагина

  // Если не вызывали процедуру обновления с серверов, то придется проверить соединение
  // с интернетом здесь, дабы блоггер видел, что непорядок есть, и хостера надобно пнуть  
  if ( !$_REQUEST['npe_update_from_server_on_admin_save'] ) {
    if ( !npe_can_get_file_from_server() ) {
      $connection_error_messages .= '<div id="error01" class="error fade"><p>Настройки хостинга не&nbsp;позволяют обновить базу с&nbsp;нашего сервера (пусть хостер включит allow_url_fopen)</p></div>';
    } elseif ( !npe_connection_is_possible() ) {
      $connection_error_messages .= '<div id="error01" class="error fade"><p>Скрипт не может соединиться с серверами в интернете (спросите у вашего хостера, что можно сделать)</p></div>';        
    }
    echo $connection_error_messages;
  }

  // Если только что сохраняли -- обновить опции
  if ( $_REQUEST['submit'] ) { npe_update_options(); }

  $npe_use_whitelist = get_option("npe_use_whitelist");
  $npe_only_white_comment_author_url = get_option("npe_only_white_comment_author_url");  

  $npe_use_blacklist = get_option("npe_use_blacklist");
  $npe_approve_blacklisted = get_option("npe_approve_blacklisted");
  
  $npe_autoupdate_every_day = get_option("npe_autoupdate_every_day");
  $npe_main_server_url = get_option("npe_main_server_url");
  $npe_backup_server_url = get_option("npe_backup_server_url");
  $npe_last_update = get_option("npe_last_update");
  $npe_next_update = get_option("npe_next_update");
  
  $npe_spammers_fuck_off = get_option("npe_spammers_fuck_off");
  $npe_spammers_fuck_off_text = get_option("npe_spammers_fuck_off_text");
  
  $npe_update_from_server_on_admin_save = get_option("npe_update_from_server_on_admin_save");
  $npe_total_scan_on_admin_save = get_option("npe_total_scan_on_admin_save");
  
  $npe_last_main_server_update_was_ok = get_option("npe_last_main_server_update_was_ok");
  $npe_last_backup_server_update_was_ok = get_option("npe_last_backup_server_update_was_ok");  

  if ($_REQUEST['total_scan']) {
    npe_check_comments();
?>

  <div id="message5" class="updated fade">
  <p>
    Комментарии проверены. <?php echo npe_print_parasites_count(npe_how_much_spam_trashed()); ?>
  </p>
  </div>

<?php    
  }
   
  ?>    
  
  <div class="wrap">
  
  <?php //// TODO -- убрать потом сообщение о бете ?> 
  <?php npe_print_beta(); ?>
    
  <h2>Novikov's Parasite Eliminator: настройки плагина</h2>
  <p>Этот плагин предназначен для избавления блогосферы от паразитов, всюду рассовывающих свои спамерские комментарии.</p>
  <p><?php echo npe_print_parasites_count(npe_how_much_spam_trashed()); ?></p>
  <form method="post">

  <fieldset class="options" style="border: #DDDDDD 1px solid;">
  <legend>Как обрабатывать комментарии</legend>
  <input tabindex="10" id="npe_use_whitelist" type="checkbox" value="1" name="npe_use_whitelist" <?php echo npe_checked("npe_use_whitelist", '1'); ?> />
  <label for="npe_use_whitelist">Проверять ссылки в комментариях по белому списку <em>(рекомендуется)</em></label>

  <br />
  <input tabindex="11" id="npe_only_white_comment_author_url" type="checkbox" value="1" name="npe_only_white_comment_author_url" <?php echo npe_checked("npe_only_white_comment_author_url", '1'); ?> />
  <label for="npe_only_white_comment_author_url">Ставить ссылку на комментатора только если она есть в белом списке <em>(рекомендуется)</em></label>

  <br /><br />
  <input tabindex="12" id="npe_use_blacklist" type="checkbox" value="1" name="npe_use_blacklist" <?php echo npe_checked("npe_use_blacklist", '1'); ?> />
  <label for="npe_use_blacklist">Проверять ссылки в комментариях по черному списку <em>(рекомендуется)</em></label>

  <br /><br />
  </fieldset>
  <br />

  <fieldset class="options" style="border: #DDDDDD 1px solid;">
  <legend>Что делать с комментариями из черного списка</legend>
  <input tabindex="20" id="hold_blacklisted" type="radio" value="0" name="npe_approve_blacklisted" <?php echo npe_checked("npe_approve_blacklisted", '0'); ?> />
  <label for="hold_blacklisted">Скрывать и отправлять модератору <em>(рекомендуется)</em></label>
  <br />
  <input tabindex="21" id="npe_approve_blacklisted" type="radio" value="spam" name="npe_approve_blacklisted" <?php echo npe_checked("npe_approve_blacklisted", 'spam'); ?> />
  <label for="npe_approve_blacklisted">Сразу отправлять в спам</label>
  <br /><br />
  </fieldset>
  <br />

  <fieldset class="options" style="border: #DDDDDD 1px solid;">
  <legend>Настройки обновлений</legend>
  
  <input tabindex="31" id="npe_autoupdate_every_day" type="checkbox" value="1" name="npe_autoupdate_every_day" <?php echo npe_checked("npe_autoupdate_every_day", '1'); ?> />
  <label for="npe_autoupdate_every_day">Автоматически обновлять списки с сервера каждый день <em>(рекомендуется)</em></label>
  <br /><br />

  <label for="npe_main_server_url">Основной сервер обновлений <?php if ( !$npe_last_main_server_update_was_ok ) { echo '<span style="color:#FF0000;">(последний раз с него не удалось скачать списки)</span>'; } ?></label><br />
  <input tabindex="32" id="npe_main_server_url" size="80" width="100%" type="text" value="<?php echo $npe_main_server_url; ?>" name="npe_main_server_url" /><br />
  <small>По умолчанию <span onclick="document.getElementById('npe_main_server_url').value=this.innerHTML" style="cursor:pointer;text-decoration:none;border-bottom:1px dashed #000"><?php echo MAIN_SERVER; ?></span></small>
  <br /><br />
 
  <label for="npe_backup_server_url">Запасной сервер обновлений <?php if ( !$npe_last_backup_server_update_was_ok ) { echo '<span style="color:#FF0000;">(последний раз с него не удалось скачать списки)</span>'; } ?></label><br />
  <input tabindex="33" id="npe_backup_server_url" size="80" width="100%" type="text" value="<?php echo $npe_backup_server_url ?>" name="npe_backup_server_url" /><br />
  <small>По умолчанию <span onclick="document.getElementById('npe_backup_server_url').value=this.innerHTML" style="cursor:pointer;text-decoration:none;border-bottom:1px dashed #000"><?php echo BACKUP_SERVER; ?></span></small>
  <br /><br />
  </fieldset>
  <br />
 
  <fieldset class="options" style="border: #DDDDDD 1px solid;">
  <legend>Маленький привет спамерам</legend>
  <input tabindex="40" id="npe_spammers_fuck_off" type="checkbox" value="1" name="npe_spammers_fuck_off" <?php echo npe_checked("npe_spammers_fuck_off", '1'); ?> />
  <label for="npe_spammers_fuck_off">Показывать спамерам маленький привет, чтобы больше не&nbsp;лезли <em>(рекомендуется, эти гоблины дрессируемы)</em>:</label>
  <?php echo $npe_spammers_fuck_off_text; ?>
  </fieldset>
  <br />

  <input tabindex="40" id="npe_update_from_server_on_admin_save" type="checkbox" value="1" name="npe_update_from_server_on_admin_save" <?php echo npe_checked("npe_update_from_server_on_admin_save", '1'); ?> />
  <label for="npe_update_from_server_on_admin_save">Забрать свежие списки с сервера при сохранении</label><br /><br />

  <input tabindex="51" id="total_scan" type="checkbox" value="1" name="total_scan" <?php echo npe_checked("npe_total_scan_on_admin_save", '1'); ?> />
  <label for="total_scan">Проверить все комментарии при&nbsp;сохранении</label><br /><br />

  <input tabindex="50" type="submit" name="submit" value="Сохранить настройки" style="font: bold 20px Arial; padding: 8px;" />
  <input type="hidden" name="hidden" id="hidden" value="true">
  
  </form>
  </div>
<?php
}

function npe_checked ($option, $control_value) {
  $current_value = get_option($option);
  if ( $current_value == $control_value ) { 
    return 'checked';
  } else {
    return false;
  }
}

function npe_update_options() {
// Эта функция записывает в базу сохраненные настройки плагина
  $updated = false;
  // Убеждаемся, что форма пришла
  if ($_REQUEST['hidden']) {
    
    // Сохраняем опции в настройках
    update_option("npe_use_whitelist", 0 + $_REQUEST['npe_use_whitelist']);
    update_option("npe_only_white_comment_author_url", 0 + $_REQUEST['npe_only_white_comment_author_url']);
    
    update_option("npe_approve_blacklisted", $_REQUEST['npe_approve_blacklisted']);
    update_option("npe_use_blacklist", 0 + $_REQUEST['npe_use_blacklist']);
    
    update_option("npe_autoupdate_every_day", 0 + $_REQUEST['npe_autoupdate_every_day']);
    
    if ( !$_REQUEST['npe_main_server_url'] ) { 
      $_REQUEST['npe_main_server_url'] = MAIN_SERVER;
      $additional_messages .= '<div id="message2" class="updated fade"><p>Выбран основной сервер по умолчанию</p></div>'; 
    }
    if ( !$_REQUEST['npe_backup_server_url'] ) { 
      $_REQUEST['npe_backup_server_url'] = BACKUP_SERVER;
      $additional_messages .= '<div id="message3" class="updated fade"><p>Выбран запасной сервер по умолчанию</p></div>'; 
    }
    
    // Сохраняем адреса серверов обновлений, и если адрес поменялся -- то снимаем с него пометку о недоступности
    if ( update_option("npe_main_server_url", $_REQUEST['npe_main_server_url']) ) { update_option("npe_last_main_server_update_was_ok", 1); }
    if ( update_option("npe_backup_server_url", $_REQUEST['npe_backup_server_url']) ) { update_option("npe_last_backup_server_update_was_ok", 1); }
    
    update_option("npe_spammers_fuck_off", 0 + $_REQUEST['npe_spammers_fuck_off']);

    $npe_update_from_server_on_admin_save = 0 + $_REQUEST['npe_update_from_server_on_admin_save'];
    update_option("npe_update_from_server_on_admin_save", $npe_update_from_server_on_admin_save);  
    
    $npe_total_scan_on_admin_save = 0 + $_REQUEST['total_scan'];
    update_option("npe_total_scan_on_admin_save", $npe_total_scan_on_admin_save);  
        
    $updated = true;
  }

  // Если произошло обновление, выводим сообщение об успехе, если сбой -- об ошибке
  if ($updated) {
    echo '<div id="message" class="updated fade">';
    echo '<p>Настройки сохранены</p>';
    echo '</div>';
    echo $additional_messages;
  } else {
    echo '<div id="message" class="error fade">';
    echo '<p>Не удалось сохранить настройки</p>';
    echo '</div>';
    echo $additional_messages;
  }
  
  if ( $npe_update_from_server_on_admin_save ) {  // Если решено получить свежие списки с сервера при сохранении

    // Получаем обновление с сервера, если все ок, то снова собираем списки и выводим сообщение
    if ( !$failure = npe_update_from_server() ) {
      npe_merge_lists();
      echo '<div id="message4" class="updated fade"><p>Свежие списки с сервера получены</p></div>';
    } else {  // Если обновиться с сервера не удалось -- честно признаемся
      echo '<div id="message4" class="error fade"><p>' . $failure . '</p></div>';
    }
  }
}


////////////////////////////// Функции обработки ///////////////////////////////

function npe_check_comments($comment_ID = false) {
// Эта функция стартует проверку комментариев по черному и белому спискам. 
// Ее мы ставим в качестве фильтра сразу после записи комментария в базу. 
  npe_perform_whitelist_check($comment_ID);
  npe_perform_blacklist_check($comment_ID);
}

function npe_perform_whitelist_check($comment_ID = false) {
// Эта функция проходится по комментариям и проверяет url комментатора на соответствие
// белому списку и помечает комментарии соответственно, возвращая количество затронутых записей 
  
  // Если в настройках отключено сканирование по белому списку, то выходим
  if ( get_option("npe_use_whitelist") == '0' ) { return false; }
  
  global $wpdb;
  $prefix = $wpdb->prefix;
  $comments_table = $prefix . 'comments';
  $process_table = $prefix . 'npe_whitelist';
  
  // Если на входе функции указан ID комментария, то обрабатываем не всю таблицу,
  // а только этот комментарий, чтобы ресурсы сэкономить
  if ( $comment_ID ) {
    $specific = ' AND `' . $comments_table . '`.`comment_ID` = ' . $comment_ID;  
  }
  
  // Обрабатываем комментарии  
  $query = ' UPDATE `' . $comments_table . '`, `' . $process_table . '` 
    	SET `' . $comments_table . '`.`npe_whitelist` = 1
      WHERE (`' . $comments_table . '`.`comment_author_url` LIKE CONCAT( \'http://\' , `' . $process_table . '`.`url` , \'%\' )
      OR     `' . $comments_table . '`.`comment_author_url` LIKE CONCAT( \'http://www.\' , `' . $process_table . '`.`url` , \'%\' ))' . $specific . ';';

  // Увеличиваем счетчик помеченных комментариев и возвращаем количество затронутых записей
  $count = $wpdb->query( $query );
  update_option("npe_white_counter", get_option("npe_white_counter") + $count);
  return $count;
}

function npe_perform_blacklist_check ($comment_ID = false) {
// Эта функция проходится по комментариям и проверяет url комментатора и текст комментария на соответствие
// черному списку и помечает комментарии соответственно, возвращая количество затронутых записей

  // Если в настройках отключено сканирование по черному списку, то выходим
  if ( get_option("npe_use_blacklist") == '0' ) { return false; }
   
  global $wpdb;
  $prefix = $wpdb->prefix;
  $comments_table = $prefix . 'comments';
  $process_table = $prefix . 'npe_blacklist';
  
  // Если на входе функции указан ID комментария, то обрабатываем не всю таблицу,
  // а только этот комментарий, чтобы ресурсы сэкономить
  if ( $comment_ID ) {
    $specific = ' AND `' . $comments_table . '`.`comment_ID` = ' . $comment_ID;  
  }
  
  // Если комментарий уже был помечен как спам, при проверке не надо повторно вытаскивать его на модерацию
  $notspam = ' AND `' . $comments_table . '`.`comment_approved` <> "spam"';
  
  // Определяем, как будем метить комментарий из черного списка
  $mark = ', `' . $comments_table . '`.`comment_approved` = "' . get_option("npe_approve_blacklisted") . '"';
  
  // Обрабатываем комментарии      
  $query = ' UPDATE `' . $comments_table . '`, `' . $process_table . '` 
    	SET `' . $comments_table . '`.`npe_blacklist` = 1 ' . $mark . '
    	WHERE (`' . $comments_table . '`.`comment_author_url` LIKE CONCAT( \'%\' , `' . $process_table . '`.`url` , \'%\' )
      OR     `' . $comments_table . '`.`comment_content` LIKE CONCAT( \'%\' , `' . $process_table . '`.`url` , \'%\' ))' . $specific . $notspam . ';';

  // Увеличиваем счетчик помеченных комментариев и возвращаем количество затронутых записей
  $count = $wpdb->query( $query );
  update_option("npe_black_counter", get_option("npe_black_counter") + $count);
  
  // Если при проверке были затронуты какие-то записи, то обновляем закешированные счетчики комментариев
  if ( $count > 0 ) {npe_update_comment_count();}
  
  return $count;
}

function npe_filter_comment_author_url($url) {
// Эта функция встает в качестве фильтра при выводе comment_author_url
// Если комментарий не помечен как "белый", то URL комментатора скрывается 

///// TODO -- сделать возможность открывать урлы авторов, но при этом направлять все через редирект
///// во избежание коллизий с уже действующими плагинами ставить низкий приоритет и проверять, не содержит ли
///// ссылка собственного домена (если содержит, то уже может быть заредиректено)

  if ( get_option("npe_only_white_comment_author_url") === '0' OR is_admin() OR current_user_can('level_10') ) { return $url; }
  global $comment;
  if ( !npe_comment_is_white($comment) ) {
    return 'http://';
  }
  return $url;  
}

function npe_comment_is_white($comment) {
// Эта функция проверяет, помечен ли комментарий как "белый"
  if ( $comment->npe_whitelist == 1 ) { return true; }
  return false; 
}

function npe_comment_is_black($comment) {
// Эта функция проверяет, помечен ли комментарий как "черный"
  if ( $comment->npe_blacklist == 1 ) { return true; }
  return false; 
}

function npe_set_comment_status($comment_ID, $comment_status) {
// Сейчас эта функция снимает пометку "черный" с комментария, если админ вручную присваивает ему статус "одобрено"

  //Если комментарию присваивается какой-то другой статус, кроме "одобрено", то мы ничего не меняем, выходим из функции  
  if ( $comment_status <> 'approve' ) {return false;}

  // Если же комментарию присваивается статус "одобрено", то мы продолжаем его обрабатывать
  global $wpdb;
  $prefix = $wpdb->prefix;
  $comments_table = $prefix . 'comments';
  $process_table = $prefix . 'npe_blacklist';
  
  // Обрабатываем комментарии      
  $query = ' UPDATE `' . $comments_table . '`, `' . $process_table . '` 
    	SET `' . $comments_table . '`.`npe_blacklist` = 0
    	WHERE   `' . $comments_table . '`.`comment_ID` = ' . $comment_ID . ';';

  // Увеличиваем счетчик помеченных комментариев и возвращаем количество затронутых записей
  $count = $wpdb->query( $query );
  return $count;
}


////////////////////////////// Функции обновления //////////////////////////////

function npe_record_update_dates($successfully_updated=true) {
// Эта функция записывыает дату последнего успешного обновления,
// а также назначает дату следующего обновления
  if ( $successfully_updated ) {
    update_option("npe_last_update", time());
    update_option("npe_next_update", time()+86400); // Если обновились успешно, следующая попытка через сутки
  } else { 
    update_option("npe_next_update", time()+10800); // Если не обновились, следующая попытка через три часа
  }
}

function npe_time_to_update() {
// Эта функция определяет, пришло ли время обновлять базу
  return ( time() > get_option("npe_next_update") ); 
}

function npe_update_sheduler() {
// Эта функция ставится в качестве фильтра при выводе поста, она вызывает
// проверку необходимости обновления и, если оно нужно, то обновляет.
  if( npe_time_to_update() ) { return npe_perform_update(); }
  return false;
}

function npe_put_list_to_database($array, $table, $field='url') {
// Эта функция получает список из массива $array и замещает им таблицу $table

  global $wpdb;
  
  // Удаляем все записи из таблицы  
  $query = ' DELETE FROM `' . $wpdb->prefix . $table . '` ; ';
  $wpdb->query( $query );
  
  // А теперь в пустую уже таблицу вставляем полученное с сервера
  npe_include_list_to_database($array, $table, $field);
}


function npe_include_list_to_database($array, $table, $field='url') {
// Эта функция получает список из массива $array и добавляет его в таблицу $table

  // Фильтруем массив, в т.ч. удаляем пустые значения, на всякий случай
  $array = npe_filter_array($array);

  // Если массив пришел пустой, то ничего удалять из базы не надо,
  // а надо выйти из функции, чтобы не было ошибки у implode
  // Эту проверку пришлось переместить после npe_filter_array, чтобы устранить
  // баг, при котором в запрос проникало пустое значение, и в результате
  // в таблице оказывалось значение '', отмечавшее все комментарии. 
  if ( empty($array) ) { return false; }

  // Получаем из массива список значений
  $values = '(\'' . implode('\'), (\'', $array) . '\')';
  
  global $wpdb;
  
  // Наполняем таблицу записями из массива
  $query = ' INSERT IGNORE 
                INTO `' . $wpdb->prefix . $table . '` (`' . $field . '`)
                VALUES ' . $values . '; '; 
  $wpdb->query( $query ); 
}

function npe_exclude_list_from_database($array, $table, $field='url') {
// Эта функция получает список из массива $array и удаляет его из таблицы $table
  
  // Фильтруем массив, в т.ч. удаляем пустые значения, на всякий случай
  $array = npe_filter_array($array);

  // Если массив пришел пустой, то ничего удалять из базы не надо,
  // а надо выйти из функции, чтобы не было ошибки у implode
  // Эту проверку пришлось переместить после npe_filter_array, чтобы устранить
  // баг, при котором в запрос проникало пустое значение, и в результате
  // в таблице оказывалось значение '', отмечавшее все комментарии. 
  if ( empty($array) ) { return false; }

  
  // Получаем из массива список значений
  $values = '(\'' . implode('\', \'', $array) . '\')';

  global $wpdb;
  
  // Удаляем из таблицы записи, которые входят в список исключений  
  $query = ' DELETE FROM `' . $wpdb->prefix . $table . '` 
                WHERE `' . $field . '` IN ' . $values. '; ';
  $wpdb->query( $query );
}

function npe_filter_array($lines) {
// Эта функция фильтрует массив, чтобы не было ошибок, оставляя только уникальные
// доменные имена, а все остальное -- убирает

  // Если массив на входе оказался пуст, возвращаем пустую строку, а то дальше будут ошибки
  if ( empty($lines) ) {return '';}

  // Причесываем каждый элемент массива и отбираем подходящее
  foreach ($lines as $line) {
    // Убираем пробелы в начале и в конце строки
    $line = trim($line);
    // Нормализуем URL
    $line = npe_normalize_url($line);
    // Если соответствует формату доменного имени -- вносим в формируемый список
    if ( npe_is_domain_name($line) ) { $result[] = $line; }
  } 
  // Если массив оказался пуст, возвращаем пустую строку, а то дальше будут ошибки
  if ( empty($result) ) {return '';}
  // Оставляем только уникальные элементы -- массив готов
  $result = array_unique($result);
  return($result);
}

function npe_get_file_from_server($url) {
// Эта функция получает файл с сервера при помощи file, а если allow_url_fopen запрещено, то через CURL
// Если ни один способ не сработает, тогда возвращает false
  if ( npe_url_fopen_is_allowed() AND npe_set_user_agent() ) {
    return @file($url);
  }
  elseif ( npe_curl_is_allowed() ) {
    return npe_get_file_via_curl($url);
  }
  else {
    return false;
  }
}

function npe_get_file_via_curl($url) {
// Эта функция получает файл с сервера через CURL
  $ch = curl_init();
  $timeout = 3; // Дадим серверу 3 секунды продуплиться
  
  // Сперва, чтобы можно было видеть, где плагин установлен, сделаем USER_AGENT
  $user_agent = 'Parasite Eliminator /Version ' . VERSION . ' /Base ' . get_option("npe_current_version")  . ' /Updated ' . date ('d.m.Y H:i', get_option("npe_last_update")) . ' /Trashed ' . npe_how_much_spam_trashed() .  ' /Wordpress ' . get_bloginfo('version') . ' CURL ON ' . getenv('SERVER_NAME');
  curl_setopt ($ch, CURLOPT_USERAGENT, $user_agent);
  
  curl_setopt ($ch, CURLOPT_URL, $url);
  curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
  ob_start();
  curl_exec($ch);
  curl_close($ch);
  $file_contents = ob_get_contents();
  ob_end_clean();
  $lines = explode("\n", $file_contents);
  return $lines;
}

function npe_update_from_server() {
// Эта функция получает апдейт с сервера и помещает его в базу данных

  // Проверяем, возможно ли обновление с сервера при текущих настройках PHP
  // Если окажется, что allow_url_fopen запрещено, а CURL недоступен, то обновить не получится
  
  if ( !npe_can_get_file_from_server() ) { 
    npe_record_update_dates(false);
    return 'Настройки хостинга не&nbsp;позволяют обновить базу с&nbsp;нашего сервера (пусть хостер включит allow_url_fopen или поставит cURL)';
  }

  // Получаем адреса основного и запасного серверов обновлений
  $main_server_url = get_option("npe_main_server_url");
  $backup_server_url = get_option("npe_backup_server_url");
  
  $first_try = $main_server_url;
  $second_try = $backup_server_url;
  
  // Вспоминаем, удачно ли завершилась последняя попытка обновиться с основного сервера
  $npe_last_main_server_update_was_ok = get_option("npe_last_main_server_update_was_ok");
  $npe_last_backup_server_update_was_ok = get_option("npe_last_backup_server_update_was_ok");
  
  // Если предыдущая попытка обновиться с основного сервера не удалась, а о запасном неизвестно,
  // что он не работал, в этот раз начнем обновление с запасного сервера
  if ( !$npe_last_main_server_update_was_ok && $npe_last_backup_server_update_was_ok ) { 
    $first_try = $backup_server_url;
    $second_try = $main_server_url;
  }
  
  // Получаем файл с основного сервера и записываем его в массив
  $lines = npe_get_file_from_server($first_try);
  
  // Если файл с первого сервера не годится, запоминаем это и пробуем взять с резервного
  if ( !npe_update_is_valid($lines) ) {
    // Смотрим, какому серверу приписать неудачу первой попытки
    if ( $first_try == $main_server_url ) { 
      update_option("npe_last_main_server_update_was_ok", 0); 
    } else { 
      update_option("npe_last_backup_server_update_was_ok", 0); 
    } 
    
    $lines = npe_get_file_from_server($second_try);

    // Если файл со второго сервера тоже не годится, закругляемся
    if ( !npe_update_is_valid($lines) ) {
      // Смотрим, какому серверу приписать неудачу второй попытки
      if ( $second_try == $main_server_url ) { 
        update_option("npe_last_main_server_update_was_ok", 0); 
      } else { 
        update_option("npe_last_backup_server_update_was_ok", 0); 
      } 
      npe_record_update_dates(false);

      // Если не удалось соединиться с серверами, проверяем причину этого безобразия
      $npe_connection_is_possible = npe_connection_is_possible();
      update_option("npe_connection_is_possible", $npe_connection_is_possible);
      if ( !$npe_connection_is_possible ) { return 'Скрипт не может соединиться с серверами в интернете (спросите у вашего хостера, что можно сделать)'; }

      return 'Обновления на серверах временно недоступны';
      
       
    } else {  // Но если он годится, тогда его и используем, и запомним успех соответствующего сервера
      $success = $second_try;
    } 
  } else {  // Но если он годится, тогда его и используем, и запомним успех соответствующего сервера
    $success = $first_try;
  }
  
  // Определяем, какой сервер дал нам обновление, и запоминаем это  
  if ( $success == $main_server_url ) {
    update_option("npe_last_main_server_update_was_ok", 1); 
  } else {
    update_option("npe_last_backup_server_update_was_ok", 1);
  }

  // Если файл нормальный, пробуем добавить строки в базу данных
  $result = npe_put_server_update_to_database($lines);
  if ( $result == 1 ) { return 'Обновление с&nbsp;сервера не&nbsp;требуется, базы в актуальном состоянии'; }
  npe_record_update_dates(true);
  
  // И код ошибки возвращаем false
  return false;
}

function npe_put_server_update_to_database($lines) {
// Эта функция вносит обновление с сервера в базу данных

  // Разбираем массив по строкам
  foreach ($lines as $line) {
    // Убираем пробелы в начале строки
    $line = ltrim($line);

    // Если в начале строки VERSION -- это версия базы
    if ( strpos($line, 'VERSION') === 0 ) {
      // Убираем VERSION и пробелы, получаем номер версии базы на сервере
      $line = str_replace('VERSION', '', $line);
      $version = trim($line);

      // Если версия на сервере такая же, как в блоге, или младше то не нужно мучать базу
      // Выходим и возвращаем код 1 
      if ( $version <= get_option("npe_current_version") ) { return 1; } 
    }
              
    // Если в начале строки минус -- это в черный список
    if ( strpos($line, '-') === 0 ) {

      // Убираем минус
      $line = ltrim($line, '-');

      // Нормализуем URL
      $line = npe_normalize_url($line);
      
      // Если соответствует формату доменного имени -- вносим в черный список
      if ( npe_is_domain_name($line) ) { $addtoblacklist[] = $line; }
    }
    
    // Если в начале строки плюс -- это в белый список
    if ( strpos($line, '+') === 0 ) {

      // Убираем плюс
      $line = ltrim($line, '+');

      // Нормализуем URL
      $line = npe_normalize_url($line);
      // Если соответствует формату доменного имени -- вносим в белый список
      if ( npe_is_domain_name($line) ) { $addtowhitelist[] = $line; }
    }
  }
  
  // Создаем из этого заново черный и белый списки
  npe_put_list_to_database($addtoblacklist, 'npe_server_blacklist');
  npe_put_list_to_database($addtowhitelist, 'npe_server_whitelist');
  
  // Обновляем версию последней базы
  update_option("npe_current_version", $version);
  
  // Возвращаем код ошибки false
  return false;
}

function npe_update_is_valid($lines) {
// Эта функция проверяет, нормально ли скачался файл обновлений с сервера
  
  if ( empty ($lines) ) { return false; }  // Не удалось соединиться с серверами

  $open = trim($lines[0]);
  $close = trim($lines[count($lines) - 1]);
 
  // Проверяем по первой строке, обновление ли это или какой-то левый файл, например, ошибка 404
  if ( $open != 'Parasite Eliminator Update' ) { return false; }  // Первая строка не наша -- файл не тот
      
  // Проверяем по первой и последней строкам, полностью ли скачался файл
  if ( count($lines) < 5 ) { return false; }  // Меньше пяти строк -- файл точно не докачался
  if ( $close != $open ) { return false; }  // Последняя строка не наша -- файл не докачался

  // Если все окей, так и говорим
  return true;
}

function npe_filter_user_list($list) {
// Эта функция расправляется с пользовательским списком: причесывает, проверяет,
// оставляет только уникальные доменные имена и возвращает результат в виде строки,
// пригодной для сохранения в опциях "Вордпресса"

  // Разбиваем строку на элементы массива по символам перевода строки
  $lines = explode("\n", $list);

  // Фильтруем массив, оставляя в нем только доменные имена.
  // Удаляем пустые значения, которые приводили к багу в бете 0.74
  $result = npe_filter_array($lines);

  // Если массив оказался пуст, возвращаем пустую строку, а то дальше будут ошибки
  if ( empty($result) ) {return '';}

  // Сортируем наш массив по алфавиту
  sort($result);
 
  // Получаем строку для сохранения
  $string = implode("\n", $result);

  return $string;
}

function npe_merge_lists() {
// Эта функция берет таблицы обновлений, скачанных с сервера, помещает их в основную таблицу,
// затем добавляет пользовательские записи и удаляет пользовательские исключения
  
  global $wpdb;
  
  // Сперва сбросим все, что есть в основных таблицах
  $query = ' DELETE FROM `' . $wpdb->prefix . 'npe_blacklist` ; ';
  $wpdb->query( $query );
  
  $query = ' DELETE FROM `' . $wpdb->prefix . 'npe_whitelist` ; ';
  $wpdb->query( $query );
  
  // Затем перенесем содержимое последнего скачанного обновления с сервера
  $query = ' INSERT IGNORE 
                INTO `' . $wpdb->prefix . 'npe_blacklist` (`url`)
                SELECT `url` FROM `' . $wpdb->prefix . 'npe_server_blacklist`; '; 
  $wpdb->query( $query );
  
  $query = ' INSERT IGNORE 
                INTO `' . $wpdb->prefix . 'npe_whitelist` (`url`)
                SELECT `url` FROM `' . $wpdb->prefix . 'npe_server_whitelist`; '; 
  $wpdb->query( $query );

  // Затем внесем пользовательские списки
  $npe_user_blacklist_include = get_option("npe_user_blacklist_include");
  $npe_user_whitelist_include = get_option("npe_user_whitelist_include");

  //TODO -- Делаем так, чтобы URL нашего блога всегда был у нас в белом списке
  $npe_user_whitelist_include .= "\n" . npe_normalize_url(get_option("siteurl"));

  npe_include_list_to_database(explode ("\n", $npe_user_blacklist_include), 'npe_blacklist');
  npe_include_list_to_database(explode ("\n", $npe_user_whitelist_include), 'npe_whitelist');

  // И, наконец, удалим из получившегося пользовательские исключения
  $npe_user_blacklist_exclude = get_option("npe_user_blacklist_exclude");
  $npe_user_whitelist_exclude = get_option("npe_user_whitelist_exclude");

  //TODO -- Делаем так, чтобы URL нашего блога никогда не был у нас в черном списке
  $npe_user_blacklist_exclude .= "\n" . npe_normalize_url(get_option("siteurl"));

  npe_exclude_list_from_database(explode ("\n", $npe_user_blacklist_exclude), 'npe_blacklist');
  npe_exclude_list_from_database(explode ("\n", $npe_user_whitelist_exclude), 'npe_whitelist');
}

function npe_perform_update() {
// Эта функция обновляет списки с сервера, затем добавляет в них пользовательские URL
// и убирает исключения
  npe_update_from_server();
  npe_merge_lists();
}


/////////////////////////// Вспомогательные функции //////////////////////////// 

function npe_normalize_url($url) {
// Нормализуем URL -- приводим URL к виду blog.site.ru 
    
    // Готовим строку к обработке функцией parse_url()
    $url = trim($url);

    // Если строка оказалась пустой, то выходим из функции, чтобы не было ошибок дальше
    if ( empty($url) ) { return ''; }
    $url = str_replace('http://','',$url);
    $url = 'http://'.$url;
    
    // Получаем из URL домен
    $parsed = parse_url($url);
    $url = $parsed[host];
    
    // Переводим все в строчные
    $url = strtolower($url);
    
    // Убираем www и пробелы
    $url = str_replace('www.', '', $url);
    $url = str_replace(' ', '', $url);
    
    return $url;
}

function npe_is_domain_name($url) {
// Эта функция проверяет, является ли строка доменным именем
  if ( $url == '' ) { return false; } // Это для исправления бага, когда регулярка пропускает в качестве URL пустую строку
  return preg_match('/^(?:(?:w{3}\.)?(?:[a-z0-9_-]*?\.){1,2}[a-z]{2,4}(?:\/[a-z0-9_-]*?)*?(?:\.[a-z~]{1,5})?\??(?:[a-z_-]+?=[a-z0-9_\%-]+?)*?(?:\&[a-z]=[a-z0-9_\%-]*)*?)?$/i', $url, $out);
}

function npe_field_exists($table, $field) {
// Эта функция проверяет, существует ли поле $field в таблице $table
  global $wpdb;
  $query = ' DESCRIBE `' . $wpdb->prefix . $table . '` `' . $field . '`';
  return $wpdb->query( $query );
}

function npe_connection_is_possible() {
// Эта функция проверяет, возможно ли соединиться с внешним сервером
// Пробуем соединиться с более-менее надежными серверами google.com и ya.ru
  if ( (gethostbyname('ya.ru')<>'ya.ru') OR (gethostbyname('google.com')<>'google.com') ) { return true; }
  return false;
}

function npe_url_fopen_is_allowed() {
// Эта функция проверяет, разрешен ли allow_url_fopen, перед этим пытается включить эту опцию
  @ini_set('allow_url_fopen', 1);
  return ini_get('allow_url_fopen');
}

function npe_set_user_agent() {
// Эта функция устанавливает нужный нам для статистики USER_AGENT, затем проверяет, установилось ли,
// и если установилось, возвращает TRUE, в противном случае -- FALSE
  $user_agent = 'Parasite Eliminator /Version ' . VERSION . ' /Base ' . get_option("npe_current_version")  . ' /Updated ' . date ('d.m.Y H:i', get_option("npe_last_update")) . ' /Trashed ' . npe_how_much_spam_trashed() .  ' /Wordpress ' . get_bloginfo('version') . ' FILE ON ' . getenv('SERVER_NAME');
  @ini_set('user_agent', $user_agent);
  if ( ini_get('user_agent') == $user_agent ) { return true; }
}

function npe_curl_is_allowed() {
// Эта функция проверяет, доступны ли нам основные функции CURL
  if( !function_exists("curl_init") &&
      !function_exists("curl_setopt") &&
      !function_exists("curl_exec") &&
      !function_exists("curl_close") ) return false;
  else return true;
}

function npe_can_get_file_from_server() {
// Эта функция проверяет, сможем ли мы получить файл с другого сервера
  if ( npe_url_fopen_is_allowed() OR npe_curl_is_allowed() ) {
    return true;
  }
  else return false;
}

function npe_update_comment_count() {
// Эта функция обновляет закешированное количество комментариев ко всем постам
  global $wpdb;
  $query = 'UPDATE `' . $wpdb->prefix . 'posts` 
    SET `comment_count` = 
    (SELECT COUNT(`comment_post_ID`) 
    FROM `' . $wpdb->prefix . 'comments` 
    WHERE `comment_approved` = \'1\' 
    AND `' . $wpdb->prefix . 'posts`.`ID` = `' . $wpdb->prefix . 'comments`.`comment_post_ID`)';
  // Возвращаем количество затронутых записей
  return $wpdb->query( $query );
}



////////////////////////// Функции для контроля версий /////////////////////////

function npe_get_recorded_version() {
// Эта функция возвращает номер сохраненной в опциях при последней установке версии плагина
  return get_option(npe_version);
}

function npe_get_recorded_db_version() {
// Эта функция возвращает номер сохраненной в опциях при последней установке версии структуры данных
  return get_option(npe_db_version);
}

// TODO -- потом сделаем тут функцию для уведомлений, касающихся версий плагина
function npe_print_beta() {
// Эта функция выводит сообщение о бета-версии
  echo '<p style="color: #FF0000;"><small>Это бета-версия (почти RC) ' . VERSION . ', <a href="http://parasite-eliminator.ru/beta/">проверяйте страничку для бета-тестеров</a> почаще.</small></p>';
}

function npe_reinstall_needed() {
// Эта функция проверяет, требуется ли реинсталляция плагина (чтобы обновить базу данных и опции)
  if( npe_get_recorded_db_version() <> DB_VERSION ) { return true; }
}

// TODO -- Написать функцию, которая автоматом обновит структуру данных при необходимости


////////////////////////// Прикольные функции ///////////////////////// 

function npe_how_much_spam_trashed() {
// Эта функция возвращает количество отловленных с момента установки плагина 
// спамерских комментариев, чтобы можно было выводить сообщения вроде 
// "Novikov's Parasite Eliminator зохавал 1400 спамерских каментов"
  return get_option("npe_black_counter");
}

function npe_print_parasites_count($number) {
// Выводим количество комментариев по-русски
  
  $blah = 'Пока ни одного паразита в&nbsp;комментариях не&nbsp;замечено.';

	if ($number == 1) {
		$blah = 'Первый&nbsp;паразит пойман и расстрелян!';
	} elseif (($number%10 == 1) && ($number > 20)) {
		$blah = 'Уже <strong>' . $number . '&nbsp;паразит</strong> пойман и повешен c&nbsp;момента установки Parasite Eliminator!';
	} elseif (($number%10  >= 2) && ($number%10  <= 4) && (($number > 21) OR ($number < 10) )) {
		$blah = 'Целых <strong>' . $number . '&nbsp;паразита</strong> поймано и четвертовано c&nbsp;момента установки Parasite Eliminator!';
	} elseif ($number%10  >= 5) {
		$blah = 'Уже <strong>' . $number . '&nbsp;паразитов</strong> поймано и замучано c&nbsp;момента установки Parasite Eliminator!';
	} elseif ($number != 0) {
		$blah = 'Целых <strong>' . $number . '&nbsp;паразитов</strong> поймано и придушено c&nbsp;момента установки Parasite Eliminator!';
	}

	return $blah;
}

function npe_spammer_fuck_off($comment_content) {
// Эта функция после оставления спамерского комментария выводит сообщение

  global $comment;
  
  // Если комментарий не помечен нами как спамерский, то ничего делать с ним не надо
  if ( ! npe_comment_is_black($comment) ) {
    return $comment_content;
  }
  // Если же комментарий помечен нами как спамерский, тогда разбираемся дальше
  // Если мы в админке, надо показать админу текст комментария, но дать знать, что это спам 
  elseif ( is_admin() ) {
    $result = '<p style="color: #FF0000; font-weight: bold;">Блоггер! Этот комментарий содержит ссылки на спамерские сайты, найденные плагином <a href="http://parasite-eliminator.ru">Parasite Eliminator</a>.</p>';
    $result .= $comment_content;
    return $result;
  }
  // Если комментарий помечен как спам, а мы не в админке, тогда, если включена соответствующая опция, выводим спамеру сообщение
  elseif ( get_option("npe_spammers_fuck_off") ) {
    return get_option("npe_spammers_fuck_off_text");
  }
  
  return $comment_content;
}

/////////////// Дальше ничего не стирать и вообще не трогать!!! ////////////////

?>
