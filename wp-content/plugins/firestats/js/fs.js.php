<?php
@header('Content-type: text/javascript; charset=utf-8');
require_once dirname(dirname(__FILE__))."/php/utils.php";
$site_id = htmlentities(isset($_GET['site_id']) ? $_GET['site_id'] : '1');
$base =  fs_get_absolute_url($_SERVER['REQUEST_URI']);
$_file_ = str_replace("\\","/",__FILE__);
$firestats_parent = dirname(dirname(dirname($_file_)));
$firestats_dir_name = substr(dirname(dirname($_file_)), strlen($firestats_parent));
$index = strrpos($base,$firestats_dir_name);
$src = substr($base, 0,$index) . $firestats_dir_name . "/php/hit.php?SITE_ID=$site_id";

?>
//<![CDATA[
FS = {}
FS.addHit = function()
{
    var st = document.createElement("script");
    st.src = "<?php echo $src ?>" + "&REF="+document.referrer+"&URL="+window.location;
    st.type = "text/javascript";
    var head = document.getElementsByTagName('head')[0]
    head.appendChild(st);
}

FS.addHit();
//]]>
