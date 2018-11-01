<?php get_header(); ?>

<?php include(TEMPLATEPATH."/left.php");?>

	<div id="content">

	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div class="post">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Постоянная ссылка: <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h2>
				<small><?php the_time('d M Y') ?> <!-- Автор: <?php the_author() ?> --></small>

				<div class="entry">
					<?php the_content('Читать полностью &raquo;'); ?>
				</div>

				<div class="postmetadata"><?php if (function_exists('the_tags')) { the_tags('Метки: ', ', ', '<br/>'); } ?>Рубрика: <?php the_category(', ') ?> | <?php edit_post_link('Править', '', ' | '); ?>  <?php comments_popup_link('Ваш отзыв &#187;', '1 отзыв &#187;', 'Отзывов (%) &#187;'); ?></div>
			
			</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&laquo; Предыдущая страница') ?></div>
			<div class="alignright"><?php previous_posts_link('Следующая страница &raquo;') ?></div>
		</div>

	<?php else : ?>

		<h2 class="center">Не найдено</h2>
		<p class="center">К сожалению, по вашему запросу ничего не найдено.</p>
		<?php include (TEMPLATEPATH . "/searchform.php"); ?>

	<?php endif; ?>

	</div>

<?php include(TEMPLATEPATH."/right.php");?>

<?php get_footer(); ?>
