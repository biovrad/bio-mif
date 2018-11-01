<?php
/*
Template Name: Links
*/
?>
<?php get_header(); ?>
	<div id="page">
		<div id="page_inner">
	<?php get_sidebar("right"); ?>
				<div id="center" class="fix">
	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div class="post" id="post-<?php the_ID(); ?>">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php printf(__('Пермассылка на %s', 'kubrick'), the_title_attribute('echo=0')); ?>"><?php the_title(); ?></a></h2>
				<small><?php the_time(__('F jS, Y', 'kubrick')) ?> <!-- by <?php the_author() ?> --></small>

				<div class="entry">
					<?php the_content(__('Читать дальше &raquo;', 'kubrick')); ?>
				</div>

				<p class="postmetadata"> <?php edit_post_link(__('Изменить', 'kubrick'), '', ' | '); ?>  </p>
			</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link(__('&laquo; Старые ', 'kubrick')) ?></div>
			<div class="alignright"><?php previous_posts_link(__('Новые &raquo;', 'kubrick')) ?></div>
		</div>

	<?php else : ?>

		<h2 class="center"><?php _e('Не найдено', 'kubrick'); ?></h2>
		<p class="center"><?php _e('То что Вы искали не найдено.', 'kubrick'); ?></p>

	<?php endif; ?>
</div>

	</div>

<?php get_footer(); ?>
</div>