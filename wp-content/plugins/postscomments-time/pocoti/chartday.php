<?php

// prevent file from being accessed directly
if ('chartday.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not access this file directly. Thanks!');

// init
global $wpdb;

// other initialisations
$tickstrings = array('M','T','W','T','F','S','S');

// get data from DB
$result_posts = $wpdb->get_results("SELECT weekday(post_date) day,count(weekday(post_date)) data
                               FROM " . $wpdb->prefix . "posts
							   WHERE post_status='publish'
							   GROUP BY day")
	or die ('<div class="wrap"><p style="color:red;">SQL Error occured:</p></div>' . mysql_error());

$result_comments = $wpdb->get_results("SELECT weekday(comment_date) day,count(weekday(comment_date)) data
                               FROM " . $wpdb->prefix . "comments
							   WHERE comment_approved='1'
							   GROUP BY day")
	or die ('<div class="wrap"><p style="color:red;">SQL Error occured:</p></div>' . mysql_error());

// Leere Daten erzeugen!
for ($i=0; $i<7; $i++) { $posts[$i] = 0; $comments[$i] = 0; }

// Daten einlesen
foreach ($result_posts as $result) {
  $posts[$result->day] = $result->data;
}
foreach ($result_comments as $result) {
  $comments[$result->day] = $result->data;
}

$title = $options['daytitlestr'];
$xtitle = $options['daystr'];

include("chart_gen.php");

// Display the graph
$graph->Stroke($dir."/chartday.png");
?>
