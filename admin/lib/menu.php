<?php

$menu_id = (int)@$menu_id;
$type = (int)@$type;

$type_list = array(
	1=>'Верхнее меню',
	2=>'Центральное меню',
	3=>'Меню в подвале слева',
	4=>'Меню в подвале справа',
);

if(@$addmenu)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_MENU." WHERE type=$type") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_MENU." SET ord='$ord', type=$type") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&menu_id=$id");
	exit;
}

function check_menu($menu_id)
{
	return 0;
}

if(@$del_menu)
{
	$del_menu = (int)$del_menu;
	if(check_menu($del_menu))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&menu_id=$menu_id");
		exit;
	}
	
	$sql = mysql_query("SELECT ord, type FROM ".TABLE_MENU." WHERE menu_id=$del_menu") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$type = (int)@$arr['type']; 
	
	mysql_query("DELETE FROM ".TABLE_MENU." WHERE menu_id='$del_menu'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_MENU." SET ord=ord-1 WHERE ord>$ord AND type=$type") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&menu_id=$menu_id");
	exit;
}

if(@$save)
{
	$public = (int)@$public;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	$url = escape_string(from_form(@$url));
	$ord = (int)@$ord;
	$submenu = (int)@$submenu;
	
	$sql = mysql_query("SELECT ord, type FROM ".TABLE_MENU." WHERE menu_id=$menu_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$type = (int)@$arr['type']; 
	
	mysql_query("UPDATE ".TABLE_MENU." SET public='$public', ord='$ord', name='$name', name_en='$name_en', submenu='$submenu', ".
				" url='$url' WHERE menu_id='$menu_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_MENU." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND type=$type AND menu_id!='$menu_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_MENU." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND type=$type AND menu_id!='$menu_id'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&menu_id=$menu_id");
	exit;
}

$replace = array();

$menutype = array();
foreach($type_list as $t=>$mt)
{
	$sql = mysql_query("SELECT menu_id, name, public  FROM ".TABLE_MENU." WHERE type=$t ORDER BY  ord") 
		or Error(1, __FILE__, __LINE__);
	
	$menus = array(); $menu_name = ""; 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = htmlspecialchars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
				
		$info['edit_link'] = ADMIN_URL."?p=$part&menu_id=$info[menu_id]";
		
		$info['del_link'] = ""; $info['icount'] = 0;
		if($i=check_menu($info['menu_id'])) $info['icount'] = $i;
		else $info['del_link'] = ADMIN_URL."?p=$part&del_menu=$info[menu_id]&menu_id=$menu_id";
		
		if($info['menu_id'] == $menu_id) $menu_name = $info['name'];
			
		$menus[] = $info;
	}
	
	$menutype[] = array('menus'=>$menus, 'name'=>$mt, 'type'=>$t);
}

$replace['menutype'] = $menutype;
$replace['menu_id'] = $menu_id;

$left_menu = get_template('templ/mainmenu_list.htm', $replace);

if($menu_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_MENU." WHERE menu_id='$menu_id'") or Error(1, __FILE__, __LINE__);
	if($menu = @mysql_fetch_array($sql))
	{
		$menu['name'] = htmlspecialchars($menu['name']);
		$menu['name_en'] = htmlspecialchars($menu['name_en']);
		$menu['url'] = htmlspecialchars($menu['url']);
		
		if(!$menu['name'] && !$menu['url']) $menu['public'] = 1;
		$menu['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $menu['public'], 0);		
		
		$menu['submenu_select'] = mysql_select('submenu', "SELECT page_id, name FROM ".TABLE_PAGE.
			" WHERE parent=0 AND page_id!=2 ORDER BY ord", $menu['submenu'], 1);
		
		$menu['ord_select'] = ord_select("SELECT name FROM ".TABLE_MENU.
			" WHERE menu_id!=$menu_id AND type=$menu[type] ORDER BY ord", 'ord', $menu['ord']);
		
		$content = get_template('templ/mainmenu.htm', $menu);
	}
}
	
?>