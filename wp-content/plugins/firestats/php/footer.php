<div class="fwrap" style="text-align:center; margin: 0 auto">
	<?php
	if (!defined('FS_VERSION')) die("direct call is not premitted");
	$helpStr = sprintf(fs_r("If you have any problems or questions, please visit the %s"),sprintf("<a href='%s'>%s</a>",FS_HOMEPAGE, fs_r("FireStats homepage")));
		
		echo fs_r('FireStats').' '.FS_VERSION."<br/>$helpStr";
	?>
</div>