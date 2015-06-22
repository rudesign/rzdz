<?php

$parent_dir_id = 8;

$news_id = (int)@$news_id;
$date = @$date;
$sy = (int)@$sy;
$sm = (int)@$sm;
if($date)
{
	list($date_y, $date_m, $date_d) = @split("-", $date);
	if(checkdate($date_m, $date_d, $date_y)) 
	{
		$date_string = (int)$date_d." ".$rus_month_1[(int)$date_m]." ".$date_y;
		$sm = $date_m; $sy = $date_y; 
	}
	else $date = '';
}

if(!$sy || !$sm || !checkdate($sm, 1, $sy)) { $sy = 0; $sm = 0; }

$date_ref = ($date) ? "&date=$date" : '';
$date_ref .= ($sy) ? "&sy=$sy&sm=$sm" : '';


if(@$addnews)
{
	mysql_query("INSERT INTO ".TABLE_NEWS." SET date=CURDATE()") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&news_id=$id");
	exit;
}

function check_news($news_id)
{
	return 0;
}

if(@$del_news)
{
	$del_news = (int)$del_news;
	if(check_news($del_news))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id");
		exit;
	}
	
	mysql_query("DELETE FROM ".TABLE_NEWS." WHERE news_id='$del_news'") or Error(1, __FILE__, __LINE__);
	
	$sql_photos = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
			" WHERE owner_id='$del_news' AND owner='$photo_owner[news]'") or Error(1, __FILE__, __LINE__);
	while($arr_photos = @mysql_fetch_array($sql_photos)) {
		$photo_id = $arr_photos['photo_id'];
		$ext = $arr_photos['ext'];
		$ext_b = $arr_photos['ext_b'];
		
		@unlink("../images/$photo_dir[news]/$photo_id.$ext_b");
		@unlink("../images/$photo_dir[news]/${photo_id}-s.$ext");

		mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	}
	$sql_photos = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
			" WHERE owner_id='$del_news' AND owner='$photo_owner[block]'") or Error(1, __FILE__, __LINE__);
	while($arr_photos = @mysql_fetch_array($sql_photos)) {
		$photo_id = $arr_photos['photo_id'];
		$ext = $arr_photos['ext'];
		$ext_b = $arr_photos['ext_b'];
		
		@unlink("../images/$photo_dir[block]/$photo_id.$ext_b");
		@unlink("../images/$photo_dir[nblockews]/${photo_id}-s.$ext");

		mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	}
	
	Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id$date_ref");
	exit;
}

if(@$save)
{
	$public = (int)@$public;
	$d = (int)@$d; $m = (int)@$m; $y = (int)@$y; 
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	$descr = @$edit ?  escape_string(from_form(@$descr1)) : escape_string(from_form(@$descr));
	$descr_en = @$edit_en ?  escape_string(from_form(@$descr_en1)) : escape_string(from_form(@$descr_en));
	$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
	$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
	$page_id = (int)@$page_id; 
	
	if(is_array(@$page)) $pages = @join(',', $page);
	else $pages = '';
	
	if($date) $date = "$y-$m-$d";
	if($sy) {$sy = $y; $sm = $m; }
	
	if(!checkdate($m, $d, $y))
	{
		$_SESSION['message'] = "Неверная дата!";
		Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id");
		exit;
	}
	
	mysql_query("UPDATE ".TABLE_NEWS." SET public='$public', date='$y-$m-$d', name='$name', name_en='$name_en', pages='$pages', 
				descr='$descr', page_id='$page_id', description='$description' , description_en='$description_en', descr_en='$descr_en'
				WHERE news_id='$news_id'") or Error(1, __FILE__, __LINE__);
				
	$sql = mysql_query("SELECT d.dir_id, d.dir FROM ".TABLE_NEWS." n
		LEFT JOIN ".TABLE_DIR." d ON d.dir_id=n.dir_id  WHERE news_id=$news_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$dir_id = (int)@$arr[0];
	$olddir = @$arr[1];
	
	$dir = escape_string(from_form(@$dir));
	
	if($dir != $olddir)
	{
		$dir = check_dir($dir, $olddir, $parent_dir_id);
	}
	
	
	$arr = array();
	$list = array('title', 'mdescription', 'keywords', 'title1', 'mdescription1', 'keywords1',
					'title_en', 'mdescription_en', 'keywords_en', 'title1_en', 'mdescription1_en', 'keywords1_en');
	foreach($list as $v)  $arr[] = "$v='".escape_string(from_form(@${$v}))."'";	
	$str = join(",", $arr);
	
	
	if($dir_id)
	mysql_query("UPDATE ".TABLE_DIR." SET dir='$dir', $str  WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);
	else
	{
		mysql_query("INSERT INTO ".TABLE_DIR." SET dir='$dir', parent=$parent_dir_id, $str") or Error(1, __FILE__, __LINE__);
		$dir_id = mysql_insert_id();
		
		mysql_query("UPDATE ".TABLE_NEWS." SET dir_id='$dir_id' WHERE news_id='$news_id'") or Error(1, __FILE__, __LINE__);
	}
	
	Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id&date=$date&sy=$sy&sm=$sm");
	exit;
}

if(@$_FILES['photo']) {

	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	
	
	if(!is_file($photo) || !($filename = @basename($photo_name))) 
	{
		$_SESSION['message'] = "Не найдена фотография!"; 
		Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id$date_ref");
		exit;
	}
	
	$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHOTO."  WHERE owner='$photo_owner[news]' AND owner_id='$news_id'") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1; 
	
	$alt = escape_string(from_form(@$alt));
	
	mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$news_id', owner='$photo_owner[news]', ext='$ext', ord=$ord, alt='$alt'") 
		or Error(1, __FILE__, __LINE__);
	$photo_id = mysql_insert_id();
	
	$small="../images/$photo_dir[news]/${photo_id}-s.$ext";
	if(is_file($small)) unlink($small);
	
	copy($photo, $small);
	
	Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id$date_ref");
	exit;
}

if(@$_FILES['block']) {

	$block = @$_FILES["block"]["tmp_name"];
	$block_name = @$_FILES["block"]["name"];
	
	
	if(!is_file($block) || !($filename = @basename($block_name))) 
	{
		$_SESSION['message'] = "Не найдена фотография!"; 
		Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id$date_ref");
		exit;
	}
	
	$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHOTO."  WHERE owner='$photo_owner[block]' AND owner_id='$news_id'") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1; 
	
	$alt = escape_string(from_form(@$alt));
	
	mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$news_id', owner='$photo_owner[block]', ext='$ext', ord=$ord, alt='$alt'") 
		or Error(1, __FILE__, __LINE__);
	$block_id = mysql_insert_id();
	
	$small="../images/$photo_dir[block]/${block_id}-s.$ext";
	if(is_file($small)) unlink($small);
	
	copy($block, $small);
	
	Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id$date_ref");
	exit;
}

if(@$delphoto) {
	
	$delphoto = (int)$delphoto;
	
	$sql = mysql_query("SELECT ext, ext_b, ord, owner_id, owner FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ext = @$arr['ext'];
	$ext_b = @$arr['ext_b'];
	$ord = (int)@$arr['ord'];
	$owner_id = (int)@$arr['owner_id'];
	$owner = (int)@$arr['owner'];
	if($owner_id != 0) $news_id = $owner_id;
	
	mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	if($owner_id != 0) mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>$ord AND owner='$photo_owner[news]' AND owner_id='$news_id'") 
		or Error(1, __FILE__, __LINE__);
	
	$dir = $owner==$photo_owner['block'] ?  $photo_dir['block'] : $photo_dir['news'];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
		
	Header("Location: ".ADMIN_URL."?p=$part&news_id=$news_id$date_ref"); 
	exit;
}


$replace = array();

require 'settings.php';

$where = ''; $limit = ''; $replace['date_string'] = '';
if($date) 
{ 
	$where = "WHERE date='$date'"; 
	$replace['date_string'] = $date_string; 
}
elseif($sy) 
{ 
	$where = "WHERE YEAR(date)=$sy AND MONTH(date)=$sm"; 
	$replace['date_string'] = $rus_month[$sm]." ".$sy; 
}
else $limit = "LIMIT $settings[news_count]";

$sql = mysql_query("SELECT news_id, name, public FROM ".TABLE_NEWS." $where ORDER BY date desc, news_id $limit") 
	or Error(1, __FILE__, __LINE__);

$newss = array(); $news_name = "";
while($info = @mysql_fetch_array($sql))
{ 
	$info['name'] = HtmlSpecialChars($info['name']);
	if(!$info['name']) $info['name'] = NONAME;
	
	$info['edit_link'] = ADMIN_URL."?p=$part&news_id=$info[news_id]$date_ref";
	
	$info['del_link'] = ""; $info['icount'] = 0;
	if($i=check_news($info['news_id'])) $info['icount'] = $i;
	else $info['del_link'] = ADMIN_URL."?p=$part&del_news=$info[news_id]$date_ref";
	
	$newss[] = $info;
}

$replace['newss'] = $newss;
$replace['news_id'] = $news_id;

$replace['calendar'] = calendar(TABLE_NEWS, $sy, $sm, 'templ/calendar.htm', "?p=$part", 0);

$left_menu = get_template('templ/news_list.htm', $replace);

if($news_id)
{
	$sql = mysql_query("SELECT n.*, 
				d.dir, d.title, d.mdescription, d.keywords, d.title_en, d.mdescription_en, d.keywords_en,  
						d.title1, d.mdescription1, d.keywords1, d.title1_en, d.mdescription1_en, d.keywords1_en
		 FROM ".TABLE_NEWS." n LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=n.dir_id) WHERE n.news_id='$news_id'") or Error(1, __FILE__, __LINE__);
	if($news = @mysql_fetch_array($sql))
	{
		$news['name'] = HtmlSpecialChars($news['name']);
		$news['name_en'] = HtmlSpecialChars($news['name_en']);
		
		$news['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $news['public'], 0);
		$news['date_select'] = date_select($news['date'], 'd', 'm', 'y', 2, 2);
		
		$news['page_select'] = mysql_select('page_id', 
			"SELECT page_id, name FROM ".TABLE_PAGE." WHERE parent=1 ORDER BY ord",
			$news['page_id'], 1);

		$news['photo_limit'] = $photo_limit['news'];		
		$sql_photos = mysql_query("SELECT photo_id, ext, ord, alt FROM ".TABLE_PHOTO.
				" WHERE owner_id='$news_id' AND owner='$photo_owner[news]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
		$photos=array();  
		$count = mysql_num_rows($sql_photos);
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$i++; 
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$w_small=0; $h_small=0; 
			if(is_file($f="../images/$photo_dir[news]/${photo_id}-s.$ext")) {
				$ord_sel = digit_select('ord', 1, $count, $arr_photos['ord']);
				$alt = HtmlSpecialChars($arr_photos['alt']);
				list($w_small, $h_small) = @getimagesize($f);
				$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 
									'smallsize'=>"width='$w_small' height='$h_small'", 
									'ord_sel'=>$ord_sel, 'alt'=>$alt,
									'photo'=>$f, 'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&news_id=$news_id");
			}
		}
		$news['photos'] = $photos;
		
		$news['block_limit'] = $photo_limit['block'];		
		$sql_photos = mysql_query("SELECT photo_id, ext, ord, alt FROM ".TABLE_PHOTO.
				" WHERE owner_id='$news_id' AND owner='$photo_owner[block]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
		$photos=array();  
		$count = mysql_num_rows($sql_photos);
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$i++; 
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$w_small=0; $h_small=0; 
			if(is_file($f="../images/$photo_dir[block]/${photo_id}-s.$ext")) {
				$ord_sel = digit_select('ord', 1, $count, $arr_photos['ord']);
				$alt = HtmlSpecialChars($arr_photos['alt']);
				list($w_small, $h_small) = @getimagesize($f);
				$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 
									'smallsize'=>"width='$w_small' height='$h_small'", 
									'ord_sel'=>$ord_sel, 'alt'=>$alt,
									'photo'=>$f, 'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&news_id=$news_id");
			}
		}
		$news['blocks'] = $photos;
		
		$news['date'] = $date;
		$news['sy'] = $sy;
		$news['sm'] = $sm;
		
		$tinymce_elements = 'description,description_en,descr,descr_en';
		$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
		$news['description'] = HtmlSpecialChars($news['description']);
		$news['description_en'] = HtmlSpecialChars($news['description_en']);
		$news['descr'] = HtmlSpecialChars($news['descr']);
		$news['descr_en'] = HtmlSpecialChars($news['descr_en']);
		
		$sql_f = mysql_query("SELECT page_id, name FROM ".TABLE_PAGE." WHERE parent=0 AND site ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
		$all = (mysql_num_rows($sql_f)%3) ? (int)(mysql_num_rows($sql_f)/3)+1 : mysql_num_rows($sql_f)/3; 
		
		$page_box = array();
		
		$i=1;
		$ch = (ereg("(^|,)0(,|$)", $news['pages'])) ? 'checked' : '';
		$page_box[] = array('i'=>$i, 'page_id'=>0, 'newcol'=>0, 'checked'=>$ch, 'name'=>'основной сайт');							
		$all++;
		
		$i++;	
		$ch = (ereg("(^|,)-1(,|$)", $news['pages'])) ? 'checked' : '';
		$page_box[] = array('i'=>$i, 'page_id'=>-1, 'newcol'=>0, 'checked'=>$ch, 'name'=>'медицина');								
		$all++;
		
		while($info = @mysql_fetch_array($sql_f))
		{ 
			$i++; 
			$newcol = !(($i+$all)%$all) ? 1 : 0; 
			$ch = (ereg("(^|,)$info[page_id](,|$)", $news['pages'])) ? 'checked' : '';
			$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 
									'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
		}
		$news['page_box'] = $page_box;
		
		$list = array('title', 'mdescription', 'keywords', 'title1', 'mdescription1', 'keywords1',
						'title_en', 'mdescription_en', 'keywords_en', 'title1_en', 'mdescription1_en', 'keywords1_en');
		foreach($list as $v) $news[$v] = HtmlSpecialChars($news[$v], ENT_COMPAT, 'cp1251');
		
		$news['dir'] = $news['dir'] ? HtmlSpecialChars($news['dir']) : "n$news[news_id]";
		
		$content = get_template('templ/news.htm', $news);
	}
}
	
?>