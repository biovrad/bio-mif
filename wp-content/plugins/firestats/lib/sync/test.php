<?php
require_once('../../php/utils.php');
require_once('mutex.php');

$mutex = new Mutex(__FILE__, true);
$rand = rand(7,15);
fs_println("Locking for $rand seconds");
$res = $mutex->lock();
if ($res === true)
{
	fs_println("Locked, sleeping for $rand seconds");
	sleep($rand);
	$mutex->unlock();
	fs_println("Unlocked");
}
else
if ($res === false)
{
	fs_println("Already locked");
}
else
{
	fs_println("Error : $res");
}
?>
