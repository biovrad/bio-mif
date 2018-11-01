<?php
include_once("../../../wp-blog-header.php");
include_once("wp-ajax-newsletter.php");

$del = $_REQUEST["del"];
$add = $_REQUEST["add"];
$delConf = $_POST['delconf'];

$content = "";
$content .= "<h1>" . get_bloginfo("name") . " - Newsletter</h1>\n";

if($del != ""){//newsletter removal confirmation
	if(ajaxNewsletter::isConfirmation($del)){
		if($delConf != ""){//we have answered confirmation
			if($delConf == "Yes"){//we have answerd Yes to the confirmation
				$id = ajaxNewsletter::getConfirmationId($del);
				$email = ajaxNewsletter::getSubscriptionEmail($id);
				ajaxNewsletter::removeSubscriber($id);
				$content .= "<div class='success'>The email <b>$email</b> has been removed from the subscribers list successfully.</div>\n";
			}else{
				$content .= "<div class='success'>Subscription removal canceled.</div>\n";
			}
		}else{ //ask before removing
			$content .= "<h2>Removal Confirmation</h2>\n";
			$content .= "<div style='padding:10px;'><p>Are you sure you want to unsubscribe the \"" . get_bloginfo("name") . "\" Newsletter?</p>\n";
			$content .= "<form action=\"\" method=\"post\">\n";
			$content .= "	<input class=\"button\" type=\"submit\" name=\"delconf\" value='Yes'/>\n";
			$content .= "	<input class=\"button\" type=\"submit\" name=\"delconf\" value='No'/>\n";
			$content .= "</form></div>\n";
		}
	}else{//the confirmation number was not valid
		$content .= "<div class=\"errorTitle\">Invalid confirmation number.</div>\n";
		$content .= "<p>Ensure that you have used the full link provided in the email.</p>\n";
	}
}elseif($add != ""){//subscription confirmation
	if(ajaxNewsletter::isConfirmation($add)){
		$id = ajaxNewsletter::getConfirmationId($add);
		
		ajaxNewsletter::activateSubscriber($id);
		$email = ajaxNewsletter::getSubscriptionEmail($id);
		$content .= "<div class=\"success\">The email <b>$email</b> has been added to the subscribers list successfully.</div>\n";
	}else{//the confirmation number was not valid
		$content .= "<div class=\"errorTitle\">Invalid confirmation number.</div>\n";
		$content .= "<p>Ensure that you have used the full link provided in the email.</p>\n";
	}
}else{//the user should not be here... redirect to homepage
	header( 'Location: '.get_bloginfo("url") ) ;
	exit();
}
//write the HTML for the confirmation page
ajaxNewsletter::writeConfirmationPage($content);
?>