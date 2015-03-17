<?php


if(@$sendorder) 
{
	$navig[] = array('name'=>'Опрос', 'link'=>'');
	$content = '<P align=center><strong>Ваши ответы отправлены. <br>
		Благодарим Вас за время, потраченное на заполнение анкеты!</strong></P>'; 
	return;
}

$common_fields = array('fam'=>'Фамилия','name'=>'Имя','otch'=>'Отчество','sanat'=>'Санаторий',
	'date_from'=>'с','date_to'=>'по','phone'=>'Телефон','email'=>'E-Mail');

$order_url = "/questionnaire/";

$order_fields = array();

$where = "";

if(!@$_SESSION['mail_id'])
{
	if(isset($qid) && isset($mid))
	{
		$_SESSION['quest_id'] = $quest_id = (int)@$qid;
		$_SESSION['mail_id'] = $mail_id = (int)@$mid;
		$secret = escape_string(@$secret);
		
		$where .= " AND secret='$secret'";
	}
	else {$quest_id=0; $mail_id=0;}
	
}
else
{
	$mail_id = (int)$_SESSION['mail_id'];
}
$quest_id = (int)$_SESSION['quest_id'];
	
if($quest_id && $mail_id)
{
	$sql = mysql_query("
		SELECT 
			q.*, qm.email as client_email
		FROM 
			".TABLE_QUESTMAIL." qm 
			LEFT JOIN ".TABLE_QUESTIONNAIRE." q  on (qm.quest_id=q.quest_id) 
		WHERE
			q.quest_id=$quest_id AND q.public='1' AND qm.mail_id=$mail_id AND !qm.done $where
		") or Error(1, __FILE__, __LINE__, 1);
	if(!($quest = @mysql_fetch_array($sql)))
	{
		/*$navig[] = array('name'=>'Опрос', 'link'=>'');
		$content = '<p>Извините, не найдены данные опроса.</p>'; 
		return;*/
		Header("Location: $lpefix/$part");
		exit;
	}
}
else
{
	$sql = mysql_query("
		SELECT *
		FROM 
			".TABLE_QUESTIONNAIRE."
		WHERE
			public='1'
		ORDER by quest_id
		LIMIT 1
		") or Error(1, __FILE__, __LINE__, 1);
	$quest = @mysql_fetch_array($sql);
	$_SESSION['quest_id'] = $quest_id = $quest['quest_id'];
}


$quest_name = HtmlSpecialChars($quest['name']);
$navig[] = array('name'=>$quest_name, 'link'=>'');
$admin_email = $quest['email'] ? $quest['email'] : $settings['admin_email'];

$sql = mysql_query("SELECT * FROM ".TABLE_QUESTFIELD." WHERE public='1' AND quest_id=$quest_id ORDER BY ord") or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{
	$order_fields[] = $arr;
}

$direct_url = MAIN_URL.ereg_replace("^/", "", $order_url);


if(@$mode)
{
	if($mode == 'reset')
	{
		$_SESSION['order_data'] = '';
		Header("Location: ".$direct_url);
		exit;
	}
	
	$arr = array();
	$err = 0;
	
	foreach($common_fields as $k=>$v) 
	{
		$arr[$k] = from_form(@${$k});
		if(!$arr[$k] && $k!='email')
		{
			$err = 1;
			$arr["err_$k"] = 1;
		}
	}
	
	foreach($order_fields as $v) 
	{
		$id = $v['field_id'];

		$arr[$id] =  ($v['type']==4) ? ( is_array(@${"value_$id"}) ? ${"value_$id"} : '') 
									: from_form(@${"value_$id"});
									
		if(!$arr[$id])	{$arr["err_$id"] = 1; $err = 1;}

		if($v['type']!=4)
		switch($v['checkfield'])
		{
			case 1:
				if(!$arr[$id]) {$arr["err_$id"] = 1; $err = 1;}
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
		elseif($v['type']==4 && $v['checkfield'])
		{
			if(!$arr[$id]) {$arr["err_$id"] = 1; $err = 1;}
			else
			{
				$kk = 0;
				foreach($arr[$id] as $v) if($v) {$kk=1; break;} 
				if(!$kk)  {$arr["err_$id"] = 1; $err = 1;}
			}
		}
	}
	
	if($err)
	{
		$_SESSION['message'] = "Пожалуйста, заполните выделенные красным поля!";
		$_SESSION['order_data'] = Serialize($arr); 
		Header("Location: ".$direct_url);
		exit;
	}
	
	$arr_html = array();
	$arr_sql = array();
	
	$mail_arr = split(", ?", $admin_email);
	
	foreach($common_fields as $k=>$v) 
	{
		$arr_html[] = array('name'=>$v, 'value'=>$arr[$k]);
		$arr_sql[] = array('name'=>$v, 'value'=>$arr[$k]);
	}
		
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
		
		$arr_html[] = array('name'=>HtmlSpecialChars($v['name']), 'value'=>$value);
		
		$value = ($v['type'] == 4) ? $value : $arr[$id];
		$arr_sql[] = array('name'=>$v['name'], 'value'=>$value);
	}
	
	$mess = get_template('templ/mail_quest_results.htm', array('list'=>$arr_html)); 
	
	foreach($mail_arr as $mail) send_mail($mail, 'опрос на '.DOMAIN, $mess);
	
	$_SESSION['order_data'] = ''; $_SESSION['quest_id'] = ''; $_SESSION['mail_id'] = '';

	mysql_query("UPDATE ".TABLE_QUESTMAIL." SET done=1 WHERE mail_id='$mail_id'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".$direct_url."?sendorder=1");
	exit;
}


$replace = array();
$data_arr = @Unserialize($_SESSION['order_data']); 
foreach($common_fields as $k=>$v) 
{
	$replace[$k] = ($k=='email' && $mail_id) ? $quest['client_email'] : HtmlSpecialChars(@$data_arr[$k]);
	$replace["err_$k"] = @$data_arr["err_$k"];
}
$replace["sanat_list"] = $sanat_list;
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
$replace['quest_name'] = $quest_name; 

$content = get_template('templ/poll.htm', $replace); 

?>