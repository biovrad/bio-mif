<?php get_header(); ?>
	<div id="page">
		<div id="page_inner">
	<?php get_sidebar("left"); ?>
				<div id="center">

		<?php if (have_posts()) : ?>

 	  <?php $post = $posts[0]; // Hack. Set $post so that the_date() works. ?>
 	  <?php /* If this is a category archive */ if (is_category()) { ?>
		<h2 class="pagetitle"><?php printf(__('Архив  &#8216;%s&#8217; Категории', 'kubrick'), single_cat_title('', false)); ?></h2>
 	  <?php /* If this is a tag archive */ } elseif( is_tag() ) { ?>
		<h2 class="pagetitle"><?php printf(__('Статьи  под тэгом &#8216;%s&#8217;', 'kubrick'), single_tag_title('', false) ); ?></h2>
 	  <?php /* If this is a daily archive */ } elseif (is_day()) { ?>
		<h2 class="pagetitle"><?php printf(_c('Архив за %s|день', 'kubrick'), get_the_time(__('F jS, Y', 'kubrick'))); ?></h2>
 	  <?php /* If this is a monthly archive */ } elseif (is_month()) { ?>
		<h2 class="pagetitle"><?php printf(_c('Архив за %s|месяц', 'kubrick'), get_the_time(__('F, Y', 'kubrick'))); ?></h2>
 	  <?php /* If this is a yearly archive */ } elseif (is_year()) { ?>
		<h2 class="pagetitle"><?php printf(_c('Архив за %s|год', 'kubrick'), get_the_time(__('Y', 'kubrick'))); ?></h2>
	  <?php /* If this is an author archive */ } elseif (is_author()) { ?>
		<h2 class="pagetitle"><?php _e('Архив автора', 'kubrick'); ?></h2>
 	  <?php /* If this is a paged archive */ } elseif (isset($_GET['paged']) && !empty($_GET['paged'])) { ?>
		<h2 class="pagetitle"><?php _e('Архив Сайта', 'kubrick'); ?></h2>
 	  <?php } ?>
		<?php while (have_posts()) : the_post(); ?>

			<div class="post" id="post-<?php the_ID(); ?>">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php printf(__('Пермассылка на %s', 'kubrick'), the_title_attribute('echo=0')); ?>"><?php the_title(); ?></a></h2>
				<small><?php the_time(__('F jS, Y', 'kubrick')) ?> <!-- by <?php the_author() ?> --></small>

				<div class="entry">
					<?php the_content(__('Читать дальше &raquo;', 'kubrick')); ?>
				</div>

				<p class="postmetadata"><?php the_tags(__('Теги:', 'kubrick') . ' ', ', ', '<br />'); ?> <?php printf(__('Опубликовано в  %s', 'kubrick'), get_the_category_list(', ')); ?> | <?php edit_post_link(__('Изменить', 'kubrick'), '', ' | '); ?>  <?php comments_popup_link(__('Нет коментариев &#187;', 'kubrick'), __('1 Коментарий &#187;', 'kubrick'), __('% Коментариев &#187;', 'kubrick'), '', __('Коментарии недоступны', 'kubrick') ); ?></p>
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


<?php get_sidebar("right"); ?>
	</div>

<?php get_footer(); ?>
</div>