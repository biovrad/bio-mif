<?php
MailPress::require_class('Options');

class MP_Autoresponders_events extends MP_Options
{
	var $path = 'autoresponder/events';
	var $abstract = 'Autoresponders_event_abstract';

	public static function get_all()
	{
		return apply_filters('MailPress_autoresponder_events_register', array());
	}
}
new MP_Autoresponders_events();