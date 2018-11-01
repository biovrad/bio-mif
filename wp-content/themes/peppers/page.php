<?php get_header(); ?>
<?php include(TEMPLATEPATH."/left.php");?>

	<div id="content">

		<?php if (have_posts()) : while (have_posts()) : the_post(); ?>
		<div class="post">
		<h2><?php the_title(); ?></h2>
			<div class="entry">
				<?php the_content('<p class="serif">Читать полностью &raquo;</p>'); ?>

				<?php wp_link_pages(array('before' => '<p><strong>Страницы:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>

			</div>
		</div>
		<?php endwhile; endif; ?>
	<?php edit_post_link('Править', '<p>', '</p>'); ?>
	</div>

<?php include(TEMPLATEPATH."/right.php");?>

<?php get_footer(); ?>