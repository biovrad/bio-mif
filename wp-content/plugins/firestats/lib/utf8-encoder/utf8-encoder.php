<?php
/**
 * Converts the specified string from the specified encoding (codepage encoding) to
 * UTF8.
 *
 * @param string $encoding input string encoding, for example: cp1255, cp1251 etc.
 * @param string $str string to convert to unicode.
 * @return utf8 string, or false if encoding is not supported.
 */
function fs_convert_to_utf8($encoding, $str)
{
	if (function_exists("iconv"))
	{
		return iconv($encoding, "utf-8", $str);
	}
	
	static $cache;
	if (!isset($cache))
	{
		$cache = array();
	}
	
	$table = null;
	if (!isset($cache[$encoding]))
	{
		$file = @fopen(dirname(__FILE__)."/$encoding.dat","rb");
		if (!$file)
		{
			$cache[$encoding] = false;
		}
		else
		{
			$ret = '';
			$read = 0;
			$len = filesize(dirname(__FILE__)."/$encoding.dat");
			while ($read < $len && ($buf = fread($file, $len - $read))) 
			{
				$read += strlen($buf);
				$ret .= $buf;
			}
			fclose($file);
			$cache[$encoding] = $ret;
			$table = $ret;
		}
	}
	else
	{
		$table = $cache[$encoding];
	}
	if (!$table) return false;
	return fs_codepage_to_utf8($table, $str);
}

/**
 * This function returns true if the specified string is a valid utf8 string.
 * Note that it's possible, although unlikely - that a string in some codepage encoding will pass as a valid utf8 string.
 * this means that this function returns true the string may still be in a different encoding.
 * however, if it returns false - it's a non utf8 string for sure.
 * 
 * @param $str
 * @return boolean
 */
function fs_is_utf8_string($str)
{
	$len = strlen($str);
	//fs_println("len = $len");for($i=0;$i<$len;$i++) echo ord($str[$i]). " ";	fs_println();
	$x = $len * 2;
	for($i=0;$i<$len;$i++)
	{
		$width = fs_next_utf8_char_width($str, $i);
		//fs_println("index = $i , ord=".ord($str[$i]) . ", width=$width");
		if ($width == -1)
		{ 
			// none utf8 char (for block start)
			//fs_println("none utf8 char (for block start)");
			return false;
		}
		for($j = $i+1;$j<$i+$width;$j++)
		{
			if ($j > $len) 
			{
				//fs_println("chopped utf8 string?");
				return false; // chopped utf8 string?
			}
			
			$c = ord($str[$j]);
			//fs_println("j=$j, ord=$c");
			if ($c >= 192 || $c < 128) 
			{
				//fs_println("invalid char inside block i=$i, j=$j, ord=$c");
				return false;
			}
		}
		$i += $width - 1; // -1 because the main loop will increate by 1 anyway.
		
	}
	return true;
}

/**
 * returns the size of the next utf8 character or -1 if the current character is not the begining of a utf8 character.
 * @param $str
 * @param $cur
 */
function fs_next_utf8_char_width($str, $cur)
{
	/*
	 * 00000000-01111111 	00-7F 	0-127		1 byte
	 * 11000000-11011111 	C0-DF 	192-223		2 bytes
 	 * 11100000-11101111 	E0-EF 	224-239 	3 bytes
	 * 11110000-11110100 	F0-F4 	240-244 	4 bytes
	 * otherwise, -1
	 */
	
	$o = ord($str[$cur]);
	if ($o >= 0x00 && $o <= 0x7f) 
		return 1;
	if ($o >= 0xc0 && $o <= 0xdf) 
		return 2;
	if ($o >= 0xe0 && $o <= 0xef) 
		return 3;
	if ($o >= 0xf0 && $o <= 0x7f) 
		return 4;
	return -1;
}


function fs_codepage_to_utf8(&$table, $str)
{
	$res = '';
	$len = strlen($str);
	for($i=0;$i<$len;$i++)
	{
		$c = ord($str[$i]);
		$c = ord($table[$c * 2]) << 8 | ord($table[$c * 2 + 1]);
		$utf8 = fs_cp_to_utf8($c);

		for($j = 0;$j<strlen($utf8);$j++)
		{
			$res .= $utf8[$j];
		}
	}
	return $res;
}

// Converts unicode codepoint to utf8.
// based on function from HtmlPurifier, which in turn is based on Feyd's function at 
// <http://forums.devnetwork.net/viewtopic.php?p=191404#191404> which is in public domain.
//
// +----------+----------+----------+----------+
// | 33222222 | 22221111 | 111111   |          |
// | 10987654 | 32109876 | 54321098 | 76543210 | bit
// +----------+----------+----------+----------+
// |          |          |          | 0xxxxxxx | 1 byte 0x00000000..0x0000007F
// |          |          | 110yyyyy | 10xxxxxx | 2 byte 0x00000080..0x000007FF
// |          | 1110zzzz | 10yyyyyy | 10xxxxxx | 3 byte 0x00000800..0x0000FFFF
// | 11110www | 10wwzzzz | 10yyyyyy | 10xxxxxx | 4 byte 0x00010000..0x0010FFFF
// +----------+----------+----------+----------+
// | 00000000 | 00011111 | 11111111 | 11111111 | Theoretical upper limit of legal scalars: 2097151 (0x001FFFFF)
// | 00000000 | 00010000 | 11111111 | 11111111 | Defined upper limit of legal scalar codes
// +----------+----------+----------+----------+ 
function fs_cp_to_utf8($code)
{
	if($code > 1114111 or $code < 0 or ($code >= 55296 and $code <= 57343)) 
	{
		// bits are set outside the "valid" range as defined
		// by UNICODE 4.1.0 
		return '';
	}

	$x = $y = $z = $w = 0; 
	if ($code < 128) 
	{
		// regular ASCII character
		$x = $code;
	} 
	else
	{
		// set up bits for UTF-8
		$x = ($code & 63) | 128;
		if ($code < 2048) 
		{
			$y = (($code & 2047) >> 6) | 192;
		} 
		else 
		{
			$y = (($code & 4032) >> 6) | 128;
			if($code < 65536) 
			{
				$z = (($code >> 12) & 15) | 224;
			}
			else 
			{
				$z = (($code >> 12) & 63) | 128;
				$w = (($code >> 18) & 7)  | 240;
			}
		} 
	}
	// set up the actual character
	$ret = '';
	if($w) $ret .= chr($w);
	if($z) $ret .= chr($z);
	if($y) $ret .= chr($y);
	$ret .= chr($x); 

	return $ret;
}
?>
