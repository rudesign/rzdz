<?php

$field_id = (int)@$field_id;

if(@$addfield)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_FIELD) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_FIELD." SET ord=$ord") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&field_id=$id");
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
				
	$sql = mysql_query("SELECT ord FROM ".TABLE_FIELD." WHERE field_id=$del_field") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	
	mysql_query("DELETE FROM ".TABLE_FIELD." WHERE field_id='$del_field'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_FIELD." SET ord=ord-1 WHERE ord>$ord") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}

if(@$save)
{
	$public = (int)@$public;
	$ord = (int)@$ord;
	$type = (int)@$type;
	$line = (int)@$line;
	$short = (int)@$short;
	$bron = (int)@$bron;
	$checkfield = (int)@$checkfield;
	$name = escape_string(from_form(@$name));
	$title = escape_string(from_form(@$title));
	$plus = escape_string(from_form(@$plus));
	$data = escape_string(trim(from_form(@$data)));
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_FIELD." WHERE field_id=$field_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];

	mysql_query("UPDATE ".TABLE_FIELD." SET public='$public', name='$name',  data='$data', type='$type', 
				short='$short', bron='$bron', checkfield='$checkfield',".
				" title='$title', plus='$plus', line='$line', ord='$ord' WHERE field_id='$field_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_FIELD." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND field_id!='$field_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_FIELD." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND field_id!='$field_id'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&field_id=$field_id");
	exit;
}

$replace = array();

$sql = mysql_query("SELECT field_id, name, public, line, title, plus FROM ".TABLE_FIELD." ORDER BY ord") or Error(1, __FILE__, __LINE__);

$fields = array(); $field_name = ""; 
while($info = @mysql_fetch_array($sql))
{ 
	$info['name'] = HtmlSpecialChars($info['name']);
	$info['plus'] = HtmlSpecialChars($info['plus']);
	
	$info['name'] .= ($info['name']) ? " $info[plus]" : $info['plus'];
	if(!$info['name']) $info['name'] = NONAME;
	
	$info['title'] = HtmlSpecialChars($info['title']);
	
	$info['edit_link'] = ADMIN_URL."?p=$part&field_id=$info[field_id]";
	
	$info['del_link'] = ""; $info['icount'] = 0;
	if($i=check_field($info['field_id'])) $info['icount'] = $i;
	else $info['del_link'] = ADMIN_URL."?p=$part&del_field=$info[field_id]";
	
	if($info['field_id'] == $field_id) $field_name = $info['name'];
	
	$fields[] = $info;
}

$replace['fields'] = $fields;
$replace['field_id'] = $field_id;

$left_menu = get_template('templ/field_list.htm', $replace);

if($field_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_FIELD." WHERE field_id='$field_id'") or Error(1, __FILE__, __LINE__);
	if($field = @mysql_fetch_array($sql))
	{
		$field['name'] = HtmlSpecialChars($field['name']);
		$field['data'] = HtmlSpecialChars($field['data']);
		
		$field['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $field['public'], 0);
		
		$field['line_select'] = array_select('line', array(0=>'Нет', 1=>'Да'), $field['line'], 0);
		
		$field['short'] = (int)($field['short']);
		$field['bron'] = (int)($field['bron']);
		
		$type_arr = array(
			0=>'Строка',
			1=>'Многострочный текст',
			2=>'Выбор из списка',
			3=>'Дата'
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
		
		$field['ord_select'] = ord_select("SELECT name FROM ".TABLE_FIELD.
			" WHERE field_id!=$field_id ORDER BY ord", 'ord', $field['ord']);
		
		$content = get_template('templ/field.htm', $field);
	}
}
	
?>