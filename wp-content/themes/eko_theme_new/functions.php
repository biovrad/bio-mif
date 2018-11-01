<?php
if ( function_exists('register_sidebar') ){
    register_sidebar(array(
    	'name' => 'left_sidebar',
	    'id' => 'left_sidebar',
        'before_widget' => '',
        'after_widget' => '',
        'before_title' => '<h2>',
        'after_title' => '</h2>',
    ));
    register_sidebar(array(
        'name' => 'right_sidebar',
	    'id' => 'right_sidebar',
       	'before_widget' => '',
        'after_widget' => '',
        'before_title' => '<h2>',
        'after_title' => '</h2>',
		));
}
function pa($mixed, $stop = false) {
	$ar = debug_backtrace(); $key = pathinfo($ar[0]['file']); $key = $key['basename'].':'.$ar[0]['line'];
	$print = array($key => $mixed); echo( '<pre>'.(print_r($print,1)).'</pre>' );
	if($stop == 1) exit();
}
//register_sidebar_widget('Banners', 'banners');    

function primary_menu(){
	 echo  '<ul id="menu">';
	$cat_list = get_categories('hide_empty=0&orderby=name&exclude=1'); //get all cats
	
	foreach($cat_list as $cat){ //build prim menu
		$has_parrent  = get_category_parents($cat->cat_ID);
		if(1>=substr_count($has_parrent,"/")){ //cat lvl check
		
			$cat_link=get_category_link($cat->cat_ID);   //get parent link 
			
			echo "<li><a href =".$cat_link.">".$cat->name."</a>"; //first lvl menu(has no parent)
			
			//$child_cat_list = get_categories('hide_empty=0&orderby=name&exclude=1'); //get all cats
			
			
			$cat_child = get_categories('hide_empty=0&orderby=name&child_of='.$cat->cat_ID); // get shild link
				if(!empty($cat_child)){ //build second menu
					
						 echo  '<ul class="sub_menu">';
						 foreach ($cat_child as $cat_child_link) {
						 	echo "<li><a href ='".get_category_link($cat_child_link->cat_ID)."'>".$cat_child_link->name."</a></li>";
						 }
						 echo "</ul>";
						 
				}	
				echo "</li>";	
		}
		
	}
	echo '</ul>';
}
function start(){
?>
<h2><a href="http://biomif.com/" title="Био Сайт" onClick="try { window.external.AddFavorite(this.href, this.title); return false;} catch(e) {}" rel="sidebar">Добавить в избранное</a></h2>
<?
}
function NewsLater(){
?>
<?php if (class_exists('ajaxNewsletter')): ?>
<li><h2>Рассылка</h2>
      <?php ajaxNewsletter::newsletterForm(); ?> 
</li>
<?php endif; ?><?
}
register_sidebar_widget('NewsLater widget', 'NewsLater');   
register_sidebar_widget('Make Startup', 'start');   
