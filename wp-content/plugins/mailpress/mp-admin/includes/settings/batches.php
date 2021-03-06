<?php
$mp_general['tab'] = 'batches';

if (class_exists('MailPress_batch_send'))
{
	$batch_send	= $_POST['batch_send'];

	$old_batch_send = get_option(MailPress_batch_send::option_name);

	update_option(MailPress_batch_send::option_name, $batch_send);

	if (!isset($old_batch_send['batch_mode'])) $old_batch_send['batch_mode'] = '';
	if ($old_batch_send['batch_mode'] != $batch_send['batch_mode'])
	{
		if ('wpcron' != $batch_send['batch_mode']) { wp_clear_scheduled_hook('mp_action_batchsend'); wp_clear_scheduled_hook('mp_process_batch_send'); }
		else							 MailPress_batch_send::schedule();
	}
}

if (class_exists('MailPress_bounce_handling'))
{
	$bounce_handling	= $_POST['bounce_handling'];

	$old_bounce_handling = get_option(MailPress_bounce_handling::option_name);

	update_option(MailPress_bounce_handling::option_name, $bounce_handling);

	if (!isset($old_bounce_handling['batch_mode'])) $old_bounce_handling['batch_mode'] = '';
	if ($old_bounce_handling['batch_mode'] != $bounce_handling['batch_mode'])
	{
		if ('wpcron' != $bounce_handling['batch_mode']) wp_clear_scheduled_hook('mp_process_bounce_handling');
		else 								MailPress_bounce_handling::schedule();
	}
}

update_option(MailPress::option_name_general, $mp_general);

$message = __("'Batches' settings saved", MP_TXTDOM);