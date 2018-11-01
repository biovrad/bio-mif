<?php
MailPress::require_class('Options');

class MP_Autoresponders_events_comment extends MP_Options
{
	var $path = 'autoresponder/events_comment';
	var $abstract = 'Autoresponders_event_abstract';
}
new MP_Autoresponders_events_comment();