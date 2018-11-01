<?php
MailPress::require_class('Options');

class MP_Dashboard_widgets_comment extends MP_Options
{
	var $path = 'dashboard/widgets_comment';
	var $abstract = 'Dashboard_widget_abstract';
}
new MP_Dashboard_widgets_comment();