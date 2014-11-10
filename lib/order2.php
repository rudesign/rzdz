<?php

session_register('order_data2');

$order_fields = array();

$sql = mysql_query("SELECT butt, email, name FROM ".TABLE_FORM." WHERE form_id=$form_id") or Error(1, __FILE__, __LINE__);
if(!($arr = @mysql_fetch_array($sql))) {$form_content = ''; return;}
$button = HtmlSpecialChars($arr['butt']);
$admin_email = $arr['email'] ? $arr['email'] : $settings['site_email'];
$form_name = $arr['name'];

$sql = mysql_query("SELECT * FROM ".TABLE_FIELD2." WHERE public='1' AND form_id=$form_id ORDER BY ord") or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{
	$order_fields[] = $arr;
}

$direct_url = MAIN_URL.ereg_replace("^/", "", $order_url);

if(@$mode)
{
	if($mode == 'reset')
	{
		$_SESSION['order_data2'] = '';
		Header("Location: ".$direct_url);
		exit;
	}
	
	$arr = array();
	
	$err = 0;
	foreach($order_fields as $v) 
	{
		$id = $v['field_id'];

		$arr[$id] =  ($v['type']==4) ? ( is_array(@${"value_$id"}) ? ${"value_$id"} : '') 
									: from_form(@${"value_$id"});

		
		switch($v['checkfield'])
		{
			case 1:
				if($v['type']==4)
				{
					if(!$arr[$id]) {$arr["err_$id"] = 1; $err = 1; break;}
					else
					{
						$dd = 0;
						foreach($arr[$id] as $v) if($v) {$dd=1; break;}
						if(!$dd) {$arr["err_$id"] = 1; $err = 1;break;}
					}
				}
				else if(!$arr[$id]) {$arr["err_$id"] = 1; $err = 1;}
				break;
			case 2:
				$arr[$id] = (int)$arr[$id];
				if(!$arr[$id]) {$arr["err_$id"] = 1; $err = 1;}
				break;
			case 3:
				if(!eregi("^([[:alnum:]]|_|-|\\.)+@([[:alnum:]]|_|-|\\.)+(\\.([[:alnum:]]|-)+)+$",$arr[$id])) 
					{$arr["err_$id"] = 1; $err = 1;}
				break;
			case 4:
				@list($d, $m, $y) = @split("\\.", $arr[$id]); $y+=2000; 
				if(!checkdate((int)$m, (int)$d, (int)$y)) {$arr["err_$id"] = 1; $err = 1; break;}
				/*if(@mktime(0,0,0, (int)$m, (int)$d, (int)$y) < mktime(0,0,0,date("m"),date("d"),date("Y"))) 
						{$arr["err_$id"] = 1; $err = 1;}*/
				break;
			case 5:
				$arr[$id] = (int)$arr[$id];
				if(!$arr[$id]) {$arr["err_$id"] = 1; $err = 1;}
				break;
		}
	}
	
	if($err)
	{
		$_SESSION['order_data2'] = Serialize($arr); 
		Header("Location: ".$direct_url);
		exit;
	}
	
	$arr_html = array();
	$arr_sql = array();
	
	$mail_arr = split(", ?", $admin_email);
	
	foreach($order_fields as $v) 
	{
		$id = $v['field_id'];
		
		//if($v['type'] == 2) $value = nl2br(HtmlSpecialChars($arr[$id]));
		if($v['type'] == 4)
		{
			$options_arr = split("((\r)?\n(\r)?)+", $v['data']);
			$val_arr = $arr[$id];
			$value = '';
			foreach($options_arr as $k=>$opt)
			{
				if(isset($val_arr[$k+1])) 
				{
					if($value) $value .= ", ";
					$value .= HtmlSpecialChars($opt);
				}
			}
		}
		elseif($v['type'] == 5)
		{
			$mail_arr[] = $arr[$id];
			continue;
		}
		else $value = nl2br(HtmlSpecialChars($arr[$id]));
		
		$arr_html[] = array('name'=>HtmlSpecialChars($v['name']), 'value'=>$value, 'line'=>$v['line']);
		
		$value = ($v['type'] == 4) ? $value : $arr[$id];
		$arr_sql[] = array('name'=>$v['name'], 'value'=>$value, 'line'=>$v['line']);
	}
	
	$mess = get_template('templ/mail_order.htm', array('list'=>$arr_html, 'form_name'=>$form_name)); 
	foreach($mail_arr as $mail) send_mail($mail, "сообщение $form_name ".DOMAIN, $mess);
	
	$data = escape_string(Serialize($arr_sql)); 
	mysql_query("INSERT INTO ".TABLE_ORDER." SET date=CURDATE(), data='$data'")	
		or Error(1, __FILE__, __LINE__);
		
	$_SESSION['order_data2'] = '';
	
	Header("Location: ".$direct_url."?sendorder=1");
	exit;
}


$replace = array();
$data_arr = @Unserialize($_SESSION['order_data2']);
$replace["err"] = 0;
$fields = array();
foreach($order_fields as $v) 
{
	$id = $v['field_id'];
	$v['value'] = @$data_arr[$id];
	$v['err'] = @$data_arr["err_$id"];
	$v['name'] = HtmlSpecialChars($v['name']);
	if($v["err"]) $replace["err"] = 1;
	
	switch ($v['type'])
	{
		case 0:
			$v['value'] = HtmlSpecialChars($v["value"]);
			break;
		case 1:
			$v['value'] = HtmlSpecialChars($v["value"]);
			break;
		case 2:
			$options_arr = split("((\r)?\n(\r)?)+", $v['data']);
			$options = '';
			foreach($options_arr as $opt)
			{
				$sel = ($v['value'] == $opt) ? 'selected' : '';
				$options .= "\n<option value=\"".HtmlSpecialChars($opt)."\" $sel>".HtmlSpecialChars($opt)."</option>";
			}
			$v['value'] = $options;
			break;
		case 3:
			@list($d, $m, $y) = @split('\\.', $v['value']);
			$v['value'] = (@checkdate($m, $d, $y+2000)) ? "$d.$m.$y" : '';
			break;
		case 4:
			$options_arr = split("((\r)?\n(\r)?)+", $v['data']);
			$incol = (count($options_arr)%3) ? (int)(count($options_arr)/3)+1 : count($options_arr)/3; 
			$i = 0;			
			$options = array();
			$val_arr = $v['value'];
			foreach($options_arr as $opt)
			{
				$i++;
				$info = array();
				$info['i'] = $i;
				$info['newcol'] = !(($i+$incol)%$incol) ? 1 : 0; 
				$info['name'] = HtmlSpecialChars($opt);
				$info['checked'] = (isset($val_arr[$i])) ? 'checked' : '';
				$options[] = $info;
			}
			$v['value'] = $options;
			break;
		case 5:
			$options_arr = split("((\r)?\n(\r)?)+", $v['data']);
			$options = '';
			foreach($options_arr as $opt)
			{
				$sel = ($v['value'] == $opt) ? 'selected' : '';
				$mail = '';
				if(ereg("\\[(.+)\\]", $opt, $F)) 
				{
					$opt = trim(ereg_replace("\\[.+\\]", "", $opt));
					$mail = $F[1];
				}
				$options .= "\n<option value=\"".HtmlSpecialChars($mail)."\" $sel>".HtmlSpecialChars($opt)."</option>";
			}
			$v['value'] = $options;
			break;
	}
		
	$fields[] = $v;
}
$replace["fields"] = $fields;
$replace["order_url"] = $order_url;

$replace['sendorder'] = @$sendorder; 
$replace['button'] = $button; 

$form_content = get_template('templ/order2.htm', $replace); 


?>