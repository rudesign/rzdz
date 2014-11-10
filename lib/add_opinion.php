<?php

$page_id = (int)@$fsanator;

$_SESSION['order_data'] = '';
			
$text = from_form(@$text);
$text = trim(substr($text, 0, 3000));
$u_name = from_form(@$u_name);
$u_email = from_form(@$u_email);
$u_phone = from_form(@$u_phone);
$fsanator = (int)(@$fsanator); 
$date_from = from_form(@$date_from);
$date_to = from_form(@$date_to);

$arr = array('page_id'=>$page_id, 'text'=>$text, 'u_name'=>$u_name, 'u_email'=>$u_email, 'u_phone'=>$u_phone, 'fsanator'=>$fsanator,
	'date_from'=>$date_from, 'date_to'=>$date_to);
$_SESSION['opinion_data'] = serialize($arr);

$url = @$opinionlink ? ereg_replace("^/", "", $opinionlink) : ''; 
$url = MAIN_URL.$url;

if(!$u_name)  
{
	$_SESSION['message'] = $lang_phrases['err_name'];
	Header("Location: ".$url); 
	exit;	
}
		
if(!eregi("^([[:alnum:]]|_|-|\\.)+@([[:alnum:]]|_|-|\\.)+(\\.([[:alnum:]]|-)+)+$",$u_email)) 
{
	$_SESSION['message'] =  $lang_phrases['err_email'];
	Header("Location: ".$url);
	exit;	
}
	
if(!$text)  
{
	$_SESSION['message'] =  $lang_phrases['err_text'];
	Header("Location: ".$url);
	exit;
}
	
if(!@$ucaptcha || @$ucaptcha!=$_SESSION['captcha'])  
{
	$_SESSION['message'] =  $lang_phrases['err_captcha'];
	Header("Location: ".$url);
	exit;
}
	
$sql = mysql_query("SELECT p.name, ct.name as city FROM ".TABLE_PAGE." p 
	LEFT JOIN ".TABLE_CITY."  ct ON (ct.city_id=p.city_id)
	WHERE p.page_id='$page_id'") or Error(1, __FILE__, __LINE__);
$arr = @mysql_fetch_array($sql);
$page_name = HtmlSpecialChars(@$arr['name'], null, 'cp1251');
$city_name = HtmlSpecialChars(@$arr['city'], null, 'cp1251');

$name =  "$u_name (отзыв требует <a href=\"".ADMIN_URL."?p=opinion&page_id=$page_id\">подтверждения публикации</a>)";
$name1 = HtmlSpecialChars($u_name, null, 'cp1251');
$mess = get_template('templ/mail_add_opinion.htm', array(
		'name'=>$name,  'email'=>$u_email, 'phone'=>$u_phone, 
		'page_name'=>$page_name." ($city_name)",
		'date_from'=>$date_from,
		'date_to'=>$date_to,
		'pagelink'=>$url,
		'text'=>nl2br(HtmlSpecialChars($text, null, 'cp1251'))
		)); 
$arr = split(", ?", $settings['admin_email']);
foreach($arr as $mail) send_mail($mail, "отзыв от $name1", $mess);

$text = escape_string($text);

$english = $englang ? 1 : 0;
$data = "date=CURDATE(), text='$text', page_id=$page_id, public=0, english=$english, client_name='$u_name', 
	client_email='".escape_string($u_email)."', client_phone='".escape_string($u_phone)."', 
	date_from='".escape_string($date_from)."', date_to='".escape_string($date_to)."'";
mysql_query("INSERT INTO ".TABLE_OPINION." SET $data")	or Error(1, __FILE__, __LINE__);

$_SESSION['opinion_data'] = '';
 
$_SESSION['message'] = $lang_phrases['dobavlen_otziv'];

Header("Location: ".$url);
exit;
	
?>