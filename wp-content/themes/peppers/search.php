<?php get_header(); ?>
<?php include(TEMPLATEPATH."/left.php");?>

	<div id="content">

	<?php if (have_posts()) : ?>

		<h2 class="pagetitle">Результаты поиска</h2>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&laquo; Предыдущая страница') ?></div>
			<div class="alignright"><?php previous_posts_link('Следующая страница &raquo;') ?></div>
		</div>


		<?php while (have_posts()) : the_post(); ?>

			<div class="post">
				<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Постоянная ссылка: <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
				<small><?php the_time('l, d M Y') ?></small>

				<div class="postmetadata"><?php the_tags('Метки: ', ', ', '<br />'); ?> Рубрика: <?php the_category(', ') ?> | <?php edit_post_link('Править', '', ' | '); ?>  <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></div>
			</div>

		<?php endwhile; ?>

		<div class="navigation">
    	<div class="alignleft"><?php next_posts_link('&laquo; Предыдущая страница') ?></div>
      <div class="alignright"><?php previous_posts_link('Следующая страница &raquo;') ?></div>
		</div>

	<?php else : ?>

		<h2 class="center">Ничего не найдено. Попробуете по другому запросу?</h2>
		<?php include (TEMPLATEPATH . '/searchform.php'); ?>

	<?php endif; ?>

	</div>

<?php include(TEMPLATEPATH."/right.php");?>

<?php get_footer(); ?>