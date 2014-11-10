<?php

function access($p, $sect='', $user=0)
{	
	if(!$user) $sect = $_SESSION['sections'];
	if(ereg("(^|,)$p(,|$)", $sect)) return true;
	return false;
}

function get_menu() 
{
	global $part, $section_list, $section_name, $english;
	$menu_array = array();

	foreach($section_list as $k=>$v)
	{
		if(!access($v)) continue;
		$link = ($part == $v) ? '' : ADMIN_URL."?p=$v";
		$menu_array[] = array('name'=>$section_name[$k],'link'=>$link, 'tag'=>$v);
	}
	
	return get_template('templ/menu.htm', array('menu_array'=>$menu_array, 'english'=>$english)); 
}

function gallery_select($gallery_id, $photocount)
{	
	$hidden = '';
	$sql = mysql_query("SELECT gallery_id, name, public FROM ".TABLE_GALLERY." WHERE parent=0 ORDER BY ord") or Error(1, __FILE__, __LINE__);
	
	$select =  "<select name=\"gallery_id\">\n";
	$select .= "<option value='0'></option>\n";
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
		
		$sel = ($gallery_id == $info['gallery_id']) ? 'selected' : '';
		$class = ($info['public']) ? '' : 'class="hid"';
		
		if(!$info['public'] && $sel) $hidden = 1;
		
		$select .= "<option value='$info[gallery_id]' $class $sel>".$info['name']."</option>\n";
		//$select .= '<optgroup label="'.$info['name'].'">';
		
		$sql_sect = mysql_query("SELECT gallery_id, name, public FROM ".TABLE_GALLERY." WHERE parent=$info[gallery_id] ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
		while($info_sect = @mysql_fetch_array($sql_sect))
		{ 
			$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
			if(!$info_sect['name']) $info_sect['name'] = NONAME;
			
			$sel = ($gallery_id == $info_sect['gallery_id']) ? 'selected' : '';
			$class = ($info_sect['public']) ? '' : 'class="hid"';
			
			if(!$info_sect['public'] && $sel) $hidden = 1;
		
			$select .= "<option value='$info_sect[gallery_id]' $class $sel> &nbsp;&nbsp; ".$info_sect['name']."</option>\n";
		}
		
		//$select .= '</optgroup>';
	}
	
	if($hidden) $hidden = " &nbsp;&nbsp;<font color='red'>галерея скрыта на сайте</font>";
	
	$select.="</select> &nbsp;&nbsp; кол-во фото 
	<input type=\"text\" name=\"photocount\" value=\"$photocount\" style=\"width: 22px;\" maxlength=\"2\"> $hidden";
	
	return $select;
}

function check_dir($dir, $olddir, $parent_dir_id)
{	
	if($dir == $olddir) return $dir;
	else
	{
		if(eregi("^([[:alnum:]]|_|-)+$", $dir)) 
		{
			$sql = mysql_query("SELECT count(*) FROM ".TABLE_DIR." WHERE parent=$parent_dir_id AND dir='$dir'") 
						or Error(1, __FILE__, __LINE__);
			$arr = @mysql_fetch_array($sql);
			$count = (int)@$arr[0]; 
			if($count || ($parent_dir_id == 0 && is_dir("../$dir")))
			{
				$_SESSION['message'] = "Название раздела \\'$dir\\' уже существует, выберите другое!";
				return $olddir;
			}
			return $dir;
		}
		else 
		{
			$_SESSION['message'] = "Недопустимое значение раздела\\nВозможны только латинские буквы, цифры и символы \\'_\\',  \\'-\\'";
			return $olddir;
		}
	}
	
}

function get_dir($dir)
{
	if(defined($cons=strtoupper($dir)."_PAGE")) return constant($cons);
	if(defined($cons="ADMIN_".strtoupper($dir)."_PAGE")) return constant($cons);
	return $dir;
}


function get_extension($filename, $err_url)
{
	$ext_list = array('jpg', 'jpeg', 'png', 'bmp', 'gif');
	
	$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
	
	if(!in_array($ext, $ext_list))
	{
		$_SESSION['message'] = "Поддерживаются только форматы JPG, JPEG, PNG, BMP и GIF!"; 
		Header("Location: $err_url"); exit;
	}
	
	return $ext;
}

function upload_small_photo($owner_name, $owner_id, $err_url)
{
	global $photo_owner, $photo_dir;
	
	if(!is_uploaded_file($_FILES["photo"]["tmp_name"]))
	{
		$_SESSION['message'] = "Ошибка загрузки фото"; 
		Header("Location: ".$err_url);
		exit;
	}	
	
	$ext = get_extension($_FILES['photo']['name'], $err_url); 
	
	mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$owner_id', owner='$photo_owner[$owner_name]', ext='$ext', ord=1") 
		or Error(1, __FILE__, __LINE__);
	$photo_id = mysql_insert_id();
 
	$small="../images/$photo_dir[$owner_name]/${photo_id}-s.$ext";
	if(is_file($small)) unlink($small);
	
	move_uploaded_file($_FILES["photo"]["tmp_name"], $small);
}


?>
