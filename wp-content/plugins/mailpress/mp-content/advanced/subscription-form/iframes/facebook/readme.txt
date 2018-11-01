How to make the MailPress subscription form a Facebook application :

. Connect to Facebook

. go to http://www.facebook.com/developers  and click Add Developer

. From the Facebook Developer application's home page, 
	. click button "+Set Up New Application" or go to http://www.facebook.com/developers/createapp.php
	. On Basic tab :
		. give application name etc ...
		. create file facebook-config.php from facebook-config-sample.php ,
		. copy the values of application id, api key and secret values in appropriate fields, 
		. save facebook-config.php file,
		. ftp upload facebook-config.php file to the (...)/advanced/subscription-form/iframes/facebook folder on your site.
	. On Authentication tab :
		. Installable to? : check Users and Facebook Pages
	. On Canvas tab :
		. Canvas Page URL : The base URL for your canvas pages on Facebook.
		. Canvas Callback URL : http://mydomain.com/wp-content/plugins/mailpress/mp-includes/action.php?action=get_form&iframe=facebook
		. Render Method : check Iframe
	. On Connect tab :
		. Connect URL : http://mydomain.com/wp-content/plugins/(...)/advanced/subscription-form/iframes/facebook/
	. Save Changes

	Wiki link on creating your facebook application : http://wiki.developers.facebook.com/index.php/Creating_a_Platform_Application#Creating_Your_Facebook_Application

. Test your subscription form going to your Canvas URL (see Canvas tab above) : http://apps.facebook.com/..../