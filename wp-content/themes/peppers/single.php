<?php get_header(); ?>
<?php include(TEMPLATEPATH."/left.php");?>

	<div id="content">

	<?php if (have_posts()) : while (have_posts()) : the_post(); ?>

		<div class="post">
				<h2><?php the_title(); ?></h2>
				<small><?php the_time('d M Y') ?>, Автор: <?php the_author() ?></small>

				<div class="entry">
					<?php the_content('Читать полностью &raquo;'); ?>
					<?php wp_link_pages(array('before' => '<p><strong>Страницы:</strong> ', 'after' => '</p>', 'next_or_number' => 'number')); ?>
				</div>

				<div class="postmetadata"><?php if (function_exists('the_tags')) { the_tags('Метки: ', ', ', '<br/>'); } ?>Рубрика: <?php the_category(', ') ?> | <?php edit_post_link('Править', '', ' | '); ?></div>
			
			</div>

	<?php comments_template(); ?>

	<?php endwhile; else: ?>

		<p>Sorry, no posts matched your criteria.</p>

<?php endif; ?>

	</div>
	
<?php include(TEMPLATEPATH."/right.php");?>

<?php get_footer(); ?>
