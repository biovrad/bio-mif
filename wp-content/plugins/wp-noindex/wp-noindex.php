<?php
/*
Plugin Name: WP-Noindex
Plugin URI: http://www.wordpressplugins.ru/seo/wp-noindex.html
Description: Заключает ссылки в комментариях в теги &lt;noindex&gt;&lt;/noindex&gt;, что запрещает их индексацию Яндексом.
Author: Flector
Author URI: http://www.wordpressplugins.ru
Version: 1.00
*/ 

function wp_noindex($comment) {
	return str_replace('<a ', '<noindex><a ', $comment);
}
function wp_noindex2($comment) {
	return str_replace('</a>', '</a></noindex>', $comment);
}

add_filter('comment_text', 'wp_noindex');
add_filter('comment_text', 'wp_noindex2');

?>