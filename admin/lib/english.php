<?php

$lang_list = array(
	'search', 'search_site', 'search_cure', 'free_call', 'region_phone',
	'news', 'spec', 'sitemap', 'faq', 'print', 'bystroe', 'san_kur_lechenie',
	'zabolev', 'san_kurort_karta', 'sanatorii', 'zhd', 'press',
	'reklamnye', 'item', 'video', 'virtual', 'pdf', 'partners', 'oplata', 'nashy_san', 'media', 'more',
	'zakazat',  'opisanie', 'opinion', 'ostavit_otziv', 'dobavit_otziv', 'kogda_otdyh', 'from', 'to', 'vash_otzyv', 'vse_sanatorii',
	'captcha', 'captcha1', 'captcha2', 'sendphone', '404', 'notfound', 'home', 'sitemap', 'pages', 'favourites',
	'we_will_call', 'leave_phone', 'name', 'phone_number', 'email', 'skryt', 'raskryt',  'otvet', 'svernut', 'zadaite', 'vash_vopros',
	'err_phone', 'err_name', 'err_email', 'err_captcha', 'err_text', 'dobavlen_otziv', 'otvetim', 'close', 'thank', 'our_manager',
	'po_zaprosu', 'naideno', 'ne_naideno', 'big_quality' , 'gtema', 'gtema_choise', 'medicine');


if(@$_POST['save'])
{
	$str = '';
	foreach($lang_list as $v)
	{
		$str .= "\$lang_settings['$v'] = array(";
		$val = get_post($v); 
		$val_en = get_post($v.'_en'); 
		if(!$val) 
		{
			$_SESSION['message'] = "Значение поля \'".AddSlashes($v)."\' неверно";
			Header("Location: ".ADMIN_URL."?p=$part");
			exit;
		}
		$str .= "'name'=>\"".str_replace("\"", "\\\"", $val)."\"";
		$str .= ", 'name_en'=>\"".str_replace("\"", "\\\"", $val_en)."\"";
		$str .= ");\n";
	}
	
	$f = fopen('lang.php', 'w');
	flock($f, LOCK_EX);
	
	fwrite($f, "<?\n");
	fwrite($f, $str);
	fwrite($f, "?>");
	
	fflush($f);
	flock($f, LOCK_UN);
	fclose($f);
	
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}

$left_menu = ' ';

require 'lang.php';

$list = array();
foreach($lang_list as $v)
{
	$arr = array('field'=>$v, 'name'=>HtmlSpecialChars(@$lang_settings[$v]['name']), 'name_en'=>HtmlSpecialChars(@$lang_settings[$v]['name_en']));
	$list[] = $arr;
}


$content = get_template('templ/lang.htm', array('list'=>$list));
	
?>