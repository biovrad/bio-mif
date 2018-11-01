<?php
foreach (@glob("codepage-tables/CP*.TXT") as $filename) 
{
	$arr = file($filename);
	$filename = basename($filename);
	$name = substr($filename,0, strlen($filename) - strlen(".TXT")).".dat";
	$name = strtolower($name);
	$f = @fopen($name, "w+b");
	if (!$f) die("error creating $name");
	foreach($arr as $line)
	{
		if (strlen($line) == 0 || $line[0] == '#') continue;
		$s = sscanf($line, "0x%X 0x%X %s");
		fwrite($f,pack('n',$s[1]));
	}
	fclose($f);
}
?>
