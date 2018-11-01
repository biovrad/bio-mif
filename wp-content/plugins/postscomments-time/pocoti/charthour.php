<?php

// prevent file from being accessed directly
if ('charthour.php' == basename($_SERVER['SCRIPT_FILENAME']))
	die ('Please do not access this file directly. Thanks!');

// init
global $wpdb;

// other initialisations
$tickstrings = array('00','01','02','03','04','05','06','07','08','09','10','11','12','13','14','15','16','17','18','19','20','21','22','23');

// get data from DB
$result_posts = $wpdb->get_results("SELECT hour(post_date) hour,count(hour(post_date)) data
                               FROM " . $wpdb->prefix . "posts
							   WHERE post_status='publish'
							   GROUP BY hour")
	or die ('<div class="wrap"><p style="color:red;">SQL Error occured:</p></div>' . mysql_error());

$result_comments = $wpdb->get_results("SELECT hour(comment_date) hour,count(hour(comment_date)) data
                               FROM " . $wpdb->prefix . "comments
							   WHERE comment_approved='1'
							   GROUP BY hour")
	or die ('<div class="wrap"><p style="color:red;">SQL Error occured:</p></div>' . mysql_error());

// Leere Daten erzeugen!
for ($i=0; $i<24; $i++) { $posts[$i] = 0; $comments[$i] = 0; }

// Daten einlesen
foreach ($result_posts as $result) {
  $posts[$result->hour] = $result->data;
}
foreach ($result_comments as $result) {
  $comments[$result->hour] = $result->data;
}

$title = $options['titlestr'];
$xtitle = $options['hourstr'];

// Daten shiften
if ($options['starthour']!=0) {
  $tickstrings = array_merge(array_slice($tickstrings, $options['starthour']), array_slice($tickstrings, 0, $options['starthour']));
  $posts = array_merge(array_slice($posts, $options['starthour']), array_slice($posts, 0, $options['starthour']));
  $comments = array_merge(array_slice($comments, $options['starthour']), array_slice($comments, 0, $options['starthour']));
}

include("chart_gen.php");

// Display the graph
$graph->Stroke($dir."/charthour.png");
?>
