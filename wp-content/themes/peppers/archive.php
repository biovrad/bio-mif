<?php get_header(); ?>
<?php include(TEMPLATEPATH."/left.php");?>

	<div id="content">

		<?php if (have_posts()) : ?>

 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="pagetitle">Рубрика: <?php single_cat_title(); ?></h2>
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="pagetitle">Публикации с меткой: <?php single_tag_title(); ?></h2>
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="pagetitle">Архивы за <?php the_time('F jS, Y'); ?></h2>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="pagetitle">Архивы за  <?php the_time('F, Y'); ?></h2>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="pagetitle">Архивы за <?php the_time('Y'); ?></h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2 class="pagetitle">Архив автора</h2>
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="pagetitle">Архив сайта</h2>
 	  <?php } ?>


		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&laquo; Предыдущая страница') ?></div>
			<div class="alignright"><?php previous_posts_link('Следующая страница &raquo;') ?></div>
		</div>

		<?php while (have_posts()) : the_post(); ?>
		<div class="post">
				<h3><a href="<?php the_permalink() ?>" rel="bookmark" title="Постоянная ссылка: <?php the_title_attribute(); ?>"><?php the_title(); ?></a></h3>
				<small><?php the_time('l, d M Y') ?></small>

				<div class="entry">
					<?php the_content() ?>
				</div>

				<div class="postmetadata"><?php the_tags('Метки: ', ', ', '<br />'); ?> Рубрика: <?php the_category(', ') ?> | <?php edit_post_link('Править', '', ' | '); ?>  <?php comments_popup_link('Ваш отзыв &#187;', '1 отзыв &#187;', 'Отзывов (%) &#187;'); ?></div>

			</div>

		<?php endwhile; ?>

		<div class="navigation">
			<div class="alignleft"><?php next_posts_link('&laquo; Предыдущая страница') ?></div>
			<div class="alignright"><?php previous_posts_link('Следующая страница &raquo;') ?></div>
		</div>

	<?php else : ?>

		<h2 class="center">Не найдено</h2>
		<?php include (TEMPLATEPATH . '/searchform.php'); ?>

	<?php endif; ?>

	</div>

<?php include(TEMPLATEPATH."/right.php");?>

<?php get_footer(); ?>
