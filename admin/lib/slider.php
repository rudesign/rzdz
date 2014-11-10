<?php

$gp_array_int = array('slider_id');
foreach($gp_array_int as $v) ${$v} = get_post($v, 1);

if(@$_GET['addslider'])
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_SLIDER) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_SLIDER." SET ord='$ord'") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&slider_id=$id");
	exit;
}

function check_slider($slider_id)
{
	//if($slider_id == 1) return "-";
	return 0;
}

if(@$_GET['del_slider'])
{
	$del_slider = (int)$_GET['del_slider'];
	if(check_slider($del_slider))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&slider_id=$slider_id");
		exit;
	}
	
	mysql_query("DELETE FROM ".TABLE_SLIDER." WHERE slider_id='$del_slider'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_DIR." SET slider_id=0 WHERE slider_id='$del_slider'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_PAGE." SET slider_id=0 WHERE slider_id='$del_slider'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&slider_id=$slider_id");
	exit;
}

if(@$_POST['saveslider'])
{
    $name = get_post('name', 2);
    $name_en = get_post('name_en', 2);
    $teaser = get_post('teaser', 2);
    $public = get_post('public', 1);
    $ord = get_post('ord', 1);
    $page_id = get_post('page_id', 1);
    $firstpage = get_post('firstpage', 1);
    $url = get_post('url', 2);
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_SLIDER." WHERE slider_id='$slider_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];

    mysql_query("UPDATE ".TABLE_SLIDER." SET name='$name', name_en='$name_en', teaser='$teaser', public='$public', firstpage=$firstpage, url='$url', ord='$ord', page_id='$page_id' ".
        " WHERE slider_id='$slider_id'") or Error(1, __FILE__, __LINE__);
		
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_SLIDER." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND slider_id!='$slider_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_SLIDER." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND slider_id!='$slider_id'") or Error(1, __FILE__, __LINE__);
			
	Header("Location: ".ADMIN_URL."?p=$part&slider_id=$slider_id");
	exit;
}

if(@$_FILES['photo']) 
{
	$url = ADMIN_URL."?p=$part&slider_id=$slider_id";
	
	$s = isset($preview) ? 'slider_preview' : 'slider';
	
	upload_small_photo($s, $slider_id, $url);
		
	Header("Location: ".$url);
	exit;
}


if(@$_GET['delphoto']) {
	
	$delphoto = (int)$_GET['delphoto'];
	
	$sql = mysql_query("SELECT ext, ext_b FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ext = @$arr['ext'];
	$ext_b = @$arr['ext_b'];
	
	mysql_query("DELETE FROM ".TABLE_PHOTO." WHERE photo_id='$delphoto'") or Error(1, __FILE__, __LINE__);
	
	$dir = isset($preview)  ? $photo_dir['slider_preview'] : $photo_dir['slider'];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
	
	Header("Location: ".ADMIN_URL."?p=$part&slider_id=$slider_id"); 
	exit;
}

$replace = array();

$sql = mysql_query("SELECT slider_id, name, public FROM ".TABLE_SLIDER." ORDER BY ord") 
	or Error(1, __FILE__, __LINE__);

$sliders = array(); $slider_name = "";
while($info = @mysql_fetch_array($sql))
{ 
	$info['name'] = HtmlSpecialChars($info['name']);
	if(!$info['name']) $info['name'] = NONAME;
	
	$info['edit_link'] = ADMIN_URL."?p=$part&slider_id=$info[slider_id]";
	
	$info['del_link'] = ""; $info['icount'] = 0;
	if($i=check_slider($info['slider_id'])) $info['icount'] = $i;
	else $info['del_link'] = ADMIN_URL."?p=$part&del_slider=$info[slider_id]&slider_id=$slider_id";
	
	if($info['slider_id'] == $slider_id) $slider_name = $info['name'];
		
	$sliders[] = $info;
}

$replace['sliders'] = $sliders;
$replace['slider_id'] = $slider_id;

$left_menu = get_template('templ/slider_list.htm', $replace);

if($slider_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_SLIDER." WHERE slider_id='$slider_id'") or Error(1, __FILE__, __LINE__);
	if($slider = @mysql_fetch_array($sql))
	{
		$slider['name'] = HtmlSpecialChars($slider['name']);
		$slider['name_en'] = HtmlSpecialChars($slider['name_en']);
		$slider['url'] = HtmlSpecialChars($slider['url']);
		
		if(!$slider['name']) $slider['public']=1;
		$slider['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $slider['public'], 0);	
		$slider['firstpage_select'] = array_select('firstpage', array(0=>'Нет', 1=>'Да'), $slider['firstpage'], 0);		
			
		$slider['ord_select'] = ord_select("SELECT name FROM ".TABLE_SLIDER.
			" WHERE slider_id!=$slider_id ORDER BY ord", 'ord', $slider['ord']);

        $slider['page_select'] = mysql_select('page_id', "SELECT page_id, name FROM zdor_page WHERE 1 ORDER BY name ASC", $slider['page_id']);
		
		$slider['photo_limit'] = $photo_limit['slider'];		
		$sql_photos = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
				" WHERE owner_id='$slider_id' AND owner='$photo_owner[slider]' ORDER BY photo_id") or Error(1, __FILE__, __LINE__);
		$photos=array(); 
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$i++; 
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$ext_b = $arr_photos['ext_b'];
			$w_big=0; $h_big=0; $w_small=0; $h_small=0; $bigsize = "";
			if(is_file($f="../images/$photo_dir[slider]/$photo_id.$ext_b")) 
			{
				list($w_big, $h_big) = @getimagesize($f);
				$bigsize = "width=$w_big, height=$h_big";
			}
			if(is_file($f="../images/$photo_dir[slider]/${photo_id}-s.$ext")) {
				list($w_small, $h_small) = @getimagesize($f);
			}
				$smallsize = $w_small > 600 ? "width=600" : "width='$w_small' height='$h_small'";
				$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 'bigsize'=>$bigsize, 'ext_b'=>$ext_b,
									'smallsize'=>$smallsize, 'ext'=>$ext,
									'photo'=>$f, 'del_link'=>ADMIN_URL.
									"?p=$part&delphoto=$photo_id&slider_id=$slider_id");
		}
		$slider['photos'] = $photos;
		$slider['photo_count'] = $i;
		
		
		$slider['preview_limit'] = $photo_limit['slider_preview'];		
		$sql_previews = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
				" WHERE owner_id='$slider_id' AND owner='$photo_owner[slider_preview]' ORDER BY photo_id") or Error(1, __FILE__, __LINE__);
		$previews=array(); 
		$i=0;
		while($arr_previews = @mysql_fetch_array($sql_previews)) {
			$i++; 
			$photo_id = $arr_previews['photo_id'];
			$ext = $arr_previews['ext'];
			$ext_b = $arr_previews['ext_b'];
			$w_big=0; $h_big=0; $w_small=0; $h_small=0; $bigsize = "";
			if(is_file($f="../images/$photo_dir[slider_preview]/$photo_id.$ext_b")) 
			{
				list($w_big, $h_big) = @getimagesize($f);
				$bigsize = "width=$w_big, height=$h_big";
			}
			if(is_file($f="../images/$photo_dir[slider_preview]/${photo_id}-s.$ext")) {
				list($w_small, $h_small) = @getimagesize($f);
			}
				$smallsize = $w_small > 600 ? "width=600" : "width='$w_small' height='$h_small'";
				$previews[] = array('number'=>$i, 'photo_id'=>$photo_id, 'bigsize'=>$bigsize, 'ext_b'=>$ext_b,
									'smallsize'=>$smallsize, 'ext'=>$ext,
									'photo'=>$f, 'del_link'=>ADMIN_URL.
									"?p=$part&delphoto=$photo_id&slider_id=$slider_id&preview");
		}
		$slider['previews'] = $previews;
		$slider['preview_count'] = $i;
		$content = get_template('templ/slider.htm', $slider);
	}
}
	
?>