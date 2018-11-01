<script type="text/javascript">
function sendNewsletter(formID,textID,value){
	var formElement = document.getElementById(formID);
	var limit = document.getElementById(textID);
	limit.value = value;
	formElement.submit();
}
</script>
<?php
$delete = $_REQUEST["del"];
$activate = $_REQUEST["actv"];

$message = "";
$succcessMsg = true;
$membersUpdate = false;
$limit = 0;

//if we have requested to send the newsletter manually
if($_POST["send"]=="Send"){
	$limit = $_POST["postLimit"];
	
	$last = get_option("snews_last");
	$posts = ajaxNewsletter::getPostsSince("" ,$last);
	$pcount = count($posts);
	
	if($limit == ""){
		$limit = 0;
	}elseif(!is_numeric($limit) || $limit < 1 || $limit > $pcount){
		$message = "The post limit must be a numeric value between <b>1</b> and <b>$pcount</b>.";
		if($pcount == 1){
			$message = "The post limit must be a numeric value of <b>1</b>.";
		}
		$message .= " <a href=\"javascript:sendNewsletter('newsSend','postLimit',$pcount);\">Send the last ";
		$message .= ajaxNewsletter::getNumberText($pcount,"post"). " &raquo;</a>";
		ajaxNewsletter::printMessage($message,false);
	}else{
		if($posts != "" && $pcount > 0){
			$content = ajaxNewsletter::generateContent($posts, $limit);
			if(ajaxNewsletter::sendNewsletter($content)){
				$limit = 0;
				$message = "Newsletter sent successfully.";
				ajaxNewsletter::printMessage($message);
			}else{
				$message = "An error occured while sending the newsletter. Please try again latter.";
				ajaxNewsletter::printMessage($message,false);
			}
		}else{
			$message = "There are no posts to add to the newsletter.";
			ajaxNewsletter::printMessage($message,false);
		}
	}
}


//if the settings have been updated
if($_POST["submit"]=="Update"){
	//update settings
	$settings = array();

	$settings["period"] = $_POST["period"];
	
	//validate that the number of posts is a positive number
	if(is_numeric($_POST["count"]) && $_POST["count"] > 0){
		$settings["count"] = $_POST["count"];
	}
	$settings["from"] = $_POST["letterFrom"];
	$settings["subject"] = strip_tags($_POST["letterSubject"]);
	$settings["header"] = strip_tags($_POST["letterHeader"]);
	$settings["template"] = strip_tags($_POST["letterTemplate"]);
	$settings["footer"] = strip_tags($_POST["letterFooter"]);

	ajaxNewsletter::saveSettings($settings);
	$message = "Settings updated successfully.";
	ajaxNewsletter::printMessage($message);
}


//if we have requested a member to be removed
if(is_numeric($activate)){
	$membersUpdate = true;
	if(ajaxNewsletter::activateSubscriber($activate)){
		$message = "Subscription activated.";
		$succcessMsg = true;
	}else{
		$email = ajaxNewsletter::getSubscriptionEmail($activate);
		$state = ajaxNewsletter::getSubscriptionState($email);
		if($state != "active"){
			$message = "An error occured with the subscription activation. Please try again later.";
		}else{
			$message = "The subscriber is already active.";
		}
		$succcessMsg = false;
	}
}
//if the settings have been updated
if(is_numeric($delete)){
	$membersUpdate = true;
	ajaxNewsletter::removeSubscriber($delete);
	$message = "Subscription deleted successfully.";
	$succcessMsg = true;
}

//print the html with newsletter info and the conditional ability to send
ajaxNewsletter::printSendDiv($limit);


//prints the HTML to configure the newsletter
ajaxNewsletter::settings();


if($membersUpdate){
	ajaxNewsletter::printMessage($message,$succcessMsg,"msgMembers");
}
//print the members list
ajaxNewsletter::manageMembers();

?>