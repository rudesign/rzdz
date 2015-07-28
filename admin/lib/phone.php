<?php


if(@$del_phone)
{
	$del_phone = (int)@$del_phone;
	
	mysql_query("DELETE FROM ".TABLE_PHONE." WHERE phone_id=$del_phone") 
		or Error(1, __FILE__, __LINE__);
				
	Header("Location: ".ADMIN_URL."?p=$part&page=$current_page");
	exit;
}

$replace = array();

$left_menu = ' ';


$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHONE) or Error(1, __FILE__, __LINE__);
$arr = mysql_fetch_array($sql);
$replace['all'] = $all = $arr[0];	

list($limit, $replace['pages']) = pages($all, ADMIN_URL."?p=$part&");
		
$sql = mysql_query("SELECT phone_id, name, phone FROM ".TABLE_PHONE."  ORDER BY phone_id LIMIT $limit") 
	or Error(1, __FILE__, __LINE__);

$list = array(); $i = ($current_page-1)*$_SESSION['on_page'];
while($info = @mysql_fetch_array($sql))
{ 
	$i++; 
	$info['i'] = $i;
	$info['del_link'] = ADMIN_URL."?p=$part&del_phone=$info[phone_id]&page=$current_page";
	$list[] = $info;
}
$replace['list'] = $list;

$content = get_template('templ/phone.htm', $replace);
	
?>