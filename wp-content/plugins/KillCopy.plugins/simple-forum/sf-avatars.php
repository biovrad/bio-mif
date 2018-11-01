<?php
/*
Simple Forum 2.1
Avatar Uploading
*/

function sf_upload_avatar($user_ID)
{
	// create file vars to make things easier to read.
	$filename = $_FILES['avatar']['name'];
	$filesize = $_FILES['avatar']['size'];
	$filetype = $_FILES['avatar']['type'];
	$file_tmp = $_FILES['avatar']['tmp_name'];
	$file_err = $_FILES['avatar']['error'];
	$file_ext = strrchr($filename, '.');

	// rename the file to ensure uniqueness
	$filename=date('U').$file_ext;

	// check if user actually put something in the file input field.
	if (($file_err == 0) && ($filesize != 0))
	{
		// Check extension.
		if (!$file_ext)
		{
			unlink($file_tmp);
			return __('AVATAR UPLOAD ERROR: File must have an extension', "sforum");
		}

		// check extension type
		if(!strpos(' .gif .GIF .png .PNG .jpg .JPG .jpeg .JPEG ', $file_ext))
		{
			return __('AVATAR UPLOAD ERROR: Unrecognised File Extension', "sforum");
		}
		
		// check size
		$maxsize = get_option('sfavatarsize');
		if(!isset($maxsize)) $maxsize = 50;
		$imageinfo = getimagesize($file_tmp);
		if(($imageinfo[0] > $maxsize) || ($imageinfo[1] > $maxsize))
		{
			$mess = sprintf(__('AVATAR UPLOAD ERROR: Image exceeds %s pixel maximum', "sforum"), $maxsize);
			return $mess;
		}

		// check upload directory OK
		$handle = @opendir(SFAVATARS);
		if ($handle) 
		{
			closedir($handle);
		} else {
			return __('AVATAR UPLOAD ERROR: Target folder cannot be reached', "sforum");
		}
		
		// extra check to prevent file attacks.
		if (is_uploaded_file($file_tmp))
		{
			// copy the file from the temporary upload directory
			if (@move_uploaded_file($file_tmp, SFAVATARS.$filename))
			{
				chmod(SFAVATARS.$filename, 0777);
				sf_update_profile_avatar($user_ID, $filename);
				// success!
				return __('Avatar Successfully Uploaded', "sforum");
			}
			else
			{
				// error moving file. check file permissions.
				unlink($file_tmp);
				return __('AVATAR UPLOAD ERROR: Unable to move file to designated directory', "sforum");
			}
		}
		else
		{
			// file seems suspicious... delete file and error out.
			unlink($file_tmp);
			return __('AVATAR UPLOAD ERROR: File does not appear to be a valid upload', "sforum");
		}
	}
	else
	{
		// Kill temp file, if any, and display error.
		if ($file_tmp != '')
		{
			unlink($file_tmp);
		}

		switch ($file_err)
		{
			case '0':
				$mess = __('AVATAR UPLOAD ERROR: That is not a valid file. 0 byte length.', "sforum");
				break;

			case '1':
				$mess = sprintf(__('AVATAR UPLOAD ERROR: This file, at %s bytes, exceeds the maximum allowed file size as set in <em>php.ini</em>.', "sforum"), $filesize);
				break;

			case '2':
				$mess = __('AVATAR UPLOAD ERROR: This file exceeds the maximum file size specified.', "sforum");
				break;

			case '3':
				$mess = __('AVATAR UPLOAD ERROR: File was only partially uploaded. This could be the result of a connection problem', "sforum");
				break;

			case '4':
				$mess = __('AVATAR UPLOAD ERROR: No Avatar File Uploaded', "sforum");
				break;
		}
		return $mess;

	}
	return __("AVATAR UPLOAD ERROR: Unknown Error", "sforum");
}

function sf_update_profile_avatar($user_ID, $filename)
{
	update_user_option($user_ID, 'sfavatar', $filename);
	return;
}

?>