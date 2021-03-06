<?php

@include 'Log.php';

$log = class_exists('Log', false) ? new Log($_SESSION['admin_name']) : null;

$page_id = (int)@$page_id;
$parent = (int)@$parent;

if(!ereg("(^|,)site(,|$)", $_SESSION['sections']))
{
	if($page_id)
	{
		if(!ereg("(^|,)$page_id(,|$)", $_SESSION['extra']) && !ereg("(^|,)-1(,|$)", $_SESSION['extra']))
			{echo "��� �������"; return;}
	}
	else
	{
		$arr = explode(",", $_SESSION['extra']);
		$page_id = (int)$arr[0];
		if($page_id==-1) 
		{
			$sql = mysql_query("SELECT site FROM ".TABLE_PAGE." WHERE parent=0 AND site AND ord=1") 
				or Error(1, __FILE__, __LINE__);
			$arr = @mysql_fetch_array($sql);
			$page_id = $arr['site'];
		}
		if(!$page_id) {echo "��� �������"; return;}
		Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
		exit;
	}
}

if(isset($confirmphoto))
{
	$photo_id = (int)@$confirmphoto;
	
	$sql = mysql_query("SELECT owner, owner_id FROM ".TABLE_PHOTO.
			" WHERE photo_id=$photo_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$page_id = $arr['owner_id'];
	
	if($arr['owner'] == $photo_owner['other'])
	{
		mysql_query("UPDATE ".TABLE_PHOTO.
			" SET public=1 WHERE photo_id=$photo_id") or Error(1, __FILE__, __LINE__);

		$_SESSION['message'] = "���� ������������.";
		Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id&other");
		exit;
	}
	else
	{
		$_SESSION['message'] = "������ ������, ���� �� �������";
		Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id&other");
		exit;	
	}
}

$parent_dir_id = 0;

$photo_list = array('item', 'video', 'pdf', 'virtual', 'cure', 'license');

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
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PAGE." WHERE parent=$parent  AND !site") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	$opinion = $parent==1 ? 1 : 0;
	mysql_query("INSERT INTO ".TABLE_PAGE." SET parent=$parent, ord=$ord, opinion=$opinion") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();

	mysql_query("INSERT INTO ".TABLE_DIR." SET parent=$parent_dir_id, dir='s$id'") or Error(1, __FILE__, __LINE__);
	$dir_id = mysql_insert_id();
	mysql_query("UPDATE ".TABLE_PAGE." SET dir_id=$dir_id WHERE page_id=$id") or Error(1, __FILE__, __LINE__);

    $objectName = ($_POST['name'] ? $_POST['name'] : '');
    $objectName .= ' ('.$id.'@'.TABLE_PAGE.')';
    if(is_object($log)) $log->store($log->getActionName('add').' �����', $objectName);

	Header("Location: ".ADMIN_URL."?p=$part&page_id=$id");
	exit;
}

function check_page($page_id, $parent=0)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_PAGE." WHERE parent=$page_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return $count."�";
	
	if($page_id < 4 || $page_id==104) return "-";
		
	return 0;
}

if(@$del_page)
{
	$del_page = (int)$del_page;
	if(check_page($del_page, $parent))
	{
		$_SESSION['message'] = "������ �� ����� ���� ������!";
		Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
		exit;
	}
	
	$sql = mysql_query("SELECT ord, parent, dir_id, name FROM ".TABLE_PAGE." WHERE page_id=$del_page")
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$parent = (int)@$arr['parent']; 
	$dir_id = (int)@$arr['dir_id']; 
	
	mysql_query("DELETE FROM ".TABLE_PAGE." WHERE page_id='$del_page'") or Error(1, __FILE__, __LINE__);

	mysql_query("UPDATE ".TABLE_PAGE." SET ord=ord-1 WHERE ord>$ord AND parent=$parent AND !site") or Error(1, __FILE__, __LINE__);
	mysql_query("DELETE FROM ".TABLE_DIR." WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);	
	mysql_query("DELETE FROM ".TABLE_RECOM." WHERE page_id1=$del_page OR page_id2=$del_page") or Error(1, __FILE__, __LINE__);

    $objectName = ($arr['name'] ? $arr['name'] : '');
    $objectName .= ' ('.$del_page.'@'.TABLE_PAGE.')';
    if(is_object($log)) $log->store($log->getActionName('del').' �����', $objectName);

	if($page_id == $del_page) $page_id = $parent;
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;
}

if(@$addrecom)
{
	if($cpart_recom = (int)@$cpart_recom)
	{
		$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_RECOM." WHERE page_id2='$cpart_recom' AND page_id1='$page_id'") 
			or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		if((int)@$arr[0])
		{
			$_SESSION['message'] = "����� ��������� ��� ���������";
			Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
			exit;	
		}
		
		$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_RECOM." WHERE page_id1='$page_id'") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$ord = (int)@$arr[0] + 1;
		
		mysql_query("INSERT INTO ".TABLE_RECOM." SET page_id1='$page_id', page_id2='$cpart_recom', ord=$ord") 
			or Error(1, __FILE__, __LINE__);

        if(is_object($log)) $log->store($log->getActionName('add').' ������������ ', $page_id.' <-> '.$cpart_recom.'@'.TABLE_RECOM);
	}
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;
}


if(@$delrecom)
{
	$delrecom = (int)@$delrecom;
	$sql = mysql_query("SELECT ord FROM ".TABLE_RECOM." WHERE page_id1=$page_id AND page_id2=$delrecom") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	
	mysql_query("DELETE FROM ".TABLE_RECOM." WHERE page_id1=$page_id AND page_id2=$delrecom") or Error(1, __FILE__, __LINE__);

	mysql_query("UPDATE ".TABLE_RECOM." SET ord=ord-1 WHERE ord>$oldord AND page_id1=$page_id") 
		or Error(1, __FILE__, __LINE__);

    if(is_object($log)) $log->store($log->getActionName('del').' ������������ ', $page_id.' <-> '.$delrecom.'@'.TABLE_RECOM);

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


    if(is_object($log)) $log->store($log->getActionName('del').' '.getMediaType($media), $delphoto.'@'.TABLE_PHOTO);

    $url = ADMIN_URL."?p=$part&page_id=$page_id";
    if($media) $url .= "&$media";

	Header("Location: ".$url); 
	exit;
}

if(@$delbig) {
	
	$delphoto = (int)$delbig; 

	if(@unlink("../images/big/$delphoto.jpg")){
        if(is_object($log)) $log->store($log->getActionName('del').' �����������', $delphoto.'@'.TABLE_PHOTO);
    }

	$url = ADMIN_URL."?p=$part&page_id=$page_id";
	if($media) $url .= "&$media";
		
	Header("Location: ".$url); 
	exit;
}


if(@$_FILES['photo_super'])
{
	$photo = @$_FILES["photo_super"]["tmp_name"];
	
	$fname = "../images/big/$photo_id.jpg";
	if(copy($photo, $fname)){
        if(is_object($log)) $log->store($log->getActionName('del').' �����������', $photo_id.'@'.TABLE_PHOTO);
    }
	
	$url = ADMIN_URL."?p=$part&page_id=$page_id&$media";
		
	Header("Location: ".$url); 
	exit;
}

if(((@$_FILES['photo'] || @$_FILES['photo_b'] || @$_FILES['pdf'])  && @$media) || 
	(@$description && (@$media=='pdf' || @$media=='video') && @$addphoto)) {
	
	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	
	$photo_b = @$_FILES["photo_b"]["tmp_name"];
	$photo_b_name = @$_FILES["photo_b"]["name"];
	
	$url = ADMIN_URL."?p=$part&page_id=$page_id";
	if($media) $url .= "&$media";

	if($media == 'pdf')
	{
		/*
		if((!is_file(@$photo_b) || !($filename = @basename($photo_b_name))) && !@$description) 
		{
			$_SESSION['message'] = "�� ������ ����!"; 
			Header("Location: $url"); exit;
		}
		*/
        if(is_file(@$photo_b) && ($filename = @basename($photo_b_name)))
        {
            $ext = $photo_b ? strtolower(escape_string(substr($filename, strrpos($filename, ".")+1))) : '';
        }

	
	}
	elseif($media == 'video' && (!is_file(@$photo_b) || !($filename = @basename($photo_b_name))))
	{
		$ext = '';
		$photo_b = '';
	}
	else
	{		
		$_SESSION['im_width'] = $im_width = (int)@$width ? (int)@$width : '';
		$_SESSION['im_height'] = $im_height = (int)@$height ? (int)@$height : '';
		$_SESSION['im_maxsize'] = $im_maxsize = (int)@$maxsize ? (int)@$maxsize : '';
		$_SESSION['photo_load'] = $photo_load = (int)@$small_auto ? 0 : 1;
		$_SESSION['watermark'] = $watermark = (int)@$wtm ? 1 : -1;
		
		if(@$photo_load) 
		{
			if( !is_file($photo) || !($filename = @basename($photo_name)) )
			{
				$_SESSION['message'] = "�� ������� ��������� ����������!"; 
				Header("Location: $url"); exit;
			}
			
			$im_small = ''; 
			list($w, $h) = @getimagesize($photo);
		
		}
		
		else
		{
			if(!is_file(@$photo_b) || !($filename = @basename($photo_b_name))) 
			{
				$_SESSION['message'] = "�� ������� ������� ����������!"; 
				Header("Location: $url"); exit;
			}
			
			if(!$im_width && !$im_height) 
			{
				$_SESSION['message'] = "������� ���� �� ���� ������ ��������!"; 
				Header("Location: $url"); exit;
			}
			
			list($w, $h, $t) = @getimagesize($photo_b);
			if($t != 2) 
			{
				$_SESSION['message'] = "�������� ������ ���� ������� JPG!"; 
				Header("Location: $url"); exit;
			}
			
			if(!($im = @imageCreateFromJpeg($photo_b))) 
			{
				$_SESSION['message'] = "������ ������ ����� ������� JPG!"; 
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
	elseif($media=='video' && !$photo_b) {}
	else
	{
		if($im_small) imageJpeg($im_small, $small, 100);
		else copy($photo, $small);
	}
	
	if(@$photo_b && $media != 'pdf' && $media != 'license')
	{
		if(!is_file($photo_b) || !($filename = @basename($photo_b_name))) 
		{
			$_SESSION['message'] = "�� ������� ����������!"; 
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
					imageJpeg($img_new, $big, 100);
				}
				else { $_SESSION['message'] = "������ ������ ����� ������� PNG!"; copy($photo_b, $big); }
			}	
			else { $_SESSION['message'] = "������ ������ ����� ������� JPG!"; copy($photo_b, $big); }		
		}
		else copy($photo_b, $big);
	}
	if($media == 'license')
	{
		$pdf = @$_FILES["pdf"]["tmp_name"];
		$pdf_name = @$_FILES["pdf"]["name"];
		
        if(is_file($pdf) && ($filename = @basename($pdf_name)))
        {
            $ext_b = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
			
			mysql_query("UPDATE ".TABLE_PHOTO." SET ext_b='$ext_b' WHERE photo_id=$photo_id") 
				or Error(1, __FILE__, __LINE__);
			
			$big="../images/$photo_dir[$media]/${photo_id}.$ext_b";
			if(is_file($big)) unlink($big);
		
			copy($pdf, $big);
        }
	}

    $object = $objectName = '';
    switch($media){
        default:
            $object = '�����������';
            break;
        case 'pdf':
            $object = '�������';
            break;
        case 'video':
            $object = '�����';
            break;
        case 'virtual':
            $object = '����������� ���';
            break;
    }

    $objectName .= ' ��� ������ '.$page_id.'@'.TABLE_PAGE;

    if(is_object($log)) $log->store($log->getActionName('add').' '.$object, $photo_id.'@'.TABLE_PHOTO);
		
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

    if(is_object($log)) $log->store($log->getActionName('edit').' '.getMediaType($media), $photo_id.'@'.TABLE_PHOTO);
	
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id&$media");
	exit;
}

// @TODO ����� ��� ���������� ���������� �� ������?
if(@$save)
{
	$public = (int)@$public;
	$nositemap = (int)@$nositemap;
	$contacts = (int)@$contacts;
	$ord = (int)@$ord;
	$name = escape_string(from_form(@$name));
	$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
	$name_en = escape_string(from_form(@$name_en));
	$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
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
	
	$sql = mysql_query("SELECT p.ord, d.dir_id, d.dir FROM ".TABLE_PAGE." p LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)".
							" WHERE p.page_id=$page_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr[0];
	$dir_id = (int)@$arr[1];
	$olddir = @$arr[2];
	
	if($dir != $olddir)
	{
		$dir = check_dir($dir, $olddir, $parent_dir_id);
	}
	
	mysql_query("UPDATE ".TABLE_PAGE." SET public='$public', nositemap='$nositemap', contacts='$contacts', name='$name',  name_en='$name_en', ".
				"gallery_id='$gallery_id', photocount='$photocount',  topimg_id='$topimg_id', opinion='$opinion', ".
				"description='$description', description_en='$description_en', ord='$ord',  ".
				"region_id='$region_id', city_id='$city_id', stars='$stars', price='$price', url='$url', brochure_url='$brochure_url' ".
				"WHERE page_id='$page_id'") or Error(1, __FILE__, __LINE__);

    $objectName = $name.' ('.$page_id.'@'.TABLE_PAGE.')';
    if(is_object($log)) $log->store($log->getActionName('edit').' �����', $objectName);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_PAGE." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND parent='$parent' AND page_id!='$page_id' AND !site") or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_PAGE." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND parent='$parent' AND page_id!='$page_id' AND !site") or Error(1, __FILE__, __LINE__);
	
	$arr = array();
	$list = array('title', 'mdescription', 'keywords', 'title1', 'mdescription1', 'keywords1',
					'title_en', 'mdescription_en', 'keywords_en', 'title1_en', 'mdescription1_en', 'keywords1_en');
	foreach($list as $v) 
	{
		$arr[] = "$v='".escape_string(from_form(@${$v}))."'";
	}
	$str = join(",", $arr);
	
	mysql_query("UPDATE ".TABLE_DIR." SET dir='$dir',  $str WHERE dir_id='$dir_id'") or Error(1, __FILE__, __LINE__);
	
	$url = ADMIN_URL."?p=$part&page_id=$page_id";
	
	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	if(@$photo)
	{
		if(!is_file($photo) || !($filename = @basename($photo_name))) 
		{
			$_SESSION['message'] = "�� ������� ����������!"; 
			Header("Location: ".$url);
			exit;
		}
		
		$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
		
		mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$page_id', owner='$photo_owner[logo]', ext='$ext', ord=1") 
			or Error(1, __FILE__, __LINE__);
		$photo_id = mysql_insert_id();

        if(is_object($log)) $log->store($log->getActionName('add').' �����������', $photo_id.'@'.TABLE_PHOTO);
		
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
			$_SESSION['message'] = "�� ������� ����������!"; 
			Header("Location: ".$url);
			exit;
		}
		
		$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
		
		mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$page_id', owner='$photo_owner[brochure]', ext='$ext', ord=1") 
			or Error(1, __FILE__, __LINE__);
		$photo_id = mysql_insert_id();

        if(is_object($log)) $log->store($log->getActionName('add').' �������', $photo_id.'@'.TABLE_PHOTO);
		
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

    if(is_object($log)) $log->store($log->getActionName('add').' ������� �������', $id.'@'.TABLE_CURE);
		
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
		$_SESSION['message'] = "�������� �������� ����������� ������ (�� 1 �� $count)";
		Header("Location: ".ADMIN_URL."?p=$part&cures=1");
		exit;
	}
	
	$public = (int)@$public;
	$name = escape_string(from_form(@$name));
	
	mysql_query("UPDATE ".TABLE_CURE." SET public='$public', name='$name', ord='$ord' ".
				"WHERE cure_id='$cure_id'") or Error(1, __FILE__, __LINE__);

    if(is_object($log)) $log->store($log->getActionName('edit').' ������� �������', $cure_id.'@'.TABLE_CURE);
				
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

    if(is_object($log)) $log->store($log->getActionName('del').' ������� �������', $cure_id.'@'.TABLE_CURE);

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

    if(is_object($log)) $log->store($log->getActionName('add').' ������', $id.'@'.TABLE_REGION);
		
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
		$_SESSION['message'] = "�������� �������� ����������� ������ (�� 1 �� $count)";
		Header("Location: ".ADMIN_URL."?p=$part&regions=1");
		exit;
	}
	
	$public = (int)@$public;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	
	mysql_query("UPDATE ".TABLE_REGION." SET public='$public', name='$name', name_en='$name_en', ord='$ord' ".
				"WHERE region_id='$region_id'") or Error(1, __FILE__, __LINE__);

    if(is_object($log)) $log->store($log->getActionName('edit').' ������', $id.'@'.TABLE_REGION);
				
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
		$_SESSION['message'] = "���������� ������� ������, ������������ ��� $arr[0] ��������";
		Header("Location: ".ADMIN_URL."?p=$part&regions=1");
		exit;
	}
	
	$sql = mysql_query("SELECT ord FROM ".TABLE_REGION." WHERE region_id=$region_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	
	mysql_query("DELETE FROM ".TABLE_REGION." WHERE region_id='$region_id'") or Error(1, __FILE__, __LINE__);

    if(is_object($log)) $log->store($log->getActionName('del').' ������', $id.'@'.TABLE_REGION);

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

    if(is_object($log)) $log->store($log->getActionName('add').' ��������� �����', $id.'@'.TABLE_CITY);
		
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
		$_SESSION['message'] = "�������� �������� ����������� ������ (�� 1 �� $count)";
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

    if(is_object($log)) $log->store($log->getActionName('edit').' ��������� �����', $city_id.'@'.TABLE_CITY);
				
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
		$_SESSION['message'] = "���������� ������� �����, ������������ ��� $arr[0] ��������";
		Header("Location: ".ADMIN_URL."?p=$part&citys=1");
		exit;
	}
	$sql = mysql_query("SELECT ord FROM ".TABLE_CITY." WHERE city_id=$city_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	
	mysql_query("DELETE FROM ".TABLE_CITY." WHERE city_id='$city_id'") or Error(1, __FILE__, __LINE__);

    if(is_object($log)) $log->store($log->getActionName('del').' ��������� �����', $city_id.'@'.TABLE_CITY);

	mysql_query("UPDATE ".TABLE_CITY." SET ord=ord-1 WHERE ord>$ord") or Error(1, __FILE__, __LINE__);	
		
	Header("Location: ".ADMIN_URL."?p=$part&citys=1");
	exit;
}

$replace = array();


function get_level($parent=0, $level=1)
{
	global $page_name, $part, $page_id, $parents;
		
	$w = '';
	if(!ereg("(^|,)site(,|$)", $_SESSION['sections']))
	{
		if($level==1) $w .= " AND page_id=1";
		elseif($level==2)
		{
			$arr = explode(",", $_SESSION['extra']);
			foreach($arr as $k=>$v) $arr[$k] = "page_id='$v'";
			$w .= " AND (".join(" OR ", $arr).") ";
		}
	}
	$sql = mysql_query("SELECT page_id, name, public FROM ".TABLE_PAGE." WHERE parent=$parent AND !site $w ORDER BY ord") 
		or Error(1, __FILE__, __LINE__);
	$pages = array();
	while($info = @mysql_fetch_array($sql))
	{ 
		if($info['page_id']==2 || $info['page_id']==3) continue;
		$info['name'] = HtmlSpecialChars($info['name'], ENT_COMPAT, 'cp1251');
		if(!$info['name']) $info['name'] = NONAME;
		
		$info['edit_link'] = ADMIN_URL."?p=$part&page_id=$info[page_id]";
		
		$info['del_link'] = ""; $info['icount'] = 0;
		if($i=check_page($info['page_id'])) $info['icount'] = $i;
		else $info['del_link'] = ADMIN_URL."?p=$part&del_page=$info[page_id]&page_id=$page_id";
		
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

if(!ereg("(^|,)site(,|$)", $_SESSION['sections']) && !$page_id)
{
	$arr = explode(",", $_SESSION['extra']);
	$page_id = (int)$arr[0];
	if(!$page_id) {echo "��� �������"; return;}
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;
}
else $pages = get_level();

$replace['pages'] = $pages;
$replace['page_id'] = $page_id;
$replace['parent'] = $parent;
$replace['editall'] = ereg("(^|,)-1(,|$)", $_SESSION['extra']) ? 1 : 0;

$left_menu = get_template('templ/page_list.htm', $replace);

if(isset($regions))
{
	$sql = mysql_query("SELECT * FROM ".TABLE_REGION." ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$regions = array(); 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name'], ENT_COMPAT, 'cp1251');
		$info['name_en'] = HtmlSpecialChars($info['name_en'], ENT_COMPAT, 'cp1251');
		//if(!$info['name']) $info['name'] = NONAME;
		
		$info['public_select'] = array_select('public', array(0=>'���', 1=>'��'), $info['public'], 0);
	
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
		$info['name'] = HtmlSpecialChars($info['name'], ENT_COMPAT, 'cp1251');
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
			$page['name'] = HtmlSpecialChars($page['name'], ENT_COMPAT, 'cp1251');
			$page['name_en'] = HtmlSpecialChars($page['name_en'], ENT_COMPAT, 'cp1251');
			
			$page['title'] = HtmlSpecialChars($page['title'], ENT_COMPAT, 'cp1251');
			$page['mdescription'] = HtmlSpecialChars($page['mdescription'], ENT_COMPAT, 'cp1251');
			$page['keywords'] = HtmlSpecialChars($page['keywords'], ENT_COMPAT, 'cp1251');
			
			$tinymce_elements = 'description';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
			$page['description'] = HtmlSpecialChars($page['description'], ENT_COMPAT, 'cp1251');
			
			$page['gallery_select'] = gallery_select($page['gallery_id'], $page['photocount']);
			
			$replace = array_merge($replace, $page);
		}
	}

	$content = get_template('templ/city_list.htm', $replace);

	return;
}

if($page_id)
{
	$sql = mysql_query("SELECT p.*, 
				d.dir, d.title, d.mdescription, d.keywords, d.title_en, d.mdescription_en, d.keywords_en,  
						d.title1, d.mdescription1, d.keywords1, d.title1_en, d.mdescription1_en, d.keywords1_en
			FROM ".TABLE_PAGE." p LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)
			WHERE p.page_id='$page_id'") or Error(1, __FILE__, __LINE__);
	if($page = @mysql_fetch_array($sql))
	{
		$page['name'] = HtmlSpecialChars($page['name'], ENT_COMPAT, 'cp1251');
		$page['name_en'] = HtmlSpecialChars($page['name_en'], ENT_COMPAT, 'cp1251');
		
		if($page['parent']==1)
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
			$page['photo_name'] = array('����', '�����',  '�������', '����������� ����', '��������', '��������');
			
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
					$alt = HtmlSpecialChars($arr_photos['alt'], ENT_COMPAT, 'cp1251');
					$alt_en = HtmlSpecialChars($arr_photos['alt_en'], ENT_COMPAT, 'cp1251');
					$ord = $arr_photos['ord'];
					//$ord_sel = digit_select('ord', 1, $count, $arr_photos['ord']);
					if($media=='pdf')
					{
						$f= $ext ? "../images/$photo_dir[$media]/${photo_id}.$ext" : '';
						$description = HtmlSpecialChars($arr_photos['description'], ENT_COMPAT, 'cp1251');
						$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 'description'=>$description, 'f'=>$f,
											'public'=>$arr_photos['public'], 'ord'=>$ord, 'alt'=>$alt,  'alt_en'=>$alt_en, 
											'del_link'=>ADMIN_URL."?p=$part&delphoto=$photo_id&page_id=$page_id&media=$media");
					}
					elseif($media=='license')
					{
						$photo = $ext ? "../images/$photo_dir[$media]/${photo_id}-s.$ext" : '';
						
						$ext_b = $arr_photos['ext_b'];
						$pdf = $ext_b ? "../images/$photo_dir[$media]/${photo_id}.$ext_b" : '';
						
						$photos[] = array('number'=>$i, 'photo_id'=>$photo_id, 'pdf'=>$pdf, 'photo'=>$photo,
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
						$description = HtmlSpecialChars($arr_photos['description'], ENT_COMPAT, 'cp1251');
						if($media == 'video') $bigphoto = "/video/?photo_id=$photo_id";
						list($w_small, $h_small) = @getimagesize($f);
						
						
						if($media=='item' || $media=='cure')
						{
							$superphoto = is_file("../images/big/$photo_id.jpg") ? "/images/big/$photo_id.jpg" : '';
							$del_super = $superphoto ? "?p=$part&delbig=$photo_id&page_id=$page_id&media=$media" : '';
						}
						else
						{
							$superphoto = ''; $del_super = '';
						}
						
						if($media=='video' && !is_file($f))
						{
							$f = '';
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
				$page['im_width'] = $media=='license' ? 400 : $im_width;
				$page['im_height'] = $media=='license' ? '' : $im_height;
				$page['watermark'] = ($watermark < 0) ? '' : 'checked';
				
				$content = get_template('templ/page_media.htm', $page);		
				return;
			}
			
		}
		
		$list = array('title', 'mdescription', 'keywords', 'title1', 'mdescription1', 'keywords1',
						'title_en', 'mdescription_en', 'keywords_en', 'title1_en', 'mdescription1_en', 'keywords1_en');
		foreach($list as $v) $page[$v] = HtmlSpecialChars($page[$v], ENT_COMPAT, 'cp1251');
		
		if(!$page['public'] && !$page['name'] && !$page['description']) $page['public'] = 1;
		$page['public_select'] = array_select('public', array(0=>'���', 1=>'��'), $page['public'], 0);

		$page['contacts'] = array_select('contacts', array(0=>'���', 1=>'��'), $page['contacts'], 0);
				
		$page['gallery_select'] = gallery_select($page['gallery_id'], $page['photocount']);
			
		$page['ord_select'] = ord_select("SELECT name FROM ".TABLE_PAGE.
			" WHERE parent=$parent AND page_id!=$page_id AND page_id!=2 AND page_id!=3 AND !site ORDER BY ord", 'ord', $page['ord']);
		
		$page['opinion_select'] = array_select('opinion', array(0=>'���', 1=>'��'), $page['opinion'], 0);

		$tinymce_elements = 'description, description_en';
		$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
		$page['description'] = HtmlSpecialChars($page['description'], ENT_COMPAT, 'cp1251');
		$page['description_en'] = HtmlSpecialChars($page['description_en'], ENT_COMPAT, 'cp1251');
		
		
		if($page['parent'] == 1 || $page['parent'] == 6)
		{
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
		}	
		
		if($page['parent'] == 1 || $page_id==41)
		{
			$page['slide_text'] = HtmlSpecialChars($page['slide_text'], ENT_COMPAT, 'cp1251');
			$page['slide_name'] = HtmlSpecialChars($page['slide_name'], ENT_COMPAT, 'cp1251');
			
			$page['url'] = HtmlSpecialChars($page['url'], ENT_COMPAT, 'cp1251');
			$page['brochure_url'] = HtmlSpecialChars($page['brochure_url'], ENT_COMPAT, 'cp1251');
		
			$page['region_select'] = mysql_select('region_id', 
				"SELECT region_id, name FROM ".TABLE_REGION." ORDER BY ord",
				$page['region_id'], 1);
	
			$page['city_select'] = mysql_select('city_id', 
				"SELECT city_id, name FROM ".TABLE_CITY." ORDER BY ord",
				$page['city_id'], 1);
	
			$page['stars_select'] = array_select('stars', array(1=>1, 2=>2, 3=>3, 4=>4, 5=>5), $page['stars'], 0);
			
			
			$sql_c = mysql_query("SELECT cure_id, name FROM ".TABLE_CURE." WHERE parent=0 ORDER BY ord") or Error(1, __FILE__, __LINE__);
			$cure_box = array(); $j=0;
			while($cure = @mysql_fetch_array($sql_c))
			{
				$cure['list'] = array();
				$sql_f = mysql_query("SELECT cure_id, name FROM ".TABLE_CURE." WHERE parent=$cure[cure_id] ORDER BY ord") 
					or Error(1, __FILE__, __LINE__);
				$i = 0; $nums = mysql_num_rows($sql_f);
				$all = ($nums%1) ? (int)($nums/1)+1 : $nums/1; 
				while($info = @mysql_fetch_array($sql_f))
				{ 
					$i++;  $j++;
					$newcol = !(($i+$all)%$all) ? 1 : 0; 
					$ch = (ereg("(^|,)$info[cure_id](,|$)", $page['cures'])) ? 'checked' : '';
					$cure['list'][] = array('i'=>$j, 'cure_id'=>$info['cure_id'], 
											'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
				}
				$cure_box[] = $cure;
			}
			$page['cure_box'] = $cure_box;
			

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
			
			
			$sql = mysql_query("SELECT page_id, name, public FROM ".TABLE_PAGE." WHERE parent=1 ORDER BY ord") or Error(1, __FILE__, __LINE__);
			
			$select =  "<select name='cpart_recom'>\n";
			$select .= "<option value='0'>�������� ������</option>\n";
			while($info_sect = @mysql_fetch_array($sql))
			{ 
				$info_sect['name'] = HtmlSpecialChars($info_sect['name'], ENT_COMPAT, 'cp1251');
				if(!$info_sect['name']) $info_sect['name'] = NONAME;					
				$class = ($info_sect['public']) ? '' : 'class="hid"';				
				$select .= "<option value='$info_sect[page_id]' $class>".$info_sect['name']."</option>\n";
			}			
			$select.="</select>";			
			$page['page_select'] = $select;			
						
			$list = array();
			$sql_sect = mysql_query("
				SELECT 
					c.page_id, c.name, c.public 
				FROM 
					".TABLE_RECOM." r
					LEFT JOIN ".TABLE_PAGE." c ON (c.page_id=r.page_id2)
				WHERE 
					r.page_id1=$page_id 
				ORDER BY r.ord") 
				or Error(1, __FILE__, __LINE__);
			while($info_sect = @mysql_fetch_array($sql_sect))
			{ 
				$info_sect['name'] = HtmlSpecialChars($info_sect['name'], ENT_COMPAT, 'cp1251');
				if(!$info_sect['name']) $info_sect['name'] = NONAME;
				$info_sect['link'] = "?p=site&page_id=$info_sect[page_id]";	
				$info_sect['dellink'] = "?p=site&delrecom=$info_sect[page_id]&page_id=$page_id";	
				$list[] = 	$info_sect;
			}			
			$page['recom_list'] = 	$list;	
		}
		
		$page['level'] = count($parents);
		$content = get_template('templ/page.htm', $page);
	}
}

function getMediaType($media){
    $object = '';

    switch($media){
        default:
            $object = '�����������';
            break;
        case 'pdf':
            $object = '�������';
            break;
        case 'video':
            $object = '�����';
            break;
        case 'virtual':
            $object = '����������� ���';
            break;
        case 'cure':
            $object = '������� �������';
            break;
    }

    return $object;
}
?>