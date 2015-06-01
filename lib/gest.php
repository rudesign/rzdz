<?php

$returnUrl = $page_url."#form";

$order_fields = array('name', 'email', 'phone', 'text', 'digits', 'err_text', 'err_name', 'err_email', 'err_digit', 'gtema_id');

if(@$_POST['mode'])
{
	if($_POST['mode'] == 'reset')
	{
		$_SESSION['gest_data'] = '';
        Header("Location: ".$returnUrl);
		exit;
	}
	
	$arr = array();
	foreach($order_fields as $v) $arr[$v] = get_post($v);
	
	$ip = get_ip();
	
	/*$sql = mysql_query("SELECT count(*) FROM ".TABLE_GEST." WHERE DATE_ADD(datetime, INTERVAL 3 MINUTE) > NOW() AND ".
		"ip='$ip'") or Error(1, __FILE__, __LINE__);
	$arr1 = @mysql_fetch_array($sql);
	if($arr1[0])
	{
		$arr['err_ip'] = 1;
		$_SESSION['gest_data'] = Serialize($arr);
		Header("Location: ".$page_url);
		exit;
	}*/
		
	if(!$arr['name'])  
	{
        $_SESSION['message'] =  $lang_phrases['err_name'];
        Header("Location: ".$returnUrl);
        exit;
	}
	
	if(!$arr['email'])  
	{
        $_SESSION['message'] =  $lang_phrases['err_email'];
        Header("Location: ".$returnUrl);
        exit;
	}
	
	$arr['text'] = trim(substr($arr['text'], 0, 3000));
	
	if(!$arr['text'])  
	{
        $_SESSION['message'] =  $lang_phrases['err_text'];
        Header("Location: ".$returnUrl);
        exit;
	}

	if(!@$ucaptcha || @$ucaptcha!=$_SESSION['captcha'])  
	{
        $_SESSION['message'] =  $lang_phrases['err_captcha'];
        Header("Location: ".$returnUrl);
        exit;
	}
	
	$mess = get_template('templ/mail_gest.htm', array(
			'name'=>HtmlSpecialChars($arr['name']), 
			'email'=>HtmlSpecialChars($arr['email']), 
			'phone'=>HtmlSpecialChars($arr['phone']),
			'text'=>nl2br(HtmlSpecialChars($arr['text']))
			)); 
	send_mail($settings['admin_email'], 'Новый вопрос в разделе FAQ', $mess);
	
	$name = escape_string($arr['name']);
	$email = escape_string($arr['email']);
	$text = escape_string($arr['text']);
	$english = $englang ? 1 : 0;
	mysql_query("INSERT INTO ".TABLE_GEST." SET datetime=NOW(), ip='$ip', english='$english', ".
		"name='$name', email='$email', text='$text', gtema_id='$gtema_id', public=0")	
		or Error(1, __FILE__, __LINE__);
		
	//$_SESSION['message'] = $lang_phrases['otvetim'];

    $_SESSION['message'] = '<h2>'.$lang_phrases['faq_alert_title'].'</h2><p>'.$lang_phrases['faq_alert_text'].'</p>';
    $_SESSION['messageType'] = 'feedback.sent';
	$_SESSION['gest_data'] = '';

	Header("Location: ".$page_url);
	exit;
}

$replace = array();
$data_arr = @Unserialize($_SESSION['gest_data']);
foreach($order_fields as $v) $replace[$v] = HtmlSpecialChars(@$data_arr[$v]);

//$replace['text'] = nl2br($replace['text']);
$replace['send'] = @$send; 

$wh = $englang ? "english" : "!english";
$wh .= " AND public";

if((int)@$_GET['tema']) 
{
	$gtema_id = (int)@$_GET['tema'];
	$wh .= " AND gtema_id='$gtema_id'";
}
else $gtema_id = $replace['gtema_id'];

$replace['gtema_select'] = mysql_select('gtema_id', 
	"SELECT gtema_id, name$englang as name FROM ".TABLE_GTEMA." ORDER BY ord",
	$gtema_id, 0, "class='select_big_fixed bgr_bs'");
	
$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_GEST." WHERE $wh ") or Error(1, __FILE__, __LINE__);
$arr = mysql_fetch_array($sql);
$replace['all'] = $all = $arr[0];

list($limit, $replace['pages']) = user_pages($all, $page_url."?", $settings['gest_count']);

$list = array();
$sql = mysql_query("SELECT gest_id, datetime, name, email, text, answer FROM ".TABLE_GEST." WHERE $wh ORDER BY gest_id desc LIMIT $limit") 
	or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{ 
	$arr['name'] = HtmlSpecialChars($arr['name'], null, 'cp1251');
	$arr['text'] = nl2br(HtmlSpecialChars($arr['text'], null, 'cp1251'));
	$arr['answer'] = nl2br(HtmlSpecialChars($arr['answer'], null, 'cp1251'));
	list($date, $time) = split(" ", $arr['datetime']); 
	$time = substr($time, 0, 5);
	$d = split("-", $date);
	$arr['datetime'] = "$d[2].$d[1].$d[0] $time";
	$list[] = $arr;
}
$replace['list'] = $list;
$replace['order_url'] = $page_url;

$form_content = get_template('templ/gest.htm', $replace);
?>