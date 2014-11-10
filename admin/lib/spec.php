<?php

$parent_dir_id = 9;

$spec_id = (int)@$spec_id;
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


if(@$addspec)
{
	mysql_query("INSERT INTO ".TABLE_SPEC." SET date=CURDATE()") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&spec_id=$id");
	exit;
}

function check_spec($spec_id)
{
	return 0;
}

if(@$del_spec)
{
	$del_spec = (int)$del_spec;
	if(check_spec($del_spec))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id");
		exit;
	}
	
	mysql_query("DELETE FROM ".TABLE_SPEC." WHERE spec_id='$del_spec'") or Error(1, __FILE__, __LINE__);
	
	$sql_photos = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
			" WHERE owner_id='$del_spec' AND owner='$photo_owner[spec]'") or Error(1, __FILE__, __LINE__);
	while($arr_photos = @mysql_fetch_array($sql_photos)) {
		$photo_id = $arr_photos['photo_id'];
		$ext = $arr_photos['ext'];
		$ext_b = $arr_photos['ext_b'];
		
		@unlink("../images/$photo_dir[spec]/$photo_id.$ext_b");
		@unlink("../images/$photo_dir[spec]/${photo_id}-s.$ext");

		mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	}
	$sql_photos = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
			" WHERE owner_id='$del_spec' AND owner='$photo_owner[specblock]'") or Error(1, __FILE__, __LINE__);
	while($arr_photos = @mysql_fetch_array($sql_photos)) {
		$photo_id = $arr_photos['photo_id'];
		$ext = $arr_photos['ext'];
		$ext_b = $arr_photos['ext_b'];
		
		@unlink("../images/$photo_dir[spec]/$photo_id.$ext_b");
		@unlink("../images/$photo_dir[specblock]/${photo_id}-s.$ext");

		mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	}
	
	Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id$date_ref");
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
	
	if($date) $date = "$y-$m-$d";
	if($sy) {$sy = $y; $sm = $m; }
	
	if(!checkdate($m, $d, $y))
	{
		$_SESSION['message'] = "Неверная дата!";
		Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id");
		exit;
	}
	
	mysql_query("UPDATE ".TABLE_SPEC." SET public='$public', date='$y-$m-$d', name='$name', name_en='$name_en', descr_en='$descr_en',
				descr='$descr', page_id='$page_id', ".
				" description='$description' , description_en='$description_en'
				WHERE spec_id='$spec_id'") or Error(1, __FILE__, __LINE__);
				
	$sql = mysql_query("SELECT d.dir_id, d.dir FROM ".TABLE_SPEC." n
		LEFT JOIN ".TABLE_DIR." d ON d.dir_id=n.dir_id  WHERE spec_id=$spec_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$dir_id = (int)@$arr[0];
	$olddir = @$arr[1];
	
	$title = escape_string(from_form(@$title));
	$mdescription = escape_string(from_form(@$mdescription));
	$keywords = escape_string(from_form(@$keywords));
	$title_en = escape_string(from_form(@$title_en));
	$mdescription_en = escape_string(from_form(@$mdescription_en));
	$keywords_en = escape_string(from_form(@$keywords_en));
	$dir = escape_string(from_form(@$dir));
	
	if($dir != $olddir)
	{
		$dir = check_dir($dir, $olddir, $parent_dir_id);
	}
	
	if($dir_id)
	mysql_query("UPDATE ".TABLE_DIR." SET dir='$dir',".
				"title='$title', mdescription='$mdescription', keywords='$keywords',
				title_en='$title_en', mdescription_en='$mdescription_en', keywords_en='$keywords_en'  ".
				"WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);
	else
	{
		mysql_query("INSERT INTO ".TABLE_DIR." SET dir='$dir', parent=$parent_dir_id, ".
					"title='$title', mdescription='$mdescription', keywords='$keywords',
					title_en='$title_en', mdescription_en='$mdescription_en', keywords_en='$keywords_en'") or Error(1, __FILE__, __LINE__);
		$dir_id = mysql_insert_id();
		
		mysql_query("UPDATE ".TABLE_SPEC." SET dir_id='$dir_id' WHERE spec_id='$spec_id'") or Error(1, __FILE__, __LINE__);
	}
	
	Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id&date=$date&sy=$sy&sm=$sm");
	exit;
}

if(@$_FILES['photo']) {

	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	
	
	if(!is_file($photo) || !($filename = @basename($photo_name))) 
	{
		$_SESSION['message'] = "Не найдена фотография!"; 
		Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id$date_ref");
		exit;
	}
	
	$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHOTO."  WHERE owner='$photo_owner[spec]' AND owner_id='$spec_id'") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1; 
	
	$alt = escape_string(from_form(@$alt));
	
	mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$spec_id', owner='$photo_owner[spec]', ext='$ext', ord=$ord, alt='$alt'") 
		or Error(1, __FILE__, __LINE__);
	$photo_id = mysql_insert_id();
	
	$small="../images/$photo_dir[spec]/${photo_id}-s.$ext";
	if(is_file($small)) unlink($small);
	
	copy($photo, $small);
	
	Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id$date_ref");
	exit;
}

if(@$_FILES['block']) {

	$block = @$_FILES["block"]["tmp_name"];
	$block_name = @$_FILES["block"]["name"];
	
	
	if(!is_file($block) || !($filename = @basename($block_name))) 
	{
		$_SESSION['message'] = "Не найдена фотография!"; 
		Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id$date_ref");
		exit;
	}
	
	$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHOTO."  WHERE owner='$photo_owner[specblock]' AND owner_id='$spec_id'") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1; 
	
	$alt = escape_string(from_form(@$alt));
	
	mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$spec_id', owner='$photo_owner[specblock]', ext='$ext', ord=$ord, alt='$alt'") 
		or Error(1, __FILE__, __LINE__);
	$block_id = mysql_insert_id();
	
	$small="../images/$photo_dir[specblock]/${block_id}-s.$ext";
	if(is_file($small)) unlink($small);
	
	copy($block, $small);
	
	Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id$date_ref");
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
	if($owner_id != 0) $spec_id = $owner_id;
	
	mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	if($owner_id != 0) mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>$ord AND owner='$photo_owner[spec]' AND owner_id='$spec_id'") 
		or Error(1, __FILE__, __LINE__);
	
	$dir = $owner==$photo_owner['specblock'] ?  $photo_dir['specblock'] : $photo_dir['spec'];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
		
	Header("Location: ".ADMIN_URL."?p=$part&spec_id=$spec_id$date_ref"); 
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
else $limit = "LIMIT $settings[spec_count]";

$sql = mysql_query("SELECT spec_id, name, public FROM ".TABLE_SPEC." $where ORDER BY date desc, spec_id $limit") 
	or Error(1, __FILE__, __LINE__);

$specs = array(); $spec_name = "";
while($info = @mysql_fetch_array($sql))
{ 
	$info['name'] = HtmlSpecialChars($info['name']);
	if(!$info['name']) $info['name'] = NONAME;
	
	$info['edit_link'] = ADMIN_URL."?p=$part&spec_id=$info[spec_id]$date_ref";
	
	$info['del_link'] = ""; $info['icount'] = 0;
	if($i=check_spec($info['spec_id'])) $info['icount'] = $i;
	else $info['del_link'] = ADMIN_URL."?p=$part&del_spec=$info[spec_id]$date_ref";
	
	$specs[] = $info;
}

$replace['specs'] = $specs;
$replace['spec_id'] = $spec_id;

$replace['calendar'] = calendar(TABLE_SPEC, $sy, $sm, 'templ/calendar.htm', "?p=$part", 0);

$left_menu = get_template('templ/spec_list.htm', $replace);

if($spec_id)
{
	$sql = mysql_query("SELECT n.*, d.dir, d.title, d.mdescription, d.keywords, d.title_en, d.mdescription_en, d.keywords_en
		 FROM ".TABLE_SPEC." n LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=n.dir_id)  WHERE n.spec_id='$spec_id'") or Error(1, __FILE__, __LINE__);
	if($spec = @mysql_fetch_array($sql))
	{
		$spec['name'] = HtmlSpecialChars($spec['name']);
		$spec['name_en'] = HtmlSpecialChars($spec['name_en']);
		
		$spec['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $spec['public'], 0);
		$spec['date_select'] = date_select($spec['date'], 'd', 'm', 'y', 2, 2);
		
		$spec['page_select'] = mysql_select('page_id', 
			"SELECT page_id, name FROM ".TABLE_PAGE." WHERE parent=1 ORDER BY ord",
			$spec['page_id'], 1);

		$spec['photo_limit'] = $photo_limit['spec'];		
		$sql_photos = mysql_query("SELECT photo_id, ext, ord, alt FROM ".TABLE_PHOTO.
				" WHERE owner_id='$spec_id' AND owner='$photo_owner[spec]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
		$photos=array();  
		$count = mysql_num_rows($sql_photos);
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$i++; 
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$w_small=0; $h_small=0; 
			if(is_file($f="../images/$photo_dir[spec]/${photo_id}-s.$ext")) {
				$ord_sel = digit_select('ord', 1, $count, $arr_photos['ord']);
				$alt = HtmlSpecialChars($arr_photos['alt']);
				list($w_small, $h_small) = @getimagesize($f);
				$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 
									'smallsize'=>"width='$w_small' height='$h_small'", 
									'ord_sel'=>$ord_sel, 'alt'=>$alt,
									'photo'=>$f, 'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&spec_id=$spec_id");
			}
		}
		$spec['photos'] = $photos;
		
		$spec['block_limit'] = $photo_limit['specblock'];		
		$sql_photos = mysql_query("SELECT photo_id, ext, ord, alt FROM ".TABLE_PHOTO.
				" WHERE owner_id='$spec_id' AND owner='$photo_owner[specblock]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
		$photos=array();  
		$count = mysql_num_rows($sql_photos);
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$i++; 
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$w_small=0; $h_small=0; 
			if(is_file($f="../images/$photo_dir[specblock]/${photo_id}-s.$ext")) {
				$ord_sel = digit_select('ord', 1, $count, $arr_photos['ord']);
				$alt = HtmlSpecialChars($arr_photos['alt']);
				list($w_small, $h_small) = @getimagesize($f);
				$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 
									'smallsize'=>"width='$w_small' height='$h_small'", 
									'ord_sel'=>$ord_sel, 'alt'=>$alt,
									'photo'=>$f, 'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&spec_id=$spec_id");
			}
		}
		$spec['blocks'] = $photos;
		
		$spec['date'] = $date;
		$spec['sy'] = $sy;
		$spec['sm'] = $sm;
		
		$tinymce_elements = 'description,description_en,descr,descr_en';
		$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
		$spec['description'] = HtmlSpecialChars($spec['description']);
		$spec['description_en'] = HtmlSpecialChars($spec['description_en']);
		$spec['descr'] = HtmlSpecialChars($spec['descr']);
		$spec['descr_en'] = HtmlSpecialChars($spec['descr_en']);
		
		$spec['title'] = HtmlSpecialChars($spec['title']);
		$spec['mdescription'] = HtmlSpecialChars($spec['mdescription']);
		$spec['keywords'] = HtmlSpecialChars($spec['keywords']);
		$spec['title_en'] = HtmlSpecialChars($spec['title_en']);
		$spec['mdescription_en'] = HtmlSpecialChars($spec['mdescription_en']);
		$spec['keywords_en'] = HtmlSpecialChars($spec['keywords_en']);
		$spec['dir'] = $spec['dir'] ? HtmlSpecialChars($spec['dir']) : "n$spec[spec_id]";
		
		$content = get_template('templ/spec.htm', $spec);
	}
}
	
?>