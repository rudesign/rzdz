<?php

$banner_id = (int)@$banner_id;
$type_list = array('');

if(@$addbanner)
{
	$type = (int)@$type;
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_BANNER." WHERE type=$type") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_BANNER." SET ord='$ord', type=$type, public=1") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&banner_id=$id");
	exit;
}

function check_banner($banner_id)
{
	return 0;
}

if(@$del_banner)
{
	$del_banner = (int)$del_banner;
	if(check_banner($del_banner))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&banner_id=$banner_id");
		exit;
	}
	
	$sql = mysql_query("SELECT ord, type FROM ".TABLE_BANNER." WHERE banner_id=$del_banner") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$type = (int)@$arr['type']; 
	
	mysql_query("DELETE FROM ".TABLE_BANNER." WHERE banner_id='$del_banner'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_BANNER." SET ord=ord-1 WHERE ord>$ord AND type=$type") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&banner_id=$banner_id");
	exit;
}

if(@$save)
{
	$public = (int)@$public;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
	$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
	$ord = (int)@$ord;
	$defaultban = (int)@$defaultban;
	
	if(is_array(@$page)) $pages = @join(',', $page);
	else $pages = '';
	
	$sql = mysql_query("SELECT ord, type FROM ".TABLE_BANNER." WHERE banner_id=$banner_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$type = (int)@$arr['type']; 
	
	mysql_query("UPDATE ".TABLE_BANNER." SET public='$public', ord='$ord', name='$name', description='$description',
				name_en='$name_en', description_en='$description_en', ".
				" pages='$pages', defaultban='$defaultban' WHERE banner_id='$banner_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_BANNER." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND type=$type AND banner_id!='$banner_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_BANNER." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND type=$type AND banner_id!='$banner_id'") or Error(1, __FILE__, __LINE__);
	
	$url = ADMIN_URL."?p=$part&banner_id=$banner_id";
	
	Header("Location: ".$url);
	exit;
}

$replace = array();

$banner_type = array();
foreach($type_list as $t=>$v)
{
	$sql = mysql_query("SELECT banner_id, name, public FROM ".TABLE_BANNER." WHERE type=$t ORDER BY ord") 
		or Error(1, __FILE__, __LINE__);
	
	$banners = array(); 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
		
		$info['edit_link'] = ADMIN_URL."?p=$part&banner_id=$info[banner_id]";
		
		$info['del_link'] = ""; $info['icount'] = 0;
		if($i=check_banner($info['banner_id'])) $info['icount'] = $i;
		else $info['del_link'] = ADMIN_URL."?p=$part&del_banner=$info[banner_id]&banner_id=$banner_id";
		
		$banners[] = $info;
	}
	$banner_type[] = array('type'=>$t, 'name'=>$v, 'banners'=>$banners);

}

$replace['banner_id'] = $banner_id;
$replace['banner_type'] = $banner_type;

$left_menu = get_template('templ/banner_list.htm', $replace);

if($banner_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_BANNER." WHERE banner_id='$banner_id'") or Error(1, __FILE__, __LINE__);
	if($banner = @mysql_fetch_array($sql))
	{
		$banner['name'] = HtmlSpecialChars($banner['name']);
		$banner['name_en'] = HtmlSpecialChars($banner['name_en']);
		
		$banner['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $banner['public'], 0);		
		
		$banner['ord_select'] = ord_select("SELECT name FROM ".TABLE_BANNER.
			" WHERE banner_id!=$banner_id AND type=$banner[type] ORDER BY ord", 'ord', $banner['ord']);
		
		$banner['defaultban_select'] = array_select('defaultban', array(0=>'Нет', 1=>'Да'), $banner['defaultban'], 0);		
		
		$sql_f = mysql_query("SELECT page_id, name FROM ".TABLE_PAGE." WHERE parent=0 AND page_id!=2 AND page_id!=3 ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
		$i = 0;
		$all = (mysql_num_rows($sql_f)%3) ? (int)(mysql_num_rows($sql_f)/3)+1 : mysql_num_rows($sql_f)/3; 
		$page_box = array();
		while($info = @mysql_fetch_array($sql_f))
		{ 
			$i++; 
			$newcol = !(($i+$all)%$all) ? 1 : 0; 
			$ch = (ereg("(^|,)$info[page_id](,|$)", $banner['pages'])) ? 'checked' : '';
			$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 
									'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
		}
		$banner['page_box'] = $page_box;
		
		$tinymce_elements = 'description,description_en';
		$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
		$banner['description'] = HtmlSpecialChars($banner['description']);
		$banner['description_en'] = HtmlSpecialChars($banner['description_en']);
		
		
		$content = get_template('templ/banner.htm', $banner);
	}
}
	
?>