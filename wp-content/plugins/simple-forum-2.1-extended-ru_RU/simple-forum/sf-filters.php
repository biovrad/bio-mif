<?php
/*
Simple Forum 2.1
Filters and actions
*/

// 2.0
add_action('user_register', 'sf_set_first_visit');

// 2.1
add_action('register_form', 'sf_register_math');
add_filter('registration_errors', 'sf_register_error');
add_action('wp_logout', 'sf_track_logout');
add_filter('wp_mail_from', 'sf_mail_filter', 1);

// Registration, logout, Spam Check and response ================================================

function sf_set_first_visit($userid)
{
	global $wpdb;

	// used to setup the last visited value in user-meta
	$wpdb->query("INSERT INTO ".SFUSERMETA." (user_id, meta_key, meta_value) VALUES (".$userid.", '".$wpdb->prefix."sflast', now());");
	return;
}

function sf_track_logout()
{
	global $wpdb, $user_ID;

	get_currentuserinfo();
	// re-use this for updating lastvisit (time at logout)
	if('' != $user_ID)
	{
		sf_set_last_visited($user_ID);
		$wpdb->query("DELETE FROM ".SFTRACK." WHERE trackuserid=".$user_ID);
		sf_destroy_users_newposts($user_ID);
	}
	return;
}

function sf_register_math()
{
	if(get_option('sfregmath'))
	{
		$spammath = sf_math_spam_build();
	
		$out ='<input type="hidden" class="yscontrol" size="30" name="url" value="" /></p>'."\n";
		$out.='<p><strong>'.__("Math Required!", "sforum").'</strong><br />'."\n";
		$out.=sprintf(__("What is the sum of: <strong> %s + %s </strong>", "sforum"), $spammath[0], $spammath[1]).'&nbsp;&nbsp;&nbsp;'."\n";
		$out.='<input type="text" tabindex="3" class="sfcontrol" size="7" name="sfvalue1" value="" /></p>'."\n";
		$out.='<input type="hidden" name="sfvalue2" value="'.$spammath[2].'" />'."\n";
		echo $out;
	}
	return;
}

function sf_register_error($errors)
{
	if(get_option('sfregmath'))
	{
		$spamtest=sf_spamcheck();
		if($spamtest[0] == true)
		{
			$errors['math_check'] = $spamtest[1];
		}
	}
	return $errors;
}

function sf_mail_filter($from)
{
	global $wpdb;

	$fromname = get_usermeta(ADMINID, $wpdb->prefix.'sfadmin');
	$fromname = str_replace(' ', '-', $fromname);
	$newfrom = str_replace('wordpress', $fromname, $from);
	return $newfrom;
}

?>