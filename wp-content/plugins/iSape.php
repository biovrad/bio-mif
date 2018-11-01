<?php
/*
Plugin Name: iSape
Version: 0.66 (17-01-2009)
Plugin URI: http://itex.name/isape
Description: SAPE.RU helper. Plugin iSape is meant for the sale of conventional and contextual links in <a href="http://www.sape.ru/r.a5a429f57e.php">Sape.ru</a> .
Author: Itex
Author URI: http://itex.name/
*/

/*
Copyright 2007-2008  Itex (web : http://itex.name/)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

/*

EN
Plugin iSape is meant for the sale of conventional and contextual links in Sape.ru.
Features:
Support for both conventional and contextual links.
Placing links up to the text of page, after page of text in the widget and footer.
Widget course customizable.
Automatic installation of a plug and the rights to the sape folder on request.
Adjustment of amount of displayed links depending on the location.

Requirements:
Wordpress 2.3-2.6.3
PHP5, PHP4
Widget compatible theme, to use the links in widgets.

Installation:
Copy the file iSape.php in wp-content/plugins .
In Plugins activate iSape.
In settings-> iSape enter your Sape Uid.
If you want to create a Sape folder automatically, coinciding with your Sape Uid.
Allow work Sape links, to specify how many references to the use of text after text, widget and footer.
Allow Sape context.
If you are adding content frequently , then content links of main page, tags and categories can return error.
If you frequently add content, the content of the main links, tags, categories can fly out in error.
For preventation of it switch on the option "Show context only on Pages and Posts".
As required switch on Check - a verification code.
For activating widget you shall go to a design-> widgets, activate the widget iSape and point its title.
If define ('WPLANG', 'ru_RU'); in wp-config.php then russian language;

RU
Плагин iSape предназначен для продажи обычных и контекстных ссылок в Sape.ru
Возможности:
Поддержка как обычных, так и контекстных ссылок.
Размещение ссылок до текста страницы, после текста страницы, в виджетах и футере.
Виджет конечно настраиваемый.
Автоматическая установка плагина и прав на папку сапы по желанию.
Регулировка количества показываемых ссылок в зависимости от места расположения.

Требования:
Wordpress 2.3-2.6.1
ПХП5, ПХП4
Виджет совместимая тема, если использовать ссылки в ввиджетах.

Установка:
Скопировать файл iSape.php в wp-content/plugins/ вордпресса.
В плагинах активировать iSape.
В настройках->iSape ввести ваш Sape Uid.
По желанию создать автоматом папку Сапы, совпадающей с вашим Sape Uid.
Разрешить работу Sape links, указать сколько ссылок использовать до текста, после текста, в виджете и футере.
Разрешить Sape context.
Если часто добавляете контент, то контентные ссылки в главной,тегах,категориях могут вылетать в эррор.
Для предотвращения включите опцию "Show context only on Pages and Posts".
По мере надобности включить Check - проверочный код.
Для активации виджета нужно зайти в дизайн->виджеты, активировать виджет iSape и указать его заголовок.
Если define ('WPLANG', 'ru_RU'); в wp-config.php, то будет русский язык.

*/
class itex_sape
{
	var $version = '0.66';
	var $error = '';
	//var $force_show_code = true;
	var $sape;
	var $sapecontext;
	//var $enable = false;
	var $sidebar = '';  //if you want add yor links : var $sidebar = '<a href="http://itex.name">your links</a>';
	var $footer = '';
	var $beforecontent = '';
	var $aftercontent = '';
	var $safeurl = '';
	///function __construct()  in php4 not working
	function itex_sape()
	{
		add_action('init', array(&$this, 'itex_sape_init'));
		add_action("plugins_loaded", array(&$this, 'itex_sape_widget_init'));
		add_action('admin_menu', array(&$this, 'itex_sape_menu'));
		add_action('wp_footer', array(&$this, 'itex_sape_footer'));
		//add_action('template_redirect', array(&$this, 'itex_sape_safe_url'));
		//add_action('plugins_loaded', array(&$this, 'itex_sape_init'));


	}

	function lang_ru()
	{
		global $l10n;
		$locale = get_locale();
		if ($locale == 'ru_RU')
		{
			$domain = 'iSape';
			if (isset($l10n[$domain])) return;
			///ru_RU lang .mo file
			$input = 'eNqllN9PHFUUx28LZena1oo/qlGTu/6iSTO7iy1KpkCkgAmxpJuyWH0RZ3cvzMgys5kZfmzig4CISUmJVaMhNtU3jT4sUHS7wPTNR3PnwcQn40P/Ah98M/F77x3Ylfoj0YGZz9xzzz33nO+5sz+3NX9McD2G+wncd3E/i/ubQ0ReLx0m5EGwD2wDR6OxCT4Evg0+An4Q8UuwBbwDxsDvo/EPIAV/jPgbeAw81UQI/skzeGTA0+BxsAzeBy6DT4IfNin/9SaVXxD53QVPgL82qf0ONavx0WY1/yh4BrU816ziXD5CyONgCTwNroBPgVvgi+BPYAo8iaQfEPWDJ8W6FhU3D74AuhG/alG6/RL5/Q4a4ImY0qMUU7otg53gWkzlz8E3hA4xpcdoKyFPgzOtKs5axK9bVW92QNGSZlK/DkcU+xzFDReppbhORRQ1iDht0fh+3HGitD0i9Il6f7whruiX6PHD0Vj0ORa9H4t4Ys+5d8DyjFyRFbpTTsm3HLs3TnoH7YOmvnGfuTTv2D6zfVq07EmPXGDjjssOGPsN+02f5l1m+IyOGCVGC5ab+LPZgzlZMkswmyw/qZN+EWLOJ/1yngzaYrOyM+3Skb7MIB0dGqCWTX3T8mjOmUv8m0OSvOw4wkOlNGzMkYwzy1xWoLky7Tao6bLxnnbT90t6KmVh66RtTLF26lt+kfW0W1k2Ry2RfHtv/b07ZfTSV5nrQRGdyNLyKu9oJHdT76iZGnZhv9Ko8kICszOM9puGPcE8MmI6s3tBqGMXy3jQjIEpuTrjeL6XJCNWgeWMvWquWIUJ5tOsSFWn5IrjTlr2BHm9UQydyIzpJdk+j2Rc5y2W97WhgrZXAI1nLmU1KTiG2gCykzbtMpuxvLrp+XS6S+tIa2c7aMc5vfPsmXRnOh2/aHi+lnUN2ysavuPqVIgIqz0xjey1LDOmEG14aHiwvmFHMh3vV2dFy5ZLiC3KTpWKhmWfp3nTcD3m90z741pX3U9sMc5cbdDOOwXUqdOunOXHX9MyDkT2tVdYedZxC95Fy/N1OjZ2fozVJy8YHisZvqnTZN04wgw3b2Zg1tI6TZnOFEtNMjdX9lIdqXM4/Px6eJXX+HZ4LVzmW3yXB42fBr/+t3P8i3AeS7d5wGuU3+FBOI/BFsWCgO+GC2IBnpUDjrd48BcuN8TCHVg3w0WKQAH/Dp6VcCFcUW3GqBq+gwU1LAjwVg2vJf55Xfgur/DbfLvhA+Q3sTLgG3wLrPGKTvjafio1pLmAbBuDEAgAb7n7gthrA/b3658h35Clq8I38BeIlYn/tix5QKoNlBAuigCicsI/Qz1Iklf5TrjIdwj/FIluQo0FWdO2atH/++KROHYTm4Sr0Yfe0C0pUWTGq8q1xqvQ8aN7+rNKebXeBFl7vUMIeDUhxQ6X4I2xkApdk0XtyFK2pHEVdWKdlCtcwWQg1ZS+BzKjUgnhVROnbJdXKBRd2I//HtYtiaz4TXFcxdELl+4VfV6mfIuvY1oK/wkMm7JdgexVTchUhce3EAtB8MPEP4fPuuxFRRjFCWhsuS7OakXsKU/gbaGakp2SPwDCqVSu';
			$input = gzuncompress(base64_decode($input));
			$inputReader = new StringReader($input);
			$l10n[$domain] = new gettext_reader($inputReader);
		}
	}

	function itex_sape_init()
	{

		if (!defined('_SAPE_USER')) define('_SAPE_USER', get_option('itex_sape_sapeuser'));
		else $this->error .= __('_SAPE_USER already defined<br/>', 'iSape');

		//FOR MASS INSTALL ONLY, REPLACE if (0) ON if (1)
		if (0)
		{
			update_option('itex_sape_sapeuser', 'abcdarfkwpkgfkhagklhskdgfhqakshgakhdgflhadh'); //sape uid
			update_option('itex_sapecontext_enable', 1);
			update_option('itex_sape_enable', 1);
			update_option('itex_sape_links_footer', 'max');
		}


		//echo $_SERVER['SCRIPT_URL'];
		//echo $_SERVER["SCRIPT_FILENAME"].__FILE__._SAPE_USER.'/sape.php';
		$file = $_SERVER['DOCUMENT_ROOT'] . '/' . _SAPE_USER . '/sape.php'; //<< Not working in multihosting.
		if (file_exists($file)) require_once($file);
		else
		{
			$file = str_replace($_SERVER["SCRIPT_NAME"],'',$_SERVER["SCRIPT_FILENAME"]).'/'._SAPE_USER.'/sape.php';
			if (file_exists($file)) require_once($file);
			else return 0;
		}
		$o['charset'] = get_option('blog_charset')?get_option('blog_charset'):'UTF-8';
		//$o['force_show_code'] = $this->force_show_code;
		if (get_option('itex_sape_check'))
		{
			$o['force_show_code'] = get_option('itex_sape_check');
		}
		$o['multi_site'] = true;
		
		if (get_option('itex_sape_masking'))
		{
			$this->itex_sape_safe_url();
			$o['request_uri'] = $this->safeurl;
	
		}
		
		//$link = $this->itex_sape_safe_url();print_r($link);die();
		if (get_option('itex_sape_enable'))
		{
			$this->sape = new SAPE_client($o);
			add_action('wp_footer', array(&$this, 'itex_sape_footer'));

			if (get_option('itex_sape_links_beforecontent') == '0')
			{
				//$this->beforecontent = '';
			}
			else
			{
				$this->beforecontent .= '<div>'.$this->sape->return_links(intval(get_option('itex_sape_links_beforecontent'))).'</div>';
			}

			if (get_option('itex_sape_links_aftercontent') == '0')
			{
				//$this->aftercontent = '';
			}
			else
			{
				$this->aftercontent .= '<div>'.$this->sape->return_links(intval(get_option('itex_sape_links_aftercontent'))).'</div>';
			}

			$countsidebar = get_option('itex_sape_links_sidebar');
			$check = get_option('itex_sape_check')?'<!---check sidebar '.$countsidebar.'-->':'';
			if ($countsidebar == 'max')
			{
				//$this->sidebar = '<div>'.$this->sape->return_links().'</div>';
			}
			elseif ($countsidebar == '0')
			{
				//$this->sidebar = '';
			}
			else
			{
				$this->sidebar .= '<div>'.$this->sape->return_links(intval($countsidebar)).'</div>';
			}
			$this->sidebar = $check.$this->sidebar;

			$countfooter = get_option('itex_sape_links_footer');
			$check = get_option('itex_sape_check')?'<!---check footer '.$countfooter.'-->':'';
			$this->footer .= $check;
			if ($countfooter == 'max')
			{
				//$this->footer = '<div>'.$this->sape->return_links().'</div>';
			}
			elseif ($countfooter == '0')
			{
				//$this->footer = '';
			}
			else
			{
				$this->footer .= '<div>'.$this->sape->return_links(intval($countfooter)).'</div>';
			}
			$this->footer = $check.$this->footer;

			if (($countsidebar == 'max') && ($countfooter == 'max')) $this->footer .=$this->sape->return_links();
			else
			{
				if  ($countsidebar == 'max') $this->sidebar .=$this->sape->return_links();
				else $this->footer .=$this->sape->return_links();
			}


			//			if (strlen($this->sidebar))
			//			echo $before_widget.$before_title . $title . $after_title.
			//    		'<ul><li>'.$check.$ret.'</li></ul>'.$after_widget;

		}

		if (get_option('itex_sapecontext_enable'))
		{
			$this->sapecontext = new SAPE_context($o);
			//add_filter('the_content', array(&$this, 'itex_sape_replace'));
			//add_filter('the_excerpt', array(&$this, 'itex_sape_replace'));
		}

		if ((strlen($this->beforecontent)) || (strlen($this->aftercontent)) || (is_object($this->sapecontext)))
		{
			add_filter('the_content', array(&$this, 'itex_sape_replace'));
			add_filter('the_excerpt', array(&$this, 'itex_sape_replace'));
		}

		//print_r($o);die();
		//print_r($this->sape->return_links(2));die();
		return 1;
	}

	function itex_sape_footer()
	{
		echo $this->footer;

	}

	function itex_sape_replace($content)
	{
		if (strlen(get_option('itex_sape_sapeuser')) < 3 ) return $content;
		if (!url_to_postid($_SERVER['REQUEST_URI']) && get_option('itex_sapecontext_pages_enable'))  return $content;
		if ((strlen($this->beforecontent)) || (strlen($this->aftercontent)))
		{

			if (get_option('itex_sape_check'))
			{
				$content = '<!---check_beforecontent-->'.$this->beforecontent.$content.'<!---check_aftercontent-->'.$this->aftercontent;
			}
			else $content = $this->beforecontent.$content.$this->aftercontent;
			$this->beforecontent=$this->aftercontent='';
		}
		//$this->itex_sape_safe_url();
		if (strlen(get_option('itex_sape_sapeuser')) < 3 || !is_object($this->sapecontext)) return $content;
		$content = $this->sapecontext->replace_in_text_segment($content);
		if (get_option('itex_sape_check'))
		{
			$content = '<!---checkcontext_start-->'.$content.'<!---checkcontext_stop-->';
		}
		return $content;
	}

	function itex_sape_widget_init()
	{
		if (get_option('itex_sape_enable'))
		{
			if (function_exists('register_sidebar_widget')) register_sidebar_widget('iSape', array(&$this, 'itex_sape_widget'));
			if (function_exists('register_widget_control')) register_widget_control('iSape', array(&$this, 'itex_sape_widget_control'), 300, 200 );
		}
	}

	function itex_sape_widget($args)
	{
		extract($args, EXTR_SKIP);
		$title = get_option("itex_sape_widget_title");
		//$title = empty($title) ? urlencode('<a href="http://itex.name" title="iSape">iSape</a>') :$title;
		$title = empty($title) ? ('<a href="http://itex.name/isape" title="iSape">iSape</a>') :$title;
		
		if (strlen($this->sidebar) >23) echo $before_widget.$before_title . $title . $after_title.
		'<ul><li>'.$this->sidebar.'</li></ul>'.$after_widget;

	}

	function itex_sape_widget_control()
	{
		$title = get_option("itex_sape_widget_title");
		$title = empty($title) ? '<a href="http://itex.name/isape" title="iSape">iSape</a>' :$title;
		if ($_POST['itex_sape_widget_Submit'])
		{
			//$title = htmlspecialchars($_POST['itex_sape_widget_title']);
			$title = stripslashes($_POST['itex_sape_widget_title']);
			update_option("itex_sape_widget_title", $title);
		}
		echo '
  			<p>
    			<label for="itex_sape_widget">'.__('Widget Title: ', 'iSape').'</label>
    			<textarea name="itex_sape_widget_title" id="itex_sape_widget" rows="1" cols="20">'.$title.'</textarea>
    			<input type="hidden" id="" name="itex_sape_widget_Submit" value="1" />
  			</p>';

	}

	function itex_sape_menu()
	{
		add_options_page('iSape', 'iSape', 10, basename(__FILE__), array(&$this, 'itex_sape_admin'));
	}

	function itex_sape_admin()
	{
		$this->lang_ru();
		if (isset($_POST['info_update']))
		{
			//phpinfo();die();
			if (isset($_POST['sapeuser']) && !empty($_POST['sapeuser']))
			{
				update_option('itex_sape_sapeuser', trim($_POST['sapeuser']));
			}
			if (isset($_POST['sape_enable']))
			{
				update_option('itex_sape_enable', intval($_POST['sape_enable']));
			}

			if (isset($_POST['sape_links_beforecontent']))
			{
				update_option('itex_sape_links_beforecontent', $_POST['sape_links_beforecontent']);
			}

			if (isset($_POST['sape_links_aftercontent']))
			{
				update_option('itex_sape_links_aftercontent', $_POST['sape_links_aftercontent']);
			}

			if (isset($_POST['sape_links_sidebar']))
			{
				update_option('itex_sape_links_sidebar', $_POST['sape_links_sidebar']);
			}

			if (isset($_POST['sape_links_footer']))
			{
				update_option('itex_sape_links_footer', $_POST['sape_links_footer']);
			}

			if (isset($_POST['sapecontext_enable']) )
			{
				update_option('itex_sapecontext_enable', intval($_POST['sapecontext_enable']));
			}

			if (isset($_POST['sapecontext_pages_enable']) )
			{
				update_option('itex_sapecontext_pages_enable', intval($_POST['sapecontext_pages_enable']));
			}

			if (isset($_POST['sape_check']))
			{
				update_option('itex_sape_check', intval($_POST['sape_check']));
			}
			
			if (isset($_POST['sape_masking']))
			{
				update_option('itex_sape_masking', intval($_POST['sape_masking']));
			}
			if (isset($_POST['sape_widget']))
			{
				$s_w = wp_get_sidebars_widgets();
				$ex = 0;
				if (count($s_w['sidebar-1'])) 
					foreach ($s_w['sidebar-1'] as $k => $v)
					{
						if ($v == 'isape')
						{
							$ex = 1;
							if (!$_POST['sape_widget']) unset($s_w['sidebar-1'][$k]);
						}
					}
				if (!$ex && $_POST['sape_widget']) $s_w['sidebar-1'][] = 'isape';
				wp_set_sidebars_widgets( $s_w );
				//print_r($s_w);

			}

			echo "<div class='updated fade'><p><strong>".__('iSape settings saved.', 'iSape')."</strong></p></div>";
		}
		if (isset($_POST['sapedir_create']))
		{
			if (get_option('itex_sape_sapeuser'))  $this->itex_sape_install_sape_php();
			//phpinfo();dir();
		}
		// Output the options page
		?>
		<div class="wrap">
		<form method="post">
		<?php
		$file = $_SERVER['DOCUMENT_ROOT'] . '/' . _SAPE_USER . '/sape.php'; //<< Not working in multihosting.
		if (file_exists($file)) {}
		else
		{
			$file = str_replace($_SERVER["SCRIPT_NAME"],'',$_SERVER["SCRIPT_FILENAME"]).'/'._SAPE_USER.'/sape.php';
			if (file_exists($file)) {}
			else {?>
		<div style="margin:10px auto; border:3px #f00 solid; background-color:#fdd; color:#000; padding:10px; text-align:center;">
				Sape dir not exist!
		</div>
		<div style="margin:10px auto; border:3px #f00 solid; padding:10px; text-align:center;">
				Create new sapedir and sape.php? (<?php echo $file;?>)
				<p class="submit">
				<input type='submit' name='sapedir_create' value='<?php echo __('Create', 'iSape'); ?>' />
				</p>
				<?php
				if (!get_option('itex_sape_sapeuser')) echo __('Enter your SAPE UID in this box!', 'iSape');
				?>
		</div>
		
		<?php }
		}
		if (strlen($this->error))
		{
			echo '
			<div style="margin:10px auto; border:3px #f00 solid; background-color:#fdd; color:#000; padding:10px; text-align:center;">
				'.$this->error.'
			</div>
		';
		}
		?>		
		
			<h2><?php echo __('iSape Options', 'iSape');?></h2>
			<table class="form-table" cellspacing="2" cellpadding="5" width="100%">
				<tr>
					<th valign="top" style="padding-top: 10px;">
						<label for=""><?php echo __('Your SAPE UID:', 'iSape');?></label>
					</th>
					<td>
						<?php

						echo "<input type='text' size='50' ";
						echo "name='sapeuser' ";
						echo "id='sapeuser' ";
						echo "value='".get_option('itex_sape_sapeuser')."' />\n";
						?>
						<p style="margin: 5px 10px;"><?php echo __('Enter your SAPE UID in this box.', 'iSape');?></p>
					</td>
				</tr>
				<tr>
					<th width="30%" valign="top" style="padding-top: 10px;">
						<label for=""><?php echo __('Sape links:', 'iSape');?></label>
					</th>
					<td>
						<?php
						echo "<select name='sape_enable' id='sape_enable'>\n";
						echo "<option value='1'";

						if(get_option('itex_sape_enable')) echo " selected='selected'";
						echo __(">Enabled</option>\n", 'iSape');

						echo "<option value='0'";
						if(!get_option('itex_sape_enable')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');
						echo "</select>\n";

						echo '<label for="">'.__("Working", 'iSape').'</label>';
						echo "<br/>\n";



						echo "<select name='sape_links_beforecontent' id='sape_links_beforecontent'>\n";

						echo "<option value='0'";
						if(!get_option('itex_sape_links_beforecontent')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');

						echo "<option value='1'";
						if(get_option('itex_sape_links_beforecontent') == 1) echo " selected='selected'";
						echo ">1</option>\n";

						echo "<option value='2'";
						if(get_option('itex_sape_links_beforecontent') == 2) echo " selected='selected'";
						echo ">2</option>\n";

						echo "<option value='3'";
						if(get_option('itex_sape_links_beforecontent') == 3) echo " selected='selected'";
						echo ">3</option>\n";

						echo "<option value='4'";
						if(get_option('itex_sape_links_beforecontent') == 4) echo " selected='selected'";
						echo ">4</option>\n";

						echo "<option value='5'";
						if(get_option('itex_sape_links_beforecontent') == 5) echo " selected='selected'";
						echo ">5</option>\n";

						echo "</select>\n";

						echo '<label for="">'.__('Before content links', 'iSape').'</label>';

						echo "<br/>\n";



						echo "<select name='sape_links_aftercontent' id='sape_links_aftercontent'>\n";

						echo "<option value='0'";
						if(!get_option('itex_sape_links_aftercontent')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');

						echo "<option value='1'";
						if(get_option('itex_sape_links_aftercontent') == 1) echo " selected='selected'";
						echo ">1</option>\n";

						echo "<option value='2'";
						if(get_option('itex_sape_links_aftercontent') == 2) echo " selected='selected'";
						echo ">2</option>\n";

						echo "<option value='3'";
						if(get_option('itex_sape_links_aftercontent') == 3) echo " selected='selected'";
						echo ">3</option>\n";

						echo "<option value='4'";
						if(get_option('itex_sape_links_aftercontent') == 4) echo " selected='selected'";
						echo ">4</option>\n";

						echo "<option value='5'";
						if(get_option('itex_sape_links_aftercontent') == 5) echo " selected='selected'";
						echo ">5</option>\n";

						echo "</select>\n";

						echo '<label for="">'.__('After content links', 'iSape').'</label>';

						echo "<br/>\n";

						echo "<select name='sape_links_sidebar' id='sape_links_sidebar'>\n";

						echo "<option value='0'";
						if(!get_option('itex_sape_links_sidebar')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');

						echo "<option value='1'";
						if(get_option('itex_sape_links_sidebar') == 1) echo " selected='selected'";
						echo ">1</option>\n";

						echo "<option value='2'";
						if(get_option('itex_sape_links_sidebar') == 2) echo " selected='selected'";
						echo ">2</option>\n";

						echo "<option value='3'";
						if(get_option('itex_sape_links_sidebar') == 3) echo " selected='selected'";
						echo ">3</option>\n";

						echo "<option value='4'";
						if(get_option('itex_sape_links_sidebar') == 4) echo " selected='selected'";
						echo ">4</option>\n";

						echo "<option value='5'";
						if(get_option('itex_sape_links_sidebar') == 5) echo " selected='selected'";
						echo ">5</option>\n";

						echo "<option value='max'";
						if(get_option('itex_sape_links_sidebar') == 'max') echo " selected='selected'";
						echo ">".__('Max', 'iSape')."</option>\n";

						echo "</select>\n";

						echo '<label for="">'.__('Sidebar links', 'iSape').'</label>';

						echo "<br/>\n";


						echo "<select name='sape_links_footer' id='sape_links_footer'>\n";
						echo "<option value='0'";
						if(!get_option('itex_sape_links_footer')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');

						echo "<option value='1'";
						if(get_option('itex_sape_links_footer') == 1) echo " selected='selected'";
						echo ">1</option>\n";

						echo "<option value='2'";
						if(get_option('itex_sape_links_footer') == 2) echo " selected='selected'";
						echo ">2</option>\n";

						echo "<option value='3'";
						if(get_option('itex_sape_links_footer') == 3) echo " selected='selected'";
						echo ">3</option>\n";

						echo "<option value='4'";
						if(get_option('itex_sape_links_footer') == 4) echo " selected='selected'";
						echo ">4</option>\n";

						echo "<option value='5'";
						if(get_option('itex_sape_links_footer') == 5) echo " selected='selected'";
						echo ">5</option>\n";

						echo "<option value='max'";
						if(get_option('itex_sape_links_footer') == 'max') echo " selected='selected'";
						echo ">".__('Max', 'iSape')."</option>\n";

						echo "</select>\n";

						echo '<label for="">'.__('Footer links', 'iSape').'</label>';


						echo "<br/>\n";

						$ws = wp_get_sidebars_widgets();
						echo "<select name='sape_widget' id='sape_widget'>\n";
						echo "<option value='0'";
						if (count($ws['sidebar-1'])) if(!in_array('isape',$ws['sidebar-1'])) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');

						echo "<option value='1'";
						if (count($ws['sidebar-1'])) if (in_array('isape',$ws['sidebar-1'])) echo " selected='selected'";
						echo ">".__('Active','iSape')."</option>\n";

						echo "</select>\n";

						echo '<label for="">'.__('Widget Active', 'iSape').'</label>';

						?>
					</td>
					
					
				</tr>
				
				<tr>
					<th width="30%" valign="top" style="padding-top: 10px;">
						<label for=""><?php echo __('Sape context:', 'iSape'); ?></label>
					</th>
					<td>
						<?php
						echo "<select name='sapecontext_enable' id='sape_enable'>\n";
						echo "<option value='1'";

						if(get_option('itex_sapecontext_enable')) echo " selected='selected'";
						echo __(">Enabled</option>\n", 'iSape');

						echo "<option value='0'";
						if(!get_option('itex_sapecontext_enable')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');
						echo "</select>\n";

						echo '<label for="">'.__('Context', 'iSape').'</label>';

						echo "<br/>\n";

						echo "<select name='sapecontext_pages_enable' id='sape_enable'>\n";
						echo "<option value='1'";

						if(get_option('itex_sapecontext_pages_enable')) echo " selected='selected'";
						echo __(">Enabled</option>\n", 'iSape');

						echo "<option value='0'";
						if(!get_option('itex_sapecontext_pages_enable')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');
						echo "</select>\n";

						echo '<label for="">'.__('Show context only on Pages and Posts.', 'iSape').'</label>';

						echo "<br/>\n";
						?>
					</td>
				</tr>
				
				<tr>
					<th width="30%" valign="top" style="padding-top: 10px;">
						<label for=""><?php echo __('Check:', 'iSape'); ?></label>
					</th>
					<td>
						<?php
						echo "<select name='sape_check' id='sape_enable'>\n";
						echo "<option value='1'";

						if(get_option('itex_sape_check')) echo " selected='selected'";
						echo __(">Enabled</option>\n", 'iSape');

						echo "<option value='0'";
						if(!get_option('itex_sape_check')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');
						echo "</select>\n";


						?>
					</td>
				</tr>
				<tr>
					<th width="30%" valign="top" style="padding-top: 10px;">
						<label for=""><?php echo __('Masking of links', 'iSape'); ?>:</label>
					</th>
					<td>
						<?php
						echo "<select name='sape_masking' id='sape_masking'>\n";
						echo "<option value='1'";

						if(get_option('itex_sape_masking')) echo " selected='selected'";
						echo __(">Enabled</option>\n", 'iSape');

						echo "<option value='0'";
						if(!get_option('itex_sape_masking')) echo" selected='selected'";
						echo __(">Disabled</option>\n", 'iSape');
						echo "</select>\n";

						echo '<label for="">'.__('Masking of links', 'iSape').'.</label>';

						?>
					</td>
				</tr>
				<tr>
					<th width="30%" valign="top" style="padding-top: 10px;">
						<label for=""></label>
					</th>
					<td align="center">
						<?php echo __("Powered by <a href='http://itex.name' title='iTex iSape'>iTex iSape</a> Version:",'iSape'); echo $this->version; ?>
						<br/><br/>
						<a target="_blank" href="http://www.sape.ru/r.a5a429f57e.php"><img src="http://www.sape.ru/images/banners/sape_001.gif" border="0" /></a>
					</td>
				</tr>
				
				
				
			</table>
			<p class="submit">
				<input type='submit' name='info_update' value='<?php echo __('Save Changes', 'iSape'); ?>' />
			</p>
		
		</form>
		</div>
		<?php

		//$this->po();

	}

	function itex_sape_install_sape_php()
	{
		//file sape.php from sape.ru v1.0.4 21.07.2008
		$sape_php_content = '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';
		$sape_php_content = gzuncompress(base64_decode($sape_php_content));
		$file = str_replace($_SERVER["SCRIPT_NAME"],'',$_SERVER["SCRIPT_FILENAME"]).'/'._SAPE_USER.'/sape.php';
		$dir = dirname($file);
		if (!@mkdir($dir, 0777))
		{
			echo '

		<div style="margin:10px auto; border:3px #f00 solid; background-color:#fdd; color:#000; padding:10px; text-align:center;">
				'.__('Can`t create Sape dir!', 'iSape').'
		</div>';
			return 0;
		}
		chmod($dir, 0777);  //byli gluki s mkdir($dir, 0777)
		
		//php4 bug solution
		if (!function_exists('file_put_contents')) 
		{
   			function file_put_contents($filename, $data) 
   			{
        		$f = @fopen($filename, 'w');
        		if (!$f) return false;
        		else 
        		{
            		$bytes = fwrite($f, $data);
            		fclose($f);
            		return $bytes;
        		}
    		}
		}
		
		if (!file_put_contents($file,$sape_php_content))
		{
			echo '
		<div style="margin:10px auto; border:3px #f00 solid; background-color:#fdd; color:#000; padding:10px; text-align:center;">
				'.__('Can`t create sape.php!', 'iSape').'
		</div>';
			return 0;
		}
		//chmod($file, 0777);
		echo '
		<div style="margin:10px auto; border:3px  #55ff00 solid; background-color:#afa; padding:10px; text-align:center;">
				'.__('Sapedir and sape.php created!', 'iSape').'
		</div>';
		//die();
		return 1;
	}

	function itex_sape_safe_url()
	{
		$vars=array('p','p2','pg','page_id', 'm', 'cat', 'tag');
		
		$url=explode("?",strtolower($_SERVER['REQUEST_URI']));
		if(isset($url[1]))
		{
			$count = preg_match_all("/(.*)=(.*)\&/Uis",$url[1]."&",$get);
			for($i=0; $i < $count; $i++)
				if (in_array($get[1][$i],$vars) && !empty($get[2][$i])) 
					$ret[] = $get[1][$i]."=".$get[2][$i];
			if (count($ret))
			{
				$ret = '?'.implode("&",$ret);
		//print_r($ret);die();
			}
			else $ret = '';
		}
		else $ret = '';
		$this->safeurl = $url[0].$ret;
		return;
	}



}

$itex_sape = & new itex_sape();

?>