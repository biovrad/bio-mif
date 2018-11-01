<?php
/*
Simple Forum 2.1
Login
*/

require(dirname(dirname(dirname(dirname(__FILE__)))).'/wp-config.php');
require_once('sf-includes.php');

global $wp_version;

	check_admin_referer('forum-userform_login');
    
	$user_login = '';
	$user_pass = '';
	$using_cookie = false;

	if( $_POST ) 
	{
		$user_login = $_POST['log'];
		$user_login = sanitize_user( $user_login );
		$user_pass  = $_POST['pwd'];
		$rememberme = $_POST['rememberme'];
	} else {
		if (function_exists('wp_get_cookie_login'))
		{
			$cookie_login = wp_get_cookie_login();
			if ( ! empty($cookie_login) ) 
			{
				$using_cookie = true;
				$user_login = $cookie_login['login'];
				$user_pass = $cookie_login['password'];
			}
		}
			elseif ( !empty($_COOKIE) )
		{
			if ( !empty($_COOKIE[USER_COOKIE]) )
				$user_login = $_COOKIE[USER_COOKIE];
			if ( !empty($_COOKIE[PASS_COOKIE]) )
			{
				$user_pass = $_COOKIE[PASS_COOKIE];
				$using_cookie = true;
			}
		}
	}

	if(version_compare($wp_version, '2.2', '<'))
	{
		//WP 2.0 and 2.1
		do_action('wp_authenticate', array(&$user_login, &$user_pass));
	} else {
		//WP 2.2 and 2.3
		do_action_ref_array('wp_authenticate', array(&$user_login, &$user_pass));
	}
	
	if ( $user_login && $user_pass ) {
		$user = new WP_User(0, $user_login);
	
		if ( wp_login($user_login, $user_pass, $using_cookie) ) {
			if ( !$using_cookie )
				wp_setcookie($user_login, $user_pass, false, '', '', $rememberme);

			// is there a last visited date in 'sfnow'? if so move it to 'sflast'
			$cookiepath = preg_replace('|https?://[^/]+|i', '', get_option('home') . '/' );

			if(isset($_COOKIE['sfnow_'.COOKIEHASH]))
			{
				$lastvisit = $_COOKIE['sfnow_'.COOKIEHASH];
				setcookie('sflast_' . COOKIEHASH, $lastvisit, time() + 30000000, $cookiepath, false);
			}
			setcookie('sfnow_' . COOKIEHASH, time(), time() + 30000000, $cookiepath, false);

			do_action('wp_login', $user_login);
			wp_redirect(SFURL);
			die();
			exit;
		} else {
			if ( $using_cookie )			
			$error = __('Your session has expired.', "sforum");
		}
	} else if ( $user_login || $user_pass ) {
	$error = __('<strong>Error</strong>: The password field is empty.', "sforum");
	}

	update_sfnotice('sfmessage', $error);
	wp_redirect(SFLOGIN);
	die();
	
?>