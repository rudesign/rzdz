<?php

$user_id = (int)@$user_id;

function check_user($user_id)
{
	if($user_id == 1) return 1;
	return 0;
}

if(@$adduser)
{
	mysql_query("INSERT INTO ".TABLE_USER." SET login=''") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&user_id=$id");
	exit;
}

if(@$saveuser)
{
	$login = from_form(@$login);
	$login_sql = escape_string($login);
	$password = escape_string(from_form(@$password));
	if(is_array(@$section)) $s = @join(',', $section);
	else $s = '';
	
	if(is_array(@$extra)) $ex = @join(',', $extra);
	else $ex = '';
	
	if(!$login)
	{
		$_SESSION['message'] = "������� �����!";
		Header("Location: ".ADMIN_URL."?p=$part&user_id=$user_id");
		exit;
	}
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_USER." WHERE login='$login_sql' AND user_id!='$user_id'") or Error(1, __FILE__, __LINE__);
	$arr = mysql_fetch_array($sql);
	if($arr[0])
	{
		$_SESSION['message'] = "����� \'".AddSlashes($login)."\' ��� ������������!";
		Header("Location: ".ADMIN_URL."?p=$part&user_id=$user_id");
		exit;
	}
	if(!$password)
	{
		$_SESSION['message'] = "������� ������!";
		Header("Location: ".ADMIN_URL."?p=$part&user_id=$user_id");
		exit;
	}
	
	mysql_query("UPDATE ".TABLE_USER." SET login='$login_sql', password='$password', sections='$s', extra='$ex'  ".
				" WHERE user_id='$user_id'") or Error(1, __FILE__, __LINE__);
				
	if($_SESSION['admin_id'] == $user_id)
	{
		$_SESSION['admin_name'] = HtmlSpecialChars($login);
		$_SESSION['sections'] = $s;
		$_SESSION['extra'] = $ex;
	}
	
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}

if(@$del_user)
{
	$del_user = (int)$del_user;
	
	if(check_user($del_user))
	{
		$_SESSION['message'] = "������������ �� ����� ���� ������!";
		Header("Location: ".ADMIN_URL."?p=$part");
		exit;
	}
	
	mysql_query("DELETE FROM ".TABLE_USER." WHERE user_id='$del_user'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&page=$current_page");
	exit;
}


$left_menu = " ";
	
if($user_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_USER." WHERE user_id='$user_id'") or Error(1, __FILE__, __LINE__);
	if($info = @mysql_fetch_array($sql))
	{
		$info['login'] = HtmlSpecialChars($info['login']);
		$info['password'] = HtmlSpecialChars($info['password']);
		
		$sect_box = array();
		foreach($section_list as $k=>$v)
		{
			if($v == 'site_extra')
			{
				$list = array();
				$sql_f = mysql_query("SELECT site, name FROM ".TABLE_PAGE." WHERE parent=0 AND site ORDER BY ord") 
					or Error(1, __FILE__, __LINE__);
				$j = 0;
				
				$j++; 
				$ch = (ereg("(^|,)-1(,|$)", $info['extra'])) ? 'checked' : '';
				$list[] = array('j'=>$j, 'site'=>-1, 'checked'=>$ch, 'name'=>'���');
				
				while($site = @mysql_fetch_array($sql_f))
				{ 
					$j++; 
					$ch = (ereg("(^|,)$site[site](,|$)", $info['extra'])) ? 'checked' : '';
					$list[] = array('j'=>$j, 'site'=>$site['site'], 'checked'=>$ch, 'name'=>$site['name']);
				}
				
			}
			else
			{
				$disabled = ($user_id == 1 && $v == 'user') ? 'disabled' : '';
				$ch = (access($v, $info['sections'], 1)) ? 'checked' : '';
				$list = '';
			}
			$sect_box[] = array('i'=>$k, 'sect'=>$v, 'checked'=>$ch, 'disabled'=>$disabled, 'name'=>$section_name[$k], 'list'=>$list);
		}
		$info['sect_box'] = $sect_box;
		
		$info['users_link'] =  ADMIN_URL."?p=$part";

		$content = get_template('templ/user.htm', $info);
	}
	
	return;
}


$replace = array();

$sql = mysql_query("SELECT user_id, login FROM ".TABLE_USER." ORDER BY user_id") or Error(1, __FILE__, __LINE__);

$users = array(); $j = 0;
while($info = @mysql_fetch_array($sql))
{ 
	$j++;
	$info['j'] = $j;
	$info['login'] = HtmlSpecialChars($info['login']);
	
	$info['edit_link'] = ADMIN_URL."?p=$part&user_id=$info[user_id]";
	
	if(!check_user($info['user_id']))
		$info['del_link'] = ADMIN_URL."?p=$part&del_user=$info[user_id]";
	else $info['del_link'] = '';
		
	$users[] = $info;
}

$replace['users'] = $users;

$content = get_template('templ/user_list.htm', $replace);

?>