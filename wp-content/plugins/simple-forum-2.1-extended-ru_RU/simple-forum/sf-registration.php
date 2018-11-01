<?php
/*
Simple Forum 2.1
Regisration etc.
*/

if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('You are not allowed to call this page directly.'); }

function sf_login()
{
	global $wpdb, $error, $wp_query;

	$errors = array();
	
	switch($_REQUEST["action"])
	{

    // == REGISTER ACTION ======================================================================

	case 'register' :
	
		if ( FALSE == get_option('users_can_register') ) 
		{
			update_sfnotice('sfmessage', __('New User Registrations are Currently Disabled', "sforum"));
			return false;
		}

		if ( $_POST ) 
		{
			//** require_once( ABSPATH . WPINC . '/registration.php');
			//** OK to use old filename for now - watch for change
			require_once( ABSPATH . WPINC . '/registration-functions.php');

			check_admin_referer('forum-userform_register');
	
			$user_login = sanitize_user( $_POST['user_login'] );
			$user_email = $_POST['user_email'];
	
			// Check the username
			if ($user_login == '')
			{
				$errors['name'] = __('<strong>ERROR</strong>: Please enter a username.', "sforum");
			} elseif ( !validate_username( $user_login ) ) {
				$errors['name'] =  __('<strong>ERROR</strong>: This username is invalid.  Please enter a valid username.', "sforum");
				$user_login = '';
			} elseif ( username_exists( $user_login ) ) {
				$errors['name'] = __('<strong>ERROR</strong>: This username is already registered, please choose another one.', "sforum");
			}
	
			// Check the e-mail address
			if ($user_email == '') 
			{
				$errors['email'] = __('<strong>ERROR</strong>: Please type your e-mail address.', "sforum");
			} elseif (!is_email( $user_email)) {
				$errors['email'] = __('<strong>ERROR</strong>: The email address isn&#8217;t correct.', "sforum");
				$user_email = '';
			} else {
				if(function_exists('email_exists'))
				{
					if (email_exists( $user_email))
					{
						$errors['email'] = __('<strong>ERROR</strong>: This email is already registered, please choose another one.', "sforum");
					}
				}
			}

			do_action('register_post');

			$errors = apply_filters( 'registration_errors', $errors );

			if (empty($errors)) 
			{
				$user_pass = substr( md5( uniqid( microtime() ) ), 0, 7);
	
				$user_id = wp_create_user( $user_login, $user_pass, $user_email );
				if ( !$user_id )
				{
					$errors['failure'] = sprintf(__('<strong>ERROR</strong>: Couldn&#8217;t register you... please contact the <a href="mailto:%s">webmaster</a> !', "sforum"), get_option('admin_email'));
				} else {					
					$user = new WP_User($user_id);
				
					$user_login = stripslashes($user->user_login);
					$user_email = stripslashes($user->user_email);
				
					$message  = sprintf(__('New user registration on your blog %s:', "sforum"), get_option('blogname')) . "\r\n\r\n";
					$message .= sprintf(__('Username: %s', "sforum"), $user_login) . "\r\n\r\n";
					$message .= sprintf(__('E-mail: %s', "sforum"), $user_email) . "\r\n";
				
					@wp_mail(get_option('admin_email'), sprintf(__('[%s] New User Registration', "sforum"), get_option('blogname')), $message);
				
					if ( empty($user_pass) )
						return;
				
					$message  = sprintf(__('Username: %s', "sforum"), $user_login) . "\r\n";
					$message .= sprintf(__('Password: %s', "sforum"), $user_pass) . "\r\n";
					$message .= SFLOGIN."\r\n";
				
					wp_mail($user_email, sprintf(__('[%s] Your username and password', "sforum"), get_option('blogname')), $message);

					update_sfnotice('sfmessage',__('New User Registration Details have been Emailed', "sforum"));
					return false;
				}
			}
		}
?>
		<!-- Registration Form -->
		
		<div id="sforum">
		<div id="sfprofileform">

			<br /><br />
			<fieldset>
				<legend><?php _e("User Registration", "sforum"); ?></legend>
				<?php
				
				if ( $errors ) 
				{
					foreach($errors as $regerror)
					{
						$errmsg .= $regerror. '<br />';
					}
					echo "<div class='sfmessage'>$errmsg</div>"; 

				
					//echo "<div class='sfmessage'>$errors[0]</div>"; 
				} 
				?>

				<form name="registerform" id="registerform" action="<?php echo(SFREGISTER); ?>" method="post">

					<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-userform_register'); ?>			
				
					<p>
					<label><?php _e('Username:', "sforum") ?><br />
					<input type="text" name="user_login" id="user_login" class="sfcontrol" value="<?php echo wp_specialchars(stripslashes($user_login), 1); ?>" size="20" tabindex="10" /></label>
					</p>
					<p>
					<label><?php _e('E-mail:', "sforum") ?><br />
					<input type="text" name="user_email" id="user_email" class="sfcontrol" value="<?php echo wp_specialchars(stripslashes($user_email), 1); ?>" size="25" tabindex="20" /></label>
					</p>
			
					<?php do_action('register_form'); ?>

					<p><?php _e('A password will be e-mailed to you.', "sforum") ?></p><br />

					<input type="submit" class="sfcontrol" name="submit" id="submit" value="<?php _e('Register', "sforum"); ?>" tabindex="100" />

				</form>
			</fieldset>

			<fieldset>
				<br />
				<a class="sfalink" href="<?php echo(SFURL); ?>"><?php _e('Return to Forum', "sforum"); ?></a>
				<br /><br />
			</fieldset>

		</div>
		</div>

<?php
		return true;

	break;

    // == LOST PASSWORD ACTION =====================================================================

	case 'lostpassword':
		
		do_action('lost_password');
?>
		<!-- Lost Password Form -->

		<div id="sforum">
		<div id="sfprofileform">

			<br /><br />
			<fieldset>
				<legend>Lost Password</legend>
				<?php if ($error) {echo "<div class='sfmessage'>$error</div>";} ?>
				<p><?php _e('Please enter your information here.<br />A new password will be emailed.', "sforum") ?></p>
		
				<form name="lostpass" action="<?php echo(SFLOSTPASSWORD); ?>" method="post" id="lostpass">

					<input type="hidden" name="action" value="retrievepassword" />
					<p><label><?php _e('Username:', "sforum") ?><br /><input type="text" class="sfcontrol" name="user_login" id="user_login" value="" size="20" tabindex="1" /></label></p>
					<p><label><?php _e('E-mail:', "sforum") ?><br /><input type="text" class="sfcontrol" name="email" id="email" value="" size="25" tabindex="2" /></label><br /></p>
					<br />
					<input type="submit" class="sfcontrol" name="submit" id="submit" value="<?php _e('Retrieve Password', "sforum"); ?>" tabindex="3" />

				</form>
			</fieldset>
			
			<fieldset>
				<br />
				<?php if (get_settings('users_can_register')) : ?>
					<a class="sfalink" href="<?php echo(SFREGISTER); ?>"><?php _e('Register', "sforum"); ?></a>
				<?php endif; ?>
				
				<a class="sfalink" href="<?php echo(SFLOGIN); ?>"><?php _e('Login', "sforum"); ?></a>
				<a class="sfalink" href="<?php echo(SFURL); ?>"><?php _e('Return to Forum', "sforum"); ?></a>
				<br /><br />
			</fieldset>
			
		</div>
		</div>
		
<?php
		return true;

	break;

    // == RETRIEVE PASSWORD ACTION =================================================================

	case 'retrievepassword':
      
    	$user_data = get_userdatabylogin($_POST['user_login']);
    	// redefining user_login ensures we return the right case in the email
    	$user_login = $user_data->user_login;
    	$user_email = $user_data->user_email;

		// User not exists    
    	if (!$user_email || $user_email != $_POST['email'])
        {
			update_sfnotice('sfmessage', __('This User Name does not exist!', "sforum"));
			return false;
        }

		do_action('retreive_password', $user_login);  // Misspelled and deprecated.
		do_action('retrieve_password', $user_login);

		// Generate something random for a password... md5'ing current time with a rand salt
		$key = substr( md5( uniqid( microtime() ) ), 0, 50);
		// now insert the new pass md5'd into the db
		$wpdb->query("UPDATE $wpdb->users SET user_activation_key = '$key' WHERE user_login = '$user_login'");

		$message = __('Someone has asked to reset the password for the following site and username.', "sforum") . "\r\n\r\n";
		$message .= get_option('siteurl') . "\r\n\r\n";
		$message .= sprintf(__('Username: %s', "sforum"), $user_login) . "\r\n\r\n";
		$message .= __('To reset your password visit the following address, otherwise just ignore this email and nothing will happen.', "sforum") . "\r\n\r\n";
		$message .= SFRESETPASSWORD."&key=$key\r\n";

		$m = wp_mail($user_email, sprintf(__('[%s] Password Reset', "sforum"), get_settings('blogname')), $message);

		if ($m == false) 
		{
			update_sfnotice('sfmessage', __('Email not sent. Possibly the host may have disabled the mail() function', "sforum"));
		} else {
			update_sfnotice('sfmessage', __('An email has been sent to this email address', "sforum"));
		}

		return false;

    break;

    // == RESET PASSWORD ACTION ====================================================================

	case 'resetpass' :

		// Generate something random for a password... md5'ing current time with a rand salt
		$key = preg_replace('/a-z0-9/i', '', $_GET['key']);
		if ( empty($key) )
		{
			update_sfnotice('sfmessage', __('Problem! That activation key does not appear to be valid', "sforum"));
			return false;
		}

		$user = $wpdb->get_row("SELECT * FROM $wpdb->users WHERE user_activation_key = '$key'");
		if ( !$user )
		{
			update_sfnotice('sfmessage', __('Problem! That activation key does not appear to be valid', "sforum"));
			return false;
		}

		do_action('password_reset');
		
		$new_pass = substr( md5( uniqid( microtime() ) ), 0, 7);
		$wpdb->query("UPDATE $wpdb->users SET user_pass = MD5('$new_pass'), user_activation_key = '' WHERE user_login = '$user->user_login'");
		wp_cache_delete($user->ID, 'users');
		wp_cache_delete($user->user_login, 'userlogins');	
		$message  = sprintf(__('Username: %s', "sforum"), $user->user_login) . "\r\n";
		$message .= sprintf(__('Password: %s', "sforum"), $new_pass) . "\r\n";
		$message .= SFLOGIN."\r\n";
		
		$m = wp_mail($user->user_email, sprintf(__('[%s] Your new password', "sforum"), get_settings('blogname')), $message);
		
		if ($m == false) 		
		{
			update_sfnotice('sfmessage', __('Email not sent. Possibly the host may have disabled the mail() function', "sforum"));
		} else {
			update_sfnotice('sfmessage', __('An email has been sent with your new password', "sforum"));

			$message = sprintf(__('Password Lost and Changed for user: %s', "sforum"), $user->user_login) . "\r\n";
			wp_mail(get_settings('admin_email'), sprintf(__('[%s] Password Lost/Change', "sforum"), get_settings('blogname')), $message);
   		}

		return false;
		
	break;
	
    // == LOGIN and Default ACTION =====================================================================
    
	case 'login' : 
	default:

		$user_login = '';
		$user_pass = '';
		$using_cookie = false;
?>
		<!-- Login Form -->
		
		<div id="sforum">
		<div id="sfprofileform">
			<br /><br />
			<fieldset>
				<legend><?php _e("Login to Forum", "sforum"); ?></legend>
				<?php if ( $error ) {echo "<div class='sfmessage'>$error</div>"; } ?>
				
				<form name="loginform" id="loginform" action="<?php echo(get_settings('siteurl').'/wp-content/plugins/simple-forum/sf-login.php'); ?>" method="post">

					<?php if(function_exists('wp_nonce_field')) wp_nonce_field('forum-userform_login'); ?>			

					<p><label><?php _e('Username:', "sforum") ?><br /><input type="text" class="sfcontrol" name="log" id="log" value="<?php echo wp_specialchars(stripslashes($user_login), 1); ?>" size="20"  /></label></p>
					<p><label><?php _e('Password:', "sforum") ?><br /> <input type="password" class="sfcontrol" name="pwd" id="login_password" value="" size="20"  /></label></p>
					<p><label><input name="rememberme" class="sfcontrol" type="checkbox" id="rememberme" value="forever" tabindex="3" /><?php _e('Remember me', "sforum"); ?></label></p>
	
					<p>
						<input type="submit" class="sfcontrol" name="submit" id="submit" value="<?php _e('Login', "sforum"); ?>" tabindex="4" />
						<input type="hidden" name="redirect_to" value="<?php echo wp_specialchars($redirect_to); ?>" />
					</p>

				</form>
			</fieldset>
			
			<fieldset>
				<br />
				<?php if (get_settings('users_can_register')) : ?>
					<a class="sfalink" href="<?php echo(SFREGISTER); ?>"><?php _e('Register', "sforum"); ?></a>
				<?php endif; ?>
				<a class="sfalink" href="<?php echo(SFLOSTPASSWORD); ?>"><?php _e('Lost Password?', "sforum"); ?></a>
				<a class="sfalink" href="<?php echo(SFURL); ?>"><?php _e('Return to Forum', "sforum"); ?></a>
				<br /><br />
			</fieldset>
		
		</div>
		</div>
<?php
		return true;
		break;
	}
}

?>