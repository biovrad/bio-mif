<?php

class MP_Forms_field_type_recaptcha extends MP_Forms_field_type_abstract
{
	var $id 	= 'recaptcha';
	var $order	= 92;
	var $file	= __FILE__;

	function submitted($field)
	{
		require_once('captcha/recaptchalib.php');

		$resp = recaptcha_check_answer ($field->settings['keys']['privatekey'], $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);

		if (!$resp->is_valid) 
		{
			// set the error code so that we can display it
			// $error = $resp->error;
			$field->submitted['on_error'] = $resp->error;
			return $field;
		}

		$field->submitted['value'] = 1;
		$field->submitted['text']  = __('ok', MP_TXTDOM);

		return $field;
	}

	function attributes_filter($no_reset)
	{
		if (!$no_reset) return;
		
		$this->attributes_filter_css();
	}

	function build_tag()
	{
		require_once('captcha/recaptchalib.php');

		$tag = recaptcha_get_html($this->field->settings['keys']['publickey'], (isset($this->field->submitted['on_error'])) ? $this->field->submitted['on_error'] : null);
		$id  = $this->get_id($this->field);

		$form_format =  '{{img}}';

		MailPress::require_class('Forms');
		$form_template = MP_Forms::get_template($this->field->form_id);
		if ($form_template)
		{
			MailPress::require_class('Forms_templates');
			$form_templates = new MP_Forms_templates();
			$f = $form_templates->get_composite_template($form_template, $this->id);
			if (!empty($f)) $form_format = $f;
		}

		$search[] = '{{img}}';		$replace[] = '%1$s';
		$search[] = '{{id}}'; 		$replace[] = '%2$s';

		$html = str_replace($search, $replace,  $form_format);

		return sprintf($html, $tag, $id);
	}
}
new MP_Forms_field_type_recaptcha(__('ReCaptcha', MP_TXTDOM));