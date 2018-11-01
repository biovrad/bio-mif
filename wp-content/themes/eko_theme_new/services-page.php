<?php
/*
Template Name: Services
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
<title>
<?php bloginfo('name');
	wp_title(); ?>
</title>
	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
<?php wp_head(); ?>
</head>
<body>
<div id="main_serv">
	<div class="inner_serv">
		<?php if (have_posts()) : ?>
			<?php while (have_posts()) : the_post(); ?>
				<div class="post_serv">
						<h2 class="serv_title"><?php the_title(); ?></h2>
						<div class="post_cont"<?php the_content(); ?></div>
				</div>
			<?php endwhile; ?>
		<?php endif; ?>	
	</div>
</div>
<?php wp_footer(); ?>
</body>
</html>	
