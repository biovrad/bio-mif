<?php

/*
 Plugin Name: Ajax Newsletter
 Plugin URI: http://code.google.com/p/wp-ajax-newsletter/
 Description: Allows users to subscribe and receive a newsletter containing the blog latest posts.
 Author: Tiago Pocinho, Siemens Networks, S.A.
 Version: 1.0
 */

class ajaxNewsletter {
	/**
	 * Constructor for the ajaxNewsletter Class
	 * 
	 * Adds wordpress actions and filters      
	 **/
	function ajaxNewsletter(){
		//add the backoffice menu entries
		add_action('admin_menu', array('ajaxNewsletter','add_pages'));
		//if we are in the newsletter page
		if (strstr($_SERVER['REQUEST_URI'], 'newsletter') !== false) {
			add_action('admin_head', array('ajaxNewsletter','admin_css'));
		}
		//Install plugin in activation
		add_action('activate_wp-ajax-newsletter/wp-ajax-newsletter.php',array('ajaxNewsletter','install'));
		
		### Function: Process Subscription
		add_action('init', array('ajaxNewsletter','process_newsletterSub'));
		
		//everytime a page loads we check if new posts are available
		add_action('init', array('ajaxNewsletter', 'checkAutomaticNewsletter'));
		
		//everytime a page loads we check if new posts are available
		add_action('publish_post', array('ajaxNewsletter', 'checkEveryNewsletter'));
	}
	
	/**
	 * Checks if the Newsletter should be sent when publishing a post.
	 * If the newsletter can be sent it will send it to all active subscribers.
	 */
	function checkEveryNewsletter(){
		$period = get_option("snews_period");
		
		if($period != "every")
			return;
		
		$last = get_option("snews_last");
		$count = get_option("snews_count");
		
		$posts = ajaxNewsletter::getPostsSince("",$last);
		$postCount = count($posts);
		
		//if the number of posts available since last newsletter are equal or greater
		//than the specified value in the settings, we send the new newsletter
		if($postCount >= $count){
			$content = ajaxNewsletter::generateContent($posts,$count);
			ajaxNewsletter::sendNewsletter($content);
		}
	}
	
	/**
	 * Prints the CSS for the back-office
	 */
	function admin_css(){
		$path = get_bloginfo("wpurl") . "/wp-content/plugins/wp-ajax-newsletter/";
		echo "<link rel=\"stylesheet\" href=\"{$path}style.css\" type=\"text/css\" />\n";
		echo "<script type=\"text/javascript\" src=\"{$path}preview.js\"></script>\n";
	}
	
	/**
	 * From http://www.phpbuilder.com/board/showthread.php?t=10222903
	 * 
	 * Gets a date based on a year and week date
	 * 
	 * @param int $wk_num Number of the week
	 * @param int $yr Year of the date
	 * @param int first $number of days
	 * @param string $format Date format
	 * 
	 * @return string The generated date
	 */
	function getDateByWeek($wk_num, $yr, $first = 1, $format = 'Y-n-d')
	{
		$wk_num --;
		if($wk_num < 0 || !is_numeric($wk_num)){
			$wk_num = 0;
		}
	    $wk_ts  = strtotime('+' . $wk_num . ' weeks', strtotime($yr . '0101'));
	    $mon_ts = strtotime('-' . date('w', $wk_ts) + $first . ' days', $wk_ts);
	    return date($format, $mon_ts);
	}
	
	/**Adds the needed leading zeros to create strings with a fixed size
	 *
	 * @param int $number Number to be converted
	 * @param int $digits Number of digits the string should have
	 * @return string The resulting string
	 */
	function NumberToString($number, $digits = 2){
		$string = "$number";
		$length = strlen($string);
		while($length < $digits){
			$string = "0" . $string;
			$length ++;
		}
		return $string;
	}

	
	/**
	 * Checks if the Newsletter should be sent.
	 * If the newsletter can be sent it will send it to all active subscribers.
	 */
	function checkAutomaticNewsletter(){
		$period = get_option("snews_period");

		//we only want to check weekly and monthly newsletters
		if($period == "manual" || $period == "every")
			return;
		
		//check if we are on the blog homepage
		$checkString = $_SERVER['REQUEST_URI'];
		if (strstr(get_bloginfo("url")."/" , $checkString) === false) {
			return;
		}
		$last = get_option("snews_last");
		$count = get_option("snews_count");
		$sendFlag = false;
		
		switch($period){ 
			case "month":
				//see if a month since last submit has elapsed and if posts are available
				$lastMonth = mysql2date("n",$last) + 0;
				$thisMonth = date("n",mktime()) + 0;
								
				$lastYear = mysql2date("Y",$last) + 0;
				$thisYear = date("Y",mktime()) + 0;
				
				if($lastYear < $thisYear && $thisMonth == 1){
					$since .= ajaxNewsletter::NumberToString($thisYear -1, 4);
					$since .= "-";
				}else{
					$since .= ajaxNewsletter::NumberToString($thisYear, 4) ."-";
				}
				if($thisMonth == 1){
					$since .= "12-01";
				}else {
					$since .= (ajaxNewsletter::NumberToString($thisMonth - 1, 2))."-01";
				}
				
				$to = $thisYear."-".ajaxNewsletter::NumberToString($thisMonth, 2)."-01";
				
				$posts = ajaxNewsletter::getPostsSince($to,$since);
				$postCount = count($posts);
				
				if(($lastYear < $thisYear || $lastMonth < $thisMonth) && $postCount > 0){
					$content = ajaxNewsletter::generateContent($posts);
					ajaxNewsletter::sendNewsletter($content, mysql2date("Y-m-d H:i:s",$to));
				}
				break;
			case "week":
				$lastWeek = mysql2date("W",$last) + 0;
				$thisWeek = date("W",mktime()) + 0;
				
				$lastYear = mysql2date("Y",$last) + 0;
				$thisYear = date("Y",mktime()) + 0;
				
				if($lastYear < $thisYear && $thisWeek == 1){
					$since = ajaxNewsletter::getDateByWeek($thisWeek - 1, $thisYear - 1);
				}else{
					$since = ajaxNewsletter::getDateByWeek($thisWeek - 1, $thisYear);
				}
				
				$to = ajaxNewsletter::getDateByWeek($thisWeek, $thisYear);
				$posts = ajaxNewsletter::getPostsSince($to,$since);
				
				$postCount = count($posts);
				if($postCount > 0){
					$content = ajaxNewsletter::generateContent($posts);
					ajaxNewsletter::sendNewsletter($content, mysql2date("Y-m-d H:i:s",$to));
				}
				break;
			default:
				break;
		}
	}
	
	/** 
	 *  Creates the menus in the wordpress backoffice
	 **/     
	function add_pages() {
		// Add a submenu to the Options menu for newsletter settings
		add_options_page('Newsletter','Newsletter',8 , 'wp-ajax-newsletter/wp-ajax-newsletter.php', array('ajaxNewsletter','newsletterConfig'));
	}
	
	/**
	 * displays the Backoffice configuration page
	 */
	function newsletterConfig(){
		include_once "backoffice.php";
	}
	
	/**
	 * Checks if a table already exists
	 *
	 * @param string $table - table name
	 * @return boolean True if the table already exists
	 **/
	function tableExists($table){
		global $wpdb;

		return strcasecmp($wpdb->get_var("show tables like '$table'"), $table) == 0;
	}
	
	/**
	 * Installation function, creates tables if needed and sets default values for settings
	 **/
	function install(){
		global $table_prefix, $wpdb;
		
		/*plugin tables*/
		$table = $table_prefix . "snews_members";
		
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		
	  	if (!ajaxNewsletter::tableExists($table)) {
	  		$sql = "CREATE TABLE $table (
	        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
	        email varchar(100) NOT NULL,
	        state ENUM('active', 'waiting') NOT NULL,
			joined datetime NOT NULL,
			user bigint(20) UNSIGNED,
	        confkey varchar(100),
			UNIQUE KEY id (id)
	       );";
	
	  		dbDelta($sql);
	  	}
	  	
	  	add_option("snews_count","5");
	  	add_option("snews_period","manual");
	  	add_option("snews_template","{TITLE}\n{DATE} - Опубликовано {AUTHOR}\n\n{EXCERPT}\n{URL}\n\n");
	  	add_option("snews_last","1970-01-01 00:00:00");
	  	add_option("snews_last_letter","1970-01-01 00:00:00");
	  	add_option("snews_header","");
	  	add_option("snews_footer","");
	  	add_option("snews_subject",get_bloginfo(). " - Розсылка");
	  	add_option("snews_from",get_bloginfo("admin_email"));
	}
	
	/**
	 * Subscribes the user to the newsletter
	 *
	 * @param string $email Email to subscribe the newsletter
	 * @return array An array with two values: 
	 * 	['result'] boolean with True is subscribed, false otherwise; 
	 * 	['message'] string with the success or error message.
	 */
	function subscribe($email){
		$returnVal = array();
		/* switch to this If if you wish to support emails@localhost as a valid email address*/
		//if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,3})*$", $email)){
		if(!eregi("^[_a-z0-9-]+(\.[_a-z0-9-]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,4})$", $email)){
			$returnVal['result']=false;
			$returnVal['message']="Некоректый адрес.";
			return $returnVal;
		}
		
		$state = ajaxNewsletter::getSubscriptionState($email);
		//Test if subscription already exists
		if($state != "active"){
			if($state == ""){ //new email
				//generate confkey
				$confKey = md5(uniqid(rand(),1));	
				if(ajaxNewsletter::sendConfEmail($email, $confKey)){
					//email was sent
					if(ajaxNewsletter::addMember($email, $confKey)){
						$returnVal['result']=true;
						$returnVal['message']="Подтверждение выслано на ваш имейл.";
						return $returnVal;
					}
				}
				
				$returnVal['result']=false;
				$returnVal['message']="Произошла ошибка , попробуйте пожже.";
				return $returnVal;
				
			}else{//Existing subscriber but in an inactive state, we will resend the activation email
				if(ajaxNewsletter::resendConfEmail($email)){
					$returnVal['result']=true;
					$returnVal['message']="Подтверждение переслано на ваш имейл.";
				}else{
					$returnVal['result']=false;
					$returnVal['message']="Произошла ошибка , попробуйте пожже.";
				}
				return $returnVal;
			}
		}else{ //active email requested a subscription
			$returnVal['result']=false;
			$returnVal['message']="Ваш адресс уже подписан";
			return $returnVal;
		}
	}
	
	/**
	 * Resends a subscription confirmation to a given email
	 * 
	 * @param string $email The destination email 
	 */
	function resendConfEmail($email){
		global $table_prefix, $wpdb;
		$table = $table_prefix . "snews_members";
		$email = addslashes( $email );
		$key = $wpdb->get_var("SELECT confkey FROM $table WHERE email = '$email'");
		return ajaxNewsletter::sendConfEmail($email, $key);
	}
	
	/**
	 * Gets the subscription state for the given email
	 * 
	 * @param string $email The destination email
	 * 
	 * @return string The state of the current subscription. 
	 * 	An empty string is sent if no subscription exists
	 */
	function getSubscriptionState($email){
		global $table_prefix, $wpdb;
		$table = $table_prefix . "snews_members";
		$email = addslashes( $email );
		return $wpdb->get_var("SELECT state FROM $table WHERE email = '$email'");
	}
	
	/**
	 * Adds a subscriber to the newsletter table
	 * 
	 * @param string $email Email of the subscriber
	 * @param string $confKey Confirmation key
	 * @param string $status Status of the subscriber (waiting or active)
	 * 
	 * @return boolean True if added successfully, false otherwise
	 */
	function addMember($email,$confKey, $status="waiting"){
		global $table_prefix, $wpdb;
		
		$userid = ajaxNewsletter::getUser($email);
		
		/*plugin tables*/
		$table = $table_prefix . "snews_members";
		
		$query = "INSERT INTO $table (email,state,confkey,joined, user) ";
		$query .= "VALUES ('$email','$status','$confKey', NOW(), $userid);";
        $results = $wpdb->query( $query );
		return $results != '';
	}
	
	/**
	 * Gets the user based on email address or on its login
	 * 
	 * @param string $email Email of the subscriber
	 * 
	 * @return int Id of the user or Zero if no user is found
	 */
	function getUser($email){
		global $user_ID, $wpdb;;
		if(is_numeric($user_ID)){
			return $user_ID;
		}
		//not logged in, so we have to check if the email is already used by a user
		$query = "SELECT * FROM {$wpdb->users} WHERE user_email='$email';";
		$results = $wpdb->get_row( $query );
		if($results != "")
			return $results->ID;
		return 0;
	}
	
	/**
	 * Gets all member to the newsletter
	 * 
	 * @param string $status Status of the members (waiting or active). If empty all will be displayed
	 * @return boolean True if added successfully, false otherwise
	 */
	function getMemberCount($status=""){
		global $table_prefix, $wpdb;
		
		/*plugin tables*/
		$table = $table_prefix . "snews_members";
		$query = "SELECT Count(*) as Count FROM $table";
		if($status != ""){
			$query .= " WHERE state='$status'";
		}
		$query .= ";";
        $results = $wpdb->get_var( $query );
		return $results;
	}
	
	/**
	 * Gets all newsletter subscribers
	 * 
	 * @param string $status Status of the members (waiting or active). If empty all will be displayed
	 * 
	 * @return boolean True if added successfully, false otherwise
	 */
	function getMembers($status=""){
		global $table_prefix, $wpdb;
		
		/*plugin tables*/
		$table = $table_prefix . "snews_members";
		$query = "SELECT * FROM $table";
		if($status != ""){
			$query .= " WHERE state='$status'";
		}
		$query .= ";";
        $results = $wpdb->get_results( $query );
		return $results;
	}

	/**
	 * Checks if a user is already a subscriber
	 * 
	 * @param string $email Email to test
	 * @param int $userID User identifier to test
	 * 
	 * @return boolean True the user already subscribed, false otherwise
	 */
	function isSubscriber($email="", $userID=null){
		global $table_prefix, $wpdb;
		
		
		if($email != "" && ajaxNewsletter::getSubscriptionState($email) != ""){
			return true;
		}
	
		if($userID == null || !is_numeric($userID))
			return false;
		
		/*plugin tables*/
		$table = $table_prefix . "snews_members";
		
		$query = "SELECT COUNT(*) FROM $table ";
		$query .= "WHERE user = $userID;";
        $result = $wpdb->get_var( $query );
		return $result > 0;
	}
	
	/**
	 * Tests if a confirmation key is valid
	 * 
	 * @param string $confKey Confirmation key
	 * 
	 * @return boolean True if the key is valid, false otherwise
	 */
	function isConfirmation($confKey){
		global $table_prefix, $wpdb;
		$table = $table_prefix . "snews_members";
		return $wpdb->get_var("SELECT id FROM $table WHERE confkey = '$confKey';") != "";
	}
	
	/**
	 * Gets subscription Id based on confirmation key
	 * 
	 * @param string $confKey Confirmation key
	 * 
	 * @return boolean True if the key is valid, false otherwise
	 */
	function getConfirmationId($confKey){
		global $table_prefix, $wpdb;
		$table = $table_prefix . "snews_members";
		return $wpdb->get_var("SELECT id FROM $table WHERE confkey = '$confKey';");
	}
	
	/**
	 * Gets subscription email based on confirmation key
	 * 
	 * @param string $id Id of the email
	 * 
	 * @return string Email address or empty string if it does not exist
	 */
	function getSubscriptionEmail($id){
		global $table_prefix, $wpdb;
		$table = $table_prefix . "snews_members";
		return $wpdb->get_var("SELECT email FROM $table WHERE id = '$id';");
	}
	
	/**
	 * Activates a subscriber
	 * 
	 * @param  int $id Subscriber identifier
	 * 
	 * @return boolean True on success, false otherwise
	 */
	function activateSubscriber($id){
		global $table_prefix, $wpdb;
		$table = $table_prefix . "snews_members";
		
		$query = "SELECT * FROM $table WHERE id='$id';";
		$result = $wpdb->get_row( $query );
		if($result != "" && $result->state == "waiting"){
			if(ajaxNewsletter::sendSubSuccess($result->email,$result->confkey)){
				$query = "UPDATE $table Set state = 'active' WHERE id='$id';";
				$results = $wpdb->query( $query );
				return $results == 1;
			}else{
				return false;
			}
		}
		return true;
	}
	
	/**
	 * Removes a subscriber
	 * 
	 * @param  int $id Subscriber identifier
	 * 
	 * @return boolean True on success, false otherwise
	 */
	function removeSubscriber($id){
		global $table_prefix, $wpdb;
		$table = $table_prefix . "snews_members";
		
		$query = "DELETE FROM $table WHERE id='$id';";
		$results = $wpdb->query( $query );
		return true;
	}
	
	/**
	 * Prints the settings page in the backoffice
	 */
	function settings(){
		//Get al plugin options to fill the form
		$count = 	get_option("snews_count");
		$period = 	get_option("snews_period");
		$header = 	stripslashes(get_option("snews_header"));
		$template = stripslashes(get_option("snews_template"));
		$footer = 	stripslashes(get_option("snews_footer"));
		$subject = 	stripslashes(get_option("snews_subject"));
		$from = 	stripslashes(get_option("snews_from"));
		
		$path = get_bloginfo("wpurl") . "/wp-content/plugins/wp-ajax-newsletter/"
		?>
		<script type="text/javascript">
			function toggleState (value, elementId) {
				var element = document.getElementById(elementId);
				element.disabled = value;
				
				return true;
			}
		</script>
		
		<div class="wrap">
			<h2><?php _e('Настройки'); ?></h2>
			<form id="settings" name="settings" action="?page=wp-ajax-newsletter/wp-ajax-newsletter.php&amp;mode=settings" method="post">
				<table class="widefat">
					<tbody>
						<tr>
							<th scope="row" style="width:6em;text-align:left;vertical-align:top;">Periodicity:</th>
							<td>
								<input <?php if($period=="manual") echo "CHECKED" ?> type="radio" id="period_0"
				name="period" value="manual" onclick="toggleState(true, 'count');" /><label for="period_0"> Manual</label><br /> 
								<input <?php if($period=="week") echo "CHECKED" ?> type="radio" id="period_1"
				name="period" value="week" onclick="toggleState(true, 'count');" /><label for="period_1"> Weekly</label><br />
								<input <?php if($period=="month") echo "CHECKED" ?> type="radio"
				id="period_2" name="period" value="month"
				onclick="toggleState(true, 'count');" /><label for="period_2"> Monthly</label><br />
								<input <?php if($period=="every") echo "CHECKED" ?> type="radio"
				id="period_3" name="period" value="every"
				onclick="toggleState(false, 'count');" /><label for="period_3"> Every</label>
								<input <?php if($period!= "every") echo "DISABLED"; ?> style="width:3em;" type="text"
				name="count" id="count" value="<?php echo $count; ?>" /><label for="count"> posts</label>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<b>Note:</b><br />
								In order to trigger the send newsletter event, for monthly and weekly 
								newsletters, the weblog's homepage, i.e. the front-office, needs to have 
								at least one access.
							</td>
						</tr>
						<tr>
							<td colspan="2"><hr /></td>
						</tr>
						<tr>
							<th style="text-align:left;vertical-align:top;" scope="row"><label style="vertical-align:top;" for="letterFrom"> From: </label></th>
							<td> 
								<input type="text" style="width:250px;" name="letterFrom" id="letterFrom" value="<?php echo $from; ?>" /><br />
							</td>
						</tr>
						<tr>
							<th style="text-align:left;vertical-align:top;" scope="row"><label style="vertical-align:top;" for="letterSubject"> Subject: </label></th>
							<td> 
								<input type="text" style="width:500px;" name="letterSubject" id="letterSubject" value="<?php echo $subject; ?>" /><br />
							</td>
						</tr>
						<tr>
							<th style="text-align:left;vertical-align:top;" scope="row"><label style="vertical-align:top;" for="letterHeader"> Header: </label></th>
							<td> 
								<textarea style="height:6em;width:800px;" name="letterHeader" id="letterHeader" >
<?php echo $header; ?>
</textarea><br />
							</td>
						</tr>
						<tr>
							<th style="text-align:left;vertical-align:top;" scope="row"><label style="vertical-align:top;" for="letterTemplate"> Template: </label></th>
							<td> 
								<textarea style="height:9em;width:800px;" name="letterTemplate" id="letterTemplate" >
<?php echo $template; ?>
</textarea><br />
								You can use the following tags to get the post information: <code>{TITLE}
			{UPTITLE} {URL} {DATE} {TIME} {AUTHOR} {EXCERPT} {CONTENT}</code>
							</td>
						</tr>
						<tr>
							<th style="text-align:left;vertical-align:top;" scope="row"><label style="vertical-align:top;" for="letterFooter"> Footer: </label></th>
							<td> 
								<textarea style="height:6em;width:800px;" name="letterFooter" id="letterFooter" >
<?php echo $footer; ?>
</textarea><br />
							</td>
						</tr>
						<tr>
							<td colspan="2"> 
								<b>Note:</b><br />
								All messages are sent in plain text.
							</td>
						</tr>
					</tbody>
					
				</table>
				<div class="submit">
					<input name="preview" type="button" value="Preview" onclick="bodyOverlayFX('bodyOverlay','letterHeader','letterFooter','letterTemplate');" />
					<input name="submit" type="submit" value="Update" />
				</div>
			</form>
		</div>
		<?php
	}
	
	/**
	 * Prints the send newsletter container (Backoffice)
	 */
	function printSendDiv($manualLimit=0){
		//get relevant options
		$period = get_option("snews_period");
		$last = get_option("snews_last");
		$lastNewsLetter = get_option("snews_last_letter");
		
		echo "<div class=\"wrap\">\n<h2>Рacсылка</h2>";
		$date = mysql2date(get_option('date_format'), $lastNewsLetter);
		$time = mysql2date(get_option('time_format'), $lastNewsLetter);
		
		$year = mysql2date("Y", $lastNewsLetter);
		if($year != "1970"){
			if($last != $lastNewsLetter){
				$postsBefore = ",с сообщениями опубликоваными до<b> ";
				$postsBefore .= mysql2date(get_option('date_format'),$last). "</b> at <b>";
				$postsBefore .= mysql2date(get_option('time_format'), $last) ."</b>";
			}
			$lastMessage = "<p>Последнее сообщение было отослано <b>$date</b> at <b>$time</b>$postsBefore.</p>";
		}else{
			$lastMessage = "<p>Сообщение не было отослано.</p>";
		}
			
		switch($period){
			case "every":
				//It is done automatically on post publish
				//Just provide some information
				$count = get_option("snews_count");
				$postText = "";
				
				$posts = ajaxNewsletter::getPostsSince("",$last); 
				$numPosts = count($posts);
				
				$postText = ajaxNewsletter::getNumberText($count,"post");
				$countText = ajaxNewsletter::getNumberText($numPosts,"post");
				if($numPosts == 1){
					$countText = "is $countText";
				}else{
					$countText = "are $countText";
				}
				
				
				echo "<p>This is done automatically every time a new post is published, if there are at least $postText in queue and an active subscriber.<br />Currently, there $countText in queue.</p>";
				echo $lastMessage;
				break;
			case "manual":
				//manual submition
				$posts = ajaxNewsletter::getPostsSince("",$last); 
				$numPosts = count($posts);
				
				$members = ajaxNewsletter::getMemberCount("active");
				
				$disable = ($numPosts == 0 || $members== 0);
				
				$postText = ajaxNewsletter::getNumberText($numPosts,"post");
				$membersText = ajaxNewsletter::getNumberText($members,"subscriber");
	
				?>
		<p>This will send your newsletter containing <?= $postText ?> to <?= $membersText ?>.</p>
				<?php 
				echo $lastMessage;
				if($disable){
					echo "<p><b>Note:</b><br />";
					if($numPosts == 0){
						echo "Нет сообщений для отсылки";
					}
					if($members == 0){
						if($numPosts == 0){
							echo " and the ";	
						}else{
							echo "The ";
						}
						echo "нет активированых подписчиков для розсылки";
					}
					
					echo ".</p>";
				}
				if(is_string($manualLimit) || $manualLimit != 0){
					$sendValue = $manualLimit;
				}else{
					$sendValue = $numPosts;
				}
				
				?>
		<form action="" method="post" id="newsSend" name="newsSend">
			Send only the last <input <?php if($disable){ echo "DISABLED"; } ?> style="width: 3em;" type="text" name="postLimit" id="postLimit" value="<?php echo $sendValue; ?>" /> posts.<br />
			<div class="submit">
				<input type="submit" name="send" value="Send" <?php if($disable){ echo "DISABLED"; } ?> />
			</div>
		</form>
			
				<?php
				break;
			case "month":
			case "week":
				//Weekly or monthly newsletter
				echo "<p>This is done automatically every <b>$period</b> if the requirements are met:<br /><ul>";
				echo "<li>At least one post was published since the last newsletter;</li>";
				echo "<li>An access is made to your weblog's homepage;</li>";
				echo "<li>A $period has ended.</li>";
				echo "</ul></p>";
				echo $lastMessage;
				break;
			default:
				break;
		}
		echo "</div>";
	}
	
	function getNumberText($number, $word){
		if($number == 1){
			return "<b>$number</b> $word";
		}else{
			return "<b>$number</b> {$word}s";
		}	
	}
	
	/**
	 * Get all posts in a given interval
	 * @param string $to The date and time to when we wish to get the posts. (Format: Y-m-d H:i:s)
	 * @param string $since The date and time from when we wish to get the posts. (Format: Y-m-d H:i:s) The default value is an empty string.
	 * @return array An array of posts
	 */
	function getPostsSince($to="", $since=""){
		global $table_prefix, $wpdb;
		/*plugin tables*/
		$table = $table_prefix . "snews_members";
		$results = array();
		if($since != "")
			$sinceString = "AND post_date >= '$since'";
		if($to != "")
			$toString = "AND post_date < '$to'";
		$query = "SELECT * FROM {$wpdb->posts} WHERE post_type='post' AND post_status='publish' $sinceString $toString ORDER BY post_date DESC;";
		$results = $wpdb->get_results($query);
		return $results;
	}
	
	/**
	 * Generates the post excerpt if it does not caontain one already
	 * //copied from wordpress core
	 * 
	 * @param object $post The post object
	 * @return string The post excerpt
	 */
	function generateExcerpt($post) {
		$text = $post->post_excerpt;
		if ( '' == $text ) {//No excerpt available so we need to fake it
			$text = $post->post_content;
			$text = str_replace(']]>', ']]&gt;', $text);
			$text = strip_tags($text);
			$excerpt_length = 55;
			$words = explode(' ', $text, $excerpt_length + 1);
			if (count($words) > $excerpt_length) {
				array_pop($words);
				array_push($words, '[...]');
				$text = implode(' ', $words);
			}
		}
		return $text;
	}
	
	
	/**
	 * Generates the newsletter content based on the available posts and the template
	 * @param array $posts An array of posts to be added to the newsletter body
	 * @param int $limit The maximum number of posts to send
	 * @return string The newsletter content formated accordingly to the template
	 */
	function generateContent($posts, $limit=0){
		$string = "";
		$template = get_option("snews_template");
		$postCount = 0;
		foreach($posts as $post){
			$postContent = $template;
			$excerpt = ajaxNewsletter::generateExcerpt($post);
			$date = mysql2date(get_option('date_format'), $post->post_date);
			$time = mysql2date(get_option('time_format'), $post->post_date);
			$title = $post->post_title;
			$url = get_permalink($post->ID);
			$author = get_author_name($post->post_author);
			$content = strip_tags($post->post_content);
			
			//replace the template tags with real content
			$postContent = str_replace("{EXCERPT}", $excerpt, $postContent);
			$postContent = str_replace("{CONTENT}", $content, $postContent);
			$postContent = str_replace("{AUTHOR}", $author, $postContent);
			$postContent = str_replace("{URL}", $url, $postContent);
			$postContent = str_replace("{TITLE}", $title, $postContent);
			$postContent = str_replace("{UPTITLE}", strtoupper($title), $postContent);
			$postContent = str_replace("{DATE}",$date,$postContent);
			$postContent = str_replace("{TIME}", $time, $postContent);
				
			$postContent .="\n";
			$string .= $postContent;
			
			$postCount++;
			if($limit > 0 && $postCount >= $limit){
				break;
			}
		}
		return $string;
	}
	
	/**
	 * Prints the members/subscribers table for the backoffice
	 */
	function manageMembers(){
		$members = ajaxNewsletter::getMembers();
		?>
		<script type="text/javascript">
function DelConfirm(email){
  var message= 'Удалить "'+email+'"?';
  return confirm(message);
}
function ActivateConfirm(email){
  var message= 'Активировать "'+email+'"?';
  return confirm(message);
}
		</script> 
		<div id="snewsMembers" class="wrap">
			
			<h2><?php _e('Подписчики'); ?></h2>
			
			<?php
			if($members != "" && count($members) > 0){
			?>
			<table class="widefat">
				<thead>
					<tr>
						<th scope="col">E-mail</th>
						<th scope="col">Username</th>
						<th scope="col">Since</th>
						<th colspan="2" scope="col" style="width:4em;">Action</th>
					</tr>
				</thead>
				<tbody>
				<?php
				$alt = true;
				foreach($members as $member){
					if($alt){
						echo "<tr class='alternate'>";
					}else{
						echo "<tr>";
					}
					$alt = !$alt;
				?>
						<td><?php echo $member->email; ?></td>
						<td><?php 
						if($member->user != 0 && is_numeric($member->user)){
							$user = get_userdata($member->user);
							echo $user->user_nicename;
						}else{
							echo "(not registered)";
						} 
						?></td>
						<td><?php echo $member->joined; ?></td>
						<td  style='text-align:center'>
							
						<?php if($member->state == "waiting"){?>
							<a class="edit"
			href="<?php echo "?page=wp-ajax-newsletter/wp-ajax-newsletter.php&amp;actv=".$member->id; ?>#msgMembers"
			onclick="return ActivateConfirm('<?php echo $member->email; ?>');">Activate</a>
						<?php }else{ echo "Active"; } ?>
						</td>
						<td>
							<a class="delete"
			href="?page=wp-ajax-newsletter/wp-ajax-newsletter.php&amp;del=<?php echo $member->id; ?>#msgMembers"
			onclick="return DelConfirm('<?php echo $member->email; ?>');">Delete</a>
						</td>
					</tr>
				<?php
				}
				?>
				</tbody>
			</table>
			<?php
			}else{
				echo "<b>Нету подписчиков на это сообщение.</b>";	
			}
			?>
		</div>
		<?php
	}
	
	/**
	 * Sends an email
	 * @param string $from The email from the sender 
	 * @param string $to Emails separated with commas to send the email to
	 * @param string $cc Emails separated with commas to send the email to as CC
	 * @param string $bcc Emails separated with commas to send the email to as BCC
	 * @param string $subject The email subject
	 * @param string $content The email body
	 * 
	 * @return bool True if the email was send, false otherwise
	 */
	function sendEmail($from,$to,$cc,$bcc,$subject,$content){
		$headers  = 'MIME-Version: 1.0' . "\r\n";
		//For future versions to support HTML email we need to set the header describing the content-type
		//$headers .= 'Content-type: text/html; charset=iso-8859-1' . "\r\n";

		//we should ensure all readers can read the text, so we must specify the charset
		$headers .= 'Content-type: text/plain; charset='.get_option('blog_charset') . "\r\n";
		
		// Additional headers
		
		/** 
		 * Removed To: from headers to prevent duplicated emails.
		 * Thanks to mauro_at_bjmaster_dot_com. 
		 **/
		/* if($to != ""){
			$headers .= 'To: '.$to ."\r\n";
		}*/
		
		$headers .= 'От: '. $from . "\r\n";
		
		if($cc != ""){
			$headers .= 'Cc: '.$cc ."\r\n";
		}
		if($bcc != ""){
			$headers .= 'Bcc: '.$bcc ."\r\n";
		}
		
		//hide errors, thus the @
		@$value = mail($to, $subject, $content, $headers);
		
		return $value;
	}
	
	/**
	 * Send the newsletter to all subscribers
	 * @param string $content The content to be send in the newsletter
	 * @param string $date The date to overwrite the last newsletter date (optional)
	 * 
	 * @return bool ture if all emails were sent, false if an error occured.
	 */
	function sendNewsletter($content, $date = ""){
		$members = ajaxNewsletter::getMembers("active");
		
		$header = get_option("snews_header");
		$footer = get_option("snews_footer");
		$subject = get_option("snews_subject");
		$from = get_option("snews_from");
		
		$sent = false;
		foreach ($members as $member){
			$to  = $member->email;
			$confirmationURL = get_bloginfo("wpurl") . "/wp-content/plugins/wp-ajax-newsletter/confirmation.php?del={$member->confkey}";
			$message = "";
			$message .= "$header\n";
			$message .= "$content\n";
			$message .= "$footer\n";
			$message .= "\n--------------------\n";
			$message .= "  Если вы хотите удалить себя из списка розсылки перейдите по следующей ссылке:\n";
			$message .= "  $confirmationURL\n";
			$message = wordwrap($message, 75, "\n");
			
			if(!ajaxNewsletter::sendEmail($from,$to,"","",$subject,$message)){
				return false; //an error occured so we stop sending emails
			}
			$sent = true;
		}
		//we set the new date for the last newsletter
		if($sent){
			$now = mktime();
			$now ++;
			//last post date
			if($date != ""){
				update_option("snews_last", $date);
			}else{
				update_option("snews_last", date("Y-m-d H:i:s", $now));
			}
			//last newsletter date
			update_option("snews_last_letter", date("Y-m-d H:i:s", $now));
		}
		
		return true;
	}
	
	/**
	 * Sends a confirmation email to the subscriber
	 * 
	 * @param string $email Email of the subscriber
	 * @param string $confKey Confirmation key
	 * 
	 * @return bool True if the confirmation was sent, false otherwise
	 */
	function sendConfEmail($email, $confKey){
		$from = get_option("snews_from");
		$subject = "[Confirm] " .get_option("snews_subject");
		$title = get_bloginfo("name");
		$url = get_bloginfo("wpurl");
		
		$confirmationURL = $url . "/wp-content/plugins/wp-ajax-newsletter/confirmation.php?add=$confKey";
		
		$message = "";
		$message .= "Вы получили письмо с подтверждением о подписке от $title at:\n$url\n";
		$message .= "\nIn чтобы подтвердить подписку перейдите по следующей ссылке:\n";
		$message .= "$confirmationURL\n";
		$message .= "\nЕсли вы не хотите получать розсылку просто игнорируйте это сообщение.\n";
		
		$message = wordwrap($message, 75, "\n");
		
		return ajaxNewsletter::sendEmail($from,$email,"","",$subject,$message);
	}
	
/**
	 * Sends a subscription success email
	 * 
	 * @param string $email Email of the subscriber
	 * 
	 * @return bool True if the confirmation was sent, false otherwise
	 */
	function sendSubSuccess($email,$key){
		$from = get_option("snews_from");
		$subject = "[Confirmation] " .get_option("snews_subject");
		$title = get_bloginfo("name");
		$url = get_bloginfo("wpurl");
		
		$confirmationURL = $url . "/wp-content/plugins/wp-ajax-newsletter/confirmation.php?del=$key";
		
		$message .= "Вы успешно подписались на получение розсылки от $title at:\n$url\n\n\n";

		$message .= "Если вы хотите удалить себя из списка розсылки перейдите по следующей ссылке:\n";
		$message .= "  " . $confirmationURL."\n";
		$message = wordwrap($message, 75, "\n");
		
		return ajaxNewsletter::sendEmail($from,$email,"","",$subject,$message);
	}
	
	/**
	 * Writes the success/error messages
	 * 
	 * @param string $string - message to be displayed
	 * @param boolean $success - boolean that defines if is a success(true) or error(false) message
	 **/
	function printMessage($string, $success=true, $anchor = "message"){
		if($success){
			echo '<div id="'.$anchor.'" class="updated fade"><p>'.$string.'</p></div>';
	 	}else{
	 		echo '<div id="'.$anchor.'" class="error fade"><p>'.$string.'</p></div>';
	 	}
	}
	
	/**
	 * Writes the success/error messages in the front-office
	 * 
	 * @param string $string - message to be displayed
	 * @param boolean $success - boolean that defines if is a success(true) or error(false) message
	 **/     
	function printMessageFO($string, $success=true){
		if($success){
	 		echo '<div class="success">'.$string.'</div>';
	 	}else{
	 		echo '<div class="error">'.$string.'</div>';
	 	}
	}
	
	/**
	 * From wordpress core with some changes to avoid trimming the content
	 */
	function update_option($option_name, $newvalue) {
		global $wpdb;
	
		// If the new and old values are the same, no need to update.
		$oldvalue = get_option($option_name);
		if ( $newvalue == $oldvalue ) {
			return false;
		}
		
		$newvalue = $wpdb->escape($newvalue);
		$option_name = $wpdb->escape($option_name);
		
		wp_cache_set($option_name, $newvalue, 'options');
	
		if ( false === $oldvalue ) {
			$wpdb->query("INSERT INTO $wpdb->options (option_name, option_value, option_description, autoload) VALUES ('$option_name', '$newvalue', '', 'yes')");
			return true;
		}else{	
			$wpdb->query("UPDATE $wpdb->options SET option_value = '$newvalue' WHERE option_name = '$option_name'");
			if ( $wpdb->rows_affected == 1 ) {
				do_action("update_option_{$option_name}", $oldvalue, $_newvalue);
				return true;
			}
		}
		return false;
	}
	
	/**
	 * Updates settings in the database
	 * 
	 * @param array $settingsArray Contains the pairs array[key]=value for the plugin settings
	 */
	function saveSettings($settingsArray){
		$keys = array_keys($settingsArray);
		foreach	($keys as $key){
			ajaxNewsletter::update_option("snews_".$key,$settingsArray[$key]);
		}
	}
	
	/**
	 * Prints the subscription form with the required includes and containers. To be used in the front-office
	 */
	function newsletterForm(){
		global $user_ID;
		$email = "";
		if(is_numeric($user_ID)){
			$user = get_userdata($user_ID);
			$email = $user->user_email;
			
			//check if the user already subscribed
			if(ajaxNewsletter::isSubscriber($email,$user_ID)){
				$email = "";
			}
		}
		
		$newsletterURL = get_bloginfo("wpurl") . "/wp-content/plugins/wp-ajax-newsletter/";
		
		$action = $_SERVER["REQUEST_URI"];
		echo '<script src="'.get_bloginfo('wpurl').'/wp-includes/js/tw-sack.js" type="text/javascript"></script>'."\n";
		?>
		
		<script type="text/javascript" src="<?= $newsletterURL ?>snews_ajax.js"></script>
		
		<div class="newsletterContainer" id="ajaxNewsletter">
		<?php ajaxNewsletter::subscriptionForm($email); ?>
		</div>
		<div style="display:none" id="newsletterLoading"><img src="<?= $newsletterURL ?>/loading.gif" alt="Loading..." title="Loading..." /> Loading ...</div>
		<?php
	}
	
	/**
	 * Prints only the from for newsletter subscription (auxiliary method, do not use in the template)
	 */
	function subscriptionForm($email=""){
		$action = get_bloginfo("url");
?>
	<form action="javascript:StartFade('<?= $action ?>','ajaxNewsletter','newsletterLoading');"
			name="newsletterForm" id="newsletterForm" method="post">
		<div class="rightAlign" id="newsletterFormDiv">
			<input
				class="newsletterTextInput"
				onblur="if(this.value==''){this.value='Введите ваш email '}"
				onfocus="if(this.value=='Введите ваш email'){this.value=''}"
				type="text" name="email"
				value="<?php if($email != "") echo $email; else echo 'Введите ваш email'; ?>" />
			<input type="hidden" id="newsletter" name="newsletter" value="true" />
			<input class="submit" type="submit" name="newsletterSub"
				value="Подписаться" />
		</div>
	</form>
<?php	
	}
	
	/**
	 * Handles Ajax requests for new subscriptions
	 */
	function process_newsletterSub() {
		global $wpdb, $user_identity, $user_ID;
		
		if(empty($_REQUEST['newsletter'])) {
			//It is not a subscription request so we let it be
			return;
		}
		
		$email = $_REQUEST['email'];
		//has the user entered an email ?
		if($email != "" && $email != "Введите ваш email") {
			
			// Check For Bot
			$bots_useragent = array('googlebot', 'google', 'msnbot', 'ia_archiver', 'lycos', 'jeeves', 'scooter', 'fast-webcrawler', 'slurp@inktomi', 'turnitinbot', 'technorati', 'yahoo', 'findexa', 'findlinks', 'gaisbo', 'zyborg', 'surveybot', 'bloglines', 'blogsearch', 'ubsub', 'syndic8', 'userland', 'gigabot', 'become.com');
			$useragent = $_SERVER['HTTP_USER_AGENT'];
			foreach ($bots_useragent as $bot) { 
				if (stristr($useragent, $bot) !== false) {
					//goodbye bot
					return;
				} 
			}
			
			$message = "";
			$result = 0;
			$value = ajaxNewsletter::subscribe($email);
			
			ajaxNewsletter::printMessageFO($value['message'], $value['result']);
			if(!$value['result'])
				ajaxNewsletter::subscriptionForm($email);
			else
				ajaxNewsletter::subscriptionForm();
			
			exit();//prevent further processing from wordpress
		}
		ajaxNewsletter::printMessageFO("Введите имейл.", false);
		ajaxNewsletter::subscriptionForm();
		exit();//prevent further processing from wordpress
	}
	
	/**
	 * Prints the confirmation page with the supplied content
	 */
	function writeConfirmationPage($content){
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title>Подпишитесь на Рассылку</title>
		<link rel="stylesheet"
			href="<?php echo get_bloginfo("wpurl"); ?>/wp-admin/wp-admin.css"
			type="text/css" />
		<style type="text/css">
		#info h1{
			text-align: center;
		}
		
		.errorTitle{
			margin: 10px 0px;
			background: #FFEFF7;
			border: 1px solid #c69;
			padding: .5em;
		}
		
		.success{
			margin: 10px 0px;
			background: #CFEBF7;
			border: 1px solid #2580B2;
			padding: .5em;
		}
		
		#info {
			background: #fff; 
			border: 1px solid #a2a2a2; 
			margin: 5em auto; 
			padding: 2em; 
			width: 80%;
			min-width: 35em;
		}
		
		#info ul{
			list-style:disc;
			margin: 0px;
			padding: 0px;
		}
		
		#info ul li {
			display: list-item;
			margin-left: 1.4em;
			text-align: left;
		}
		
		#inlineList ul{
			list-style: none;
			margin: 0px;
			padding: 0px;
		}
		
		#inlineList ul li {
			display: inline;
			margin-right: 1.4em;
			margin-left: 0px;
			text-align: center;
		}
		</style>
	</head>
	<body>
		<div id="info">
			<?php echo $content; ?><br /><br />
			<div id="inlineList">
				<ul>
					<li><a href="<?php bloginfo('home'); ?>"
						title="<?php bloginfo('name'); ?>">&laquo; Go to <?php echo get_bloginfo("name"); ?></a>
					</li>
				</ul>
			</div>
		</div>
	</body>
</html><?php
	}
}
//instance of the newsletter to run the constructor
$newsletter = new ajaxNewsletter();
?>
