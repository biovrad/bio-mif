<?php
/*
Plugin Name: ОднаКнопка
Plugin URI: http://odnaknopka.ru/
Description: Одна кнопка для всех сервисов закладок - Русский AddThis - Самая простая кнопка
Author: Антон Лобовкин <anton@lobovkin.ru>
Version: 1.1
*/ 
class widget_odnaknopka{
function widget_odnaknopka() {
add_filter('the_content',array(&$this,odnaknopka));
}
function odnaknopka($content) { 
$url=get_permalink();
$title=get_the_title();
return $content.'<script type="text/javascript" src="http://odnaknopka.ru/wp/ok1.utf8.js"></script><div class="knopa" ><script type="text/javascript">okbm("'.htmlspecialchars($url).'","'.htmlspecialchars($title).'")</script></div>';
}
}
$widget&=new widget_odnaknopka();
?>