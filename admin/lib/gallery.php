<?php

$gallery_id = (int)@$gallery_id;
$parent = (int)@$parent;

if($gallery_id)
{
	$sql = mysql_query("SELECT parent FROM ".TABLE_GALLERY." WHERE gallery_id=$gallery_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$parent = (int)@$arr[0]; 
}

if(@$addgallery)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_GALLERY." WHERE parent=$parent") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_GALLERY." SET parent=$parent, ord=$ord") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$id");
	exit;
}

function check_gallery($gallery_id)
{	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_GALLERY." WHERE parent=$gallery_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return $count."р";
	
	//if($gallery_id == 1 || $gallery_id == 2) return '-';
	
	return 0;
}

if(@$del_gallery)
{
	$del_gallery = (int)$del_gallery;
	if(check_gallery($del_gallery))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id");
		exit;
	}
	
	if(!@$delimages)
	{
		$sql_photos = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHOTO.
				" WHERE owner_id='$del_gallery' AND owner='$photo_owner[gallery]'") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql_photos);
		$count = (int)@$arr[0]; 
		if($count)
		{
			$_SESSION['confirm'] = "В галерее есть фотографии ($count шт.).\\nХотите их удалить?";
			$_SESSION['confirm_url'] = ADMIN_URL."?p=$part&del_gallery=$del_gallery&delimages=1";
			$_SESSION['confirm_nourl'] = ADMIN_URL."?p=$part&gallery_id=$gallery_id";
			Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id");
			exit;
		}
	}
			
	$sql = mysql_query("SELECT ord, parent FROM ".TABLE_GALLERY." WHERE gallery_id=$del_gallery") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$parent = (int)@$arr['parent']; 
	
	mysql_query("DELETE FROM ".TABLE_GALLERY." WHERE gallery_id='$del_gallery'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_GALLERY." SET ord=ord-1 WHERE ord>$ord AND parent=$parent") or Error(1, __FILE__, __LINE__);
	
	$sql_photos = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
			" WHERE owner_id='$del_gallery' AND owner='$photo_owner[gallery]'") or Error(1, __FILE__, __LINE__);
	while($arr_photos = @mysql_fetch_array($sql_photos)) {
		$photo_id = $arr_photos['photo_id'];
		$ext = $arr_photos['ext'];
		$ext_b = $arr_photos['ext_b'];
		
		@unlink("../images/$photo_dir[gallery]/$photo_id.$ext_b");
		@unlink("../images/$photo_dir[gallery]/${photo_id}-s.$ext");

		mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	}
	
	if($gallery_id == $del_gallery) $gallery_id = $parent;
	Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id");
	exit;
}

if(@$save)
{
	$public = (int)@$public;
	$ord = (int)@$ord;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	$name_en = escape_string(from_form(@$description));

	$sql = mysql_query("SELECT ord FROM ".TABLE_GALLERY." WHERE gallery_id=$gallery_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	
	mysql_query("UPDATE ".TABLE_GALLERY." SET public='$public', name='$name', name_en='$name_en', ".
				" ord='$ord' WHERE gallery_id='$gallery_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_GALLERY." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND parent='$parent' AND gallery_id!='$gallery_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_GALLERY." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND parent='$parent' AND gallery_id!='$gallery_id'") or Error(1, __FILE__, __LINE__);
	
	//$_SESSION['message'] = "Изменения сохранены!";
	Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id");
	exit;
}

if(@$_FILES['photo'] || @$_FILES['photo_b']) {
	
	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	
	$photo_b = @$_FILES["photo_b"]["tmp_name"];
	$photo_b_name = @$_FILES["photo_b"]["name"];
	
	$_SESSION['im_width'] = $im_width = (int)@$width ? (int)@$width : '';
	$_SESSION['im_height'] = $im_height = (int)@$height ? (int)@$height : '';
	$_SESSION['photo_load'] = $photo_load = (int)@$small_auto ? 0 : 1;
	$_SESSION['watermark'] = $watermark = (int)@$wtm ? 1 : -1;
	
	$url = ADMIN_URL."?p=$part&gallery_id=$gallery_id";
	
	if(@$photo_load) 
	{
		if( !is_file($photo) || !($filename = @basename($photo_name)) )
		{
			$_SESSION['message'] = "Не найдена маленькая фотография!"; 
			Header("Location: $url"); exit;
		}
		
		if(!is_file(@$photo_b) || !($filename = @basename($photo_b_name))) 
		{
			$_SESSION['message'] = "Не найдена большая фотография!"; 
			Header("Location: $url"); exit;
		}
		
		$im_small = ''; 
		list($w, $h) = @getimagesize($photo);
	
	}
	
	else
	{
		if(!is_file(@$photo_b) || !($filename = @basename($photo_b_name))) 
		{
			$_SESSION['message'] = "Не найдена большая фотография!"; 
			Header("Location: $url"); exit;
		}
		
		if(!$im_width && !$im_height) 
		{
			$_SESSION['message'] = "Укажите хотя бы один размер картинки!"; 
			Header("Location: $url"); exit;
		}
		
		list($w, $h, $t) = @getimagesize($photo_b);
		if($t != 2) 
		{
			$_SESSION['message'] = "Картинка должна быть формата JPG!"; 
			Header("Location: $url"); exit;
		}
		
		if(!($im = @imageCreateFromJpeg($photo_b))) 
		{
			$_SESSION['message'] = "Ошибка чтения файла формата JPG!"; 
			Header("Location: $url"); exit;
		}
		
		$x = 0; $y = 0;
		
		if($im_width && !$im_height)
		{
			$w_small = $im_width;
			$koef = $w/$w_small;
			$h_small = (int) ($h/$koef);
		}
		elseif(!$im_width && $im_height)
		{
			$h_small = $im_height;	
			$koef = $h/$h_small; 
			$w_small = (int) ($w/$koef);
		}
		else
		{
			$w_small = $im_width;
			$h_small = $im_height;	
			if($w_small/$h_small > $w/$h) 
			{ 	
				$koef = $w/$w_small; 
				$h_new = $h_small*$koef;
				$y = ($h - $h_new) / 2; $x = 0;
				$h = (int)$h_new; 
			}
			else
			{ 
				$koef = $h/$h_small; 
				$w_new = $w_small*$koef;
				$x = ($w - $w_new) / 2; $y = 0;
				$w = (int)$w_new;
			}
		}		
		$im_small = imagecreatetruecolor($w_small, $h_small);
		imagecopyresampled($im_small, $im, 0,0, $x,$y, $w_small,$h_small, $w,$h);
	}
	
	$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHOTO."  WHERE owner='$photo_owner[gallery]' AND owner_id='$gallery_id'") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1; 
	
	$alt = escape_string(from_form(@$alt));
	$alt_en = escape_string(from_form(@$alt_en));
	$description = escape_string(from_form(@$description));

	mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$gallery_id', owner='$photo_owner[gallery]', ext='$ext', 
		ord=$ord, alt='$alt', alt_en='$alt_en', description='$description'") or Error(1, __FILE__, __LINE__);
	$photo_id = mysql_insert_id();
	
	$small="../images/$photo_dir[gallery]/${photo_id}-s.$ext";
	if(is_file($small)) unlink($small);
	
	if($im_small) imageJpeg($im_small, $small, 80);
	else copy($photo, $small);
	
	if(@$photo_b)
	{
		if(!is_file($photo_b) || !($filename = @basename($photo_b_name))) 
		{
			$_SESSION['message'] = "Не найдена фотография!"; 
			Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id");
			exit;
		}
		
		$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
			
		mysql_query("UPDATE ".TABLE_PHOTO." SET ext_b='$ext' WHERE photo_id=$photo_id") 
			or Error(1, __FILE__, __LINE__);
		
		$big="../images/$photo_dir[gallery]/${photo_id}.$ext";
		if(is_file($big)) unlink($big);
		
		if($watermark > 0 && is_file($wm="../images/watermark.png"))
		{
			if($im = @imagecreatefromjpeg($photo_b))
			{
				if( $im_wm = imagecreatefrompng($wm) )
				{
			        require 'lib/watermark.class.php';
					$wtm = new watermark();
			        $img_new = $wtm->create_watermark($im, $im_wm);
					imageJpeg($img_new, $big, 80);
				}
				else { $_SESSION['message'] = "Ошибка чтения файла формата PNG!"; copy($photo_b, $big); }
			}	
			else { $_SESSION['message'] = "Ошибка чтения файла формата JPG!"; copy($photo_b, $big); }		
		}
		else copy($photo_b, $big);
	}
		
	Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id");
	exit;
}

if(@$delphoto) {
	
	$delphoto = (int)$delphoto;
	
	$sql = mysql_query("SELECT ext, ext_b, ord, owner_id FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ext = @$arr['ext'];
	$ext_b = @$arr['ext_b'];
	$ord = (int)@$arr['ord'];
	$owner_id = (int)@$arr['owner_id'];
	if($owner_id != 0) $gallery_id = $owner_id;
	
	mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	if($owner_id != 0) mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>$ord AND owner='$photo_owner[gallery]' AND owner_id='$gallery_id'") 
		or Error(1, __FILE__, __LINE__);
	
	$dir = $photo_dir['gallery'];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
		
	Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id"); 
	exit;
}

if(@$savephoto)
{
    $photo_id = (int)@$photo_id;
	$ord = (int)@$ord;
	$alt = escape_string(from_form(@$alt));
	$alt_en = escape_string(from_form(@$alt_en));
    $description = escape_string(from_form(@$description));

	$sql = mysql_query("SELECT ord FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
		
	if(@$remove_gallery_id)
	{
		$remove_gallery_id = (int)$remove_gallery_id;

		$sql = mysql_query("SELECT count(*) FROM ".TABLE_PHOTO." WHERE owner='$photo_owner[gallery]' AND owner_id='$remove_gallery_id'") or Error(1, __FILE__, __LINE__);
        $arr = @mysql_fetch_array($sql);
		$count = (int)@$arr[0];
		$ord = $count + 1;

        $query = "UPDATE ".TABLE_PHOTO." SET alt='$alt', alt_en='$alt_en', description='$description', ord='$ord', owner_id=$remove_gallery_id WHERE photo_id='$photo_id'";
		mysql_query($query) or Error(1, __FILE__, __LINE__);
					
		mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>$oldord AND ".
			"owner_id=$gallery_id AND owner='$photo_owner[gallery]'") or Error(1, __FILE__, __LINE__);
	}
	else
	{
		$sql = mysql_query("SELECT count(*) FROM ".TABLE_PHOTO." WHERE owner='$photo_owner[gallery]' AND owner_id='$gallery_id'") 
			or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$count = (int)@$arr[0];
		if($ord > $count) $ord = $count;
		if($ord < 1) $ord = 1;
		
		mysql_query("UPDATE ".TABLE_PHOTO." SET alt='$alt', alt_en='$alt_en', description='$description', ord='$ord' WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
					
		if($ord > $oldord) mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>'$oldord' AND ord<='$ord' ".
			"AND owner='$photo_owner[gallery]' AND owner_id='$gallery_id' AND photo_id!='$photo_id'") 
				or Error(1, __FILE__, __LINE__);
		elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord+1 WHERE ord>='$ord' AND ord<'$oldord' ".
			"AND owner='$photo_owner[gallery]' AND owner_id='$gallery_id' AND photo_id!='$photo_id'") 
				or Error(1, __FILE__, __LINE__);
	}
	
	Header("Location: ".ADMIN_URL."?p=$part&gallery_id=$gallery_id");
	exit;
}

$replace = array();

$sql = mysql_query("SELECT gallery_id, name, public FROM ".TABLE_GALLERY." WHERE parent=0 ORDER BY ord") or Error(1, __FILE__, __LINE__);

$gallerys = array(); $gallery_name = ""; 
while($info = @mysql_fetch_array($sql))
{ 
	$info['name'] = HtmlSpecialChars($info['name']);
	if(!$info['name']) $info['name'] = NONAME;
	
	$info['edit_link'] = ADMIN_URL."?p=$part&gallery_id=$info[gallery_id]";
	
	$info['del_link'] = ""; $info['icount'] = 0;
	if($i=check_gallery($info['gallery_id'])) $info['icount'] = $i;
	else $info['del_link'] = ADMIN_URL."?p=$part&del_gallery=$info[gallery_id]&gallery_id=$gallery_id";
	
	if($info['gallery_id'] == $gallery_id) $gallery_name = $info['name'];
	
	$level2 = array();
	if($info['gallery_id'] == $gallery_id || $info['gallery_id'] == $parent)
	{
		$sql_sect = mysql_query("SELECT gallery_id, name, public FROM ".TABLE_GALLERY." WHERE parent=$info[gallery_id] ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
		while($info_sect = @mysql_fetch_array($sql_sect))
		{ 
			$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
			if(!$info_sect['name']) $info_sect['name'] = NONAME;
			
			$info_sect['edit_link'] = ADMIN_URL."?p=$part&gallery_id=$info_sect[gallery_id]";
			
			$info_sect['del_link'] = ""; $info_sect['icount'] = 0;
			if($i=check_gallery($info_sect['gallery_id'])) $info_sect['icount'] = $i; 
			else $info_sect['del_link'] = ADMIN_URL."?p=$part&del_gallery=$info_sect[gallery_id]&gallery_id=$gallery_id";
			
			if($info_sect['gallery_id'] == $gallery_id) $gallery_name = $info_sect['name'];
			
			$level2[] = $info_sect;
		}
	}
	$info['level2'] = $level2;
	
	$gallerys[] = $info;
}

$replace['gallerys'] = $gallerys;
$replace['gallery_id'] = $gallery_id;
$replace['parent'] = $parent;

$left_menu = get_template('templ/gallery_list.htm', $replace);

if($gallery_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_GALLERY." WHERE gallery_id='$gallery_id'") or Error(1, __FILE__, __LINE__);
	if($gallery = @mysql_fetch_array($sql))
	{
		$gallery['name'] = HtmlSpecialChars($gallery['name']);
		$gallery['name_en'] = HtmlSpecialChars($gallery['name_en']);
		
		$gallery['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $gallery['public'], 0);
		
		$gallery['ord_select'] = ord_select("SELECT name FROM ".TABLE_GALLERY.
			" WHERE parent=$parent AND gallery_id!=$gallery_id ORDER BY ord", 'ord', $gallery['ord']);
		
		$gallery['photo_limit'] = $photo_limit['gallery'];		
		$sql_photos = mysql_query("SELECT photo_id, ext, ext_b, ord, alt, alt_en, description FROM ".TABLE_PHOTO.
				" WHERE owner_id='$gallery_id' AND owner='$photo_owner[gallery]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
		$photos=array(); 
		$count = mysql_num_rows($sql_photos);
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$i++; 
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$ext_b = $arr_photos['ext_b'];
			$w_big=0; $h_big=0; $w_small=0; $h_small=0; $bigsize = ""; $bigphoto = "";
			if(is_file($f="../images/$photo_dir[gallery]/$photo_id.$ext_b")) 
			{
				$bigphoto = "/images/$photo_dir[gallery]/$photo_id.$ext_b";
				@list($w_big, $h_big) = @getimagesize($f);
				if($w_big && $h_big) $bigsize = "$w_big,$h_big";
			}
			$f="../images/$photo_dir[gallery]/${photo_id}-s.$ext";
			//$ord_sel = digit_select('ord', 1, $count, $arr_photos['ord']);
			$ord = $arr_photos['ord'];
			$alt = HtmlSpecialChars($arr_photos['alt']);
			$alt_en = HtmlSpecialChars($arr_photos['alt_en']);
			$description = HtmlSpecialChars($arr_photos['description']);
			list($w_small, $h_small) = @getimagesize($f);
			$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 'bigsize'=>$bigsize, 'bigphoto'=>$bigphoto,
								'smallsize'=>"width='$w_small' height='$h_small'", 
								'ord'=>$ord, 'alt'=>$alt, 'alt_en'=>$alt_en, 'description'=>$description,
								'photo'=>$f, 'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&gallery_id=$gallery_id");
		}
		$gallery['photos'] = $photos;
		$gallery['photo_count'] = $i;
		
		$gallery['small_load'] = ($photo_load != '') ? 'checked' : '';
		$gallery['small_auto'] = ($photo_load == '') ? 'checked' : '';
		$gallery['disabled'] = $gallery['small_load'] ? '' : 'disabled';
		if(!$photo_load && !$im_width && !$im_height) {$im_width = IMG_WIDTH; $im_height = IMG_HEIGHT;}
		$gallery['im_width'] = $im_width;
		$gallery['im_height'] = $im_height;
		$gallery['watermark'] = ($watermark < 0) ? '' : 'checked';
		
		$gallery['parent'] = $parent;
		
		$content = get_template('templ/gallery.htm', $gallery);
	}
}
	
?>