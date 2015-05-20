<?

require 'admin/config.php';
require 'admin/lang.php';
require 'lib/func.php';

session_name(SES_NAME);
session_start();

global $englang, $lprefix, $lang_phrases;
$r_u = ereg_replace("^(\/)?", "", getenv('REQUEST_URI'));  
$r_u = ereg_replace("(\/)?(\?.*)?$", "", $r_u);  
$rus_url = preg_replace("/(^|\/)en\/?/", '', getenv('REQUEST_URI'));
$eng_url = preg_match("~^/~", $rus_url) ?  "/en".$rus_url : "/en/".$rus_url;

$lprefix = $englang ? '/en' : '';
$lang_phrases = array();
$nn = $englang ? "name$englang" : 'name';
foreach($lang_settings as $k=>$v) $lang_phrases[$k] = $k=='price' ? str_replace("Ð", "<span class=\"rub\">a</span>", $v[$nn]) : $v[$nn];

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);


$q = "SELECT name$englang as name, description$englang as description FROM ".TABLE_CURE." WHERE cure_id=14";
$sql1 = mysql_query($q) or Error(1, __FILE__, __LINE__);
$info = @mysql_fetch_array($sql1);
$name = $info['name'];
$description = $info['description'];

	
?>

<style>

    .pop-content{
        display: table-cell;
        vertical-align: middle;
        width: 563px;
        height: 376px;
        color: #fff;
        font-family: Arial,sans-serif;
        font-size: 16px;
        line-height: 1.3;
        border:none;
        background: url("/img/medicine_popup_window.png") no-repeat;
    }
    .pop-content a{
        color: #fff;
    }
    .pop-content .u{
        font-size: 18px;
        line-height: 1.2em;
        text-transform: uppercase;
    }
    .pop-content .centered{
        text-align: center;
    }
</style>

<div class="pop-content">
    <div class="centered">
        <p class="u"><b><?=$name?></b></p>
        <p>&nbsp;</p>
        <p><?=$description?></p>
    </div>
</div>
