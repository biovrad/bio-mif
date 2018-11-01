<div id="left">
<ul>
<?php if ( !function_exists('dynamic_sidebar')
        || !dynamic_sidebar(1) ) : ?>
        
<?php wp_list_categories('title_li=<h2>Рубрики</h2>'); ?>

<?php wp_list_bookmarks(); ?>

<?php endif; ?>
</ul>

</div>

