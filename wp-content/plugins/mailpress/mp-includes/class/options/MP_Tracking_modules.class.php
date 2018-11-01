<?php
MailPress::require_class('Options');

class MP_Tracking_modules extends MP_Options
{
	var $path = 'tracking/';
	var $abstract = 'Tracking_module_abstract';

	function __construct($type, $settings = false)
	{
		if ($settings === false)
		{
			$settings = get_option(MailPress_tracking::option_name);
			if (!is_array($settings)) $settings = array();
			$this->includes = $settings;
		}

		$this->path .= $type;
		parent::__construct();
	}

	function get_all($type)
	{
		return apply_filters('MailPress_tracking_module_register', array(), $type);
	}
}