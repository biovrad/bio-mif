<?php 

require_once('../MP_Iframe_abstract.class.php');

class MP_Facebook extends MP_Iframe_abstract
{
	function init()
	{
		include('facebook-config.php');

	// Facebook api
		require_once('api/facebook.php');
		$facebook = new Facebook($this->fb_api_key, $this->fb_secret);
		$user_id  = $facebook->require_login();
		$user_details = $facebook->api_client->users_getInfo($user_id, 'name, contact_email');
		$this->user_email = $user_details[0]['contact_email'];
		$this->user_name  = $user_details[0]['name'];

	// Clean up $_GET for shortcode attributes
		foreach($_GET as $k => $v) if (strpos($k, 'fb_') == 0) unset($_GET[$k]);

	// Set up $_GET for shortcode attributes
		$_GET['css'] = $_GET['js'] = $_GET['jq'] = 1;
		$_GET['txtloading'] = '';
	}

	function form_email($email) { return $this->user_email; }
	function form_name($name)   { return $this->user_name;  }

	function admin_xml_ns()
	{
		echo 'xmlns:fb="http://www.facebook.com/2008/fbml"';
	}

	function print_styles()
	{
		wp_register_style( 'mp_form',   get_option('siteurl') . '/' . MP_PATH_CONTENT . 'advanced/subscription-form/iframes/facebook/style.css' );
		wp_enqueue_style(  'mp_form');
	}

	function print_scripts()
	{
		wp_register_script( 'mp_form',	'/' . MP_PATH . 'mp-includes/js/mp_form.js', array('jquery') );
		wp_enqueue_script(  'mp_form');
	}

	function form_imgloading($imgloading)
	{
		return get_option('siteurl') . '/' . MP_PATH_CONTENT . 'advanced/subscription-form/iframes/facebook/img/loading.png';
	}

	function get_header()
	{
		include(MP_ABSPATH . 'mp-includes/html/header.php');
		do_action('admin_print_styles');
?>
	</head>
	<body>
<?php 
	}

	function before()
	{
?>
		<div class='MailPressHeader'>
			<?php echo "{$this->user_name}"; ?> Subscribe to our Newsletter !
		</div>
		<div class='MailPressForm_fb'>
<?php
	}

	function after()
	{
?>
		</div>
<?php
	}

	function get_footer()
	{
?>
		<script type='text/javascript'>
			/* <![CDATA[ */
			var MP_Widget = { url: '<?php echo MP_Action_url; ?>'
			};
			/* ]]> */
		</script>
<?php
		do_action('admin_print_scripts'); 
?>
	</body>
</html>
<?php
	}
}
new MP_Facebook();