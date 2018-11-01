<?php get_header(); ?>
	<div id="page">
		<div id="page_inner">
	<?php get_sidebar("left"); ?>
				<div id="center">
				<?php 
					 global $wp_query;
				//	 pa($wp_query->query_vars[cat],1);
					 $cat = $wp_query->query_vars[cat];
				?>
	<?php query_posts("orderby=date&order=DESC&cat=".$cat); ?>
	<?php if (have_posts()) : ?>

		<?php while (have_posts()) : the_post(); ?>

			<div class="post" id="post-<?php the_ID(); ?>">
				<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="<?php printf(__('Пермассылка на %s', 'kubrick'), the_title_attribute('echo=0')); ?>"><?php the_title(); ?></a></h2>
				<small><?php the_time(__('F jS, Y', 'kubrick')) ?> <!-- by <?php the_author() ?> --></small>

				<div class="entry">
					<?php the_content(__('Читать дальше &raquo;', 'kubrick')); ?>
				</div>

				<p class="postmetadata">
					<?php the_tags(__('Теги:', 'kubrick') . ' ', ', ', '<br />'); ?>
					<?php printf(__('Опубликовано в  %s', 'kubrick'), get_the_category_list(', ')); ?>
					| <?php edit_post_link(__('Изменить', 'kubrick'), '', ' | '); ?>  
					<?php comments_popup_link(__('Нет коментариев &#187;', 'kubrick'), __('1 Коментарий &#187;', 'kubrick'), __('% Коментариев &#187;', 'kubrick'), '', __('Коментарии недоступны', 'kubrick') ); ?>
					<span class="view"><?php if(function_exists('the_views')) { echo the_views(false); } ?></span>
				</p>
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