<?php
MailPress::require_class('Options');

class MP_Dashboard_widgets extends MP_Options
{
	var $path = 'dashboard/widgets';
	var $abstract = 'Dashboard_widget_abstract';
}
new MP_Dashboard_widgets();

do_action('MailPress_dashboard');