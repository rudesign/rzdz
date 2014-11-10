<?php

$dir_id = (int)@$dir_id;
$parent = (int)@$parent;
$parent_dir = '';

if($dir_id)
{
	$sql = mysql_query("SELECT d.parent, p.dir FROM ".TABLE_DIR." d LEFT JOIN ".TABLE_DIR." p ON (p.dir_id=d.parent) ".
					"WHERE d.dir_id=$dir_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$parent = (int)@$arr['parent']; 
	$parent_dir = @$arr['dir'];
}


function check_dr($dir_id, $parent)
{
	$and = ($dir_id == 2) ? " AND dir_id<30" : '';
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_DIR." WHERE parent=$dir_id $and") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return $count."ð";
	
	return 0;
}

if(@$save)
{
	$title = escape_string(from_form(@$title));
	$mdescription = escape_string(from_form(@$mdescription));
	$keywords = escape_string(from_form(@$keywords));
	$text = escape_string(from_form(@$text));
	$title_en = escape_string(from_form(@$title_en));
	$mdescription_en = escape_string(from_form(@$mdescription_en));
	$keywords_en = escape_string(from_form(@$keywords_en));
	$text_en = escape_string(from_form(@$text_en));
	$help = escape_string(from_form(@$help));
	$page_id = (int)(@$page_id);
	$topimg_id = (int)(@$topimg_id);
		
	mysql_query("UPDATE ".TABLE_DIR." SET page_id='$page_id',  topimg_id='$topimg_id', ".
				"title='$title', mdescription='$mdescription', keywords='$keywords', text='$text',  ".
				"title_en='$title_en', mdescription_en='$mdescription_en', keywords_en='$keywords_en', text_en='$text_en',  ".
				"help='$help' WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);
			
	
	$url = "?p=$part&dir_id=$dir_id";
		
	Header("Location: ".$url);
	exit;
}

$replace = array();

$sql = mysql_query("SELECT d.dir_id, d.dir, d.parent FROM ".TABLE_DIR." d LEFT JOIN ".TABLE_PAGE." p on (p.dir_id=d.dir_id)".
					" WHERE p.page_id IS NULL AND d.parent=0 ORDER BY d.dir_id") or Error(1, __FILE__, __LINE__);

$dirs = array(); $dir_name = "";
while($info = @mysql_fetch_array($sql))
{ 
	if(!$info['dir']) $info['dir'] = 'DEFAULT';
	
	$info['name'] = get_dir($info['dir']);		
	
	$info['icount'] = '-';
	if($i=check_dr($info['dir_id'], $info['parent'])) $info['icount'] = $i;
			
	$info['edit_link'] = ADMIN_URL."?p=$part&dir_id=$info[dir_id]";
	
	$level2 = array();
	if(($info['dir_id'] == $dir_id || $info['dir_id'] == $parent) && $info['dir_id']>10)
	{
		$sql_sect = mysql_query("SELECT d.dir_id, d.dir, d.parent FROM ".TABLE_DIR." d ".
			" WHERE d.parent=$info[dir_id] ORDER BY d.dir_id") or Error(1, __FILE__, __LINE__);
		while($info_sect = @mysql_fetch_array($sql_sect))
		{ 
			$info_sect['name'] = get_dir($info_sect['dir']);		
	
			$info_sect['edit_link'] = ADMIN_URL."?p=$part&dir_id=$info_sect[dir_id]";
			
			$info_sect['icount'] = '-';
			if($i=check_dr($info_sect['dir_id'], $info_sect['parent'])) $info_sect['icount'] = $i;
			
			$level2[] = $info_sect;
		}
	}
	$info['level2'] = $level2;
		
	$dirs[] = $info;
}

$replace['dirs'] = $dirs;
$replace['dir_id'] = $dir_id;
$replace['parent'] = $parent;

$left_menu = get_template('templ/dir_list.htm', $replace);

if($dir_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_DIR." WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);
	if($dir = @mysql_fetch_array($sql))
	{
		$dir['name'] = get_dir($dir['dir']);	
		if($parent_dir) $dir['dir'] = $parent_dir."/".$dir['dir'];
		
		$dir['page_select'] = mysql_select('page_id', 
			"SELECT page_id, name FROM ".TABLE_PAGE." WHERE parent=0 AND page_id!=2 ORDER BY ord",
			$dir['page_id'], 1);

		$dir['title'] = HtmlSpecialChars($dir['title']);
		$dir['mdescription'] = HtmlSpecialChars($dir['mdescription']);
		$dir['keywords'] = HtmlSpecialChars($dir['keywords']);
			
		$dir['title_en'] = HtmlSpecialChars($dir['title_en']);
		$dir['mdescription_en'] = HtmlSpecialChars($dir['mdescription_en']);
		$dir['keywords_en'] = HtmlSpecialChars($dir['keywords_en']);
			
		$dir['topimg_select'] = mysql_select('topimg_id', 
			"SELECT topimg_id, name FROM ".TABLE_TOPIMG." ORDER BY public desc, topimg_id",
			$dir['topimg_id'], 1);

		if($dir_id==7)
		{
			$tinymce_elements = 'text,text_en';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
			$dir['text'] = HtmlSpecialChars($dir['text']);
			$dir['text_en'] = HtmlSpecialChars($dir['text_en']);
		}	
					
		$content = get_template('templ/dir.htm', $dir);
	}
}
	
?>