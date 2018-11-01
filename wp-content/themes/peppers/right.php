<div id="right">
<ul>

<?php if ( !function_exists('dynamic_sidebar')
        || !dynamic_sidebar(2) ) : ?>

<li><h2>Прочее</h2>
	<ul>
		<?php wp_register(); ?>
		<li><?php wp_loginout(); ?></li>
		<li><a href="http://validator.w3.org/check/referer" title="Эта страница соответствует XHTML 1.0 Transitional">Валидный <abbr title="eXtensible HyperText Markup Language">XHTML</abbr></a></li>
		<li><a href="http://wordpress.org/" title="Разработано на WordPress — платформе, которая вдохновляет">WordPress</a></li>
		<?php wp_meta(); ?>
	</ul>
</li>
<?php endif; ?>
</ul>

</div>

