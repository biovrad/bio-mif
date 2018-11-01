<?php
/*
Simple Forum 2.1
Display Cetgories for Post Linking
*/

require(dirname(dirname(dirname(dirname(dirname(__FILE__))))).'/wp-config.php');

global $catlist;
$catlist ='<br /><br /><fieldset><legend>'.__("Select Catgories for Post", "sforum").'</legend>'.sf_write_nested_categories(sf_get_nested_categories($default),1).'</fieldset><br />';
echo $catlist;
die();

function sf_write_nested_categories($categories, $level) 
{
	global $catlist;
	
	foreach ( $categories as $category ) 
	{
		for($x=0; $x<$level; $x++)
		{
			$catlist.='&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
		$catlist.= '<label class="sfcatlist" for="in-category-'.$category["cat_ID"].'"><input value="'.$category['cat_ID'].'" type="checkbox" name="post_category[]" id="in-category-'.$category['cat_ID'].'"/>&nbsp;'.wp_specialchars($category['cat_name']).'</label><br />';

		if ( $category['children'] ) 
		{
			$level++;
			sf_write_nested_categories( $category['children'], $level );
			$level--;
		}
	}
	return $catlist;
}

function sf_get_nested_categories( $default = 0, $parent = 0 ) {

	$cats = sf_return_categories_list( $parent);
	$result = array ();

	if ( is_array( $cats ) ) {
		foreach ( $cats as $cat) {
			$result[$cat]['children'] = sf_get_nested_categories( $default, $cat);
			$result[$cat]['cat_ID'] = $cat;
			$result[$cat]['cat_name'] = get_the_category_by_ID( $cat);
		}
	}
	return $result;
}

function sf_return_categories_list( $parent = 0 ) {
	global $wpdb, $wp_version;
	
	if(version_compare($wp_version, '2.1', '<'))
	{
		return $wpdb->get_col("SELECT cat_ID FROM $wpdb->categories WHERE category_parent = $parent ORDER BY category_count DESC");	
	} elseif(version_compare($wp_version, '2.3', '<')) {
		return $wpdb->get_col("SELECT cat_ID FROM $wpdb->categories WHERE category_parent = $parent AND ( link_count = 0 OR category_count != 0 OR ( link_count = 0 AND category_count = 0 ) ) ORDER BY category_count DESC" );
	} else {
		$args=array();
		$args['parent']=$parent;
		$args['hide_empty']=false;
		$cats = get_categories($args);
		
		if($cats)
		{
			$catids=array();
			foreach($cats as $cat)
			{
				$catids[] = $cat->term_id;
			}
			return $catids;
		}		
		return;
	}
}


?>