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
foreach($lang_settings as $k=>$v) $lang_phrases[$k] = $k=='price' ? str_replace("Р", "<span class=\"rub\">a</span>", $v[$nn]) : $v[$nn];

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);


$q = "SELECT name$englang as name, description$englang as description FROM ".TABLE_CURE." WHERE cure_id=14";
$sql1 = mysql_query($q) or Error(1, __FILE__, __LINE__);
$info = @mysql_fetch_array($sql1);
$name = $info['name'];
$description = $info['description'];

	
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
<meta content="text/html; charset=windows-1251" http-equiv=Content-Type>
<style>
    a{
        color: #008c95;
    }
    .u{
        text-transform: uppercase;
    }
    .content{
        width: 600px;
        height: 400px;
        color: #636363;
        font-family: Tahoma,Arial,sans-serif;
        font-size: 13px;
        line-height: 1.3;
    }
    .centered{
        text-align: center;
    }
</style>
  </head>
  <body>
  <div class="content">
            <p class="centered u"><b><?=$name?></b></p>
            <p>&nbsp;</p>
            <p><?=$description?></p>
            <p>&nbsp;</p>
            <p class="centered"><a href="/medicine/14" target="_blank"><b>Полный список противопоказаний</b></a></p>
  </div>
  </body>
 </html>