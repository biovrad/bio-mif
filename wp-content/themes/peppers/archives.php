<?php
/*
Template Name: Archives
*/
?>

<?php get_header(); ?>
<?php include(TEMPLATEPATH."/left.php");?>

<div id="content">

<?php include (TEMPLATEPATH . '/searchform.php'); ?>

<h2>Архивы по месяцам:</h2>
	<ul>
		<?php wp_get_archives('type=monthly'); ?>
	</ul>

<h2>Архивы по рубрикам:</h2>
	<ul>
		 <?php wp_list_categories(); ?>
	</ul>

</div>

<?php include(TEMPLATEPATH."/right.php");?>

<?php get_footer(); ?>
