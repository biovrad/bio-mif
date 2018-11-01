<?php
/*
Simple Forum 2.1
Forum Search url creation
*/

require(dirname(dirname(dirname(dirname(__FILE__)))).'/wp-config.php');

require_once('sf-includes.php');
require_once('sf-support.php');
require_once('sf-primitives.php');

$url = $_SERVER['HTTP_REFERER'];

$param=array();

if(isset($_POST['membersearch'])) 
{
	$id=$_POST['userid'];
	$param['forum']='all';
	$param['value']=urlencode('sf%members%1%user'.$id);
	$param['search']=1;
	$url=add_query_arg($param, SFURL);
} else {
	if(isset($_POST['searchvalue']))
	{
		$param=array();
		if($_POST['searchoption'] == 'All Forums')
		{
			$param['forum']='all';
			$param['value']=urlencode(sf_construct_search_parameter($_POST['searchvalue'], $_POST['searchtype']));
			$param['search']=1;
		} else {
			$param['forum']=$_POST['forumid'];
			$param['value']=urlencode(sf_construct_search_parameter($_POST['searchvalue'], $_POST['searchtype']));
			$param['search']=1;
		}
		$url=add_query_arg($param, SFURL);
	}
}

wp_redirect($url);

?>