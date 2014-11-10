<?php

function send_order_rs($data, $fsanator)
{
	$url = "http://rs.tour-shop.ru/siteorder.php"; 
	
	$parse_url = parse_url($url); 
	
	$path = $parse_url["path"];
	$host= $parse_url["host"]; 

	$site_id=32; $secret = 'e74e79ab9a';
	if($fsanator)
	{ 	
		$s_dirs = array(
			'zeleniy_gay'=>array(20, 'ed430e5309'),
			'dolina_narzanov_kislovodsk'=>array(21, '9618f39783'),
			'dolina_narzanov_essentuki'=>array(22, '6bb367d1a2'),
			'dolina_narzanov_nalchik'=>array(23, '1b72862a31'),
			'don'=>array(24, '816745f3a8'),
			'radon'=>array(25, 'bedde8d0ed'),
			'buran'=>array(26, '3023755487'),
			'jeleznodorojnik'=>array(27, '869021d6d0'),
			'voljskie_dali'=>array(28, '6c8ff1d07b'),
			'sosnoviy_bor'=>array(29, '39b0ee4db7'),
			'yantar'=>array(30, '7a2f1dc042'),
			'jemchujina_zauralya'=>array(31, '6d860d3acd')
		);
		$sql = mysql_query("SELECT dir FROM ".TABLE_PAGE." c LEFT JOIN ".TABLE_DIR." d ON (c.dir_id=d.dir_id) 
			WHERE c.page_id=$fsanator") 
			or Error(1, __FILE__, __LINE__);

		$info = @mysql_fetch_array($sql);
		if($dd = @$info['dir'])
		{
			if(isset($s_dirs[$dd])) { $site_id=$s_dirs[$dd][0]; $secret = $s_dirs[$dd][1]; }
		}
	}

	$data="site_id=$site_id&secret=$secret&data=".urlencode($data);
	
	$fp = fsockopen($host, 80, $errno, $errstr, 10);
	
	$out = "POST ".$path." HTTP/1.0\n";
	$out .= "Host: ".$host."\n";
	$out .= "Content-Type: application/x-www-form-urlencoded\n";
	$out .= "Content-Length: ".strlen($data)."\n\n";
	$out .= $data."\n\n"; 
	  
	fputs($fp, $out);	
	fclose($fp); 
}

if(@$s_id)
{
	$data_arr = @Unserialize($_SESSION['order_data']);
	$data_arr['fsanator'] = (int)@$s_id;
	$_SESSION['order_data'] = Serialize($data_arr); 
	$url =  MAIN_URL;
	Header("Location: ".$url."$part/");
	exit;
}

$order_fields = array();

$sql = mysql_query("SELECT * FROM ".TABLE_FIELD." WHERE public='1' ORDER BY bron") or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{
	$order_fields[] = $arr;
}

if(@$mode)
{
	if($mode == 'reset')
	{
		$_SESSION['order_data'] = '';
		$url =  MAIN_URL;
		Header("Location: ".$url."$part/");
		exit;
	}
	
	$arr = array();
	$arr['fsanator'] = (int)@$fsanator;
	
	$err = 0;
	$files = array();
	foreach($order_fields as $v) 
	{
		$id = $v['field_id'];
		
		if($mode == 'shortform')
		{
			$arr[$id] =  from_form(@${"value$id"});
			if($arr[$id] == $v['name']) $arr[$id] = '';
		}
		
		else
		{		
			$arr[$id] = from_form(@${"value_$id"});
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
					if(@mktime(0,0,0, (int)$m, (int)$d, (int)$y) < mktime(0,0,0,date("m"),date("d"),date("Y"))) 
							{$arr["err_$id"] = 1; $err = 1;}
					break;
			}
		}
	}
	
	if($err || $mode == 'shortform')
	{
		$_SESSION['order_data'] = Serialize($arr); 
		$url =  MAIN_URL;
		Header("Location: ".$url."$part/");
		exit;
	}
	
	
	$fsanator = (int)@$fsanator;
	$sql1 = mysql_query("SELECT p.name, ct.name as city FROM ".TABLE_PAGE."  p
		LEFT JOIN  ".TABLE_CITY."  ct ON (ct.city_id=p.city_id)
		WHERE p.page_id=$fsanator") or Error(1, __FILE__, __LINE__);
	$arr1 = @mysql_fetch_array($sql1);
	$sanat_name = $arr1['name']." ($arr1[city])";
	
	$arr_html = array();
	$arr_html[] = array('name'=>'Санаторий', 'value'=>$sanat_name, 'line'=>0);
	$arr_sql = array();
	$arr_sql[] = array('name'=>'Санаторий', 'value'=>$sanat_name, 'line'=>0);
	foreach($order_fields as $v) 
	{
		$id = $v['field_id'];
		
		//if($v['type'] == 3)$value = ${"d_$id"}.' '.$rus_month_1[${"m_$id"}].' '.${"y_$id"};
		//else 
		$value = HtmlSpecialChars($arr[$id]);
		
		if($v['type'] == 2) $value = nl2br($value);
		if($v['type'] == 4 && $value)  $value = "во вложении";
		
		$arr_html[] = array('name'=>HtmlSpecialChars($v['name']), 'value'=>$value, 'line'=>$v['line']);
		
		$value = ($v['type'] == 4 && $value) ? "во вложении" : $arr[$id];
		$arr_sql[] = array('name'=>$v['name'], 'value'=>$value, 'line'=>$v['line']);
	}
	
	$mess = get_template('templ/mail_order.htm', array('list'=>$arr_html)); 
	//send_mail($settings['admin_email'], 'заявка на '.DOMAIN, $mess);
	
	$data = Serialize($arr_sql);
	send_order_rs($data, $fsanator);
	
	$data = escape_string($data); 
	mysql_query("INSERT INTO ".TABLE_ORDER." SET date=NOW(), data='$data', page_id=$fsanator")	
		or Error(1, __FILE__, __LINE__);
		
	$_SESSION['order_data'] = '';
	
	$url =  MAIN_URL;
	Header("Location: ".$url."$part/?sendorder=1");
	exit;
}


$replace = array();
$data_arr = @Unserialize($_SESSION['order_data']);
$replace["err"] = 0;
$fields = array();
foreach($order_fields as $v) 
{
	$id = $v['field_id'];
	$v['value'] = @$data_arr[$id];
	$v['err'] = @$data_arr["err_$id"];
	$v['name'] = HtmlSpecialChars($v['name']);
	if($v["err"]) $replace["err"] = '_err';
	
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
	}
	
	if($id == 1 && !$v['value'] && @$item_id)
	{
		$item_id = (int)$item_id;
		$sql = mysql_query("SELECT name FROM ".TABLE_ITEM." WHERE item_id=$item_id AND public='1'") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$v['value'] = HtmlSpecialChars(@$arr['name']);
	}
	
	$fields[] = $v;
}
$replace["fields"] = $fields;

$replace['sendorder'] = @$sendorder; 
$replace['sanat_list'] = $sanat_list;

//if($replace['err'])  $_SESSION['message'] = "ОШИБКА! Выделенные красным цветом поля не заполнены или заполнены неверно.";

$content = get_template('templ/order.htm', $replace); 

?>