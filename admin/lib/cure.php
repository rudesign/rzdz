<?php
$cure_id = (int)@$cure_id;
$subcure_id = (int)@$subcure_id;
$cure_type_list = array(
	1=>'программы', 
	2=>'услуги',
	3=>'объединяющий раздел', 
	4=>'информационный раздел',
	5=>'новости', 
	6=>'галерея', 
	7=>'лицензии');

if(isset($addcure))
{	
	$addcure = (int)@$addcure;
	$curestr_id = (int)@$curestr_id;
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$addcure") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_CURE." SET ord=$ord, parent=$addcure, curestr_id='$curestr_id'") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
		
	$link = "?p=$part";
	if($addcure) $link .= "&cure_id=$addcure&subcure_id=$id";
	else $link .= "&cure_id=$id";
	Header("Location: ".$link);
	exit;
}

if(@$save)
{
	if($subcure_id)
	{
		$url = "?p=$part&cure_id=$cure_id&subcure_id=$subcure_id";
		
		$sql = mysql_query("SELECT c.ord, c.parent, p.type FROM ".TABLE_CURE." c
			LEFT JOIN ".TABLE_CURE." p  ON (p.cure_id=c.parent)
			WHERE c.cure_id='$subcure_id'") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$oldord = (int)@$arr['ord'];
		$parent = (int)@$arr['parent'];
		$cure_type = (int)@$arr['type'];
		
		$sql_ord = '';
		if($cure_type!=2)
		{
			$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$cure_id") or Error(1, __FILE__, __LINE__);
			$arr = @mysql_fetch_array($sql);
			$count = (int)@$arr[0];
			$ord = (int)@$ord;
			if($ord < 1 || $ord > $count) 
			{
				$_SESSION['message'] = "Неверное значение порядкового номера (от 1 до $count)";
				Header("Location: ".$url);
				exit;
			}
			$sql_ord = ", ord='$ord'";
		}
		
		
		$name = escape_string(from_form(@$name));
		$name_en = escape_string(from_form(@$name_en));
		$anons = escape_string(from_form(@$anons));
		$anons_en = escape_string(from_form(@$anons_en));
        $profile = escape_string(from_form(@$profile));
        $profile_en = escape_string(from_form(@$profile_en));
		$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
		$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
		$inmenu = (int)@$inmenu;
		if($cure_type==2 || $cure_type==4 || $cure_type==7) $sql_ord .= ", inmenu='$inmenu'";
		$page_id = (int)@$page_id;
		if($cure_type==7) $sql_ord .= ", page_id='$page_id'";
		
		mysql_query("UPDATE ".TABLE_CURE." SET  name='$name', name_en='$name_en', anons='$anons', anons_en='$anons_en',
		    profile='$profile', profile_en='$profile_en', 
			description='$description', description_en='$description_en' $sql_ord
			WHERE cure_id='$subcure_id'") or Error(1, __FILE__, __LINE__);
				
		if($cure_type!=2)
		{	
			if($ord > $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 ".
				"WHERE ord>'$oldord' AND ord<='$ord' AND parent=$cure_id AND  cure_id!='$subcure_id'") or Error(1, __FILE__, __LINE__);
			elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord+1 ".
				"WHERE ord>='$ord' AND ord<'$oldord' AND parent=$cure_id AND cure_id!='$subcure_id'") or Error(1, __FILE__, __LINE__);
		}
			
		if(is_array(@$sanat)) 
		{ 
			$sql_f = mysql_query("SELECT p.page_id, ch.cure_id, ch.price FROM ".TABLE_PAGE." p 
				LEFT JOIN ".TABLE_CUREHOTEL." ch ON (ch.page_id=p.page_id AND ch.cure_id=$subcure_id)			
				WHERE p.parent=1 GROUP BY p.page_id ORDER BY p.ord") 
				or Error(1, __FILE__, __LINE__);				
			while($info = @mysql_fetch_array($sql_f))
			{		
				$page_id = 	(int)$info['page_id'];
				$count = (int)@$info['cure_id'];
				$price_old = @$info['price'];
				
				$checked = in_array($info['page_id'], $sanat);
				
				if($count && !$checked)
					mysql_query("DELETE FROM ".TABLE_CUREHOTEL." WHERE cure_id=$subcure_id AND page_id='$page_id'") 
					or Error(1, __FILE__, __LINE__);
				elseif(!$count && $checked)
					mysql_query("INSERT INTO ".TABLE_CUREHOTEL." SET cure_id=$subcure_id, page_id='$page_id'") 
					or Error(1, __FILE__, __LINE__);
					
				$price_new = @from_form($price[$page_id]);
				if($checked && $price_old!=$price_new)
					mysql_query("UPDATE ".TABLE_CUREHOTEL." SET price='".escape_string($price_new)."' WHERE cure_id=$subcure_id AND page_id='$page_id'") 
					or Error(1, __FILE__, __LINE__);
			}
		}
		else 
			mysql_query("DELETE FROM ".TABLE_CUREHOTEL." WHERE cure_id=$subcure_id") 
			or Error(1, __FILE__, __LINE__);
	}
	else
	{
		$url = "?p=$part&cure_id=$cure_id";
	
		$name = escape_string(from_form(@$name));
		$name_en = escape_string(from_form(@$name_en));
		$inhotel = escape_string(from_form(@$inhotel));
		$inhotel_en = escape_string(from_form(@$inhotel_en));
		$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
		$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
		$public = (int)@$public;
		$ord = (int)@$ord;
		$type = (int)@$type;
		$partof = (int)@$partof;
		
		$update = '';
		if(!$partof)
		{
			$inmenu = (int)@$inmenu;
			$update = ", inmenu='$inmenu'";
		}

		$sql = mysql_query("SELECT ord FROM ".TABLE_CURE." WHERE cure_id='$cure_id'") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$oldord = (int)@$arr['ord'];
		
		mysql_query("UPDATE ".TABLE_CURE." SET  name='$name', name_en='$name_en', ord='$ord', public='$public', 
			type='$type', partof='$partof',
			description='$description', description_en='$description_en',inhotel='$inhotel', inhotel_en='$inhotel_en' $update
			WHERE cure_id='$cure_id'") or Error(1, __FILE__, __LINE__);
			
		if($ord > $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 ".
			"WHERE ord>'$oldord' AND ord<='$ord' AND parent=0 AND  cure_id!='$cure_id'") or Error(1, __FILE__, __LINE__);
		elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord+1 ".
			"WHERE ord>='$ord' AND ord<'$oldord' AND parent=0 AND cure_id!='$cure_id'") or Error(1, __FILE__, __LINE__);
			
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
			
			mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$cure_id', owner='$photo_owner[cure_part]', ext='$ext', ord=1") 
				or Error(1, __FILE__, __LINE__);
			$photo_id = mysql_insert_id();
			
			$small="../images/$photo_dir[cure_part]/${photo_id}-s.$ext";
			if(is_file($small)) unlink($small);
			
			copy($photo, $small);
		}
	}
	
	Header("Location: ".$url);
	exit;
}

if(@$savedescr)
{

	$price = escape_string(from_form(@$price));
	$price_en = escape_string(from_form(@$price_en));
	$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
	$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
		
	mysql_query("UPDATE ".TABLE_CUREHOTEL." SET price='$price' , price_en='$price_en',
		description='$description', description_en='$description_en'
		WHERE cure_id=$subcure_id AND page_id='$page_id'") 
	or Error(1, __FILE__, __LINE__);
	
	$url = "?p=$part&cure_id=$cure_id&subcure_id=$subcure_id&descr=$page_id";
	Header("Location: ".$url);
	exit;
}

if(@$del_cure)
{
	$del_cure = (int)$del_cure;
	$sql = mysql_query("SELECT ord, parent FROM ".TABLE_CURE." WHERE cure_id=$del_cure") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$parent = (int)@$arr['parent']; 
	
	mysql_query("DELETE FROM ".TABLE_CURE." WHERE cure_id='$del_cure'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 WHERE parent=$parent AND ord>$ord") or Error(1, __FILE__, __LINE__);	
		
	$url = "?p=$part&cure_id=$cure_id";
	if(isset($curestr_id)) $url .= "&service&curestr_id=$curestr_id";
	
	Header("Location: ".$url);
	exit;
}

if(@$delphoto) {
	
	$delphoto = (int)$delphoto; 

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
	
	$dir = $photo_dir['cure_part'];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
	
	$url = ADMIN_URL."?p=$part&cure_id=$cure_id";
		
	Header("Location: ".$url); 
	exit;
}

function check_cure($subcure_id, $parent=0)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CUREHOTEL." WHERE cure_id=$subcure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return $count."о";
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$subcure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return $count."о";
	
	if($subcure_id < 1) return "-";
		
	return 0;
}

if(isset($loadcure))
{	
	$cure_id = (int)@$cure_id;
	$curestr_id = (int)@$curestr_id;
	$page_id = (int)@$page_id;
	
	$url = "?p=$part&cure_id=$cure_id&curestr_id=$curestr_id&service";
	
	if(!$cure_id || !$curestr_id || !$page_id)
	{
		Header("Location: ".$url); 
		exit;
	}
	
	$text_arr = explode("\n", from_form(@$text));
	
	foreach($text_arr as $v)
	{
		$list = explode(";", $v);
		if(isset($list[0]) && isset($list[1]) && isset($list[2]))
		{
			if(!strpos($list[2], "-00")) continue;
			
			$name = escape_string($list[0]);
			$price = str_replace("-00", '', escape_string($list[2]));
			
			$sql = mysql_query("SELECT cure_id FROM ".TABLE_CURE." WHERE name='$name' AND parent=$cure_id") or Error(1, __FILE__, __LINE__);
			$arr = @mysql_fetch_array($sql);
			if(@$arr[0]) $subcure_id = $arr[0];
			else
			{
				$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$cure_id") or Error(1, __FILE__, __LINE__);
				$arr = @mysql_fetch_array($sql);
				$ord = (int)@$arr[0] + 1;
				
				mysql_query("INSERT INTO ".TABLE_CURE." SET ord=$ord, parent=$cure_id, curestr_id='$curestr_id', name='$name'") 
					or Error(1, __FILE__, __LINE__);
				$subcure_id = mysql_insert_id();
			}
			
			
			$sql = mysql_query("SELECT count(*) FROM ".TABLE_CUREHOTEL." WHERE page_id=$page_id AND cure_id=$subcure_id") 
				or Error(1, __FILE__, __LINE__);
			$arr = @mysql_fetch_array($sql);
			$count = $arr[0];
			
			if($count)
				mysql_query("UPDATE ".TABLE_CUREHOTEL." SET price='".escape_string($price)."' WHERE cure_id=$subcure_id AND page_id='$page_id'") 
					or Error(1, __FILE__, __LINE__);
			else
				mysql_query("INSERT INTO ".TABLE_CUREHOTEL." SET cure_id=$subcure_id, page_id='$page_id', price='".escape_string($price)."'") 
				or Error(1, __FILE__, __LINE__);
			
		}
	}
	
		
	Header("Location: ".$url);
	exit;
}

if(isset($addcurestr) && $cure_id)
{	
	$addcurestr = (int)@$addcurestr;
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURESTR." WHERE parent=$addcurestr AND cure_id=$cure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_CURESTR." SET ord=$ord, parent=$addcurestr, cure_id=$cure_id") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
		
	Header("Location: ".ADMIN_URL."?p=$part&cure_id=$cure_id#link$id");
	exit;
}

if(@$savecurestr)
{
	$curestr_id = (int)@$curestr_id;
	$sql = mysql_query("SELECT ord, parent, cure_id FROM ".TABLE_CURESTR." WHERE curestr_id='$curestr_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr['ord'];
	$parent = (int)@$arr['parent'];
	$cure_id = (int)@$arr['cure_id'];
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURESTR." WHERE parent=$parent AND cure_id=$cure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	
	$ord = (int)@$ord;
	if($ord < 1 || $ord > $count) 
	{
		$_SESSION['message'] = "Неверное значение порядкового номера (от 1 до $count)";
		Header("Location: ".ADMIN_URL."?p=$part&cure_id=$cure_id");
		exit;
	}
	
	$name = escape_string(from_form(@$name));
	
	mysql_query("UPDATE ".TABLE_CURESTR." SET name='$name', ord='$ord' ".
				"WHERE curestr_id='$curestr_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_CURESTR." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND parent=$parent AND cure_id=$cure_id AND  curestr_id!='$curestr_id'") 
		or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_CURESTR." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND parent=$parent AND cure_id=$cure_id AND curestr_id!='$curestr_id'") 
		or Error(1, __FILE__, __LINE__);
	
	$url = "?p=$part&cure_id=$cure_id#link$curestr_id";
	
	Header("Location: ".$url);
	exit;
}

if(@$delcurestr)
{
	$curestr_id = (int)@$curestr_id;
	$url = "?p=$part&cure_id=$cure_id";
	
	$sql = mysql_query("SELECT ord, parent, cure_id FROM ".TABLE_CURESTR." WHERE curestr_id=$curestr_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$parent = (int)@$arr['parent']; 
	$cure_id = (int)@$arr['cure_id'];
	
	if($parent) $url .= "#link$parent";
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURESTR." WHERE parent=$curestr_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) 
	{
		$_SESSION['message'] = "Раздел не может быть удален, в нем есть подразделы";
		Header("Location: ".$url);
		exit;
	}
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE curestr_id=$curestr_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) 
	{
		$_SESSION['message'] = "Раздел не может быть удален, к нему привязаны разделы";
		Header("Location: ".$url);
		exit;
	}
		
	mysql_query("DELETE FROM ".TABLE_CURESTR." WHERE curestr_id='$curestr_id'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_CURESTR." SET ord=ord-1 WHERE parent=$parent AND cure_id=$cure_id AND ord>$ord") or Error(1, __FILE__, __LINE__);	
		
	Header("Location: ".$url);
	exit;
}

$replace = array();


function get_level($parent=0, $level=1)
{
	global $part;
		
	$sql = mysql_query("SELECT cure_id, name, public, partof FROM ".TABLE_CURE." WHERE parent=$parent ORDER BY ord") 
		or Error(1, __FILE__, __LINE__);
	$pages = array();
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
		
		$info['edit_link'] = ADMIN_URL."?p=$part&cure_id=$info[cure_id]";
		
		$info['del_link'] = ""; $info['icount'] = 0;
		if($i=check_cure($info['cure_id'])) $info['icount'] = $i;
		else $info['del_link'] = ADMIN_URL."?p=$part&del_cure=$info[cure_id]";
		
		$pages[] = $info;
	}
	return $pages;
}

$pages = get_level();

$replace['pages'] = $pages;
$replace['cure_id'] = $cure_id;

$left_menu = get_template('templ/cure_list.htm', $replace);

if($cure_id)
{		
	$sql = mysql_query("SELECT * FROM ".TABLE_CURE." WHERE cure_id=$cure_id") 
		or Error(1, __FILE__, __LINE__);
	$replace = @mysql_fetch_array($sql);
	$cure_type = $replace['type'];
	
	if(!$subcure_id)
	{
		$replace['name'] = HtmlSpecialChars($replace['name']);		
		$replace['name_en'] = HtmlSpecialChars($replace['name_en']);	
		$replace['inhotel'] = HtmlSpecialChars($replace['inhotel']);		
		$replace['inhotel_en'] = HtmlSpecialChars($replace['inhotel_en']);	
		$replace['ord_select'] = ord_select("SELECT name FROM ".TABLE_CURE.
				" WHERE parent=0 ORDER BY ord", 'ord', $replace['ord']);
		$replace['type_select'] = array_select('type', $cure_type_list, $replace['type'], 0);
		$replace['public_select'] = array_select('public', array(0=>'Нет', 1=>'Да'), $replace['public'], 0);
		
		if($cure_type==4)
		{
			$replace['description'] = HtmlSpecialChars($replace['description']);
			$replace['description_en'] = HtmlSpecialChars($replace['description_en']);
			$tinymce_elements = 'description, description_en';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
		}
		if(!$replace['partof'])
		{
			
			$sql_photos = mysql_query("SELECT photo_id, ext, ext_b, ord FROM ".TABLE_PHOTO.
					" WHERE owner_id=$cure_id AND owner='$photo_owner[cure_part]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
			$replace['photo'] = '';
			if($arr_photos = @mysql_fetch_array($sql_photos)) {
				$photo_id = $arr_photos['photo_id'];
				$ext = $arr_photos['ext'];
				$w_small=0; $h_small=0;
				$f="../images/$photo_dir[cure_part]/${photo_id}-s.$ext";
				list($w_small, $h_small) = @getimagesize($f);
				$replace['photo'] = $f;
				$replace['smallsize'] = "width='$w_small' height='$h_small'";
				$replace['photo_del_link'] = "?p=$part&delphoto=$photo_id&cure_id=$cure_id";
			}		
		}
		
		$sql = mysql_query("SELECT count(*)  FROM ".TABLE_CURE." WHERE partof=$cure_id") 
			or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$replace['partof_select'] =  !$arr[0] ? mysql_select('partof', "SELECT cure_id, name FROM ".TABLE_CURE.
				" WHERE parent=0 AND cure_id!=$cure_id AND !partof ORDER BY ord", $replace['partof'], 1) : '';
		
		$ord = $cure_type==2 ? 'name' : 'ord';
		$sql = mysql_query("SELECT cure_id, name, anons FROM ".TABLE_CURE." WHERE parent=$cure_id ORDER BY $ord") or Error(1, __FILE__, __LINE__);
		
		$cures = array(); 
		$all = (mysql_num_rows($sql)%4) ? (int)(mysql_num_rows($sql)/4)+1 : mysql_num_rows($sql)/4; 
		$k=0;
		while($info = @mysql_fetch_array($sql))
		{ 
			$k++; 
			$info['name'] = $info['name'] ? HtmlSpecialChars($info['name']) : NONAME;	
			$info['title'] = HtmlSpecialChars($info['anons']);		
			
			$info['del_link'] = ""; $info['icount'] = 0;
			if($i=check_cure($info['cure_id'])) $info['icount'] = $i;
			else $info['del_link'] = ADMIN_URL."?p=$part&del_cure=$info[cure_id]&cure_id=$cure_id";
		
			$info['edit_link'] = ADMIN_URL."?p=$part&cure_id=$cure_id&subcure_id=$info[cure_id]";
			
			$info['newcol'] = !(($k+$all)%$all) && $k!=mysql_num_rows($sql) ? 1 : 0; 
			$cures[] = $info;
		}
	
		$replace['cure_list'] = $cures;
		
		if($cure_type==2)
		{		
			$replace['service'] = isset($service) ? 1 : 0;
			
			if($replace['service'])
			{
				$replace['curestr_id'] = $curestr_id = (int)@$curestr_id;
				
				$page_id = 22;
				$replace['san_select'] = mysql_select('page_id', 
						"SELECT p.page_id, concat(p.name, ' ', ct.name) as name FROM ".TABLE_PAGE." p 
						LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id WHERE p.parent=1 ORDER BY p.ord",	
						$page_id);
				
				$sql = mysql_query("SELECT curestr_id, name FROM ".TABLE_CURESTR." WHERE parent=0 AND cure_id=$cure_id ORDER BY ord") 
					or Error(1, __FILE__, __LINE__);
				
				$select =  "<select name=\"curestr_id\" ".
					"onchange=\"document.location='?p=$part&cure_id=$cure_id&service&curestr_id='+this.value\">\n";
				$select .= "<option value='0'>все</option>\n";
				while($info = @mysql_fetch_array($sql))
				{ 
					$info['name'] = HtmlSpecialChars($info['name']);
					if(!$info['name']) $info['name'] = NONAME;
					
					$sel = ($curestr_id == $info['curestr_id']) ? 'selected' : '';
					
					$select .= "<option value='$info[curestr_id]' $sel>".$info['name']."</option>\n";
					//$select .= '<optgroup label="'.$info['name'].'">';
					
					$sql_sect = mysql_query("SELECT curestr_id, name FROM ".TABLE_CURESTR." WHERE parent=$info[curestr_id] ORDER BY ord") 
						or Error(1, __FILE__, __LINE__);
					while($info_sect = @mysql_fetch_array($sql_sect))
					{ 
						$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
						if(!$info_sect['name']) $info_sect['name'] = NONAME;
						
						$sel = ($curestr_id == $info_sect['curestr_id']) ? 'selected' : '';
					
						$select .= "<option value='$info_sect[curestr_id]' $sel style='padding-left:20px'>".$info_sect['name']."</option>\n";
					}
				}
								
				$select.="</select>";
				$replace['curestr_select'] = $select;
				
				//if($curestr_id)
				{
					$where = "parent=$cure_id";
					if($curestr_id) $where .= " AND curestr_id=$curestr_id";
					$ord = $curestr_id ? 'ord' : 'name';
					$sql = mysql_query("SELECT cure_id, name, inmenu FROM ".TABLE_CURE." WHERE  $where ORDER BY $ord") 
						or Error(1, __FILE__, __LINE__);
					
					$cures = array(); 
					while($info = @mysql_fetch_array($sql))
					{ 
						$info['name'] = $info['name'] ? HtmlSpecialChars($info['name']) : NONAME;	
						
						$info['del_link'] = ""; $info['icount'] = 0;
						if($i=check_cure($info['cure_id'])) $info['icount'] = $i;
						else $info['del_link'] = ADMIN_URL."?p=$part&del_cure=$info[cure_id]&cure_id=$cure_id&curestr_id=$curestr_id";
					
						$info['edit_link'] = ADMIN_URL."?p=$part&cure_id=$cure_id&subcure_id=$info[cure_id]";
						
						$cures[] = $info;
					}
					$replace['cure_list'] = $cures;
				}
			}
			
			else
			{
				$sql = mysql_query("SELECT * FROM ".TABLE_CURESTR." WHERE parent=0 AND cure_id=$cure_id ORDER BY ord") 
				or Error(1, __FILE__, __LINE__);
				
				$cures = array(); 
				while($info = @mysql_fetch_array($sql))
				{ 
					$info['name'] = HtmlSpecialChars($info['name']);		
					
					$sql1 = mysql_query("SELECT * FROM ".TABLE_CURESTR." WHERE parent=$info[curestr_id] ORDER BY ord") 
					or Error(1, __FILE__, __LINE__);	
					$list = array(); 
					while($info1 = @mysql_fetch_array($sql1))
					{ 
						$info1['name'] = HtmlSpecialChars($info1['name']);							
						$list[] = $info1;
					}
					$info['list'] = $list;
				
					$cures[] = $info;
				}
			
				$replace['curestrs'] = $cures;
			}
		}
	}
	
	$replace['subcure_id'] = $subcure_id;
	
	if($subcure_id)
	{	
		$sql = mysql_query("SELECT * FROM ".TABLE_CURE." WHERE cure_id=$subcure_id") or Error(1, __FILE__, __LINE__);
		$subcure = @mysql_fetch_array($sql);

		
		if(@$descr)
		{
			$page_id = (int)@$descr;
			$replace['descr'] = $page_id;
			
			$sql = mysql_query("SELECT cr.description, cr.description_en, cr.price, cr.price_en, p.name FROM ".TABLE_CUREHOTEL." cr 
				LEFT JOIN ".TABLE_PAGE." p ON p.page_id=cr.page_id
				WHERE cr.cure_id=$subcure_id AND cr.page_id=$page_id") 
				or Error(1, __FILE__, __LINE__);
			$info = @mysql_fetch_array($sql);
				
			$subcure['list_link'] = "?p=cure&cure_id=$cure_id";
			if($subcure['curestr_id']) $subcure['list_link'] .= "&service&&curestr_id=".$subcure['curestr_id'];
			$subcure['page_id'] =  $page_id;
			$subcure['pname'] =  HtmlSpecialChars($info['name']);
			$subcure['price'] =  HtmlSpecialChars($info['price']);
			$subcure['price_en'] =  HtmlSpecialChars($info['price_en']);
			$subcure['description'] = HtmlSpecialChars($info['description']);
			$subcure['description_en'] = HtmlSpecialChars($info['description_en']);
			$tinymce_elements = 'description, description_en';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
		}
		else
		{
			$subcure['list_link'] = "?p=cure&cure_id=$cure_id";
			if($subcure['curestr_id']) $subcure['list_link'] .= "&service&&curestr_id=".$subcure['curestr_id'];
			$subcure['ord_select'] = $cure_type!=2 ? ord_select("SELECT name FROM ".TABLE_CURE.
				" WHERE parent=$cure_id AND cure_id!=$subcure_id ORDER BY ord", 'ord', $subcure['ord']) : '';
			$subcure['name'] = HtmlSpecialChars($subcure['name']);
			$subcure['name_en'] = HtmlSpecialChars($subcure['name_en']);
			$subcure['anons'] = HtmlSpecialChars($subcure['anons']);
			$subcure['anons_en'] = HtmlSpecialChars($subcure['anons_en']);
			$subcure['profile'] = HtmlSpecialChars($subcure['profile']);
			$subcure['profile_en'] = HtmlSpecialChars($subcure['profile_en']);
			$subcure['description'] = HtmlSpecialChars($subcure['description']);
			$subcure['description_en'] = HtmlSpecialChars($subcure['description_en']);
			$tinymce_elements = 'description, description_en';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
			
			$curehotel = array();
			$page_box = array();
			if($cure_type!=4 && $cure_type!=7)
			{
				$sql = mysql_query("SELECT page_id, price FROM ".TABLE_CUREHOTEL." WHERE cure_id=$subcure_id") 
					or Error(1, __FILE__, __LINE__);
				while($info = @mysql_fetch_array($sql)) $curehotel[$info[0]] = $info[1];
					
				$sql_f = mysql_query("SELECT p.page_id, p.name, ct.name as city FROM ".TABLE_PAGE." p 
					LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
					WHERE p.parent=1 AND p.public='1' ORDER BY p.ord") 
					or Error(1, __FILE__, __LINE__);
				$all = (mysql_num_rows($sql_f)%2) ? (int)(mysql_num_rows($sql_f)/2)+1 : mysql_num_rows($sql_f)/2; 
				
				$i = 0;	
				while($info = @mysql_fetch_array($sql_f))
				{ 
					$i++; 
					$newcol = !(($i+$all)%$all) ? 1 : 0; 
					$ch = isset($curehotel[$info['page_id']]) ? 'checked' : '';
					//if(preg_match("/долина/i", $info['name'])) 
						$info['name'] .= " ($info[city])";
					$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 'price'=>@$curehotel[$info['page_id']],
											'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
				}
			}
			$subcure['page_box'] = $page_box;
			
			if($cure_type==7)
			{
				$subcure['san_select'] = mysql_select('page_id', 
						"SELECT p.page_id, concat(p.name, ' ', ct.name) as name FROM ".TABLE_PAGE." p 
						LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id WHERE p.parent=1 ORDER BY p.ord",	
						$subcure['page_id']);
			}
		}
			
		
		$replace['subcure'] = $subcure;
	}
	
	//$content = get_template("templ/cure$cure_type.htm", $replace);
	$content = get_template("templ/cure.htm", $replace);
	return;
}


?>