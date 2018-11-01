<?php
MailPress::require_class('Options');

class MP_Autoresponders_events_mailinglist extends MP_Options
{
	var $path = 'autoresponder/events_mailinglist';
	var $abstract = array('Autoresponders_event_abstract', 'Autoresponders_event_mailinglist_abstract');
}
new MP_Autoresponders_events_mailinglist();