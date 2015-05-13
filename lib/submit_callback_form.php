<?
$referrer = $_SERVER['HTTP_REFERER'];

$englang = @$_POST['en'] ? '_en' : '';
$lang_phrases = array();
$nn = $englang ? "name$englang" : 'name';
foreach($lang_settings as $k=>$v) $lang_phrases[$k] = $v[$nn];

$email = $settings['admin_email'];
$subj = "оставили телефон на :domain:";
$message = "на :domain: оставили телефон :phone: имя: :name:";

$phone = substr(@$_POST['phone'], 0, 20);
$name = substr(@$_POST['name'], 0, 20);
$ucaptcha = substr(@$_POST['ucaptcha'], 0, 3);

$yes = 0;

if(!@$phone || !ereg("^([[:digit:]]| |\\+|-|\\(|\\))+$", $phone))
{
    $_SESSION['message'] =  $lang_phrases['err_phone'];
    Header("Location: ".$referrer);
    exit;
}

$domain = @$domain;
if(!$domain) $domain = getenv("HTTP_REFERER");

if(!@$phone || !ereg("^([[:digit:]]| |\\+|-|\\(|\\))+$", $phone))
{
    $_SESSION['message'] =  $lang_phrases['err_captcha'];
    Header("Location: ".$referrer);
    exit;
}

$head="Content-type: text/html; charset=windows-1251";
$arr = explode(",", $email);
$head.="\nFrom: ".MAIL_FROM;

$comment = nl2br(@$comment);
$time = nl2br(@$time);

$mess = ereg_replace("\\:phone\\:", $phone, $message);
$mess = ereg_replace("\\:name\\:", $name, $mess);
$mess = ereg_replace("\\:domain\\:", DOMAIN, $mess);

foreach($arr as $v) mail($v, DOMAIN, $mess, $head);
$yes = 1;

$_SESSION['message'] =  $lang_phrases['our_manager'];
Header("Location: ".$referrer);
exit;
