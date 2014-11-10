<?php

$field_id = (int)@$field_id;
$form_id = (int)@$form_id;

if(@$addfield)
{
	$form_id = (int)@$addfield;

	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_FIELD2." WHERE form_id=$form_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_FIELD2." SET ord=$ord, form_id=$form_id") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&field_id=$id");
	exit;
}

if(@$addform)
{	
	mysql_query("INSERT INTO ".TABLE_FORM." SET name=''") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	mysql_query("UPDATE ".TABLE_FORM." SET name='Форма № $id' WHERE form_id=$id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&form_id=$id");
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
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&field_id=$field_id");
		exit;
	}
				
	$sql = mysql_query("SELECT ord, form_id FROM ".TABLE_FIELD2." WHERE field_id=$del_field") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$form_id = (int)@$arr['form_id'];
	
	mysql_query("DELETE FROM ".TABLE_FIELD2." WHERE field_id='$del_field'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_FIELD2." SET ord=ord-1 WHERE ord>$ord AND form_id=$form_id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}

if(@$del_form)
{
	$del_form = (int)$del_form;
				
	mysql_query("DELETE FROM ".TABLE_FIELD2." WHERE form_id='$del_form'") or Error(1, __FILE__, __LINE__);
	mysql_query("DELETE FROM ".TABLE_FORM." WHERE form_id='$del_form'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}

if(@$save)
{
	$public = (int)@$public;
	$ord = (int)@$ord;
	$type = (int)@$type;
	$line = (int)@$line;
	$checkfield = (int)@$checkfield;
	$name = escape_string(from_form(@$name));
	$title = escape_string(from_form(@$title));
	$data = escape_string(trim(from_form(@$data)));
	
	$sql = mysql_query("SELECT ord, form_id FROM ".TABLE_FIELD2." WHERE field_id=$field_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$form_id = (int)@$arr[1];

	mysql_query("UPDATE ".TABLE_FIELD2." SET public='$public', name='$name',  data='$data', type='$type', checkfield='$checkfield',".
				" title='$title', line='$line', ord='$ord' WHERE field_id='$field_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_FIELD2." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND field_id!='$field_id' AND form_id=$form_id") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_FIELD2." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND field_id!='$field_id' AND form_id=$form_id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&field_id=$field_id");
	exit;
}

if(@$saveform)
{
	$name = escape_string(from_form(@$name));
	$butt = escape_string(from_form(@$butt));
	$email = escape_string(from_form(@$email));
	
	mysql_query("UPDATE ".TABLE_FORM." SET name='$name', butt='$butt', email='$email' WHERE form_id='$form_id'") or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&form_id=$form_id");
	exit;
}

$replace = array();

$forms = array();
$sql_form = mysql_query("SELECT form_id, name, butt FROM ".TABLE_FORM." ORDER BY form_id") or Error(1, __FILE__, __LINE__);
	
while($info_form = @mysql_fetch_array($sql_form))
{
	$sql = mysql_query("SELECT field_id, name, public, line, title FROM ".TABLE_FIELD2.
		" WHERE form_id=$info_form[form_id] ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$fields = array(); $field_name = ""; 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);		
		if(!$info['name']) $info['name'] = NONAME;
		
		$info['title'] = HtmlSpecialChars($info['title']);
		
		$info['edit_link'] = ADMIN_URL."?p=$part&field_id=$info[field_id]";
		
		$info['del_link'] = ""; $info['icount'] = 0;
		if($i=check_field($info['field_id'])) $info['icount'] = $i;
		else $info['del_link'] = ADMIN_URL."?p=$part&del_field=$info[field_id]";
		
		if($info['field_id'] == $field_id) 
		{
			$form_id = $info_form['form_id'];
			$field_name = $info['name'];
		}
		
		$fields[] = $info;
	}
	
	$info_form['fields'] = $fields;
	$info_form['del_link'] = ADMIN_URL."?p=$part&del_form=$info_form[form_id]";
	$info_form['edit_link'] = ADMIN_URL."?p=$part&form_id=$info_form[form_id]";

	$forms[] = $info_form;

}

$replace['forms'] = $forms;
$replace['field_id'] = $field_id;
$replace['form_id'] = $form_id;

$left_menu = get_template('templ/field_list2.htm', $replace);

if($field_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_FIELD2." WHERE field_id='$field_id'") or Error(1, __FILE__, __LINE__);
	if($field = @mysql_fetch_array($sql))
	{
		$field['name'] = HtmlSpecialChars($field['name']);
		$field['data'] = HtmlSpecialChars($field['data']);
		
		$field['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $field['public'], 0);
		
		$field['line_select'] = array_select('line', array(0=>'Нет', 1=>'Да'), $field['line'], 0);
		
		$type_arr = array(
			0=>'Строка',
			1=>'Многострочный текст',
			2=>'Выбор из списка',
			3=>'Дата',
			4=>'Checkbox (галочки)',
			5=>'Адресат'
			);
		$field['type_select'] = array_select('type', $type_arr, $field['type'], 0);
		
		$checkfield_arr = array(
			0=>'Без проверки',
			1=>'Не пустое поле',
			2=>'Целое число',
			3=>'E-Mail',
			4=>'Дата'
			);
		$field['checkfield_select'] = array_select('checkfield', $checkfield_arr, $field['checkfield'], 0);
		
		$field['ord_select'] = ord_select("SELECT name FROM ".TABLE_FIELD2.
			" WHERE field_id!=$field_id AND form_id=$field[form_id] ORDER BY ord", 'ord', $field['ord']);
		
		$content = get_template('templ/field2.htm', $field);
	}
	return;
}

if($form_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_FORM." WHERE form_id='$form_id'") or Error(1, __FILE__, __LINE__);
	if($form = @mysql_fetch_array($sql))
	{
		$form['name'] = HtmlSpecialChars($form['name']);

		$form['butt'] = HtmlSpecialChars($form['butt']);		
		if(!$form['butt']) $form['butt'] = 'Отправить запрос';
			
		$form['email'] = HtmlSpecialChars($form['email']);
		
		$content = get_template('templ/form.htm', $form);
	}

}
	
?>