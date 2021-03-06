<?php
/*
Plugin Name: Subscribe2
Plugin URI: http://subscribe2.wordpress.com
Description: Notifies an email list when new entries are posted.
Version: 2.19
Author: Matthew Robinson
Author URI: http://subscribe2.wordpress.com
*/

/*
Copyright (C) 2006-7 Matthew Robinson
Based on the Original Subscribe2 plugin by 
Copyright (C) 2005 Scott Merrill (skippy@skippy.net)

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
http://www.gnu.org/licenses/gpl.html
*/

// If you are on a host that limits the number of recipients
// permitted on each outgoing email message
// change the value on the line below to your hosts limit
define('BCCLIMIT', '0');

// by default, Subscribe2 grabs the first page from your database for use
// when displaying the confirmation screen to public subscribers.
// You can override this by specifying a page ID on the line below.
define('S2PAGE', '15');

// change the value below to TRUE if you want a daily digest
// of the days posts sent to your subscribers
define('S2DIGEST', false);

// our version number. Don't touch this or any line below
// unless you know exacly what you are doing
define('S2VERSION', '2.19');

// use Owen's excellent ButtonSnap library
require(ABSPATH . '/wp-content/plugins/buttonsnap.php');

$mysubscribe2 = new s2class;
$mysubscribe2->s2init();

// start our class
class s2class {
// variables and constructor are declared at the end

	/**
	Load all our strings
	*/
	function load_strings() {
		// adjust the output of Subscribe2 here

		$this->please_log_in = "<p>" . __('<div class="subscribe">Для управления подпиской необходимо ', 'subscribe2') . "<a href=\"" . get_settings('siteurl') . "/wp-login.php\">залогиниться</a>.</div></p>";

		$this->use_profile = "<div class=\"subscribe\"><p>" . __('Для управления подпиской перейтите на страницу своего ', 'subscribe2') . "<a href=\"" . get_settings('siteurl') . "/wp-admin/profile.php?page=" . plugin_basename(__FILE__) . "\">профиля</a>.</p></div>";

		$this->confirmation_sent = "<p>" . __('На указанный Вами email выслано письмо для подтверждения выбранного действия.', 'subscribe2') . "</p>";

		$this->already_subscribed = "<p>" . __('Этот email уже подписан!', 'subscribe2') . "</p>";

		$this->not_subscribed = "<p>" . __('Этот email не подписан.', 'subscribe2') . "</p>";

		$this->not_an_email = "<p>" . __('Ммм… Вы уверены, что правильно написали свой email?', 'subscribe2') . "</p>";

		$this->barred_domain = "<p>" . __('Электронные адреса на данном домене забанены за спам, используйте другой email.', 'subscribe2') . "</p>";

		$this->mail_sent = "<p>" . __('Письмо отправлено!', 'subscribe2') . "</p>";

		$this->form = "<form method=\"post\" action=\"\">" . __('<div class="subscribe"><span>Ваш email</span>:', 'subscribe2') . "&#160;<input class=\"btn\" type=\"text\" name=\"email\" value=\"\" size=\"20\" />&#160;<br /><br /><input type=\"radio\" name=\"s2_action\" value=\"subscribe\" checked=\"checked\" /> " . __('<span>подписаться</span>', 'subscribe2') . " <input type=\"radio\" name=\"s2_action\" value=\"unsubscribe\" /> " . __('<span>отписаться</span>', 'subscribe2') . " &#160;<input type=\"submit\" value=\"" . __(' окей ', 'subscribe2') . "\" /></div></form><br />\r\n";

		// confirmation messages
		$this->no_such_email = "<p>" . __('Такого email нет в базе.', 'subscribe2') . "</p>";

		$this->added = "<p>" . __('Подписка подтверждена, Вы подписаны.', 'subscribe2') . "</p>";

		$this->deleted = "<p>" . __('Ваш email удален из базы подписчиков, подписка анулирована.', 'subscribe2') . "</p>";

		$this->confirm_subject = "[" . get_settings('blogname') . "] " . __('ПОДТВЕРЖДЕНИЕ ПОДПИСКИ', 'subscribe2');

		$this->remind_subject = "[" . get_settings('blogname') . "] " . __('НАПОМИНАНИЕ О ПОДПИСКЕ', 'subscribe2');

		$this->subscribe = __('subscribe', 'subscribe2'); //ACTION replacement in subscribing confirmation email

		$this->unsubscribe = __('unsubscribe', 'subscribe2'); //ACTION replacement in unsubscribing in confirmation email

		// menu strings
		$this->options_saved = __('Сохранено', 'subscribe2');
		$this->options_reset = __('Options reset!', 'subscribe2');
	} // end load_strings()

/* ===== WordPress menu registration ===== */
	/**
	Hook the menu
	*/
	function admin_menu() {
		add_management_page(__('Subscribers', 'subscribe2'), __('Subscribers', 'subscribe2'), "manage_options", __FILE__, array(&$this, 'manage_menu'));
		add_options_page(__('Subscribe2 Options', 'subscribe2'), __('Subscribe2','subscribe2'), "manage_options", __FILE__, array(&$this, 'options_menu'));
		add_submenu_page('profile.php', __('Subscriptions', 'subscribe2'), __('Subscriptions', 'subscribe2'), "read", __FILE__, array(&$this, 'user_menu'));
		add_submenu_page('post.php', __('Mail Subscribers','subscribe2'), __('Mail Subscribers', 'subscribe2'),"manage_options", __FILE__, array(&$this, 'write_menu'));
		$s2nonce = md5('subscribe2');
	}

	/**
	Insert Javascript into admin_header
	*/
	function admin_head() {
		echo "<script type=\"text/javascript\">\r\n";
		echo "<!--\r\n";
		echo "function setAll(theElement) {\r\n";
		echo "	var theForm = theElement.form, z = 0;\r\n";
		echo "	for(z=0; z<theForm.length;z++){\r\n";
		echo "		if(theForm[z].type == 'checkbox' && theForm[z].name == 'category[]'){\r\n";
		echo "			theForm[z].checked = theElement.checked;\r\n";
		echo "		}\r\n";
		echo "	}\r\n";
		echo "}\r\n";
		echo "-->\r\n";
		echo "</script>\r\n";
	}

/* ===== Install, upgrade, reset ===== */
	/**
	Install our table
	*/
	function install() {
		// include upgrade-functions for maybe_create_table;
		if (!function_exists('maybe_create_table')) {
			require_once(ABSPATH . '/wp-admin/upgrade-functions.php');
		}
		$date = date('Y-m-d');
		$sql = "CREATE TABLE $this->public (
			id int(11) NOT NULL auto_increment,
			email varchar(64) NOT NULL default '',
			active tinyint(1) default 0,
			date DATE default '$date' NOT NULL,
			PRIMARY KEY (id) )";

		// create the table, as needed
		maybe_create_table($this->public, $sql);
		$this->reset();
	} // end install()

	/**
	Upgrade the database
	*/
	function upgrade() {
		global $wpdb;

		// include upgrade-functions for maybe_create_table;
		if (!function_exists('maybe_create_table')) {
			require_once(ABSPATH . '/wp-admin/upgrade-functions.php');
		}
		$date = date('Y-m-d');
		maybe_add_column($this->public, 'date', "ALTER TABLE `$this->public` ADD `date` DATE DEFAULT '$date' NOT NULL AFTER `active`;");

		// let's take the time to check process registered users
		// existing public subscribers are subscribed to all categories
		$users = $wpdb->get_col("SELECT ID FROM $wpdb->users");
		if (!empty($users)) {
			foreach ($users as $user) {
				$this->register($user);
			}
		}
		// update the options table to serialized format
		$old_options = $wpdb->get_col("SELECT option_name from $wpdb->options where option_name LIKE 's2%' AND option_name != 's2_future_posts'");

		if (!empty($old_options)) {
			foreach ($old_options as $option) {
				$value = get_option($option);
				$option_array = substr($option, 3);
				$this->subscribe2_options[$option_array] = $value;
				delete_option($option);
			}
		}
		$this->subscribe2_options['version'] = S2VERSION;
		//double check that the options are in the database
		require(ABSPATH . "/wp-content/plugins/subscribe2/include.php");
		update_option('subscribe2_options', $this->subscribe2_options);
	} // end upgrade()

	/**
	Reset our options
	*/
	function reset() {
		delete_option('subscribe2_options');
		unset($this->subscribe2_options);
		require(ABSPATH . "/wp-content/plugins/subscribe2/include.php");
		update_option('subscribe2_options', $this->subscribe2_options);
	} // end reset()

/* ===== mail handling ===== */
	/**
	Performs string substitutions for subscribe2 mail texts
	*/
	function substitute($string = '') {
		if ('' == $string) {
			return;
		}
		$string = str_replace('BLOGNAME', get_settings('blogname'), $string);
		$string = str_replace('BLOGLINK', get_bloginfo('url'), $string);
		$string = str_replace('TITLE', stripslashes($this->post_title), $string);
		$string = str_replace('PERMALINK', $this->permalink, $string);
		$string = str_replace('MYNAME', stripslashes($this->myname), $string);
		$string = str_replace('EMAIL', $this->myemail, $string);
		$string = str_replace('AUTHORNAME', $this->authorname, $string);
		return $string;
	} // end sustitute()

	/**
	Delivers email to recipients in HTML or plaintext
	*/
	function mail ($recipients = array(), $subject = '', $message = '', $type='text') {
		if ( (empty($recipients)) || ('' == $message) ) { return; }
		
		// Set sender details
		if ('' == $this->myname) {
			$admin = get_userdata(1);
			$this->myname = $admin->display_name;
			$this->myemail = $admin->user_email;
		}
		$headers = "From: " . $this->myname . " <" . $this->myemail . ">\n";
		$headers .= "Return-Path: <" . $this->myemail . ">\n";
		$headers .= "Reply-To: " . $this->myemail . "\n";
		$headers .= "X-Mailer:PHP" . phpversion() . "\n";
		$headers .= "Precedence: list\nList-Id: " . get_settings('blogname') . "\n";

		if ('html' == $type) {
				// To send HTML mail, the Content-Type header must be set
				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-Type: " . get_bloginfo('html_type') . "; charset=\"". get_bloginfo('charset') . "\"\n";
				$mailtext = "<html><head><title>" . $subject . "</title></head><body>" . $message . "</body></html>";
		} else {
				$headers .= "MIME-Version: 1.0\n";
				$headers .= "Content-type: Text/plain; charset=\"". get_bloginfo('charset') . "\"\n";
				$message = preg_replace('|&[^a][^m][^p].{0,3};|', '', $message);
				$message = preg_replace('|&amp;|', '&', $message);
				$mailtext = wordwrap(strip_tags($message), 80, "\n");
		}

		// BCC all recipients
		$bcc = '';
		if ( (defined('BCCLIMIT') && (BCCLIMIT > 0) ) &&
			(count($recipients) > BCCLIMIT) ) {
			// we're on Dreamhost, and have more than 30 susbcribers
				$count = 1;
				$batch = array();
				foreach ($recipients as $recipient) {
				// advance the array pointer by one, for use down below
				// the array pointer _is not_ advanced by the foreach() loop itself
					next($recipients);
					$recipient = trim($recipient);
					// sanity check -- make sure we have a valid email
					if (!is_email($recipient)) { continue; }
					// and NOT the sender's email, since they'll
					// get a copy anyway
					if ( (! empty($recipient)) && ($this->myemail != $recipient) ) {
						('' == $bcc) ? $bcc = "Bcc: $recipient" : $bcc .= ",\r\n $recipient";
						// Headers constructed as per definition at http://www.ietf.org/rfc/rfc2822.txt
					}
					if (BCCLIMIT == $count) {
						$count = 1;
						$batch[] = $bcc;
						$bcc = '';
					} else {
						if (false == current($recipients)) {
						// we've reached the end of the subscriber list
						// add what we have to the batch, and move on
							$batch[] = $bcc;
							break;
						} else {
							$count++;
						}
					}
				}
			// rewind the array, just to be safe
			reset($recipients);
		} else {
			// we're not on dreamhost, or have less than 30
			// subscribers, so do it normal
			foreach ($recipients as $recipient) {
				$recipient = trim($recipient);
				// sanity check -- make sure we have a valid email
					if (!is_email($recipient)) { continue; }
					// and NOT the sender's email, since they'll
					// get a copy anyway
					 if ( (!empty($recipient)) && ($this->myemail != $recipient) ) {
						('' == $bcc) ? $bcc = "Bcc: $recipient" : $bcc .= ",\r\n $recipient";
						// Headers constructed as per definition at http://www.ietf.org/rfc/rfc2822.txt
						}
			}
			$headers .= "$bcc\r\n";
		}
		// actually send mail
		if ( (defined('BCCLIMIT')) && (BCCLIMIT > 0) && (isset($batch)) ) {
			foreach ($batch as $bcc) {
					$newheaders = $headers . "$bcc\r\n";
					@wp_mail($this->myemail, $subject, $mailtext, $newheaders);
			}
		} else {
			@wp_mail($this->myemail, $subject, $mailtext, $headers);
		}
	} // end mail()

	/**
	Sends an email notification of a new post
	*/
	function publish($id = 0, $cron = 0) {
		if (!$id) { return $id; }

		// are we doing daily digests? If so, don't send anything now
		if ( (defined('S2DIGEST')) && (true == S2DIGEST) ) { return; }

		// we need to determine whether this is a new post, or an edit
		if (0 == $cron) {
			// we're not being called from WP-Cron
			if ($this->private) {
				// this post was published from draft status
				// OR is an edit of an existing post
				// so send no notification
				return $id;
			}
		}

		$post_cats = wp_get_post_cats('1', $id);
		$post_cats_string = implode(',', $post_cats);
		$check = false;
		// is the current post assigned to any categories
		// which should not generate a notification email?
		foreach (explode(',', $this->subscribe2_options['exclude']) as $cat) {
			if (in_array($cat, $post_cats)) {
				$check = true;
			}
		}
		// if so, bail out
		if ($check) {
			// hang on -- can registered users subscribe to
			// excluded categories?
			if ('0' == $this->subscribe2_options['reg_override']) {
				// nope? okay, let's leave
				return $id;
			}
		}

		global $wpdb;
		$post =& get_post($id);
		// is this post set in the future?
		if ($post->post_date > current_time('mysql')) {
			// is wp-cron installed?
			if (function_exists('wp_cron_init')) {
				// are we doing daily digests?
				if ( (defined('S2DIGEST')) && (false == S2DIGEST) ) {
					// not doing daily digests, so
					// add this post to the list of
					// future posts
					$our_post = array('id' => $id, 'date' => $post->post_date);
					$future_posts = get_option('s2_future_posts');
					$future_posts[] = $our_post;
					update_option('s2_future_posts', $future_posts);
				}
			}
			// bail out
			return $id;
		}

		// lets collect our public subscribers
		// and all our registered subscribers for these categories
		if (!$check) {
			// if this post is assigned to an excluded
			// category, then this test will prevent
			// the public from receiving notification
			$public = $this->get_public();
		}
		$registered = $this->get_registered("cats=$post_cats_string");

		// do we have subscribers?
		if ( (empty($public)) && (empty($registered)) ) {
			// if not, no sense doing anything else
			return $id;
		}
		// we set these class variables so that we can avoid
		// passing them in function calls a little later
		$this->post_title = $post->post_title;
		$this->permalink = "<a href=\"" . get_permalink($id) . "\">" . get_permalink($id) . "</a>";
		
		$author = get_userdata($post->post_author);
		$this->authorname = $author->display_name;

		// do we send as admin, or post author?
		if ('author' == $this->subscribe2_options['sender']) {
		// get author details
			$user =& $author;
		} else {
			// get admin details
			$user = get_userdata(1);
		}
		$this->myemail = $user->user_email;
		$this->myname = $user->display_name;
		// Get email subject
		$subject = $this->substitute(stripslashes($this->s2_subject));
		// Get the message template
		$mailtext = $this->substitute(stripslashes($this->subscribe2_options['mailtext']));

		$plaintext = $post->post_content;
		$content = apply_filters('the_content', $post->post_content);
		$content = str_replace(']]>', ']]&gt', $content);
		$excerpt = $post->post_excerpt;
		if ('' == $excerpt) {
			// no excerpt, is there a <!--more--> ?
			if (false !== strpos($plaintext, '<!--more-->')) {
				list($excerpt, $more) = explode('<!--more-->', $plaintext, 2);
				// strip leading and trailing whitespace
				$excerpt = strip_tags($excerpt);
				$excerpt = trim($excerpt);
			} else {
				// no <!--more-->, so grab the first 55 words
						$excerpt = strip_tags($plaintext);
						$excerpt_length = 55;
						$words = explode(' ', $excerpt, $excerpt_length + 1);
						if (count($words) > $excerpt_length) {
								array_pop($words);
								array_push($words, '[...]');
								$excerpt = implode(' ', $words);
						}
			}
		}

		// first we send plaintext summary emails
		$body = str_replace('POST', $excerpt, $mailtext);
		$registered = $this->get_registered("cats=$post_cats_string&format=text&amount=excerpt");
		if (empty($registered)) {
			$recipients = (array)$public;
		}
		elseif (empty($public)) {
			$recipients = (array)$registered;
		} else {
		$recipients = array_merge((array)$public, (array)$registered);
		}
		$this->mail($recipients, $subject, $body);
		// next we send plaintext full content emails
		$body = str_replace('POST', strip_tags($plaintext), $mailtext);
		$this->mail($this->get_registered("cats=$post_cats_string&format=text&amount=post"), $subject, $body);
		// finally we send html full content emails
		$body = str_replace("\r\n", "<br />\r\n", $mailtext);
		$body = str_replace('POST', $content, $body);
		$this->mail($this->get_registered("cats=$post_cats_string&format=html"), $subject, $body, 'html');

		return $id;
	} // end publish()

	/**
	Sends a notification when a draft post is published
	*/
	function private2publish($id = 0) {
		if (0 == $id) { return $id; }

		$this->publish($id);
		$this->private = TRUE;
		return $id;
	} // end private2publish()

	/**
	Prevents notifications from being sent when editing posts
	*/
	function edit($id = 0) {
		if (0 == $id) { return; }

		$this->private = TRUE;
		return $id;
	}

	/**
	Send confirmation email to the user
	*/
	function send_confirm($what = '', $is_remind = FALSE) {
		if ($this->filtered == 1) { return; }
		if ( (!$this->email) || (!$what) ) {
			return false;
		}
		$id = $this->get_id($this->email);
		if (!$id) {
			return false;
		}

		// generate the URL "?s2=ACTION+HASH+ID"
		// ACTION = 1 to subscribe, 0 to unsubscribe
		// HASH = md5 hash of email address
		// ID = user's ID in the subscribe2 table
		$link = get_settings('siteurl') . "?s2=";

		if ('add' == $what) {
			$link .= '1';
		} elseif ('del' == $what) {
			$link .= '0';
		}
		$link .= md5($this->email);
		$link .= $id;

		$admin = get_userdata(1);
		$this->myname = $admin->display_name;

		if ($is_remind == TRUE) {
			$body = $this->substitute(stripslashes($this->subscribe2_options['remind_email']));
			$subject = stripslashes($this->remind_subject);
		} else {
			$body = $this->substitute(stripslashes($this->subscribe2_options['confirm_email']));
			if ('add' == $what) {
				$body = str_replace("ACTION", $this->subscribe, $body);
			} elseif ('del' == $what) {
				$body = str_replace("ACTION", $this->unsubscribe, $body);
			}
			$subject = stripslashes($this->confirm_subject);
		}

		$body = str_replace("LINK", $link, $body);

		$mailheaders .= "From: $admin->display_name <$admin->user_email>\n";
		$mailheaders .= "Return-Path: <$admin->user_email>\n";
		$mailheaders .= "X-Mailer:PHP" . phpversion() . "\n";
		$mailheaders .= "Precedence: list\nList-Id: " . get_settings('blogname') . "\n";
		$mailheaders .= "MIME-Version: 1.0\n";
		$mailheaders .= "Content-Type: text/plain; charset=\"". get_bloginfo('charset') . "\"\n";

		@wp_mail ($this->email, $subject, $body, $mailheaders);
	} // end send_confirm()

/* ===== Category functions ===== */
	/**
	Return either a comma-separated list of all the category IDs in the blog or an array of cat_ID => cat_name
	*/
	function get_all_categories($select = 'id') {
		global $wpdb;
		if ('id' == $select) {
			return implode(',', $wpdb->get_col("SELECT cat_ID FROM $wpdb->categories"));
		} else {
			$cats = array();
			$result = $wpdb->get_results("SELECT cat_ID, cat_name FROM $wpdb->categories", ARRAY_N);
			foreach ($result as $result) {
				$cats[$result[0]] = $result[1];
			}
			return $cats;
		}
	} // end get_all_categories()

/* ===== Subscriber functions ===== */
	/**
	Given a public subscriber ID, returns the email address
	*/
	function get_email ($id = 0) {
		global $wpdb;

		if (!$id) {
			return false;
		}
		return $wpdb->get_var("SELECT email FROM $this->public WHERE id=$id");
	} // end get_email

	/**
	Given a public subscriber email, returns the subscriber ID
	*/
	function get_id ($email = '') {
		global $wpdb;

		if (!$email) {
			return false;
		}
		return $wpdb->get_var("SELECT id FROM $this->public WHERE email='$email'");
	} // end get_id()

	/**
	Activate an email address
	If the address is not already present, it will be added
	*/
	function activate ($email = '') {
		global $wpdb;

		if ('' == $email) {
			if ('' != $this->email) {
				$email = $this->email;
			} else {
				return false;
			}
		}

		if (false !== $this->is_public($email)) {
			$check = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email='$this->email'");
			if ($check) { return; }
			$wpdb->get_results("UPDATE $this->public SET active='1' WHERE email='$email'");
		} else {
			$wpdb->get_results("INSERT INTO $this->public (email, active, date) VALUES ('$email', '1', NOW())");
		}
	} // end activate()

	/**
	Add an unconfirmed email address to the subscriber list
	*/
	function add ($email = '') {
		if ($this->filtered ==1) { return; }
		global $wpdb;

		if ('' == $email) {
			if ('' != $this->email) {
				$email = $this->email;
			} else {
				return false;
			}
		}

		if (!is_email($email)) { return false; }

		if (false !== $this->is_public($email)) {
			$wpdb->get_results("UPDATE $this->public SET date=NOW() WHERE email='$email'");
		} else {
			$wpdb->get_results("INSERT INTO $this->public (email, active, date) VALUES ('$email', '0', NOW())");
		}
	} // end add()

	/**
	Remove a user from the subscription table
	*/
	function delete($email = '') {
		global $wpdb;

		if ('' == $email) {
			if ('' != $this->email) {
				$email = $this->email;
			} else {
				return false;
			}
		}

		if (!is_email($email)) { return false; }
		$wpdb->get_results("DELETE FROM $this->public WHERE email='$email'");
	} // end delete()

	/**
	Toggle a public subscriber's status
	*/
	function toggle($email = '') {
		global $wpdb;

		if ( ('' == $email) || (! is_email($email)) ) { return false; }

		// let's see if this is a public user
		$status = $this->is_public($email);
		if (false === $status) { return false; }

		if ('0' == $status) {
			$wpdb->get_results("UPDATE $this->public SET active='1' WHERE email='$email'");
		} else {
			$wpdb->get_results("UPDATE $this->public SET active='0' WHERE email='$email'");
		}
	} // end toggle()

	/**
	Send reminder email to unconfirmed public subscribers
	*/
	function remind($emails = '') {
		if ('' == $emails) { return false; }

		$admin = get_userdata(1);
		$this->myname = $admin->display_name;
		
		$recipients = explode(",", $emails);
		if (!is_array($recipients)) { $recipients = array(); }
		foreach ($recipients as $recipient) {
			$this->email = $recipient;
			$this->send_confirm('add', TRUE);
		}
	} //end remind()

	/**
	Export email list to CSV download
	*/
	function exportcsv($emails = '') {
		if ('' == $emails) {return false; }

		$f = fopen(ABSPATH . '/wp-content/email.csv', 'w');
		fwrite($f, $emails);
		fclose($f);
	} //end exportcsv

	/**
	Check email is not from a barred domain
	*/
	function is_barred($email='') {
		$barred_option = $this->subscribe2_options['barred'];
		list($user, $domain) = split('@', $email);
		$bar_check = stristr($barred_option, $domain);
		
		if(!empty($bar_check)) {
			return true;
		} else {
			return false;
		}
	} //end is_barred()
	
	/**
	Confirm request from the link emailed to the user and email the admin
	*/
	function confirm($content = '') {
		global $wpdb;

		if (1 == $this->filtered) { return $content; }

		$code = $_GET['s2'];
		$action = intval(substr($code, 0, 1));
		$hash = substr($code, 1, 32);
		$code = str_replace($hash, '', $code);
		$id = intval(substr($code, 1));
		if ($id) {
			$this->email = $this->get_email($id);
			if ( (!$this->email) || ($hash !== md5($this->email)) ) {
				return $this->no_such_email;
			}
		} else {
			return $this->no_such_email;
		}

		if ('1' == $action) {
			// make this subscription active
			$this->activate();
			$this->message = $this->added;
			$subject = '[' . get_settings('blogname') . '] ' . __('Новый подписчик', 'subscribe2');
			$message = "$this->email " . __('подписан на нвости и уведомления о новых записях!', 'subscribe2');
			$recipients = $wpdb->get_col("SELECT DISTINCT(user_email) FROM $wpdb->users INNER JOIN $wpdb->usermeta ON $wpdb->users.ID = $wpdb->usermeta.user_id WHERE $wpdb->usermeta.meta_key='wp_user_level' AND $wpdb->usermeta.meta_value='10'");
			$this->mail($recipients, $subject, $message);
			$this->filtered = 1;
		} elseif ('0' == $action) {
			// remove this subscriber
			$this->delete();
			$this->message = $this->deleted;
			$this->filtered = 1;
		}

		if ('' != $this->message) {
			return $this->message;
		}
	} // end confirm

	/**
	Is the supplied email address a public subscriber?
	*/
	function is_public($email = '') {
		global $wpdb;

		if ('' == $email) { return false; }

		$check = $wpdb->get_var("SELECT active FROM $this->public WHERE email='$email'");
		if ( ('0' == $check) || ('1' == $check) ) {
			return $check;
		} else {
			return false;
		}
	} // end is_public

	/**
	Is the supplied email address a registered user of the blog?
	*/
	function is_registered($email = '') {
		global $wpdb;

		if ('' == $email) { return false; }

		$check = $wpdb->get_var("SELECT email FROM $wpdb->users WHERE user_email='$email'");
		if ($check) {
			return true;
		} else {
			return false;
		}
	}

	/**
	Return an array of all the public subscribers
	*/
	function get_public ($confirmed = 1) {
		global $wpdb;
		if (1 == $confirmed) {
			if ('' == $this->all_public) {
				$this->all_public = $wpdb->get_col("SELECT email FROM $this->public WHERE active='1'");
			}
			return $this->all_public;
		} else {
			if ('' == $this->all_unconfirmed) {
				$this->all_unconfirmed = $wpdb->get_col("SELECT email FROM $this->public WHERE active='0'");
			}
			return $this->all_unconfirmed;
		}
	} // end get_public()

	/**
	Return an array of registered subscribers
	Collect all the registered users of the blog who are subscribed to the specified categories
	*/
	function get_registered ($args = '') {
		global $wpdb;

		$format = '';
		$amount = '';
		$cats = '';
		$subscribers = array();

		parse_str($args, $r);
		if (!isset($r['cats']))
			$r['cats'] = '';
		if (!isset($r['format']))
			$r['format'] = 'all';
		if (!isset($r['amount']))
			$r['amount'] = 'all';

		$JOIN = ''; $AND = '';
		// text or HTML subscribers
		if ('all' != $r['format']) {
			$JOIN .= "INNER JOIN $wpdb->usermeta AS b ON a.user_id = b.user_id ";
			$AND .= " AND b.meta_key='s2_format' AND b.meta_value=";
			if ('html' == $r['format']) {
				$AND .= "'html'";
			} elseif ('text' == $r['format']) {
				$AND .= "'text'";
			}
		}

		// full post or excerpt subscribers
		if ('all' != $r['amount']) {
			$JOIN .= "INNER JOIN $wpdb->usermeta AS c ON a.user_id = c.user_id ";
			$AND .= " AND c.meta_key='s2_excerpt' AND c.meta_value=";
			if ('excerpt' == $r['amount']) {
				$AND .= "'excerpt'";
			} elseif ('post' == $r['amount']) {
				$AND.= "'post'";
			}
		}

		// specific category subscribers
		if ('' != $r['cats']) {
			$JOIN .= "INNER JOIN $wpdb->usermeta AS d ON a.user_id = d.user_id ";
			foreach (explode(',', $r['cats']) as $cat) {
				('' == $and) ? $and = "d.meta_key='s2_cat$cat'" : $and .= " OR d.meta_key='s2_cat$cat'";
			}
			$AND .= "AND ($and)";
		}

		$sql = "SELECT a.user_id FROM $wpdb->usermeta AS a " . $JOIN . " WHERE a.meta_key='s2_subscribed'" . $AND;
		$result = $wpdb->get_col($sql);
		if ($result) {
			$ids = implode(',', $result);
			return $wpdb->get_col("SELECT user_email FROM $wpdb->users WHERE ID IN ($ids)");
		}
	} // end get_registered()

	/**
	Collects the signup date for all public subscribers
	*/
	function signup_date($email = '') {
		if ('' == $email) { return false; }

		global $wpdb;
		if (!empty($this->signup_dates)) {
			return $this->signup_dates[$email];
		} else {
			$results = $wpdb->get_results("SELECT email, date FROM $this->public", ARRAY_N);
			foreach ($results as $result) {
				$this->signup_dates[$result[0]] = $result[1];
			}
			return $this->signup_dates[$email];
		}
	} // end signup_date()

	/**
	Create the appropriate usermeta values when a user registers
	If the registering user had previously subscribed to notifications, this function will delete them from the public subscriber list first
	*/
	function register ($user_id = 0) {
		global $wpdb;

		if (0 == $user_id) { return $user_id; }
		$user = get_userdata($user_id);

		// has this user previously signed up for email notification?
		if (false !== $this->is_public($user->user_email)) {
			// delete this user from the public table, and subscribe them to all the categories
			$this->delete($user->user_email);
			update_usermeta($user_id, 's2_subscribed', $this->get_all_categories());
			foreach(explode(',', $this->get_all_categories()) as $cat) {
				update_usermeta($user_id, 's2_cat' . $cat, "$cat");
			}
			update_usermeta($user_id, 's2_format', 'text');
			update_usermeta($user_id, 's2_excerpt', 'excerpt');
		} else {
			// add the usermeta for new registrations, but don't subscribe them
			$check = get_usermeta($user_id, 's2_subscribed');
			// ensure existing subscriptions are not overwritten on upgrade
			if (empty($check)) {
				if ('yes' == $this->subscribe2_options['autosub']) {
					// don't add entires by default if autosub is off, messes up daily digests
					update_usermeta($user_id, 's2_subscribed', $this->get_all_categories());
						foreach(explode(',', $this->get_all_categories()) as $cat) {
							update_usermeta($user_id, 's2_cat' . $cat, "$cat");
						}
					if ('html' == $this->subscribe2_options['autoformat']) {
						update_usermeta($user_id, 's2_format', 'html');
						update_usermeta($user_id, 's2_excerpt', 'post');
					} elseif ('fulltext' == $this->subscribe2_options['autoformat']) {
						update_usermeta($user_id, 's2_format', 'text');
						update_usermeta($user_id, 's2_excerpt', 'post');
					} else {
						update_usermeta($user_id, 's2_format', 'text');
						update_usermeta($user_id, 's2_excerpt', 'excerpt');
					}
				} else {
					update_usermeta($user_id, 's2_subscribed', '-1');
				}
			}
		}
		return $user_id;
	} // end register()

	/**
	Subscribe all registered users to category selected on Admin Manage Page
	*/
	function subscribe_registered_users ($emails = '', $cats = '') {
		if ( ('' == $emails) || ('' == $cats) ) { return false; }
		global $wpdb;
		
		$useremails = explode(",", $emails);
		$useremails = implode("', '", $useremails);

		$sql = "SELECT ID FROM $wpdb->users WHERE user_email IN ('$useremails')";
		$user_IDs = $wpdb->get_col($sql);
		$cats = $_POST['category'];
		if (!is_array($cats)) {
		 	$cats = array($_POST['category']);
		}
		
		foreach ($user_IDs as $user_ID) {	
			$old_cats = explode(',', get_usermeta($user_ID, 's2_subscribed'));
			if (!is_array($old_cats)) {
				$old_cats = array($old_cats);
			}
			$new = array_diff($cats, $old_cats);
			if (!empty($new)) {
				// add subscription to these cat IDs
				foreach ($new as $id) {
					update_usermeta($user_ID, 's2_cat' . $id, "$id");
				}
			}
			$newcats = array_merge($cats, $old_cats);
			update_usermeta($user_ID, 's2_subscribed', implode(',', $newcats));
		}
	} // end subscribe_registered_users

	/**
	Unsubscribe all registered users to category selected on Admin Manage Page
	*/
	function unsubscribe_registered_users ($emails = '', $cats = '') {
		if ( ('' == $emails) || ('' == $cats) ) { return false; }
		global $wpdb;
		
		$useremails = explode(",", $emails);
		$useremails = implode("', '", $useremails);

		$sql = "SELECT ID FROM $wpdb->users WHERE user_email IN ('$useremails')";
		$user_IDs = $wpdb->get_col($sql);
		$cats = $_POST['category'];
		if (!is_array($cats)) {
		 	$cats = array($_POST['category']);
		}
		
		foreach ($user_IDs as $user_ID) {	
			$old_cats = explode(',', get_usermeta($user_ID, 's2_subscribed'));
			if (!is_array($old_cats)) {
				$old_cats = array($old_cats);
			}
			$remain = array_diff($old_cats, $cats);
			if (!empty($remain)) {
				// remove subscription to these cat IDs and update s2_subscribed
				foreach ($cats as $id) {
					delete_usermeta($user_ID, 's2_cat' . $id, "$id");
				}
				update_usermeta($user_ID, 's2_subscribed', implode(',', $remain));
			} else {
				// remove subscription to these cat IDs and update s2_subscribed to ''
				foreach ($cats as $id) {
					delete_usermeta($user_ID, 's2_cat' . $id, "$id");
				}
				update_usermeta($user_ID, 's2_subscribed', '-1');
			}
		}
	} // end unsubscribe_registered_users

	/**
	Autosubscribe registered users to newly created categories
	if registered user has selected this option
	*/
	function autosub_new_category ($new_category='') {
		global $wpdb;

		$sql = "SELECT DISTINCT user_id FROM $wpdb->usermeta WHERE $wpdb->usermeta.meta_key='s2_autosub' AND $wpdb->usermeta.meta_value='yes'";
		$user_IDs = $wpdb->get_col($sql);
		if ('' == $user_IDs) { return; }

		foreach ($user_IDs as $user_ID) {	
			$old_cats = explode(',', get_usermeta($user_ID, 's2_subscribed'));
			if (!is_array($old_cats)) {
				$old_cats = array($old_cats);
			}
			// add subscription to these cat IDs
			update_usermeta($user_ID, 's2_cat' . $new_category, "$new_category");
			$newcats = array_merge($old_cats, (array)$new_category);
			update_usermeta($user_ID, 's2_subscribed', implode(',', $newcats));
		}
	} // end autosub_new_category
	
/* ===== Menus ===== */
	/**
	Our management page
	*/
	function manage_menu() {
		global $wpdb, $s2nonce;

		//Get Registered Subscribers for bulk management
		$registered = $this->get_registered();
		if (!empty($registered)) {
			$emails = implode(",", $registered);
		}

		$what = '';
		$reminderform = false;

		// was anything POSTed ?
		if (isset($_POST['s2_admin'])) {
			check_admin_referer('subscribe2-manage_subscribers' . $s2nonce);
			if ('subscribe' == $_POST['s2_admin']) {
				foreach (preg_split ("/[\s,]+/", $_POST['addresses']) as $email) {
						if (is_email($email)) {
						$this->activate($email);
					}
				}
				$_POST['what'] = 'confirmed';
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>" . __('Address(es) subscribed!', 'subscribe2') . "</p></strong></div>";
			} elseif ('delete' == $_POST['s2_admin']) {
				$this->delete($_POST['email']);
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>" . $_POST['email'] . ' ' . __('deleted!', 'subscribe2') . "</p></strong></div>";
			} elseif ('toggle' == $_POST['s2_admin']) {
				$this->toggle($_POST['email']);
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>" . $_POST['email'] . ' ' . __('status changed!', 'subscribe2') . "</p></strong></div>";
			} elseif ('remind' == $_POST['s2_admin']) {
				$this->remind($_POST['reminderemails']);
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>" . __('Reminder Email(s) Sent!','subscribe2') . "</p></strong></div>"; 
			} elseif ('exportcsv' == $_POST['s2_admin']) {
				$this->exportcsv($_POST['exportcsv']);
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>" . __('CSV File Created in wp-content','subscribe2') . "</p></strong></div>"; 
			} elseif ( ('register' == $_POST['s2_admin']) && ('Subscribe' == $_POST['submit']) ) {
				$this->subscribe_registered_users($_POST['emails'], $_POST['category']);
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>" . __('Registered Users Subscribed!','subscribe2') . "</p></strong></div>";
			} elseif ( ('register' == $_POST['s2_admin']) && ('Unsubscribe' == $_POST['submit']) ) {
				$this->unsubscribe_registered_users($_POST['emails'], $_POST['category']);
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>" . __('Registered Users Unsubscribed!','subscribe2') . "</p></strong></div>";
			}
		}

		if (isset($_POST['what'])) {
			if ('all' == $_POST['what']) {
				$what = 'all';
				$confirmed = $this->get_public();
				$unconfirmed = $this->get_public(0);
				$subscribers = array_merge((array)$confirmed, (array)$unconfirmed, (array)$registered);
			} elseif ('public' == $_POST['what']) {
				$what = 'public';
				$confirmed = $this->get_public();
				$unconfirmed = $this->get_public(0);
				$subscribers = array_merge((array)$confirmed, (array)$unconfirmed);
			} elseif ('confirmed' == $_POST['what']) {
				$what = 'confirmed';
				$confirmed = $this->get_public();				
				$subscribers = $confirmed;
			} elseif ('unconfirmed' == $_POST['what']) {
				$what = 'unconfirmed';
				$unconfirmed = $this->get_public(0);				
				$subscribers = $unconfirmed;
				if (!empty($unconfirmed)) {
					$reminderemails = implode(",", $unconfirmed);
					$reminderform = true;
				}
			} elseif (is_numeric($_POST['what'])) {
				$what = intval($_POST['what']);
				$subscribers = $this->get_registered("cats=$what");
			} elseif ('registered' == $_POST['what']) {
				$what = 'registered';
				$subscribers = $registered;
			}
		} elseif ('' == $what) {
			$what = 'registered';
			$subscribers = $registered;
			$registermessage = '';
			if (empty($subscribers)) {
				$confirmed = $this->get_public();
				$subscribers = $confirmed;
				$what = 'confirmed';
				if (empty($subscribers)) {
					$unconfirmed = $this->get_public(0);
					$subscribers = $unconfirmed;
					$what = 'unconfirmed';
					if (empty($subscribers)) {
						$what = 'all';
					}
				}
			}
		}
		if (!empty($subscribers)) {
			natcasesort($subscribers);
		}
		// safety check for our arrays
		if ('' == $confirmed) { $confirmed = array(); }
		if ('' == $unconfirmed) { $unconfirmed = array(); }
		if ('' == $registered) { $registered = array(); }

		// show our form
		echo "<div class=\"wrap\">";
		echo "<h2>" . __('Подписать эл. адреса:', 'subscribe2') . "</h2>\r\n";
		echo "<form method=\"post\" action=\"\">\r\n";
		if (function_exists('wp_nonce_field')) {
			wp_nonce_field('subscribe2-manage_subscribers' . $s2nonce);
		}
		echo "<span style=\"align:left\">" . __('Впишите эл. адреса, 1 адрес в строчке или разделите запятой', 'subscribe2') . "</span><br />\r\n";
		echo "<textarea rows=\"2\" cols=\"80\" name=\"addresses\"></textarea>";
		echo "<span class=\"submit\"><input type=\"submit\" name=\"submit\" value=\"" . __('подписать', 'subscribe2') . "\"/>";
		echo "<input type=\"hidden\" name=\"s2_admin\" value=\"subscribe\" /></span>";
		echo "</form></div>";

		// subscriber lists
		echo "<div class=\"wrap\"><h2>" . __('Подписчики', 'subscribe2') . "</h2>\r\n";

		$this->display_subscriber_dropdown($what, __('фильтр', 'subscribe2'));
		// show the selected subscribers
		$alternate = 'alternate';
		if (!empty($subscribers)) {
			echo "<p align=\"center\"><b>" . __('Эл. адреса зарегистрированнх пользователей слева, подтвержденные эл. адреса посередине, неподтвержденные — справа', 'subscribe2') . "</b></p>";
			if (is_writable(ABSPATH . '/wp-content')) {
				$exportcsv = implode(",", $subscribers);
				echo "<span class=\"submit\"><form method=\"post\" action=\"\">\r\n";
				if (function_exists('wp_nonce_field')) {
					wp_nonce_field('subscribe2-manage_subscribers' . $s2nonce);
				}
				echo "<input type=\"hidden\" name=\"exportcsv\" value=\"$exportcsv\" />\r\n";
				echo "<input type=\"hidden\" name=\"s2_admin\" value=\"exportcsv\" />\r\n";
				echo "<input type=\"submit\" name=\"submit\" value=\"" . __('Сохранить эл. адреса в CSV файл','subscribe2') . "\" />\r\n";
				echo "</form></span>\r\n";
			}
		}
		echo "<table cellpadding=\"2\" cellspacing=\"2\">";
		if (!empty($subscribers)) {
			foreach ($subscribers as $subscriber) {
				echo "<tr class=\"$alternate\">";
				echo "<td width=\"75%\"";
				if (in_array($subscriber, $unconfirmed)) {
					echo " align=\"right\">";
				} elseif (in_array($subscriber, $confirmed)) {
					echo "align=\"center\">";
				} else {
					echo "align=\"left\" colspan=\"3\">";
				}
				echo "<a href=\"mailto:$subscriber\">$subscriber</a>\r\n";
				if (in_array($subscriber, $unconfirmed) || in_array($subscriber, $confirmed) ) {
					echo "(" . $this->signup_date($subscriber) . ")</td>\r\n";
					echo "<td width=\"5%\" align=\"center\">\r\n";
					echo "<form method=\"post\" action=\"\">";
					if (function_exists('wp_nonce_field')) {
						wp_nonce_field('subscribe2-manage_subscribers' . $s2nonce);
					}
					echo "<input type=\"hidden\" name=\"email\" value=\"$subscriber\" />\r\n";
					echo "<input type=\"hidden\" name=\"s2_admin\" value=\"toggle\" />\r\n";
					echo "<input type=\"hidden\" name=\"what\" value=\"$what\" />\r\n";
					echo "<input type=\"submit\" name=\"submit\" value=\"";
					(in_array($subscriber, $unconfirmed)) ? $foo = '&lt;-' : $foo= '-&gt;';
					echo "$foo\" /></form></td>\r\n";
					echo "<td width=\"2%\" align=\"center\">\r\n";
					echo "<form method=\"post\" action=\"\">\r\n";
					if (function_exists('wp_nonce_field')) {
						wp_nonce_field('subscribe2-manage_subscribers' . $s2nonce);
					}
					echo "<span class=\"delete\">\r\n";
					echo "<input type=\"hidden\" name=\"email\" value=\"$subscriber\" />\r\n";
					echo "<input type=\"hidden\" name=\"s2_admin\" value=\"delete\" />\r\n";
					echo "<input type=\"hidden\" name=\"what\" value=\"$what\" />\r\n";
					echo "<input type=\"submit\" name=\"submit\" value=\"X\" />\r\n";
					echo "</span></form>";
				}
				echo "</td></tr>\r\n";
				('alternate' == $alternate) ? $alternate = '' : $alternate = 'alternate';
			}
		} else {
			echo "<tr><td align=\"center\"><b>" . __('NONE', 'subscribe2') . "</b></td></tr>\r\n";
		}
		echo "</table>";
		if ($reminderform) {
			echo "<span class=\"submit\"><form method=\"post\" action=\"\">\r\n";
			if (function_exists('wp_nonce_field')) {
				wp_nonce_field('subscribe2-manage_subscribers' . $s2nonce);
			}
			echo "<input type=\"hidden\" name=\"reminderemails\" value=\"$reminderemails\" />\r\n";
			echo "<input type=\"hidden\" name=\"s2_admin\" value=\"remind\" />\r\n";
			echo "<input type=\"submit\" name=\"submit\" value=\"" . __('Послать напоминание о подтверждении подписки','subscribe2') . "\" />\r\n";
			echo "</form></span>";
		}
		echo "</div>\r\n";

		//show bulk managment form
		echo "<div class=\"wrap\">";
		echo "<h2 >" . __('Рубрики', 'subscribe2') . "</h2>\r\n";
		echo __('Зарегистрированные пользователи могут быть автоматически подписаны/отписаны на эти рубрики.', 'subscribe2') . "<br />\r\n";
		echo "<strong><em style=\"color: red\">" . __('Consider User Privacy as changes cannot be undone', 'subscribe2') . "</em></strong><br />\r\n";
		echo "<span class=\"submit\"><form method=\"post\" action=\"\">\r\n";
		if (function_exists('wp_nonce_field')) {
			wp_nonce_field('subscribe2-manage_subscribers' . $s2nonce);
		}
		echo "<input type=\"hidden\" name=\"emails\" value=\"$emails\" /><input type=\"hidden\" name=\"s2_admin\" value=\"register\" />";
		$this->display_category_form();
		echo "<input type=\"submit\" id=\"deletepost\" name=\"submit\" value=\"" . __('подписать','subscribe2') . "\" />";
		echo "<input type=\"submit\" id=\"deletepost\" name=\"submit\" value=\"" . __('отписать','subscribe2') . "\" /></form></span>";

		echo "</div>\r\n";
		echo "<div style=\"clear: both;\"><p>&nbsp;</p></div>";

		include(ABSPATH . '/wp-admin/admin-footer.php');
		// just to be sure
		die;
	} // end manage_menu()

	/**
	Our options page
	*/
	function options_menu() {
		global $s2nonce;

		// was anything POSTed?
		if (isset($_POST['s2_admin'])) {
			check_admin_referer('subscribe2-options_subscribers' . $s2nonce);
			if ('RESET' == $_POST['s2_admin']) {
				$this->reset();
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>$this->options_reset</p></strong></div>";
			} elseif ('options' == $_POST['s2_admin']) {
				// excluded categories
				if (!empty($_POST['category'])) {
					$exclude_cats = implode(',', $_POST['category']);
				} else {
					$exclude_cats = '';
				}
				$this->subscribe2_options['exclude'] = $exclude_cats;
				// allow override?
				(isset($_POST['reg_override'])) ? $override = '1' : $override = '0';
				$this->subscribe2_options['reg_override'] = $override;

				// show button?
				($_POST['show_button'] == '1') ? $showbutton = '1' : $showbutton = '0';
				$this->subscribe2_options['show_button'] = $showbutton;

				// send as author or admin?
				$sender = 'author';
				if ('admin' == $_POST['sender']) {
					$sender = 'admin';
				}
				$this->subscribe2_options['sender'] = $sender;

				// email templates
				$mailtext = $_POST['mailtext'];
				$this->subscribe2_options['mailtext'] = $mailtext;
				$confirm_email = $_POST['confirm_email'];
				$this->subscribe2_options['confirm_email'] = $confirm_email;
				$remind_email = $_POST['remind_email'];
				$this->subscribe2_options['remind_email'] = $remind_email;

				//automatic subscription
				$autosub_option = $_POST['autosub'];
				$this->subscribe2_options['autosub']= $autosub_option;
				$autosub_format_option = $_POST['autoformat'];
				$this->subscribe2_options['autoformat']= $autosub_format_option;
				
				//barred domains
				$barred_option = $_POST['barred'];
				$this->subscribe2_options['barred'] = $barred_option;
				echo "<div id=\"message\" class=\"updated fade\"><strong><p>$this->options_saved</p></strong></div>";
				update_option('subscribe2_options', $this->subscribe2_options);
			}
		}
		// show our form
		echo "<div class=\"wrap\">";
		echo "<form method=\"post\" action=\"\">\r\n";
		if (function_exists('wp_nonce_field')) {
			wp_nonce_field('subscribe2-options_subscribers' . $s2nonce);
		}
		echo "<input type=\"hidden\" name=\"s2_admin\" value=\"options\" />\r\n";
		echo "<h2>" . __('Настройки рассылок', 'subscribe2') . ":</h2>\r\n";
		echo __('Посылать письма с электронного адреса', 'subscribe2') . ': ';
		echo "<input type=\"radio\" name=\"sender\" value=\"author\"";
		if ('author' == $this->subscribe2_options['sender']) {
			echo "checked=\"checked\" ";
		}
		echo " /> " . __('автора записи', 'subscribe2') . " &nbsp;&nbsp;";
		echo "<input type=\"radio\" name=\"sender\" value=\"admin\"";
		if ('admin' == $this->subscribe2_options['sender']) {
			echo "checked=\"checked\" ";
		}
		echo " /> " . __('администратора сайта', 'subscribe2') . "<br />\r\n";
		echo "<h2>" . __('Шаблоны писем', 'subscribe2') . "</h2>\r\n";
		echo "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"1\" class=\"editform\">\r\n";
		echo "<tr><td>";
		echo __('Уведомление о новой записи (шаблон не м.б. пустым)', 'subscribe2') . ":";
		echo "<br />\r\n";
		echo "<textarea rows=\"9\" cols=\"60\" name=\"mailtext\">" . stripslashes($this->subscribe2_options['mailtext']) . "</textarea><br /><br />\r\n";
		echo "</td><td valign=\"top\" rowspan=\"3\">";
		echo "<h3>" . __('Условные функции в шаблонах писем', 'subscribe2') . "</h3>\r\n";
		echo "<dl>";
		echo "<dt><b>BLOGNAME</b></dt><dd>" . get_bloginfo('name') . "</dd>\r\n";
		echo "<dt><b>BLOGLINK</b></dt><dd>" . get_bloginfo('url') . "</dd>\r\n";
		echo "<dt><b>TITLE</b></dt><dd>" . __("название записи", 'subscribe2') . "</dd>\r\n";
		echo "<dt><b>POST</b></dt><dd>" . __("тело письма: полная запись или анонс<br />(<i>зависит от индивидуальных настроек подписчика</i>)", 'subscribe2') . "</dd>\r\n";
		echo "<dt><b>PERMALINK</b></dt><dd>" . __("ссылка на запись", 'subscribe2') . "</dd>\r\n";
		echo "<dt><b>MYNAME</b></dt><dd>" . __("Имя администратора или автора записи", 'subscribe2') . "</dd>\r\n";
		echo "<dt><b>EMAIL</b></dt><dd>" . __("email администратора или автора записи", 'subscribe2') . "</dd>\r\n";
		echo "<dt><b>AUTHORNAME</b></dt><dd>" . __("Имя автора записи", 'subscribe2') . "</dd>\r\n";
		echo "<dt><b>LINK</b></dt><dd>" . __("сгенерированная ссылка на подтверждение подписки/отписки<br />(<i>используется только в письмах о подтверждении подписки /отписки</i>)", 'subscribe2') . "</dd>\r\n";
		echo "<dt><b>ACTION</b></dt><dd>" . __("Действие. Action performed by LINK in confirmation email<br />(<i>используется только в письмах о подтверждении подписки /отписки</i>)", 'subscribe2') . "</dd>\r\n";
		echo "</dl></td></tr><tr><td>";
		echo __('Подтверждение подписки /отписки', 'subscribe2') . ":<br />\r\n";
		echo "<textarea rows=\"9\" cols=\"60\" name=\"confirm_email\">" . stripslashes($this->subscribe2_options['confirm_email']) . "</textarea><br /><br />\r\n";
		echo "</td></tr><tr valign=\"top\"><td>";
		echo __('Напоминание неподтвержденным подписчикам', 'subscribe2') . ":<br />\r\n";
		echo "<textarea rows=\"9\" cols=\"60\" name=\"remind_email\">" . stripslashes($this->subscribe2_options['remind_email']) . "</textarea><br /><br />\r\n";
		echo "</td></tr></table><br />\r\n";

		// excluded categories
		echo "<h2>" . __('Исключить категории', 'subscribe2') . "</h2>\r\n";
		$this->display_category_form(explode(',', $this->subscribe2_options['exclude']));
		echo "<center><input type=\"checkbox\" name=\"reg_override\" value=\"1\"";
		if ('1' == $this->subscribe2_options['reg_override']) {
			echo " checked=\"checked\"";
		}
		echo " /> " . __('Разрешить зарегистрированным подписчикам подписываться на исключенные категории?', 'subscribe2') . "</center><br />\r\n";
		echo "<h2>" . __('Другие настройки', 'subscribe2') . "</h2>\r\n";
		echo "<input type=\"checkbox\" name=\"show_button\" value=\"1\"";
		if ('1' == $this->subscribe2_options['show_button']) {
			echo " checked=\"checked\"";
		}
		echo " /> " . __('Показывать кнопку Subscribe2 в панели визуального редактора?', 'subscribe2') . "<br /><br />\r\n";
		
		//Auto Subscription for new registrations
		echo "<h2>" . __('Автоматическая подписка', 'subscribe2') . "</h2>\r\n";
		echo __('Автоматически подписывать новых зарегистрированных пользователей.', 'subscribe2') . "<br />\r\n";
		echo "<input type=\"radio\" name=\"autosub\" value=\"yes\"";
		if ('yes' == $this->subscribe2_options['autosub']) {
			echo " checked=\"checked\"";
		}
		echo " /> " . __('Yes', 'subscribe2') . " &nbsp;&nbsp;";
		echo "<input type=\"radio\" name=\"autosub\" value=\"no\"";
		if ('no' == $this->subscribe2_options['autosub']) {
			echo " checked=\"checked\"";
		}
		echo " /> " . __('No', 'subscribe2') . "<br /><br />\r\n";
		echo __('По умолчанию установить подписку в формате', 'subscribe2') . ": <br />\r\n";
		echo "<input type=\"radio\" name=\"autoformat\" value=\"html\"";
		if ('html' == $this->subscribe2_options['autoformat']) {
			echo "checked=\"checked\" ";
		}
		echo "/> " . __('HTML', 'subscribe2') ." &nbsp;&nbsp;";
		echo "<input type=\"radio\" name=\"autoformat\" value=\"fulltext\" ";
		if ('fulltext' == $this->subscribe2_options['autoformat']) {
			echo "checked=\"checked\" ";
		}
		echo "/> " . __('Plain Text - полная запись', 'subscribe2') . " &nbsp;&nbsp;";
		echo "<input type=\"radio\" name=\"autoformat\" value=\"text\" ";
		if ('text' == $this->subscribe2_options['autoformat']) {
			echo "checked=\"checked\" ";
		}
		echo "/> " . __('Plain Text - краткий анонс записи', 'subscribe2') . " <br /><br />";

		//barred domains
		echo "<h2>" . __('Забанненые домены', 'subscribe2') . "</h2>\r\n";
		echo __('Введите домены, эл. адресам которых не разрешена публичная подписка (т.е. если этот email не принадлежит зарегистрированному пользователю сайта): <br /> (Один домен на одну строчку and omit the "@" symbol, например site.ru)', 'subscribe2');
		echo "<br />\r\n<textarea style=\"width: 98%;\" rows=\"4\" cols=\"60\" name=\"barred\">" . $this->subscribe2_options['barred'] . "</textarea>";

		// submit
		echo "<p align=\"center\"><span class=\"submit\"><input type=\"submit\" id=\"save\" name=\"submit\" value=\"" . __('сохранить', 'subscribe2') . "\" /></span></p>";
		echo "</form>\r\n";
		echo "</div><div class=\"wrap\">";

		// reset
		echo "<h2>" . __('Вернуться к настройкам по умолчанию', 'subscribe2') . "</h2>\r\n";
		echo "<p>" . __('Сбросить все свои настройки и вернуться к настройкам по умолчанию. Это <strong><em>НЕ затронет</em></strong> ваш список подписчиков.', 'subscribe2') . "</p>\r\n";
		echo "<form method=\"post\" action=\"\">";
		if (function_exists('wp_nonce_field')) {
			wp_nonce_field('subscribe2-options_subscribers' . $s2nonce);
		}
		echo "<p align=\"center\"><span class=\"submit\">";
		echo "<input type=\"hidden\" name=\"s2_admin\" value=\"RESET\" />";
		echo "<input type=\"submit\" id=\"deletepost\" name=\"submit\" value=\"" . __('окей', 'subscribe2') .
		"\" />";
		echo "</span></p></form></div>\r\n";

		include(ABSPATH . '/wp-admin/admin-footer.php');
		// just to be sure
		die;
	} // end options_menu()

	/**
	Our profile menu
	*/
	function user_menu() {
		global $user_ID, $s2nonce;

		get_currentuserinfo();

		// was anything POSTed?
		if ( (isset($_POST['s2_admin'])) && ('user' == $_POST['s2_admin']) ) {
			check_admin_referer('subscribe2-user_subscribers' . $s2nonce);
			echo "<div id=\"message\" class=\"updated fade\"><p><strong>" . __('Ваши настройки сохранены.', 'subscribe2') . "</strong></p></div>\n";
			$format = 'text';
			$post = 'post';
			if ('html' == $_POST['s2_format']) {
				$format = 'html';
			}
			if ('excerpt' == $_POST['s2_excerpt']) {
				$post = 'excerpt';
			}
			update_usermeta($user_ID, 's2_excerpt', $post);
			update_usermeta($user_ID, 's2_format', $format);
			update_usermeta($user_ID, 's2_autosub', $_POST['new_category']);

			$cats = $_POST['category'];
			if (empty($cats)) {
				$cats = explode(',', get_usermeta($user_ID, 's2_subscribed'));
				if ($cats) {
					foreach ($cats as $cat) {
						delete_usermeta($user_ID, "s2_cat" . $cat);
					}
				}
				update_usermeta($user_ID, 's2_subscribed', '-1');
			} else {
				 if (!is_array($cats)) {
				 	$cats = array($_POST['category']);
				}
				$old_cats = explode(',', get_usermeta($user_ID, 's2_subscribed'));
				$remove = array_diff($old_cats, $cats);
				$new = array_diff($cats, $old_cats);
				if (!empty($remove)) {
					// remove subscription to these cat IDs
					foreach ($remove as $id) {
						delete_usermeta($user_ID, "s2_cat" .$id);
					}
				}
				if (!empty($new)) {
					// add subscription to these cat IDs
					foreach ($new as $id) {
						update_usermeta($user_ID, 's2_cat' . $id, "$id");
					}
				}
				update_usermeta($user_ID, 's2_subscribed', implode(',', $cats));
			}
		}

		// show our form
		echo "<div class=\"wrap\">";
		echo "<h2>" . __('Настройка получения писем', 'subscribe2') . "</h2>\r\n";
		echo "<form method=\"post\" action=\"\">";
		if (function_exists('wp_nonce_field')) {
			wp_nonce_field('subscribe2-user_subscribers' . $s2nonce);
		}
		echo "<input type=\"hidden\" name=\"s2_admin\" value=\"user\" />";
		if ( (defined('S2DIGEST')) && (FALSE == S2DIGEST) ) {
			echo __('Получать письма в формате ', 'subscribe2') . ": &nbsp;&nbsp;";
			echo "<input type=\"radio\" name=\"s2_format\" value=\"html\"";
			if ('html' == get_usermeta($user_ID, 's2_format')) {
				echo "checked=\"checked\" ";
			}
			echo "/> " . __('HTML', 'subscribe2') ." &nbsp;&nbsp;";
			echo "<input type=\"radio\" name=\"s2_format\" value=\"text\" ";
			if ('text' == get_usermeta($user_ID, 's2_format')) {
				echo "checked=\"checked\" ";
			}
			echo "/> " . __('Plain Text', 'subscribe2') . "<br /><br />\r\n";

			echo __('На email будет приходить', 'subscribe2') . ": &nbsp;&nbsp;";
			$amount = array ('excerpt' => __('Небольшой анонс', 'subscribe2'), 'post' => __('Полный текст', 'subscribe2'));
			foreach ($amount as $key => $value) {
				echo "<input type=\"radio\" name=\"s2_excerpt\" value=\"" . $key . "\"";
				if ($key == get_usermeta($user_ID, 's2_excerpt')) {
					echo " checked=\"checked\"";
				}
				echo " /> " . $value . "&nbsp;&nbsp;";
			}
			echo "<p style=\"color: red\">" . __('Внимание: если Вы выберите получение писем в HTML формате, то они всегда будут содержать полный текст.', 'subscribe2') . "</p>\r\n";
			echo __('Автоматически подписывать меня на записи из новых категорий', 'subscribe2') . ': &nbsp;&nbsp;';
			echo "<input type=\"radio\" name=\"new_category\" value=\"yes\" ";
			if ('yes' == get_usermeta($user_ID, 's2_autosub')) {
				echo "checked=\"yes\" ";
			}
			echo "/> да &nbsp;&nbsp;";
			echo "<input type=\"radio\" name=\"new_category\" value=\"no\" ";
			if ('no' == get_usermeta($user_ID, 's2_autosub')) {
				echo "checked=\"yes\" ";
			}
			echo "/> нет<br /><br />";

			// subscribed categories
			echo "<h2>" . __('Подписка на получение записей из категорий:', 'subscribe2') . "</h2>\r\n";
			$this->display_category_form(explode(',', get_usermeta($user_ID, 's2_subscribed')), $this->subscribe2_options['reg_override']);
		} else {
			// we're doing daily digests, so just show
			// subscribe / unnsubscribe
			echo __('Получать список новых постов за день?', 'subscribe2') . ': &nbsp;&nbsp;';
			echo "<input type=\"radio\" name=\"category\" value=\"1\" ";
			if (get_usermeta($user_ID, 's2_subscribed')) {
				echo "checked=\"yes\" ";
			}
			echo "/> да <input type=\"radio\" name=\"category\" value=\"\" ";
			if (! get_usermeta($user_ID, 's2_subscribed')) {
				echo "checked=\"yes\" ";
			}
			echo "/> нет";
		}

		// submit
		echo "<p align=\"right\"><span class=\"submit\"><input type=\"submit\" name=\"submit\" value=\"" . __("Сохранить &raquo;", 'subscribe2') . "\" /></span></p>";
		echo "</form></div>\r\n";

		include(ABSPATH . '/wp-admin/admin-footer.php');
		// just to be sure
		die;
	} // end user_menu()

	/**
	Display the Write sub-menu
	*/
	function write_menu() {
		global $wpdb, $s2nonce;

		// was anything POSTed?
		if (isset($_POST['s2_admin']) && ('mail' == $_POST['s2_admin']) ) {
			check_admin_referer('subscribe2-write_subscribers' . $s2nonce);
			if ('confirmed' == $_POST['what']) {
				$recipients = $this->get_public();
			} elseif ('unconfirmed' == $_POST['what']) {
				$recipients = $this->get_public(0);
			} elseif ('public' == $_POST['what']) {
				$confirmed = $this->get_public();
				$unconfirmed = $this->get_public(0);
				$recipients = array_merge((array)$confirmed, (array)$unconfirmed);
			} elseif (is_numeric($_POST['what'])) {
				$cat = intval($_POST['what']);
				$recipients = $this->get_registered("cats=$cat");
			} else {
				$recipients = $this->get_registered();
			}
			global $user_identity, $user_email;
			get_currentuserinfo();
			$this->myname = $user_identity;
			$this->myemail = $user_email;
			$subject = strip_tags($_POST['subject']);
			$message = stripslashes($_POST['message']);
			$this->mail($recipients, $subject, $message, 'text');
			$message = $this->mail_sent;
		}

		if ('' != $message) {
			echo "<div id=\"message\" class=\"updated\"><strong><p>" . $message . "</p></strong></div>\r\n";
		}
		// show our form
		echo "<div class=\"wrap\"><h2>" . __('Послать письма всем подписчикам', 'subscribe2') . "</h2>\r\n";
		echo "<form method=\"post\" action=\"\">\r\n";
		if (function_exists('wp_nonce_field')) {
			wp_nonce_field('subscribe2-write_subscribers' . $s2nonce);
		}
		echo __('Subject', 'subscribe2') . ": <input type=\"text\" size=\"69\" name=\"subject\" value=\"" . __('A message from ', 'subscribe2') . get_settings('blogname') . "\" /> <br />";
		echo "<textarea rows=\"12\" cols=\"75\" name=\"message\"></textarea>";
		echo "<br />\r\n";
		echo __('Recipients: ', 'subscribe2');
		$this->display_subscriber_dropdown('registered', false, array('all'));
		echo "<input type=\"hidden\" name=\"s2_admin\" value=\"mail\" />";
		echo "&nbsp;&nbsp;<span class=\"submit\"><input type=\"submit\" name=\"submit\" value=\"" . __('Send', 'subscribe2') . "\" /></span>&nbsp;";
		echo "</form></div>\r\n";
		echo "<div style=\"clear: both;\"><p>&nbsp;</p></div>";

		include(ABSPATH . '/wp-admin/admin-footer.php');
		// just to be sure
		die;
	} // end write_menu()

/* ===== helper functions: forms and stuff ===== */
	/**
	Display a table of categories with checkboxes
	Optionally pre-select those categories specified
	*/
	function display_category_form($selected = array(), $override = 1) {
		global $wpdb;

		$all_cats = $this->get_all_categories('array');
		if (0 == $override) {
			// registered users are not allowed to subscribe to
			// excluded categories
			foreach (explode(',', $this->subscribe2_options['exclude']) as $cat) {
				$category = get_category($cat);
				$excluded[$cat] = $category->cat_name;
			}
			$all_cats = array_diff($all_cats, $excluded);
		}

		$half = (count($all_cats) / 2);
		$i = 0;
		$j = 0;
		echo "<table width=\"100%\" cellspacing=\"2\" cellpadding=\"5\" class=\"editform\">\r\n";
		echo "<tr valign=\"top\"><td width=\"50%\" align=\"left\">\r\n";
		foreach ($all_cats as $cat_ID => $cat_name) {
			 if ( ($i >= $half) && (0 == $j) ){
						echo "</td><td width=\"50%\" align=\"left\">\r\n";
						$j++;
				}
				if (0 == $j) {
						echo "<input type=\"checkbox\" name=\"category[]\" value=\"" . $cat_ID . "\"";
						if (in_array($cat_ID, $selected)) {
								echo " checked=\"checked\" ";
						}
						echo " /> " . $cat_name . "<br />\r\n";
					} else {

						echo "<input type=\"checkbox\" name=\"category[]\" value=\"" . $cat_ID . "\"";
						if (in_array($cat_ID, $selected)) {
									echo " checked=\"checked\" ";
						}
						echo " /> " . $cat_name . "<br />\r\n";
				}
				$i++;
		}
		echo "</td></tr>\r\n";
		echo "<tr><td align=\"left\">\r\n";
		echo "<input type=\"checkbox\" name=\"checkall\" onclick=\"setAll(this)\" /> " . __('Выбрать все / Сбросить все' ,'subscribe2') . "\r\n";
		echo "</td></tr>\r\n";
		echo "</table>\r\n";
	} // end display_category_form()

	/**
	Display a drop-down form to select subscribers
	$selected is the option to select
	$submit is the text to use on the Submit button
	*/
	function display_subscriber_dropdown ($selected = 'registered', $submit = '', $exclude = array()) {
		global $wpdb;

		$who = array('all' => __('All Subscribers', 'subscribe2'),
			'public' => __('Public Subscribers', 'subscribe2'),
			'confirmed' => ' &nbsp;&nbsp;' . __('Confirmed', 'subscribe2'),
			'unconfirmed' => ' &nbsp;&nbsp;' . __('Unconfirmed', 'subscribe2'),
			'registered' => __('Registered Subscribers', 'subscribe2'));

		// count the number of subscribers
		$count['confirmed'] = $wpdb->get_var("SELECT COUNT(id) FROM $this->public WHERE active='1'");
		$count['unconfirmed'] = $wpdb->get_var("SELECT COUNT(id) FROM $this->public WHERE active='0'");
		if (in_array('unconfirmed', $exclude)) {
			$count['public'] = $count['confirmed'];
		} elseif (in_array('confirmed', $exclude)) {
			$count['public'] = $count['unconfirmed'];
		} else {
			$count['public'] = ($count['confirmed'] + $count['unconfirmed']);
		}
		$count['registered'] = $wpdb->get_var("SELECT COUNT(meta_key) FROM $wpdb->usermeta WHERE meta_key='s2_subscribed'");
		$count['all'] = ($count['confirmed'] + $count['unconfirmed'] + $count['registered']);
		foreach ($this->get_all_categories('array') as $cat_ID => $cat_name) {
			$count[$cat_name] = $wpdb->get_var("SELECT COUNT(meta_value) FROM $wpdb->usermeta WHERE meta_key='s2_cat$cat_ID'");
		}

		// do have actually have some subscribers?
		if ( (0 == $count['confirmed']) && (0 == $count['unconfirmed']) && (0 == $count['registered']) ) {
			// no? bail out
			return;
		}

		if (false !== $submit) {
			echo "<form method=\"post\" action=\"\">";
		}
		echo "<select name=\"what\">\r\n";
		foreach ($who as $whom => $display) {
			if (in_array($whom, $exclude)) { continue; }
			if (0 == $count[$whom]) { continue; }

			echo "<option value=\"" . $whom . "\"";
			if ($whom == $selected) { echo " selected=\"selected\" "; }
			echo ">$display (" . ($count[$whom]) . ")</option>\r\n";
		}

		if ($count['registered'] > 0) {
			foreach ($this->get_all_categories('array') as $cat_ID => $cat_name) {
				if (in_array($cat_ID, $exclude)) { continue; }
				if (0 == $count[$cat_name]) { continue; }
				echo "<option value=\"$cat_ID\"";
				if ($cat_ID == $selected) { echo " selected=\"selected\" "; }
				echo "> &nbsp;&nbsp;$cat_name (" . $count[$cat_name] . ") </option>\r\n";
			}
		}
		echo "</select>";
		if (false !== $submit) {
			echo "<span class=\"submit\"><input type=\"submit\" value=\"$submit\" /></span></form>\r\n";
		}
	} // end display_subscriber_dropdown()

/* ===== template and filter functions ===== */
	/**
	Display our form; also handles (un)subscribe requests
	*/
	function filter($content = '') {
		if ( ('' == $content) || (! preg_match('|<!--subscribe2-->|', $content)) ) { return $content; }
		$this->s2form = $this->form;

		global $user_ID;
		get_currentuserinfo();
		if ($user_ID) {
			$this->s2form = $this->use_profile;
		}
		if (isset($_POST['s2_action'])) {
			global $wpdb, $user_email;
			if (!is_email($_POST['email'])) {
				$this->s2form = $this->form . $this->not_an_email;
			} elseif ($this->is_barred($_POST['email'])) {
				$this->s2form = $this->form . $this->barred_domain;
			} else {
				$this->email = $_POST['email'];
				// does the supplied email belong to a registered user?
				$check = $wpdb->get_var("SELECT user_email FROM $wpdb->users WHERE user_email = '$this->email'");
				if ('' != $check) {
					// this is a registered email
					$this->s2form = $this->please_log_in;
				} else {
					// this is not a registered email
					// what should we do?
					if ('subscribe' == $_POST['s2_action']) {
						// someone is trying to subscribe
						// lets see if they've tried to subscribe previously
						if ('1' !== $this->is_public($this->email)) {
							// the user is unknown or inactive
							$this->add();
							$this->send_confirm('add');
							// set a variable to denote that we've already run, and shouldn't run again
							$this->filtered = 1; //set this to not send duplicate emails
							$this->s2form = $this->confirmation_sent;
						} else {
							// they're already subscribed
							$this->s2form = $this->already_subscribed;
						}
						$this->action = 'subscribe';
					} elseif ('unsubscribe' == $_POST['s2_action']) {
						// is this email a subscriber?
						if (false == $this->is_public($this->email)) {
							$this->s2form = $this->form . $this->not_subscribed;
						} else {
							$this->send_confirm('del');
							// set a variable to denote that we've already run, and shouldn't run again
							$this->filtered = 1;
							$this->s2form = $this->confirmation_sent;
						}
						$this->action='unsubscribe';
					}
				}
			}
		}
		return preg_replace('|(<p>)(\n)*<!--subscribe2-->(\n)*(</p>)|', $this->s2form, $content);
	} // end filter()

	/**
	Overrides the default query when handling a (un)subscription confirmation
	This is basically a trick: if the s2 variable is in the query string, just grab the first
	static page and override it's contents later with title_filter()
	*/
	function query_filter() {
		// don't interfere if we've already done our thing
		if (1 == $this->filtered) { return; }

		global $wpdb;

		if ( (defined('S2PAGE')) && (0 != S2PAGE) ) {
			return "page_id=" . S2PAGE;
		} else {
			$id = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_status='static' LIMIT 1");
			if ($id) {
				return "page_id=$id";
			} else {
				return "showposts=1";
			}
		}
	} // end query_filter()

	/**
	Overrides the page title
	*/
	function title_filter() {
		// don't interfere if we've already done our thing
		if (1 == $this->filtered) { return; }
		return __('Подписка', 'subscribe2');
	} // end title_filter()

/* ===== wp-cron functions ===== */
	/**
	Send notifications for any posts that are now visible
	*/
	function subscribe2_hourly() {
		$future_posts = get_option('s2_future_posts');

		// if we have no future posts, bail out
		if (!$future_posts) { return; }

		// this will hold the posts that aren't yet visible
		$not_yet = array();

		foreach ($future_posts as $post) {
			if ( (current_time('mysql')) > ($post['date']) ) {
				// this post is now visible, so let's
				// send a notification
				$this->publish($post['id'], 1);
			} else {
				array_push($not_yet, $post);
			}
		}
		// are the number of elements in $not_yet
		// the same as in $future posts?
		if ( (count($not_yet)) != (count($future_posts)) ) {
			// if not, then some posts have been removed
			// from $future_posts, and the remainder need
			// to be recorded back to the database
			update_option('s2_future_posts', $not_yet);
		}
	} // end subscribe2_hourly

	/**
	Send a daily digest of today's new posts
	*/
	function subscribe2_daily() {
		global $wpdb;

		// collect yesterday's posts
		$yesterday = date('Y-m-d', (get_option('wp_cron_daily_lastrun')-60));
		$posts = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_content FROM $wpdb->posts WHERE post_date > '$yesterday 00:00:00' AND post_date < '$yesterday 23:59:59' AND post_status='publish'");

		// do we have any posts?
		if (!$posts) { return; }

		// if we have posts, let's prepare the digest
		foreach ($posts as $post) {
			$post_cats = wp_get_post_cats('1', $post->ID);
			$post_cats_string = implode(',', $post_cats);
			$check = false;
			// is the current post assigned to any categories
			// which should not generate a notification email?
			foreach (explode(',', $this->subscribe2_options['exclude']) as $cat) {
				if (in_array($cat, $post_cats)) {
					$check = true;
				}
			}
			// if this post is in an excluded category,
			// don't include it in the digest
			if ($check) {
				continue;
			}
			$message .= $post->post_title . "\r\n";
			$message .= get_permalink($post->ID) . "\r\n";
			$excerpt = $post->post_excerpt;
			if ('' == $excerpt) {
				 // no excerpt, is there a <!--more--> ?
				 if (false !== strpos($post->post_content, '<!--more-->')) {
				 	list($excerpt, $more) = explode('<!--more-->', $plaintext, 2);
					// strip leading and trailing whitespace
					$excerpt = trim($excerpt);
				} else {
					$excerpt = strip_tags($post->post_content);
					$words = explode(' ', $excerpt, 56);
					if (count($words) > 55) {
						array_pop($words);
						array_push($words, '[...]');
						$excerpt = implode(' ', $words);
					}
				}
			}
			$message .= $excerpt . "\r\n\r\n";
		}

		$author = get_userdata($post->post_author);
		$this->authorname = $author->display_name;

		// do we send as admin, or post author?
		if ('author' == $this->subscribe2_options['sender']) {
			// get author details
			$user =& $author;
		} else {
			// get admin detailts
			$user = get_userdata(1);
		}
		$this->myemail = $user->user_email;
		$this->myname = $user->display_name;
		
		$subject = '[' . stripslashes(get_settings('blogname')) . '] ' . __('Daily Digest', 'subscribe2') . ' ' . $yesterday;
		$public = $this->get_public();
		$registered = $this->get_registered();
		$recipients = array_merge((array)$public, (array)$registered);
		$mailtext = $this->substitute(stripslashes($this->subscribe2_options['mailtext']));
		$body = str_replace('POST', $message, $mailtext);
		$this->mail($recipients, $subject, $body);
	} // end subscribe2_daily

	/**
	If the to-be-deleted post was future-dated, remove it from the list of future-dated posts
	*/
	function delete_future($id = 0) {
		if (0 == $id) { return $id; }

		$future = get_settings('s2_future_posts');
		// if we have no future-dated posts scheduled, bail out
		if (!$future) {
			return $id;
		}
		foreach ($future as $post) {
			// is the deleted post in the list of future posts?
			if ($id == $post['id']) {
				// skip it
				continue;
			} else {
				// add this to the new list of future posts
				$new_future[] = $post;
			}
		}
		if ($new_future != $future) {
			update_option('s2_future_posts', $new_future);
		}
	} // end delete_future()

/* ===== Our constructor ===== */
	/**
	Subscribe2 constructor
	*/
	function s2init() {
		// load the options
		$this->subscribe2_options = array();
		$this->subscribe2_options = get_option('subscribe2_options');

		add_action('init', array(&$this, 'subscribe2'));
		if('1' == $this->subscribe2_options['show_button']) {
			add_action('init', array(&$this, 'button_init'));
		}
	}

	function subscribe2() {
		global $table_prefix;

		load_plugin_textdomain('subscribe2', 'wp-content/plugins/subscribe2');

		// do we need to install anything?
		$this->public = $table_prefix . "subscribe2";
		if(!mysql_query("DESCRIBE " . $this->public)) { $this->install(); }
		//do we need to upgrade anything?
		if ($this->subscribe2_options['version'] !== S2VERSION) {
			add_action('shutdown', array(&$this, 'upgrade'));
		}

		if (isset($_GET['s2'])) {
			// someone is confirming a request
			add_filter('query_string', array(&$this, 'query_filter'));
			add_filter('the_title', array(&$this, 'title_filter'));
			add_filter('the_content', array(&$this, 'confirm'));
		}

		add_action('admin_head', array(&$this, 'admin_head'));
		add_action('admin_menu', array(&$this, 'admin_menu'));
		add_action('publish_post', array(&$this, 'publish'));
		add_action('edit_post', array(&$this, 'edit'));
		add_action('private_to_published', array(&$this, 'private2publish'));
		add_action('user_register', array(&$this, 'register'));
		add_action('create_category', array(&$this, 'autosub_new_category'));
		add_filter('the_content', array(&$this, 'filter'));
		add_action('wp_cron_hourly', array(&$this, 'subscribe2_hourly'));
		if ( (defined('S2DIGEST')) && (TRUE == S2DIGEST) ) {
			add_action('wp_cron_daily', array(&$this, 'subscribe2_daily'));
		}
		add_action('delete_post', array(&$this, 'delete_future'));

		// load our strings
		$this->load_strings();
	} // end subscribe2()
	
	/* ===== ButtonSnap configuration ===== */
	/**
	Register our button in the QuickTags bar
	*/
	function button_init() {
		$url = get_settings('siteurl') . '/wp-content/plugins/subscribe2/s2_button.png';
		buttonsnap_textbutton($url, 'Subscribe2', '<!--subscribe2-->');
		buttonsnap_register_marker('subscribe2', 's2_marker');
	}

	/**
		Style a marker in the Rich Text Editor for our tag
		By default, the RTE suppresses output of HTML comments, so this places a CSS style on our token 	in order to make it display
	*/
	function subscribe2_css() {
		$marker_url = get_settings('siteurl') . '/wp-content/plugins/subscribe2/s2_marker.png';
		echo "
			.s2_marker {
				display: block;
				height: 45px;
				margin-top: 5px;
				background-image: url({$marker_url});
				background-repeat: no-repeat;
				background-position: center;
			}
		";
	}

/* ===== our variables ===== */
	// cache variables
	var $version = '';
	var $all_public = '';
	var $all_unconfirmed = '';
	var $excluded_cats = '';
	var $post_title = '';
	var $permalink = '';
	var $myname = '';
	var $myemail = '';
	var $s2_subject = '[BLOGNAME] TITLE';
	var $signup_dates = array();
	var $private = false;
	var $filtered = 0;

	// state variables used to affect processing
	var $action = '';
	var $email = '';
	var $message = '';
	var $error = '';

	// some messages
	var $please_log_in = '';
	var $use_profile = '';
	var $confirmation_sent = '';
	var $already_subscribed = '';
	var $not_subscribed ='';
	var $not_an_email = '';
	var $barred_domain = '';
	var $mail_sent = '';
	var $form = '';
	var $no_such_email = '';
	var $added = '';
	var $deleted = '';
	var $confirm_subject = '';
	var $options_saved = '';
	var $options_reset = '';
} // end class subscribe2
?>