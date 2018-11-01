<?php
MailPress::require_class('Options');

class MP_Ip_providers extends MP_Options
{
	var $path = 'ip/providers';
	var $abstract = 'Ip_provider_abstract';

	public static function get_all()
	{
		$providers[MP_Ip::provider] = array('url' => '%1$s', 'type' => 'xml', 'md5' => false);
		return apply_filters('MailPress_ip_provider_register', $providers);
	}
}
new MP_Ip_providers();