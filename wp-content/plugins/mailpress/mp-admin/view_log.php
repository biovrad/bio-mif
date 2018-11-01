<?php 
MailPress::require_class('Admin_page');

class MP_AdminPage extends MP_Admin_page
{
	const screen 	= 'mailpress_viewlog';
	const capability	= 'MailPress_view_logs';
	const help_url	= 'http://www.mailpress.org/wiki/index.php?title=Add_ons:View_logs';
	const file        = __FILE__;

	// for path
	public static function get_path() 
	{
		return MP_PATH . 'tmp';
	}

////  Title  ////

	public static function title() { global $title; $title = __('MailPress Log', MP_TXTDOM); }

////  Styles  ////

	public static function print_styles($styles = array()) 
	{
		wp_register_style ( self::screen, 	'/' . MP_PATH . 'mp-admin/css/view_log.css' );
		$styles[] = self::screen;

		parent::print_styles($styles);
	}

////  Scripts  ////

	public static function print_scripts($scripts = array()) 
	{
		$scripts = apply_filters('MailPress_autorefresh_files_js', $scripts);

		parent::print_scripts($scripts);
	}
}