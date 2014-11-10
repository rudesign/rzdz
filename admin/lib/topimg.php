<?php

$gp_array_int = array('topimg_id');
foreach($gp_array_int as $v) ${$v} = get_post($v, 1);

if(@$_GET['addtopimg'])
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_TOPIMG) or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_TOPIMG." SET ord='$ord'") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	Header("Location: ".ADMIN_URL."?p=$part&topimg_id=$id");
	exit;
}

function check_topimg($topimg_id)
{
	//if($topimg_id == 1) return "-";
	return 0;
}

if(@$_GET['del_topimg'])
{
	$del_topimg = (int)$_GET['del_topimg'];
	if(check_topimg($del_topimg))
	{
		$_SESSION['message'] = "Раздел не может быть удален!";
		Header("Location: ".ADMIN_URL."?p=$part&topimg_id=$topimg_id");
		exit;
	}
	
	mysql_query("DELETE FROM ".TABLE_TOPIMG." WHERE topimg_id='$del_topimg'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_DIR." SET topimg_id=0 WHERE topimg_id='$del_topimg'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_PAGE." SET topimg_id=0 WHERE topimg_id='$del_topimg'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&topimg_id=$topimg_id");
	exit;
}

if(@$_POST['savetopimg'])
{
	$name = get_post('name', 2);
	$name_en = get_post('name_en', 2);
	$public = get_post('public', 1);
	$ord = get_post('ord', 1);
	$firstpage = get_post('firstpage', 1);
	$url = get_post('url', 2);
	
	if(is_array(@$page)) $pages = @join(',', $page);
	else $pages = '';
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_TOPIMG." WHERE topimg_id='$topimg_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	
	mysql_query("UPDATE ".TABLE_TOPIMG." SET name='$name', name_en='$name_en', public='$public', 
				 pages='$pages', 
				firstpage=$firstpage, url='$url', ord=$ord ".
				" WHERE topimg_id='$topimg_id'") or Error(1, __FILE__, __LINE__);
		
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_TOPIMG." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND topimg_id!='$topimg_id'") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_TOPIMG." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND topimg_id!='$topimg_id'") or Error(1, __FILE__, __LINE__);
			
	Header("Location: ".ADMIN_URL."?p=$part&topimg_id=$topimg_id");
	exit;
}

if(@$_FILES['photo']) 
{
	$url = ADMIN_URL."?p=$part&topimg_id=$topimg_id";
	
	upload_small_photo('topimg', $topimg_id, $url);
		
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
	
	$dir = $photo_dir['topimg'];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
	
	Header("Location: ".ADMIN_URL."?p=$part&topimg_id=$topimg_id"); 
	exit;
}

$replace = array();

$sql = mysql_query("SELECT topimg_id, name, public FROM ".TABLE_TOPIMG." ORDER BY ord") 
	or Error(1, __FILE__, __LINE__);

$topimgs = array(); $topimg_name = "";
while($info = @mysql_fetch_array($sql))
{ 
	$info['name'] = HtmlSpecialChars($info['name']);
	if(!$info['name']) $info['name'] = NONAME;
	
	$info['edit_link'] = ADMIN_URL."?p=$part&topimg_id=$info[topimg_id]";
	
	$info['del_link'] = ""; $info['icount'] = 0;
	if($i=check_topimg($info['topimg_id'])) $info['icount'] = $i;
	else $info['del_link'] = ADMIN_URL."?p=$part&del_topimg=$info[topimg_id]&topimg_id=$topimg_id";
	
	if($info['topimg_id'] == $topimg_id) $topimg_name = $info['name'];
		
	$topimgs[] = $info;
}

$replace['topimgs'] = $topimgs;
$replace['topimg_id'] = $topimg_id;

$left_menu = get_template('templ/topimg_list.htm', $replace);

if($topimg_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_TOPIMG." WHERE topimg_id='$topimg_id'") or Error(1, __FILE__, __LINE__);
	if($topimg = @mysql_fetch_array($sql))
	{
		$topimg['name'] = HtmlSpecialChars($topimg['name']);
		$topimg['name_en'] = HtmlSpecialChars($topimg['name_en']);
		$topimg['url'] = HtmlSpecialChars($topimg['url']);
		
		$topimg['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $topimg['public'], 0);	
		$topimg['firstpage_select'] = array_select('firstpage', array(0=>'Нет', 1=>'Да'), $topimg['firstpage'], 0);		
			
		$topimg['ord_select'] = ord_select("SELECT name FROM ".TABLE_TOPIMG.
			" WHERE topimg_id!=$topimg_id ORDER BY ord", 'ord', $topimg['ord']);
		
		$topimg['photo_limit'] = $photo_limit['topimg'];		
		$sql_photos = mysql_query("SELECT photo_id, ext, ext_b FROM ".TABLE_PHOTO.
				" WHERE owner_id='$topimg_id' AND owner='$photo_owner[topimg]' ORDER BY photo_id") or Error(1, __FILE__, __LINE__);
		$photos=array(); 
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$i++; 
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$ext_b = $arr_photos['ext_b'];
			$w_big=0; $h_big=0; $w_small=0; $h_small=0; $bigsize = "";
			if(is_file($f="../images/$photo_dir[topimg]/$photo_id.$ext_b")) 
			{
				list($w_big, $h_big) = @getimagesize($f);
				$bigsize = "width=$w_big, height=$h_big";
			}
			if(is_file($f="../images/$photo_dir[topimg]/${photo_id}-s.$ext")) {
				list($w_small, $h_small) = @getimagesize($f);
			}
				$smallsize = $w_small > 600 ? "width=600" : "width='$w_small' height='$h_small'";
				$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 'bigsize'=>$bigsize, 'ext_b'=>$ext_b,
									'smallsize'=>$smallsize, 'ext'=>$ext,
									'photo'=>$f, 'del_link'=>ADMIN_URL.
									"?p=$part&delphoto=$photo_id&topimg_id=$topimg_id");
		}
		$topimg['photos'] = $photos;
		$topimg['photo_count'] = $i;
		
		$sql_f = mysql_query("SELECT page_id, name FROM ".TABLE_PAGE." WHERE parent=0 AND site ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
		$all = (mysql_num_rows($sql_f)%3) ? (int)(mysql_num_rows($sql_f)/3)+1 : mysql_num_rows($sql_f)/3; 
		
		$page_box = array();
		
		$i=1;
		$ch = (ereg("(^|,)0(,|$)", $topimg['pages'])) ? 'checked' : '';
		$page_box[] = array('i'=>$i, 'page_id'=>0, 'newcol'=>0, 'checked'=>$ch, 'name'=>'основной сайт');								
		$all++;
		
		while($info = @mysql_fetch_array($sql_f))
		{ 
			$i++; 
			$newcol = !(($i+$all)%$all) ? 1 : 0; 
			$ch = (ereg("(^|,)$info[page_id](,|$)", $topimg['pages'])) ? 'checked' : '';
			$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 
									'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
		}
		$topimg['page_box'] = $page_box;
		
		$content = get_template('templ/topimg.htm', $topimg);
	}
}
	
?>