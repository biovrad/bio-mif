<?php
/*
Simple Forum 2.1
Forum RSS Feeds
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

require(dirname(dirname(dirname(dirname(__FILE__)))).'/wp-config.php');

global $wpdb;

require_once('sf-includes.php');
require_once('sf-support.php');
require_once('sf-primitives.php');

$limit=get_option('sfrsscount');
if(!isset($limit)) $limit=15;

$feed=$_GET['xfeed'];

switch($feed)
{
	case 'group':
	
		//== Get Data
		$groupid = intval($_GET['group']);
		$posts = $wpdb->get_results("SELECT post_id, topic_id, ".SFPOSTS.".forum_id, post_content, ".sf_zone_datetime(post_date).", user_id, guest_name, user_login, display_name, group_id FROM (".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID) LEFT JOIN ".SFFORUMS." ON ".SFPOSTS.".forum_id = ".SFFORUMS.".forum_id WHERE ".SFFORUMS.".group_id=".$groupid." ORDER BY post_date DESC LIMIT 0, ".$limit.";");

		//== Define Channel Elements
		$rssTitle=get_bloginfo('name').' - '.__("Group", "sforum");
		$rssLink=SFQURL.'group='.$groupid;

		$rssDescription=get_bloginfo('description');
		$rssGenerator=__('Simple Forum Version ', "sforum").SFVERSION;
		
		$rssItem=array();
	
		if($posts)
		{
			foreach($posts as $post)
			{
				$thisforum = $wpdb->get_row("SELECT forum_name, forum_view FROM ".SFFORUMS." WHERE forum_id = $post->forum_id");	
				if(sf_access_granted($thisforum->forum_view))
				{
					//== Define Item Elements
					$item = new stdClass;
		
					$poster = sf_filter_user($post->user_id, $post->display_name);
					if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($post->guest_name));
					$topic=sf_get_topic_row($post->topic_id);
					
					$item->title=$poster.__(' on ', "sforum").stripslashes($topic->topic_name);
					$item->link=sf_url($post->forum_id, $post->topic_id);
					$item->pubDate=mysql2date(SFDATES, $post->post_date);
					$item->category=sf_get_forum_name($post->forum_id);
					$text=sf_filter_content(stripslashes($post->post_content), '');
					$item->description=sf_rss_excerpt($text);
					$item->guid=$item->link.'#p'.$post->post_id;
	
					$rssItem[]=$item;
				}
			}
		}

		break;

	case 'topic':

		//== Get Data
		$topicid = intval($_GET['topic']);
	
		$topic=sf_get_topic_row($topicid);
		$posts = $wpdb->get_results("SELECT post_id, post_content, ".sf_zone_datetime(post_date).", user_id, guest_name, user_login, display_name FROM ".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID WHERE topic_id = ".$topicid." ORDER BY post_date DESC LIMIT 0, ".$limit);
		
		//== Define Channel Elements
		$rssTitle=get_bloginfo('name').' - '.__("Topic", "sforum").': '.stripslashes($topic->topic_name);
		$rssLink=sf_url($topic->forum_id, $topic->topic_id);
		$rssDescription=get_bloginfo('description');
		$rssGenerator=__('Simple Forum Version ', "sforum").SFVERSION;
		
		$rssItem=array();
	
		if($posts)
		{
			foreach($posts as $post)
			{
				//== Define Item Elements
				$item = new stdClass;
	
				$poster = sf_filter_user($post->user_id, $post->display_name);
				if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($post->guest_name));
	
				$item->title=$poster.__(' on ', "sforum").stripslashes($topic->topic_name);
				$item->link=sf_url($topic->forum_id, $topic->topic_id);
				$item->pubDate=mysql2date(SFDATES, $post->post_date);
				$item->category=sf_get_forum_name($topic->forum_id);
				$text=sf_filter_content(stripslashes($post->post_content), '');
				$item->description=sf_rss_excerpt($text);
				$item->guid=$item->link.'#p'.$post->post_id;
	
				$rssItem[]=$item;
			}
		}

		break;
	
	case 'forum':
	
		//== Get Data
		$forumid = intval($_GET['forum']);
		$forum=sf_get_forum_row($forumid);
		$posts = $wpdb->get_results("SELECT post_id, topic_id, forum_id, post_content, ".sf_zone_datetime(post_date).", user_id, guest_name, user_login, display_name FROM ".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID WHERE forum_id = ".$forumid." ORDER BY post_date DESC LIMIT 0, ".$limit);
		
		//== Define Channel Elements
		$rssTitle=get_bloginfo('name').' - '.__("Forum", "sforum").': '.stripslashes($forum->forum_name);
		$rssLink=sf_url($forum->forum_id);
		$rssDescription=get_bloginfo('description');
		$rssGenerator=__('Simple Forum Version ', "sforum").SFVERSION;
		
		$rssItem=array();
	
		if($posts)
		{
			foreach($posts as $post)
			{
				//== Define Item Elements
				$item = new stdClass;
	
				$poster = sf_filter_user($post->user_id, $post->display_name);
				if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($post->guest_name));
				$topic=sf_get_topic_row($post->topic_id);
				
				$item->title=$poster.__(' on ', "sforum").stripslashes($topic->topic_name);
				$item->link=sf_url($topic->forum_id, $topic->topic_id);
				$item->pubDate=mysql2date(SFDATES, $post->post_date);
				$item->category=sf_get_forum_name($post->forum_id);
				$text=sf_filter_content(stripslashes($post->post_content), '');
				$item->description=sf_rss_excerpt($text);
				$item->guid=$item->link.'#p'.$post->post_id;

				$rssItem[]=$item;
			}
		}

		break;
		
	case 'all':
	
		//== Get Data
		$posts = $wpdb->get_results("SELECT post_id, topic_id, forum_id, post_content, ".sf_zone_datetime(post_date).", user_id, guest_name, user_login, display_name FROM ".SFPOSTS." LEFT JOIN ".SFUSERS." ON ".SFPOSTS.".user_id = ".SFUSERS.".ID ORDER BY post_date DESC LIMIT 0, ".$limit);
		
		//== Define Channel Elements
		$rssTitle=get_bloginfo('name').' - '.__("All Forums", "sforum");
		$rssLink=SFURL;
		$rssDescription=get_bloginfo('description');
		$rssGenerator=__('Simple Forum Version ', "sforum").SFVERSION;
		
		$rssItem=array();
	
		if($posts)
		{
			foreach($posts as $post)
			{
				$thisforum = $wpdb->get_row("SELECT forum_name, forum_view FROM ".SFFORUMS." WHERE forum_id = $post->forum_id");	
				if(sf_access_granted($thisforum->forum_view))
				{
					//== Define Item Elements
					$item = new stdClass;
		
					$poster = sf_filter_user($post->user_id, $post->display_name);
					if(empty($poster)) $poster = apply_filters('sf_show_post_name', stripslashes($post->guest_name));
					$topic=sf_get_topic_row($post->topic_id);
					
					$item->title=$poster.__(' on ', "sforum").stripslashes($topic->topic_name);
					$item->link=sf_url($post->forum_id, $post->topic_id);
					$item->pubDate=mysql2date(SFDATES, $post->post_date);
					$item->category=sf_get_forum_name($post->forum_id);
					$text=sf_filter_content(stripslashes($post->post_content), '');
					$item->description=sf_rss_excerpt($text);
					$item->guid=$item->link.'#p'.$post->post_id;
	
					$rssItem[]=$item;
				}
			}
		}

		break;
}

//== Send headers and XML
header("HTTP/1.1 200 OK");
header('Content-Type: application/xml');
header("Cache-control: max-age=3600");
header("Expires: ".date('r', time()+3600));
header("Pragma: ");

echo'<?xml version="1.0" ?>';
?>
<rss version="2.0">
<channel>
	<title><?php sf_rss_filter($rssTitle) ?></title>
	<link><?php sf_rss_filter($rssLink) ?></link>
	<description><![CDATA[<?php sf_rss_filter($rssDescription) ?>]]></description>
	<generator><?php sf_rss_filter($rssGenerator) ?></generator>
<?php foreach($rssItem as $item): ?>
<item>
	<title><?php sf_rss_filter($item->title) ?></title>
	<link><?php sf_rss_filter($item->link) ?></link>
	<category><?php sf_rss_filter($item->category) ?></category>
	<guid isPermaLink="true"><?php sf_rss_filter($item->guid) ?></guid>
	<description><![CDATA[<?php sf_rss_filter($item->description) ?>]]></description>
	<pubDate><?php sf_rss_filter($item->pubDate) ?></pubDate>
</item>
<?php endforeach; ?>
</channel>
</rss>