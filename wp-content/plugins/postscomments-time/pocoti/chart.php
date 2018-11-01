<?php
if ( (file_exists("chartday.png")) && (file_exists("charthour.png"))) {
?>
<img src="chartday.png?x=<?php echo time() ?>" title="Posts/comments chart"/>
<br />
<img src="charthour.png?x=<?php echo time() ?>" title="Posts/comments chart"/>
<?php } ?>