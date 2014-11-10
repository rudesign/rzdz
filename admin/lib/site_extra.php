<?php

$page_id = (int)@$page_id;
$parent = (int)@$parent;

$parent_dir_id = 0;

$photo_list = array('item', 'video', 'pdf', 'virtual');

if($page_id)
{
	$p = $page_id;
	$i = 0;
	do
	{
		$sql = mysql_query("SELECT parent FROM ".TABLE_PAGE." WHERE page_id=$p") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$p = (int)@$arr[0]; 
		if($p) { $i++; $parents[$p] = 1; if($i==1) $parent = $p; }
	} while($p);
}

if($parent) 
{
	$sql_text = (@$addpage) ? 
		"SELECT dir_id FROM ".TABLE_PAGE." WHERE page_id=$parent" :
		"SELECT d.dir_id FROM ".TABLE_DIR." d LEFT JOIN ".TABLE_PAGE." p ON (p.dir_id=d.dir_id) WHERE p.page_id=$parent";
	$sql = mysql_query($sql_text) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$parent_dir_id = (int)@$arr[0]; //echo $parent; exit;
}

if(@$addpage)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PAGE." WHERE parent=$parent") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	$opinion = $parent==1 ? 1 : 0;
	mysql_query("INSERT INTO ".TABLE_PAGE." SET parent=$parent, ord=$ord, opinion=$opinion") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	
	mysql_query("INSERT INTO ".TABLE_DIR." SET parent=$parent_dir_id, dir='s$id'") or Error(1, __FILE__, __LINE__);
	$dir_id = mysql_insert_id();
	mysql_query("UPDATE ".TABLE_PAGE." SET dir_id=$dir_id WHERE page_id=$id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$id");
	exit;
}

if(@$addnewsite)
{
	$site = (int)@$site;
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PAGE." WHERE parent=0  AND site") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	$sql = mysql_query("SELECT p.name, d.dir FROM ".TABLE_PAGE." p 
		LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)
		WHERE p.page_id=$site ") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$name = escape_string($arr['name']);
	$dir = $arr['dir'];
		
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_DIR." WHERE parent=0 AND dir='$dir'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	if((int)@$arr[0]) $dir = '';
	
	if($site)
	{	
		mysql_query("INSERT INTO ".TABLE_PAGE." SET parent=0, ord=$ord, site=$site, name='$name', description='<p>&nbsp;&nbsp;</p>', public='1'") 
			or Error(1, __FILE__, __LINE__);
		$page_id = mysql_insert_id();		
		if(!$dir) $dir = "s$page_id";
		mysql_query("INSERT INTO ".TABLE_DIR." SET parent=0, dir='$dir'") or Error(1, __FILE__, __LINE__);
		$dir_id = mysql_insert_id();
		mysql_query("UPDATE ".TABLE_PAGE." SET dir_id=$dir_id WHERE page_id=$page_id") or Error(1, __FILE__, __LINE__);
		
		mysql_query("INSERT INTO ".TABLE_PAGE." SET parent=$page_id, ord=1, name='Библиотека', name_en='Media', public='1'") 
			or Error(1, __FILE__, __LINE__);
		$id = mysql_insert_id();
		mysql_query("INSERT INTO ".TABLE_DIR." SET parent=$dir_id, dir='media'") or Error(1, __FILE__, __LINE__);
		$dir_id = mysql_insert_id();
		mysql_query("UPDATE ".TABLE_PAGE." SET dir_id=$dir_id WHERE page_id=$id") or Error(1, __FILE__, __LINE__);
		
		mysql_query("INSERT INTO ".TABLE_PAGE." SET parent=$page_id, ord=2, name='Новости', name_en='News', public='1'") 
			or Error(1, __FILE__, __LINE__);
		$id = mysql_insert_id();
		mysql_query("INSERT INTO ".TABLE_DIR." SET parent=$dir_id, dir='news'") or Error(1, __FILE__, __LINE__);
		$dir_id = mysql_insert_id();
		mysql_query("UPDATE ".TABLE_PAGE." SET dir_id=$dir_id WHERE page_id=$id") or Error(1, __FILE__, __LINE__);
		
		mysql_query("INSERT INTO ".TABLE_PAGE." SET parent=$page_id, ord=3, name='Отзывы', name_en='Opinions', public='1'") 
			or Error(1, __FILE__, __LINE__);
		$id = mysql_insert_id();
		mysql_query("INSERT INTO ".TABLE_DIR." SET parent=$dir_id, dir='opinion'") or Error(1, __FILE__, __LINE__);
		$dir_id = mysql_insert_id();
		mysql_query("UPDATE ".TABLE_PAGE." SET dir_id=$dir_id WHERE page_id=$id") or Error(1, __FILE__, __LINE__);
		
		copy("../templ/footer_8.htm", "../templ/footer_$site.htm");
		copy("../templ/phone_8.htm", "../templ/phone_$site.htm");
		copy("../templ/weather_informer_8.htm", "../templ/weather_informer_$site.htm");
	}
	
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;
}
function check_page($page_id, $parent=0)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PAGE." WHERE parent=$page_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return $count."р";
	
	if($page_id < 4) return "-";
		
	return 0;
}

if(@$del_page)
{
	$del_page = (int)$del_page;
	if(check_page($del_page, $parent))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
		exit;
	}
	
	$sql = mysql_query("SELECT ord, parent, dir_id FROM ".TABLE_PAGE." WHERE page_id=$del_page") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$parent = (int)@$arr['parent']; 
	$dir_id = (int)@$arr['dir_id']; 
	
	mysql_query("DELETE FROM ".TABLE_PAGE." WHERE page_id='$del_page'") or Error(1, __FILE__, __LINE__);
	$w = $parent ? '' : " AND site";
	mysql_query("UPDATE ".TABLE_PAGE." SET ord=ord-1 WHERE ord>$ord AND parent=$parent $w ") or Error(1, __FILE__, __LINE__);
	mysql_query("DELETE FROM ".TABLE_DIR." WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);	
	mysql_query("DELETE FROM ".TABLE_RECOM." WHERE page_id1=$del_page OR page_id2=$del_page") or Error(1, __FILE__, __LINE__);
	
	
	if($page_id == $del_page) $page_id = $parent;
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;
}

if(@$delphoto) {
	
	$delphoto = (int)$delphoto; 
	$media = isset($media) ? $media : (isset($brochure) ? 'brochure' : 'logo');

	$sql = mysql_query("SELECT ext, ext_b, owner, owner_id, ord FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ext = @$arr['ext'];
	$ext_b = @$arr['ext_b'];
	$owner = (int)@$arr['owner'];
	$owner_id = (int)@$arr['owner_id'];
	$ord = (int)@$arr['ord'];
	
	mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>$ord AND owner='$owner' AND owner_id='$owner_id'") 
		or Error(1, __FILE__, __LINE__);
	
	$dir = $photo_dir[$media];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
	@unlink("../images/big/$delphoto.jpg");
	
	$url = ADMIN_URL."?p=$part&page_id=$page_id";
	if($media) $url .= "&$media";
		
	Header("Location: ".$url); 
	exit;
}



if(((@$_FILES['photo'] || @$_FILES['photo_b'])  && @$media) || (@$description && @$media=='pdf' && @$addphoto)) {
	
	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	
	$photo_b = @$_FILES["photo_b"]["tmp_name"];
	$photo_b_name = @$_FILES["photo_b"]["name"];
	
	$url = ADMIN_URL."?p=$part&page_id=$page_id";
	if($media) $url .= "&$media";

	if($media == 'pdf')
	{
		
		if((!is_file(@$photo_b) || !($filename = @basename($photo_b_name))) && !@$description) 
		{
			$_SESSION['message'] = "Не найден файл!"; 
			Header("Location: $url"); exit;
		}
		$ext = $photo_b ? strtolower(escape_string(substr($filename, strrpos($filename, ".")+1))) : '';
	
	}
	else
	{
	
		$photo = @$_FILES["photo"]["tmp_name"];
		$photo_name = @$_FILES["photo"]["name"];
		
		$photo_b = @$_FILES["photo_b"]["tmp_name"];
		$photo_b_name = @$_FILES["photo_b"]["name"];
		
		$_SESSION['im_width'] = $im_width = (int)@$width ? (int)@$width : '';
		$_SESSION['im_height'] = $im_height = (int)@$height ? (int)@$height : '';
		$_SESSION['im_maxsize'] = $im_maxsize = (int)@$maxsize ? (int)@$maxsize : '';
		$_SESSION['photo_load'] = $photo_load = (int)@$small_auto ? 0 : 1;
		$_SESSION['watermark'] = $watermark = (int)@$wtm ? 1 : -1;
		
		if(@$photo_load) 
		{
			if( !is_file($photo) || !($filename = @basename($photo_name)) )
			{
				$_SESSION['message'] = "Не найдена маленькая фотография!"; 
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
	}
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PHOTO."  WHERE owner='$photo_owner[$media]' AND owner_id='$page_id'") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1; 
	
	$alt = escape_string(from_form(@$alt));
	$alt_en = escape_string(from_form(@$alt_en));
	$description = escape_string(from_form(@$description));
	
	mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$page_id', owner='$photo_owner[$media]', ext='$ext', ord=$ord, 
		description='$description', alt='$alt', alt_en='$alt_en', date=CURDATE()") 
		or Error(1, __FILE__, __LINE__);
	$photo_id = mysql_insert_id();
	
	$small= $media=='pdf' ? "../images/$photo_dir[$media]/${photo_id}.$ext" : "../images/$photo_dir[$media]/${photo_id}-s.$ext";
	if(is_file($small)) unlink($small);
	
	if($media=='pdf') {if($photo_b) copy($photo_b, $small);}
	else
	{
		if($im_small) imageJpeg($im_small, $small, 80);
		else copy($photo, $small);
	}
	
	if(@$photo_b && $media != 'pdf')
	{
		if(!is_file($photo_b) || !($filename = @basename($photo_b_name))) 
		{
			$_SESSION['message'] = "Не найдена фотография!"; 
			Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
			exit;
		}
		
		$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
			
		mysql_query("UPDATE ".TABLE_PHOTO." SET ext_b='$ext' WHERE photo_id=$photo_id") 
			or Error(1, __FILE__, __LINE__);
		
		$big="../images/$photo_dir[$media]/${photo_id}.$ext";
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
		
		
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id&$media");
	exit;
}


if(@$savephoto)
{
	$photo_id = (int)@$photo_id;
	$ord = (int)@$ord;
	$public = (int)@$public;
	$alt = escape_string(from_form(@$alt));
	$alt_en = escape_string(from_form(@$alt_en));
	$description = escape_string(from_form(@$description));
	
	$sql = mysql_query("SELECT ord, owner, owner_id FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$owner = @$arr[1];
	$page_id = @$arr[2];
		
	if(@$remove_page_id)
	{
		$remove_page_id = (int)$remove_page_id;
		
		$sql = mysql_query("SELECT count(*) FROM ".TABLE_PHOTO.
			" WHERE owner='$photo_owner[$media]' AND owner_id='$remove_page_id'") 
			or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$count = (int)@$arr[0];
		$ord = $count + 1;
		
		mysql_query("UPDATE ".TABLE_PHOTO." SET alt='$alt', alt_en='$alt_en', ord='$ord', owner_id=$remove_page_id ".
			"WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
					
		mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>$oldord AND ".
			"owner_id=$page_id AND owner='$photo_owner[$media]'") or Error(1, __FILE__, __LINE__);
	}
	else
	{
		$sql = mysql_query("SELECT count(*) FROM ".TABLE_PHOTO." WHERE owner='$photo_owner[$media]' AND owner_id='$page_id'") 
			or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$count = (int)@$arr[0];
		if($ord > $count) $ord = $count;
		if($ord < 1) $ord = 1;
		
		mysql_query("UPDATE ".TABLE_PHOTO." SET alt='$alt', alt_en='$alt_en', ord='$ord', public='$public', description='$description' WHERE photo_id='$photo_id'") 
			or Error(1, __FILE__, __LINE__);

		if($ord > $oldord) mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord-1 WHERE ord>'$oldord' AND ord<='$ord' ".
			"AND owner='$owner' AND owner_id='$page_id' AND photo_id!='$photo_id'") 
				or Error(1, __FILE__, __LINE__);
		elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_PHOTO." SET ord=ord+1 WHERE ord>='$ord' AND ord<'$oldord' ".
			"AND owner='$owner' AND owner_id='$page_id' AND photo_id!='$photo_id'") 
				or Error(1, __FILE__, __LINE__);
	}
	
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id&$media");
	exit;
}

if(@$save)
{
	$public = (int)@$public;
	$contacts = (int)@$contacts;
	$ord = (int)@$ord;
	$name = escape_string(from_form(@$name));
	$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
	$title = escape_string(from_form(@$title));
	$mdescription = escape_string(from_form(@$mdescription));
	$keywords = escape_string(from_form(@$keywords));
	$name_en = escape_string(from_form(@$name_en));
	$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
	$title_en = escape_string(from_form(@$title_en));
	$mdescription_en = escape_string(from_form(@$mdescription_en));
	$keywords_en = escape_string(from_form(@$keywords_en));
	$gallery_id = (int)@$gallery_id;
	$photocount = (int)@$photocount;
	$dir = escape_string(from_form(@$dir));
	$topimg_id = (int)(@$topimg_id);
	$opinion = (int)(@$opinion);
	
	$region_id = (int)(@$region_id);
	$city_id = (int)(@$city_id);
	$stars = (int)(@$stars);
	$price = (int)(@$price);
	$url = escape_string(from_form(@$url));
	$brochure_url = escape_string(from_form(@$brochure_url));
	
	if(is_array(@$cure)) $cures = @join(',', $cure);
	else $cures = '';
	
	$sql = mysql_query("SELECT p.ord, d.dir_id, d.dir, p.site FROM ".TABLE_PAGE." p LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)".
							" WHERE p.page_id=$page_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$dir_id = (int)@$arr[1];
	$olddir = @$arr[2];
	$site = (int)@$arr[3];
	
	if($dir != $olddir)
	{
		$dir = check_dir($dir, $olddir, $parent_dir_id);
	}
	
	mysql_query("UPDATE ".TABLE_PAGE." SET public='$public', contacts='$contacts', name='$name',  name_en='$name_en', ".
				"gallery_id='$gallery_id', photocount='$photocount',  topimg_id='$topimg_id', opinion='$opinion', ".
				"description='$description', description_en='$description_en', ord='$ord',  cures='$cures', ".
				"region_id='$region_id', city_id='$city_id', stars='$stars', price='$price', url='$url', brochure_url='$brochure_url' ".
				"WHERE page_id='$page_id'") or Error(1, __FILE__, __LINE__);
				
	$w = $parent ? '' : " AND site";
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_PAGE." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND parent='$parent' AND page_id!='$page_id' $w") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_PAGE." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND parent='$parent' AND page_id!='$page_id' $w") or Error(1, __FILE__, __LINE__);
	
	mysql_query("UPDATE ".TABLE_DIR." SET dir='$dir', ".
				"title='$title', mdescription='$mdescription', keywords='$keywords',
				title_en='$title_en', mdescription_en='$mdescription_en', keywords_en='$keywords_en'  ".
				"WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);
				
	if($parent==0 && $site)
	{
		$arr = array('phone', 'footer', 'weather_informer');
		foreach($arr as $v)
		{
			$text = from_form(@${$v});
			
			$f = fopen("../templ/${v}_$site.htm", 'w');
			flock($f, LOCK_EX);
			fwrite($f, $text);
			fflush($f);
			flock($f, LOCK_UN);
			fclose($f);
		}
	}
	
	$url = ADMIN_URL."?p=$part&page_id=$page_id";
	
	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	if(@$photo)
	{
		if(!is_file($photo) || !($filename = @basename($photo_name))) 
		{
			$_SESSION['message'] = "Не найдена фотография!"; 
			Header("Location: ".$url);
			exit;
		}
		
		$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
		
		mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$page_id', owner='$photo_owner[logo]', ext='$ext', ord=1") 
			or Error(1, __FILE__, __LINE__);
		$photo_id = mysql_insert_id();
		
		$small="../images/$photo_dir[logo]/${photo_id}-s.$ext";
		if(is_file($small)) unlink($small);
		
		copy($photo, $small);
	}
	$brochure = @$_FILES["brochure"]["tmp_name"];
	$brochure_name = @$_FILES["brochure"]["name"];
	if(@$brochure)
	{
		if(!is_file($brochure) || !($filename = @basename($brochure_name))) 
		{
			$_SESSION['message'] = "Не найдена фотография!"; 
			Header("Location: ".$url);
			exit;
		}
		
		$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
		
		mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$page_id', owner='$photo_owner[brochure]', ext='$ext', ord=1") 
			or Error(1, __FILE__, __LINE__);
		$photo_id = mysql_insert_id();
		
		$small="../images/$photo_dir[brochure]/${photo_id}-s.$ext";
		if(is_file($small)) unlink($small);
		
		copy($brochure, $small);
	}
	
	Header("Location: ".$url);
	exit;
}


if(isset($addcure))
{	
	$addcure = (int)@$addcure;
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$addcure") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_CURE." SET ord=$ord, parent=$addcure") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
		
	Header("Location: ".ADMIN_URL."?p=$part&cures=1");
	exit;
}

if(@$savecure)
{
	$sql = mysql_query("SELECT ord, parent FROM ".TABLE_CURE." WHERE cure_id='$cure_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr['ord'];
	$parent = (int)@$arr['parent'];
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$parent") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	
	$ord = (int)@$ord;
	if($ord < 1 || $ord > $count) 
	{
		$_SESSION['message'] = "Неверное значение порядкового номера (от 1 до $count)";
		Header("Location: ".ADMIN_URL."?p=$part&cures=1");
		exit;
	}
	
	$public = (int)@$public;
	$name = escape_string(from_form(@$name));
	
	mysql_query("UPDATE ".TABLE_CURE." SET public='$public', name='$name', ord='$ord' ".
				"WHERE cure_id='$cure_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND parent=$parent AND  cure_id!='$cure_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND parent=$parent AND cure_id!='$cure_id'") or Error(1, __FILE__, __LINE__);
	
	$url = ADMIN_URL."?p=$part&cures=1";
	
	Header("Location: ".$url);
	exit;
}

if(@$delcure)
{
	$sql = mysql_query("SELECT ord, parent FROM ".TABLE_CURE." WHERE cure_id=$cure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$parent = (int)@$arr['parent']; 
	
	mysql_query("DELETE FROM ".TABLE_CURE." WHERE cure_id='$cure_id'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 WHERE parent=$parent AND ord>$ord") or Error(1, __FILE__, __LINE__);	
		
	Header("Location: ".ADMIN_URL."?p=$part&cures=1");
	exit;
}

if(@$addregion)
{	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_REGION) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_REGION." SET ord=$ord") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
		
	Header("Location: ".ADMIN_URL."?p=$part&regions=1");
	exit;
}

if(@$saveregion)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_REGION) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_REGION." WHERE region_id='$region_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr['ord'];
	
	$ord = (int)@$ord;
	if($ord < 1 || $ord > $count) 
	{
		$_SESSION['message'] = "Неверное значение порядкового номера (от 1 до $count)";
		Header("Location: ".ADMIN_URL."?p=$part&regions=1");
		exit;
	}
	
	$public = (int)@$public;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	
	mysql_query("UPDATE ".TABLE_REGION." SET public='$public', name='$name', name_en='$name_en', ord='$ord' ".
				"WHERE region_id='$region_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_REGION." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND region_id!='$region_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_REGION." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND region_id!='$region_id'") or Error(1, __FILE__, __LINE__);
	
	$url = ADMIN_URL."?p=$part&regions=1";
	
	Header("Location: ".$url);
	exit;
}

if(@$delregion)
{
	$sql = mysql_query("SELECT count(*) FROM ".TABLE_PAGE." WHERE region_id=$region_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	if($arr[0])
	{
		$_SESSION['message'] = "Невозможно удалить регион, используется для $arr[0] объектов";
		Header("Location: ".ADMIN_URL."?p=$part&regions=1");
		exit;
	}
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_REGION." WHERE region_id=$region_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	
	mysql_query("DELETE FROM ".TABLE_REGION." WHERE region_id='$region_id'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_REGION." SET ord=ord-1 WHERE ord>$ord") or Error(1, __FILE__, __LINE__);	
		
	Header("Location: ".ADMIN_URL."?p=$part&regions=1");
	exit;
}


if(@$addcity)
{	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CITY) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_CITY." SET ord=$ord") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
		
	mysql_query("INSERT INTO ".TABLE_DIR." SET parent=$city_parent_dir_id, dir='c$id'") or Error(1, __FILE__, __LINE__);
	$dir_id = mysql_insert_id();
	mysql_query("UPDATE ".TABLE_CITY." SET dir_id=$dir_id WHERE city_id=$id") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&citys=1&city_id=$id");
	exit;
}

if(@$savecity)
{
	$city_id = (int)@$city_id;
	$public = (int)@$public;
	$ord = (int)@$ord;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	$dir = escape_string(from_form(@$dir));
	$description = escape_string(from_form(@$description));
	$title = escape_string(from_form(@$title));
	$mdescription = escape_string(from_form(@$mdescription));
	$keywords = escape_string(from_form(@$keywords));
	$gallery_id = (int)@$gallery_id;
	$photocount = (int)@$photocount;
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CITY) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	
	$sql = mysql_query("SELECT p.ord, d.dir_id, d.dir FROM ".TABLE_CITY." p LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)".
							" WHERE p.city_id=$city_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$dir_id = (int)@$arr[1];
	$olddir = @$arr[2];
	
	if($ord < 1 || $ord > $count) 
	{
		$_SESSION['message'] = "Неверное значение порядкового номера (от 1 до $count)";
		Header("Location: ".ADMIN_URL."?p=$part&citys=1&city_id=$city_id");
		exit;
	}
	
	if($dir != $olddir)
	{
		$dir = check_dir($dir, $olddir, $city_parent_dir_id);
	}
	
	mysql_query("UPDATE ".TABLE_CITY." SET public='$public', name='$name', name_en='$name_en', ord='$ord', description='$description',
				gallery_id='$gallery_id', photocount='$photocount' ".
				"WHERE city_id='$city_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_CITY." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND city_id!='$city_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_CITY." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND city_id!='$city_id'") or Error(1, __FILE__, __LINE__);
	
	mysql_query("UPDATE ".TABLE_DIR." SET dir='$dir', ".
				"title='$title', mdescription='$mdescription', keywords='$keywords'  ".
				"WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);
	
	$url = ADMIN_URL."?p=$part&citys=1&city_id=$city_id";
	
	Header("Location: ".$url);
	exit;
}

if(@$delcity)
{
	$sql = mysql_query("SELECT count(*) FROM ".TABLE_PAGE." WHERE city_id=$city_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	if($arr[0])
	{
		$_SESSION['message'] = "Невозможно удалить город, используется для $arr[0] объектов";
		Header("Location: ".ADMIN_URL."?p=$part&citys=1");
		exit;
	}
	$sql = mysql_query("SELECT ord FROM ".TABLE_CITY." WHERE city_id=$city_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	
	mysql_query("DELETE FROM ".TABLE_CITY." WHERE city_id='$city_id'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_CITY." SET ord=ord-1 WHERE ord>$ord") or Error(1, __FILE__, __LINE__);	
		
	Header("Location: ".ADMIN_URL."?p=$part&citys=1");
	exit;
}

$replace = array();


function get_level($parent=0, $level=1)
{
	global $page_name, $part, $page_id, $parents;
		
	$w = $level==1 ? " AND site" : '';
	$sql = mysql_query("SELECT page_id, name, public FROM ".TABLE_PAGE." WHERE parent=$parent $w ORDER BY ord") 
		or Error(1, __FILE__, __LINE__);
	$pages = array();
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
		
		$info['edit_link'] = ADMIN_URL."?p=$part&page_id=$info[page_id]";
		
		$info['del_link'] = ""; $info['icount'] = 0;
		if($i=check_page($info['page_id'])) $info['icount'] = $i;
		else $info['del_link'] = $level==1 ? '' : ADMIN_URL."?p=$part&del_page=$info[page_id]&page_id=$page_id";
		
		if($info['page_id'] == $page_id) $page_name = $info['name'];
		
		if(($info['page_id'] == $page_id || @$parents[$info['page_id']]) && $level <= 3)
		{
			$info["level".($level+1)] = get_level($info['page_id'], $level+1);
		}
		else $info["level".($level+1)] = array();
				
		$pages[] = $info;
	}
	return $pages;
}

$pages = get_level(0);

$replace['pages'] = $pages;
$replace['page_id'] = $page_id;
$replace['parent'] = $parent;

$left_menu = get_template('templ/page_extra_list.htm', $replace);

if(isset($regions))
{
	$sql = mysql_query("SELECT * FROM ".TABLE_REGION." ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$regions = array(); 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		$info['name_en'] = HtmlSpecialChars($info['name_en']);
		//if(!$info['name']) $info['name'] = NONAME;
		
		$info['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $info['public'], 0);
	
		$regions[] = $info;
	}

	$replace['regions'] = $regions;

	$content = get_template('templ/region_list.htm', $replace);

	return;
}

if(isset($citys))
{
	$sql = mysql_query("SELECT city_id, name, dir_id FROM ".TABLE_CITY." ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$citys = array(); 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		//if(!$info['name']) $info['name'] = NONAME;
		
		$info['edit_link'] = "?p=$part&citys&city_id=$info[city_id]";
		$info['del_link'] = "?p=$part&citys&city_id=$info[city_id]&delcity=1";
	
		$citys[] = $info;
	}

	$replace['citys'] = $citys;
	$replace['city_id'] = $city_id = (int)@$city_id;
	
	if($city_id)
	{	
		$sql = mysql_query("SELECT p.*, d.dir, d.title, d.mdescription, d.keywords  ".
				"FROM ".TABLE_CITY." p LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)".
				" WHERE p.city_id='$city_id'") or Error(1, __FILE__, __LINE__);
		
		if($page = @mysql_fetch_array($sql))
		{ 
			$page['name'] = HtmlSpecialChars($page['name']);
			$page['name_en'] = HtmlSpecialChars($page['name_en']);
			
			$page['title'] = HtmlSpecialChars($page['title']);
			$page['mdescription'] = HtmlSpecialChars($page['mdescription']);
			$page['keywords'] = HtmlSpecialChars($page['keywords']);
			
			$tinymce_elements = 'description';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
			$page['description'] = HtmlSpecialChars($page['description']);			
			
			$page['gallery_select'] = gallery_select($page['gallery_id'], $page['photocount']);
			
			$replace = array_merge($replace, $page);
		}
	}

	$content = get_template('templ/city_list.htm', $replace);

	return;
}

if(isset($cures))
{
	$sql = mysql_query("SELECT * FROM ".TABLE_CURE." WHERE parent=0 ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$cures = array(); 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);		
		$info['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $info['public'], 0);
		
		$sql1 = mysql_query("SELECT * FROM ".TABLE_CURE." WHERE parent=$info[cure_id] ORDER BY ord") or Error(1, __FILE__, __LINE__);	
		$list = array(); 
		while($info1 = @mysql_fetch_array($sql1))
		{ 
			$info1['name'] = HtmlSpecialChars($info1['name']);		
			$info1['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $info1['public'], 0);
			
			$list[] = $info1;
		}
		$info['list'] = $list;
	
		$cures[] = $info;
	}

	$replace['cures'] = $cures;

	$content = get_template('templ/cure_list.htm', $replace);

	return;
}

if(isset($addsite))
{
	$replace = array();
	$page_id = 0;
	$replace['san_select'] = mysql_select('site', 
			"SELECT p.page_id, concat(p.name, ' ', ct.name) as name FROM ".TABLE_PAGE." p 
			LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id WHERE p.parent=1 ORDER BY p.ord",	
			$page_id);
	
	$content = get_template('templ/page_extra_new.htm', $replace);

	return;
}

if($page_id)
{
	$sql = mysql_query("SELECT p.*, d.dir, d.title, d.mdescription, d.keywords, d.title_en, d.mdescription_en, d.keywords_en  ".
			"FROM ".TABLE_PAGE." p LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)".
			" WHERE p.page_id='$page_id'") or Error(1, __FILE__, __LINE__);
	if($page = @mysql_fetch_array($sql))
	{
		$page['name'] = HtmlSpecialChars($page['name']);
		$page['name_en'] = HtmlSpecialChars($page['name_en']);
		
		if(count($parents)==2)
		{
			$media = 0;
			foreach($photo_list as $v)
			{
				$sql_photos = mysql_query("SELECT count(*) FROM ".TABLE_PHOTO.
						" WHERE owner_id=$page[page_id] AND owner='$photo_owner[$v]'") or Error(1, __FILE__, __LINE__);
				$arr_photos = @mysql_fetch_array($sql_photos);
				$page[$v."_count"] = $arr_photos[0];
				
				if(isset(${$v})) {$media = $v; $page[$v] = 1;}
				else $page[$v] = 0;
			}
			$page['photo_list'] = $photo_list;
			$page['photo_name'] = array('Фото', 'Видео',  'Брошюры', 'Виртуальные туры');
			
			if($media)
			{
				$page['media'] = $media;
				$page['photo_limit'] = $photo_limit[$media];		
				$sql_photos = mysql_query("SELECT * FROM ".TABLE_PHOTO.
						" WHERE owner_id='$page_id' AND owner='$photo_owner[$media]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
				$photos=array(); 
				$count = mysql_num_rows($sql_photos);
				$i=0;
				while($arr_photos = @mysql_fetch_array($sql_photos)) {
					$i++; 
					$photo_id = $arr_photos['photo_id'];
					$ext = $arr_photos['ext'];
					$alt = HtmlSpecialChars($arr_photos['alt']);
					$alt_en = HtmlSpecialChars($arr_photos['alt_en']);
					$ord = $arr_photos['ord'];
					//$ord_sel = digit_select('ord', 1, $count, $arr_photos['ord']);
					if($media=='pdf')
					{
						$f= $ext ? "../images/$photo_dir[$media]/${photo_id}.$ext" : '';
						$description = HtmlSpecialChars($arr_photos['description']);
						$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 'description'=>$description, 'f'=>$f,
											'public'=>$arr_photos['public'], 'ord'=>$ord, 'alt'=>$alt,  'alt_en'=>$alt_en, 
											'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&page_id=$page_id&media=$media");
					}
					else
					{
						$ext_b = $arr_photos['ext_b'];
						$w_big=0; $h_big=0; $w_small=0; $h_small=0; $bigsize = ""; $bigphoto = "";
						if(is_file($f="../images/$photo_dir[$media]/$photo_id.$ext_b")) 
						{
							$bigphoto = "/images/$photo_dir[$media]/$photo_id.$ext_b";
							@list($w_big, $h_big) = @getimagesize($f);
							if($w_big && $h_big) $bigsize = "$w_big,$h_big";
						}
						$f="../images/$photo_dir[$media]/${photo_id}-s.$ext";
						$description = HtmlSpecialChars($arr_photos['description']);
						if($media == 'video') $bigphoto = "/video/?photo_id=$photo_id";
						list($w_small, $h_small) = @getimagesize($f);
						
						if($media=='item')
						{
							$superphoto = is_file("../images/big/$photo_id.jpg") ? "/images/big/$photo_id.jpg" : '';
							$del_super = $superphoto ? "?p=$part&delbig=$photo_id&page_id=$page_id&media=$media" : '';
						}
						else
						{
							$superphoto = ''; $del_super = '';
						}
						
						$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 'bigsize'=>$bigsize, 'bigphoto'=>$bigphoto,
											'smallsize'=>"width='$w_small' height='$h_small'", 'public'=>$arr_photos['public'],
											'ord'=>$ord, 'alt'=>$alt,  'alt_en'=>$alt_en, 'description'=>$description,
											'photo'=>$f, 'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&page_id=$page_id&media=$media",
											'superphoto'=>$superphoto, 'del_super'=>$del_super);
					}
					
				}
				$page['photos'] = $photos;
				$page['photo_count'] = $i;

				$page['small_load'] = ($photo_load != '') ? 'checked' : '';
				$page['small_auto'] = ($photo_load == '') ? 'checked' : '';
				$page['disabled'] = $page['small_load'] ? '' : 'disabled';
				//if(!$photo_load && !$im_width && !$im_height) 
					{$im_width = IMG_WIDTH; $im_height = IMG_HEIGHT;}
				$page['im_width'] = $im_width;
				$page['im_height'] = $im_height;
				$page['watermark'] = ($watermark < 0) ? '' : 'checked';
				
				$page['extra'] = '_extra';
				
				$content = get_template('templ/page_media.htm', $page);		
				return;
			}
			
		}
		
		$page['title'] = HtmlSpecialChars($page['title']);
		$page['mdescription'] = HtmlSpecialChars($page['mdescription']);
		$page['keywords'] = HtmlSpecialChars($page['keywords']);
		$page['title_en'] = HtmlSpecialChars($page['title_en']);
		$page['mdescription_en'] = HtmlSpecialChars($page['mdescription_en']);
		$page['keywords_en'] = HtmlSpecialChars($page['keywords_en']);
		
		if(!$page['public'] && !$page['name'] && !$page['description']) $page['public'] = 1;
		$page['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $page['public'], 0);

		$page['contacts'] = array_select('contacts', array(0=>'Нет', 1=>'Да'), $page['contacts'], 0);
				
		$page['gallery_select'] = gallery_select($page['gallery_id'], $page['photocount']);
			
		$w = $parent ? '' : " AND site";
		$page['ord_select'] = ord_select("SELECT name FROM ".TABLE_PAGE.
			" WHERE parent=$parent AND page_id!=$page_id $w ORDER BY ord", 'ord', $page['ord']);
		
		$page['opinion_select'] = array_select('opinion', array(0=>'Нет', 1=>'Да'), $page['opinion'], 0);

		$tinymce_elements = 'description, description_en';
		$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
		$page['description'] = HtmlSpecialChars($page['description']);
		$page['description_en'] = HtmlSpecialChars($page['description_en']);
		
		if($parent==0)
		{
			$page['phone'] = htmlspecialchars(get_template("../templ/phone_$page[site].htm", $page));
			$page['weather_informer'] = htmlspecialchars(get_template("../templ/weather_informer_$page[site].htm", $page));
			$page['footer'] = htmlspecialchars(get_template("../templ/footer_$page[site].htm", $page));
			
			
			$sql_photos = mysql_query("SELECT photo_id, ext, ext_b, ord FROM ".TABLE_PHOTO.
					" WHERE owner_id=$page[page_id] AND owner='$photo_owner[logo]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
			$page['photo'] = '';
			if($arr_photos = @mysql_fetch_array($sql_photos)) {
				$photo_id = $arr_photos['photo_id'];
				$ext = $arr_photos['ext'];
				$w_small=0; $h_small=0;
				$f="../images/$photo_dir[logo]/${photo_id}-s.$ext";
				list($w_small, $h_small) = @getimagesize($f);
				$page['photo'] = $f;
				$page['smallsize'] = "width='$w_small' height='$h_small'";
				$page['photo_del_link'] = "?p=$part&delphoto=$photo_id&page_id=$page_id";
			}	
			
			$sql_brochures = mysql_query("SELECT photo_id, ext, ext_b, ord, alt FROM ".TABLE_PHOTO.
					" WHERE owner_id=$page[page_id] AND owner='$photo_owner[brochure]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
			$page['brochure'] = '';
			if($arr_brochures = @mysql_fetch_array($sql_brochures)) {
				$photo_id = $arr_brochures['photo_id'];
				$ext = $arr_brochures['ext'];
				$w_small=0; $h_small=0;
				$f="../images/$photo_dir[brochure]/${photo_id}-s.$ext";
				list($w_small, $h_small) = @getimagesize($f);
				$page['brochure'] = $f;
				$page['brochure_smallsize'] = "width='$w_small' height='$h_small'";
				$page['brochure_del_link'] = "?p=$part&delphoto=$photo_id&brochure&page_id=$page_id";
			}	
			
		
		}
		
		$page['level'] = count($parents);
		$content = get_template('templ/page_extra.htm', $page);
	}
}
	
?>