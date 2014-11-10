<?php

if(@$save)
{
	$str = '';
	foreach($settings_list as $v)
	{
		$str .= "\$settings['$v[field]'] = ";
		$val = @${$v['field']};
		if($v['type'] == 'int') 
		{
			$val = (int)$val;
			if($val < 1)
			{
				$_SESSION['message'] = "Значение поля \'".AddSlashes($v['name'])."\' может быть только целым, больше нуля";
				Header("Location: ".ADMIN_URL."?p=$part");
				exit;
			}
			$str .= $val;
		}
		elseif($v['type'] == 'email') 
		{
			foreach(explode(",", $val) as $vv)
			if(!eregi("^([[:alnum:]]|_|-|\\.)+@([[:alnum:]]|_|-|\\.)+(\\.([[:alnum:]]|-)+)+$",trim($vv))) 
			{
				$_SESSION['message'] = "$vv Значение поля \'".AddSlashes($v['name'])."\' неверно";
				Header("Location: ".ADMIN_URL."?p=$part");
				exit;
			}
			$str .= "\"".AddSlashes($val)."\"";
		}
		elseif($v['type'] == 'text') 
		{
			if(!$val) 
			{
				$_SESSION['message'] = "Значение поля \'".AddSlashes($v['name'])."\' неверно";
				Header("Location: ".ADMIN_URL."?p=$part");
				exit;
			}
			$str .= "\"".AddSlashes($val)."\"";
		}
		$str .= ";\n";
	}
	
	$f = fopen('settings.php', 'w');
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

require 'settings.php';

$list = array();
foreach($settings_list as $v)
{
	//$v['name'] = HtmlSpecialChars($v['name']);
	$v['val'] = HtmlSpecialChars(@$settings[$v['field']]);
	$list[] = $v;
}


$content = get_template('templ/settings.htm', array('list'=>$list));
	
?>