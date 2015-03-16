<?php

$field_id = (int)@$field_id;
$quest_id = (int)@$quest_id;

if(@$addfield)
{
	$quest_id = (int)@$addfield;

	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_QUESTFIELD." WHERE quest_id=$quest_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_QUESTFIELD." SET ord=$ord, quest_id=$quest_id") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&field_id=$id");
	exit;
}

if(@$addquest)
{	
	mysql_query("INSERT INTO ".TABLE_QUESTIONNAIRE." SET name=''") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	mysql_query("UPDATE ".TABLE_QUESTIONNAIRE." SET name='Опрос № $id' WHERE quest_id=$id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&quest_id=$id");
	exit;
}

function check_field($field_id)
{
	return 0;
}

if(@$del_field)
{
	$del_field = (int)$del_field;
	if(check_field($del_field))
	{
		$message = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&field_id=$field_id");
		exit;
	}
				
	$sql = mysql_query("SELECT ord, quest_id FROM ".TABLE_QUESTFIELD." WHERE field_id=$del_field") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$quest_id = (int)@$arr['quest_id'];
	
	mysql_query("DELETE FROM ".TABLE_QUESTFIELD." WHERE field_id='$del_field'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_QUESTFIELD." SET ord=ord-1 WHERE ord>$ord AND quest_id=$quest_id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}

if(@$del_quest)
{
	$del_quest = (int)$del_quest;
				
	mysql_query("DELETE FROM ".TABLE_QUESTFIELD." WHERE quest_id='$del_quest'") or Error(1, __FILE__, __LINE__);
	mysql_query("DELETE FROM ".TABLE_QUESTIONNAIRE." WHERE quest_id='$del_quest'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}


if(@$save)
{
	$public = (int)@$public;
	$ord = (int)@$ord;
	$type = (int)@$type;
	$checkfield = (int)@$checkfield;
	$name = escape_string(from_form(@$name));
	$data = escape_string(trim(from_form(@$data)));
	
	$sql = mysql_query("SELECT ord, quest_id FROM ".TABLE_QUESTFIELD." WHERE field_id=$field_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$quest_id = (int)@$arr[1];

	mysql_query("UPDATE ".TABLE_QUESTFIELD." SET public='$public', name='$name',  data='$data', type='$type', checkfield='$checkfield',".
				"  ord='$ord' WHERE field_id='$field_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_QUESTFIELD." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND field_id!='$field_id' AND quest_id=$quest_id") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_QUESTFIELD." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND field_id!='$field_id' AND quest_id=$quest_id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&field_id=$field_id");
	exit;
}

if(@$savequest)
{
	$name = escape_string(from_form(@$name));
	$butt = escape_string(from_form(@$butt));
	$email = escape_string(from_form(@$email));
	$public = (int)(@$public);
	
	mysql_query("UPDATE ".TABLE_QUESTIONNAIRE." SET name='$name', butt='$butt', email='$email', public='$public' WHERE quest_id='$quest_id'") 
		or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&quest_id=$quest_id");
	exit;
}

if(@$emails)
{
	$list = array();
	$i = 0; $y=0;
	$quest_id = (int)(@$quest_id);
	while(eregi("([[:alnum:]]|_|-|\\.)+@([[:alnum:]]|_|-|\\.)+(\\.([[:alnum:]]|-)+)+", $emails, $F))
	{
		$sql = mysql_query("SELECT count(*) FROM ".TABLE_QUESTMAIL." WHERE email='$F[0]' AND quest_id=$quest_id") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		if(!$arr[0])
		{
			$sim = "23456789abdefghijkmnprstuvwxyz";
			$secret = '';
			for($j=0;$j<6;$j++) $secret .= $sim[mt_rand(0,strlen($sim)-1)];
	
			mysql_query("INSERT INTO ".TABLE_QUESTMAIL." SET quest_id=$quest_id, email='$F[0]', secret='$secret'") 
			or Error(1, __FILE__, __LINE__);
			$i++;
		}
		
		$emails = str_replace($F[0], "", $emails); 
	}
	
	$_SESSION['message'] = "Добавлено $i адресов";
	$url = ADMIN_URL."?p=$part&quest_id=$quest_id";
	Header("Location: ".$url);
	exit;
}

if(@$del_email)
{
	$del_email = (int)@$del_email;
	$quest_id = (int)(@$quest_id);
	
	mysql_query("DELETE FROM ".TABLE_QUESTMAIL." WHERE mail_id=$del_email AND quest_id=$quest_id") 
		or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&db=1&quest_id=$quest_id&page=$current_page");
	exit;
}

if(@$clear)
{
	$quest_id = (int)(@$quest_id);
	
	mysql_query("DELETE FROM ".TABLE_QUESTMAIL." WHERE quest_id=$quest_id") 
		or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&quest_id=$quest_id");
	exit;
}
if(@$startquest || @$stopquest)
{
	$quest_id = (int)(@$quest_id);
	
	$status = @$startquest ? 1 : 0;
	mysql_query("UPDATE  ".TABLE_QUESTIONNAIRE." SET status=$status WHERE quest_id=$quest_id") 
		or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&quest_id=$quest_id");
	exit;
}

$replace = array();

$quests = array();
$sql_quest = mysql_query("SELECT quest_id, name, butt, public FROM ".TABLE_QUESTIONNAIRE." ORDER BY quest_id") or Error(1, __FILE__, __LINE__);
	
while($info_quest = @mysql_fetch_array($sql_quest))
{
	$sql = mysql_query("SELECT field_id, name, public FROM ".TABLE_QUESTFIELD.
		" WHERE quest_id=$info_quest[quest_id] ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$fields = array(); $field_name = ""; 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);		
		if(!$info['name']) $info['name'] = NONAME;
		
		
		$info['edit_link'] = ADMIN_URL."?p=$part&field_id=$info[field_id]";
		
		$info['del_link'] = ""; $info['icount'] = 0;
		if($i=check_field($info['field_id'])) $info['icount'] = $i;
		else $info['del_link'] = ADMIN_URL."?p=$part&del_field=$info[field_id]";
		
		if($info['field_id'] == $field_id) 
		{
			$quest_id = $info_quest['quest_id'];
			$field_name = $info['name'];
		}
		
		$fields[] = $info;
	}
	
	$info_quest['fields'] = $fields;
	$info_quest['del_link'] = ADMIN_URL."?p=$part&del_quest=$info_quest[quest_id]";
	$info_quest['edit_link'] = ADMIN_URL."?p=$part&quest_id=$info_quest[quest_id]";

	$quests[] = $info_quest;

}

$replace['quests'] = $quests;
$replace['field_id'] = $field_id;
$replace['quest_id'] = $quest_id;

$left_menu = get_template('templ/quest_field_list.htm', $replace);

if($field_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_QUESTFIELD." WHERE field_id='$field_id'") or Error(1, __FILE__, __LINE__);
	if($field = @mysql_fetch_array($sql))
	{
		$field['name'] = HtmlSpecialChars($field['name']);
		$field['data'] = HtmlSpecialChars($field['data']);
		
		if(!$field['public'] && !$field['name']) $field['public'] = 1;
		$field['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $field['public'], 0);
		
		$field['line_select'] = array_select('line', array(0=>'Нет', 1=>'Да'), @$field['line'], 0);
		
		$type_arr = array(
			0=>'Строка',
			1=>'Многострочный текст',
			2=>'Выбор из списка (один ответ)',
			//3=>'Дата',
			4=>'Выбор из списка (галочки)',
			//5=>'Адресат'
			);
		$field['type_select'] = array_select('type', $type_arr, $field['type'], 0);
		
		$checkfield_arr = array(
			0=>'Без проверки',
			1=>'Не пустое поле',
			//2=>'Целое число',
			//3=>'E-Mail',
			//4=>'Дата'
			);
		if(!$field['checkfield'] && !$field['name']) $field['checkfield'] = 1;
		$field['checkfield_select'] = array_select('checkfield', $checkfield_arr, $field['checkfield'], 0);
		
		$field['ord_select'] = ord_select("SELECT name FROM ".TABLE_QUESTFIELD.
			" WHERE field_id!=$field_id AND quest_id=$field[quest_id] ORDER BY ord", 'ord', $field['ord']);
		
		$content = get_template('templ/quest_field.htm', $field);
	}
	return;
}

if($quest_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_QUESTIONNAIRE." WHERE quest_id='$quest_id'") or Error(1, __FILE__, __LINE__);
	if($quest = @mysql_fetch_array($sql))
	{
		$quest['name'] = HtmlSpecialChars($quest['name']);

		$quest['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $quest['public'], 0);
		
		$quest['butt'] = HtmlSpecialChars($quest['butt']);		
		if(!$quest['butt']) $quest['butt'] = 'Отправить запрос';
			
		$quest['email'] = HtmlSpecialChars($quest['email']);
						
	}
	else {$content = "не найден опрос"; return;}
	
	
	$where = "quest_id=$quest_id";
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_QUESTMAIL." WHERE $where") or Error(1, __FILE__, __LINE__);
	$arr = mysql_fetch_array($sql);
	$quest['all'] = $all = $arr[0];	

	list($limit, $quest['pages']) = pages($all, ADMIN_URL."?p=$part&quest_id=$quest_id&");
			
	$sql = mysql_query("SELECT mail_id, email, send FROM ".TABLE_QUESTMAIL."  WHERE $where ORDER BY email LIMIT $limit") 
		or Error(1, __FILE__, __LINE__);
	
	$list = array(); $i = ($current_page-1)*$_SESSION['on_page'];
	while($info = @mysql_fetch_array($sql))
	{ 
		$i++; 
		$info['i'] = $i;
		$info['del_link'] = ADMIN_URL."?p=$part&del_email=$info[mail_id]&quest_id=$quest_id&page=$current_page";
		$list[] = $info;
	}
	$quest['list'] = $list;


	$sql = mysql_query("SELECT count(*) FROM ".TABLE_QUESTMAIL." WHERE quest_id=$quest_id AND send") or Error(1, __FILE__, __LINE__);
	$arr = mysql_fetch_array($sql);
	$quest['mailcount'] = $arr[0];	


	$content = get_template('templ/quest.htm', $quest);
}
	
?>