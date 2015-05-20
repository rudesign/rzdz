<?php

$gest_id = (int)@$gest_id;

if(isset($_GET['addgest']))
{
	$english = get_post('english');
		
	mysql_query("INSERT INTO ".TABLE_GEST." SET datetime=NOW(), english='$english'")	
		or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&page=$current_page");
	exit;
}

if(@$_POST['save'])
{
	$name = get_post('name');
	$email = get_post('email');
	$text = get_post('text');
	$answer = get_post('answer');
	$gtema_id = (int)(@$gtema_id);
	
	if(@$sendemail && $email)
	{
		require("settings.php");
		$mess = get_template('../templ/mail_answer.htm', array(
				'name'=>HtmlSpecialChars($name), 
				'email'=>HtmlSpecialChars($email), 
				'text'=>nl2br(HtmlSpecialChars($text)),
				'answer'=>nl2br(HtmlSpecialChars($answer)),
				'admin_email'=>$settings['admin_email']
				)); 
		send_mail($email, 'ответ на '.DOMAIN, $mess);
		$_SESSION['message'] = "Ответ отправлен на email ".escape_string($email);
	}
	
	$name = escape_string($name);
	$email = escape_string($email);
	$text = escape_string($text);
	$answer = escape_string($answer);
	
	mysql_query("UPDATE ".TABLE_GEST." SET name='$name', email='$email', text='$text', gtema_id='$gtema_id', ".
				" answer='$answer' WHERE gest_id='$gest_id'") or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&page=$current_page");
	exit;
}

if(@$del)
{
	mysql_query("DELETE FROM ".TABLE_GEST." WHERE gest_id='$gest_id'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&page=$current_page");
	exit;
}


if(@$addgtema)
{	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_GTEMA) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_GTEMA." SET ord=$ord") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
		
	Header("Location: ".ADMIN_URL."?p=$part&gtemas=1");
	exit;
}

if(@$savegtema)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_GTEMA) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_GTEMA." WHERE gtema_id='$gtema_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr['ord'];
	
	$ord = (int)@$ord;
	if($ord < 1 || $ord > $count) 
	{
		$_SESSION['message'] = "Неверное значение порядкового номера (от 1 до $count)";
		Header("Location: ".ADMIN_URL."?p=$part&gtemas=1");
		exit;
	}
	
	$public = (int)@$public;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	
	mysql_query("UPDATE ".TABLE_GTEMA." SET public='$public', name='$name', name_en='$name_en', ord='$ord' ".
				"WHERE gtema_id='$gtema_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_GTEMA." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND gtema_id!='$gtema_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_GTEMA." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND gtema_id!='$gtema_id'") or Error(1, __FILE__, __LINE__);
	
	$url = ADMIN_URL."?p=$part&gtemas=1";
	
	Header("Location: ".$url);
	exit;
}

if(@$delgtema)
{
	$sql = mysql_query("SELECT count(*) FROM ".TABLE_GEST." WHERE gtema_id=$gtema_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	if($arr[0])
	{
		$_SESSION['message'] = "Невозможно удалить тему, используется для $arr[0] вопросов";
		Header("Location: ".ADMIN_URL."?p=$part&gtemas=1");
		exit;
	}
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_GTEMA." WHERE gtema_id=$gtema_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	
	mysql_query("DELETE FROM ".TABLE_GTEMA." WHERE gtema_id='$gtema_id'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_GTEMA." SET ord=ord-1 WHERE ord>$ord") or Error(1, __FILE__, __LINE__);	
		
	Header("Location: ".ADMIN_URL."?p=$part&gtemas=1");
	exit;
}

$replace = array();


if(isset($gtemas))
{
	$sql = mysql_query("SELECT * FROM ".TABLE_GTEMA." ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$gtemas = array(); 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		$info['name_en'] = HtmlSpecialChars($info['name_en']);
		//if(!$info['name']) $info['name'] = NONAME;
		
		$info['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $info['public'], 0);
	
		$gtemas[] = $info;
	}

	$replace['gtemas'] = $gtemas;

	$content = get_template('templ/gtema_list.htm', $replace);

	return;
}


$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_GEST) or Error(1, __FILE__, __LINE__);
$arr = mysql_fetch_array($sql);
$replace['all'] = $all = $arr[0];

list($limit, $replace['pages']) = pages($all, ADMIN_URL."?p=$part&");
$replace['onpage_select'] = array_select('onpage', $onpage_list, $_SESSION['on_page'], 0, 
	"onchange=\"window.location='".ADMIN_URL."?p=$part&onpage='+this.value\"");
		
$sql = mysql_query("SELECT g.* FROM ".TABLE_GEST." g ".
	" ORDER BY gest_id desc LIMIT $limit") or Error(1, __FILE__, __LINE__);

$gests = array(); $i = ($current_page-1)*$_SESSION['on_page']; 
while($info = @mysql_fetch_array($sql))
{ 
	$i++;
	$info['j'] = $i;
	$info['name'] = HtmlSpecialChars($info['name']);
	$info['email'] = HtmlSpecialChars($info['email']);
	$info['text'] = HtmlSpecialChars($info['text']);
	
	list($date, $time) = split(" ", $info['datetime']); 
	$time = substr($time, 0, 5);
	$d = split("-", $date);
	$info['datetime'] = "$d[2].$d[1].$d[0] $time";
	
	$info['gtema_select'] = mysql_select('gtema_id', 
		"SELECT gtema_id, name FROM ".TABLE_GTEMA." ORDER BY ord",
		$info['gtema_id'], 0);
	
	$gests[] = $info;
}

$replace['gests'] = $gests;
$replace['current_page'] = $current_page;

$content = get_template('templ/gest_list.htm', $replace);

?>