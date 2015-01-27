<?php

if(isset($_GET['logout']))
{
	$_SESSION['sections'] = '';
	$_SESSION['admin_id'] =  '';
	$_SESSION['admin_name'] = '';
	Header("Location: ".ADMIN_URL);
	exit;
}

if(!isset($_GET['login']) || !isset($_GET['password']))
{
	Header("Location: ".ADMIN_URL);
	exit;
}
$login = get_post('login', 2); 
$password = get_post('password', 2); 
$sql = mysql_query("SELECT user_id, sections, extra FROM ".TABLE_USER." WHERE login='$login' AND password='$password'") 
	or Error(1, __FILE__, __LINE__);

if(!mysql_num_rows($sql))
{
	Header("Location: ".ADMIN_URL);
	exit;
}

$info = @mysql_fetch_array($sql);
$_SESSION['sections'] = $info['sections'];
$_SESSION['admin_id'] =  $info['user_id'];
$_SESSION['admin_name'] = HtmlSpecialChars($login);
$_SESSION['extra'] = $info['extra'];

$url = getenv('HTTP_REFERER') ? getenv('HTTP_REFERER') : ADMIN_URL;
Header("Location: ".$url);
exit;
?>