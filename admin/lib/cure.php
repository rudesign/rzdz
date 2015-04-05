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
	$page_id = (int)@$page_id;
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$addcure AND curestr_id=$curestr_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_CURE." SET ord=$ord, parent=$addcure, curestr_id='$curestr_id'") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	
	if($page_id)
	mysql_query("INSERT INTO ".TABLE_CUREHOTEL." SET page_id=$page_id, cure_id=$id") or Error(1, __FILE__, __LINE__);
		
	$link = "?p=$part";
	if($addcure) $link .= "&cure_id=$addcure&subcure_id=$id&page_id=$page_id";
	else $link .= "&cure_id=$id";
	Header("Location: ".$link);
	exit;
}

if(@$save)
{
	if($subcure_id)
	{
		$url = "?p=$part&cure_id=$cure_id&subcure_id=$subcure_id";
		
		$sql = mysql_query("SELECT c.ord, c.parent, c.curestr_id, p.type FROM ".TABLE_CURE." c
			LEFT JOIN ".TABLE_CURE." p  ON (p.cure_id=c.parent)
			WHERE c.cure_id='$subcure_id'") or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql);
		$oldord = (int)@$arr['ord'];
		$parent = (int)@$arr['parent'];
		$curestr_id = (int)@$arr['curestr_id'];
		$cure_type = (int)@$arr['type'];
		
		$sql_ord = '';
		//if($cure_type!=2)
		{
			$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$cure_id AND curestr_id=$curestr_id") 
				or Error(1, __FILE__, __LINE__);
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

		mysql_query("UPDATE ".TABLE_CURE." SET  name='$name', name_en='$name_en',
			anons='$anons', anons_en='$anons_en',
		    profile='$profile', profile_en='$profile_en', 
			description='$description', description_en='$description_en' $sql_ord
			WHERE cure_id='$subcure_id'") or Error(1, __FILE__, __LINE__);
				
		//if($cure_type!=2)
		{	
			if($ord > $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 ".
				"WHERE ord>'$oldord' AND ord<='$ord' AND parent=$cure_id AND curestr_id=$curestr_id AND  cure_id!='$subcure_id'") 
					or Error(1, __FILE__, __LINE__);
			elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord+1 ".
				"WHERE ord>='$ord' AND ord<'$oldord' AND parent=$cure_id AND curestr_id=$curestr_id AND cure_id!='$subcure_id'") 
					or Error(1, __FILE__, __LINE__);
		}
		
		if(is_array(@$sanat)) 
		{ 
			$field = $cure_type==4 ? ' ch.description, ch.description_en' : 'ch.price';
			$sql_f = mysql_query("SELECT p.page_id, ch.cure_id, $field FROM ".TABLE_PAGE." p 
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
					
				if($cure_type==4)
				{ 
					$description = @from_form($descr[$page_id]);
					$description_en = @from_form($descr_en[$page_id]); 
					mysql_query("UPDATE ".TABLE_CUREHOTEL." SET description='".escape_string($description)."',
						 description_en='".escape_string($description_en)."'
						WHERE cure_id=$subcure_id AND page_id='$page_id'") or Error(1, __FILE__, __LINE__);
				}
				else
				{
					/*$price_new = @from_form($price[$page_id]);
					$price1_new = @from_form($price1[$page_id]);
					if($checked)
						mysql_query("UPDATE ".TABLE_CUREHOTEL." SET price='".escape_string($price_new)."', 
							price1='".escape_string($price1_new)."' 
							WHERE cure_id=$subcure_id AND page_id='$page_id'") or Error(1, __FILE__, __LINE__);*/
				}
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
		$name_extra = escape_string(from_form(@$name_extra));
		$name_extra_en = escape_string(from_form(@$name_extra_en));
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
		
		mysql_query("UPDATE ".TABLE_CURE." SET  name='$name', name_en='$name_en', name_extra='$name_extra', name_extra_en='$name_extra_en',
			 ord='$ord', public='$public', 
			type='$type', partof='$partof',
			description='$description', description_en='$description_en',inhotel='$inhotel', inhotel_en='$inhotel_en' $update
			WHERE cure_id='$cure_id'") or Error(1, __FILE__, __LINE__);
			
		if($ord > $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 ".
			"WHERE ord>'$oldord' AND ord<='$ord' AND parent=0 AND  cure_id!='$cure_id'") or Error(1, __FILE__, __LINE__);
		elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_CURE." SET ord=ord+1 ".
			"WHERE ord>='$ord' AND ord<'$oldord' AND parent=0 AND cure_id!='$cure_id'") or Error(1, __FILE__, __LINE__);
			
		if(is_array(@$sanat)) 
		{ 
			$sql_f = mysql_query("SELECT p.page_id, ch.cure_id FROM ".TABLE_PAGE." p 
				LEFT JOIN ".TABLE_CUREHOTEL." ch ON (ch.page_id=p.page_id AND ch.cure_id=$cure_id)			
				WHERE p.parent=1 GROUP BY p.page_id ORDER BY p.ord") 
				or Error(1, __FILE__, __LINE__);				
			while($info = @mysql_fetch_array($sql_f))
			{		
				$page_id = 	(int)$info['page_id'];
				$count = (int)@$info['cure_id'];
							
				$checked = in_array($info['page_id'], $sanat);
				
				if($count && !$checked)
					mysql_query("DELETE FROM ".TABLE_CUREHOTEL." WHERE cure_id=$cure_id AND page_id='$page_id'") 
					or Error(1, __FILE__, __LINE__);
				elseif(!$count && $checked)
					mysql_query("INSERT INTO ".TABLE_CUREHOTEL." SET cure_id=$cure_id, page_id='$page_id'") 
					or Error(1, __FILE__, __LINE__);
			}
		}
		
	}
	
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
		
		$owner_id = $subcure_id ? $subcure_id : $cure_id;
		mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$owner_id', owner='$photo_owner[cure_part]', ext='$ext', ord=1") 
			or Error(1, __FILE__, __LINE__);
		$photo_id = mysql_insert_id();
		
		$small="../images/$photo_dir[cure_part]/${photo_id}-s.$ext";
		if(is_file($small)) unlink($small);
		
		copy($photo, $small);
	}
	
	Header("Location: ".$url);
	exit;
}

if(@$savedescr)
{

	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	$price = escape_string(from_form(@$price));
	$price_en = escape_string(from_form(@$price_en));
	$price1 = escape_string(from_form(@$price1));
	$price1_en = escape_string(from_form(@$price1_en));
	$description = @$editor ?  escape_string(from_form(@$description1)) : escape_string(from_form(@$description));
	$description_en = @$editor_en ?  escape_string(from_form(@$description_en1)) : escape_string(from_form(@$description_en));
	$title = (int)@$title;
	
	$curestr_id = (int)@$curestr_id;
		
	$table = $subcure_id ? TABLE_CUREHOTEL : TABLE_CURESTRHOTEL;
	$wh = $subcure_id ? "cure_id=$subcure_id" : "curestr_id=$curestr_id";
	mysql_query("UPDATE $table SET name='$name' , name_en='$name_en', 
		price='$price' , price_en='$price_en', price1='$price1' , price1_en='$price1_en',
		description='$description', description_en='$description_en', title='$title'
		WHERE $wh AND page_id='$page_id'") 
	or Error(1, __FILE__, __LINE__);
	
	$url = "?p=$part&cure_id=$cure_id";
	if($subcure_id) $url .= "&subcure_id=$subcure_id";
	if($curestr_id) $url .= "&curestrd=$curestr_id";
	$url .= "&descr=$page_id";
	Header("Location: ".$url);
	exit;
}

if(@$savecurestr1)
{
	$curestr_id = (int)$curestr_id;
	$name = escape_string(from_form(@$name));
	$name_en = escape_string(from_form(@$name_en));
	$description = escape_string(from_form(@$description));
	$description_en = escape_string(from_form(@$description_en));
		
	mysql_query("UPDATE ".TABLE_CURESTR." SET name='$name' , name_en='$name_en', 
		description='$description', description_en='$description_en'
		WHERE curestr_id=$curestr_id") 
	or Error(1, __FILE__, __LINE__);
	
	if(is_array(@$sanat)) 
	{ 
		$sql_f = mysql_query("SELECT p.page_id, ch.curestr_id FROM ".TABLE_PAGE." p 
			LEFT JOIN ".TABLE_CURESTRHOTEL." ch ON (ch.page_id=p.page_id AND ch.curestr_id=$curestr_id)			
			WHERE p.parent=1 GROUP BY p.page_id ORDER BY p.ord") 
			or Error(1, __FILE__, __LINE__);				
		while($info = @mysql_fetch_array($sql_f))
		{		
			$page_id = 	(int)$info['page_id'];
			$count = (int)@$info['curestr_id'];
						
			$checked = in_array($info['page_id'], $sanat);
			
			if($count && !$checked)
				mysql_query("DELETE FROM ".TABLE_CURESTRHOTEL." WHERE curestr_id=$curestr_id AND page_id='$page_id'") 
				or Error(1, __FILE__, __LINE__);
			elseif(!$count && $checked)
				mysql_query("INSERT INTO ".TABLE_CURESTRHOTEL." SET curestr_id=$curestr_id, page_id='$page_id'") 
				or Error(1, __FILE__, __LINE__);
		}
	}
	else 
		mysql_query("DELETE FROM ".TABLE_CURESTRHOTEL." WHERE curestr_id=$curestr_id") 
		or Error(1, __FILE__, __LINE__);
		
	$url = "?p=$part&cure_id=$cure_id&curestrd=$curestr_id";
	
	
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
		
		$owner_id = $curestr_id;
		mysql_query("INSERT INTO ".TABLE_PHOTO." SET owner_id='$owner_id', owner='$photo_owner[curestr]', ext='$ext', ord=1") 
			or Error(1, __FILE__, __LINE__);
		$photo_id = mysql_insert_id();
		
		$small="../images/$photo_dir[curestr]/${photo_id}-s.$ext";
		if(is_file($small)) unlink($small);
		
		copy($photo, $small);
	}
	
	Header("Location: ".$url);
	exit;
}

if(@$del_cure)
{
	$del_cure = (int)$del_cure;
	$sql = mysql_query("SELECT ord, parent, curestr_id FROM ".TABLE_CURE." WHERE cure_id=$del_cure") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$curestr_id = (int)@$arr['curestr_id']; 
	$parent = (int)@$arr['parent']; 

	mysql_query("DELETE FROM ".TABLE_CURE." WHERE cure_id='$del_cure'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_CURE." SET ord=ord-1 WHERE parent=$parent AND curestr_id=$curestr_id AND ord>$ord") or Error(1, __FILE__, __LINE__);	
	
	mysql_query("DELETE FROM ".TABLE_CUREHOTEL." WHERE cure_id='$del_cure'") or Error(1, __FILE__, __LINE__);
	mysql_query("DELETE FROM ".TABLE_TABLE." WHERE cure_id='$del_cure'") or Error(1, __FILE__, __LINE__);
		
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
	
	$dir = $curestr_id ? $photo_dir['curestr'] : $photo_dir['cure_part'];
	
	@unlink("../images/$dir/$delphoto.$ext_b");
	@unlink("../images/$dir/${delphoto}-s.$ext");
	
	$url = ADMIN_URL."?p=$part&cure_id=$cure_id";
	if($subcure_id) $url .= "&subcure_id=$subcure_id";
	if($curestr_id) $url .= "&curestrd=$curestr_id";
		
	Header("Location: ".$url); 
	exit;
}

function check_cure($subcure_id, $parent=0)
{
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE partof=$subcure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return "-"; //return $count."р";
	
	/*$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CUREHOTEL." WHERE cure_id=$subcure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return $count."о";*/
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURE." WHERE parent=$subcure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) return "-"; //return $count."о";
	
	if($subcure_id < 1) return "-";
		
	return 0;
}

if(isset($loadcure))
{	
	$cure_id = (int)@$cure_id;
	$subcure_id = (int)@$subcure_id;
	$curestr_id = (int)@$curestr_id;
	$page_id = (int)@$page_id;
	$tab = (int)@$tab;
	
	$url = $subcure_id ? "?p=$part&cure_id=$cure_id&subcure_id=$subcure_id&descr=$page_id" :
		"?p=$part&cure_id=$cure_id&service&curestr_id=$curestr_id&page_id=$page_id";
	
	if((!$subcure_id && !$curestr_id)  || !$page_id)
	{
		Header("Location: ".$url); 
		exit;
	}
	
	$text_arr = explode("\n", from_form(@$text));
	
	$ff = $subcure_id ? "cure_id='$subcure_id'" : "curestr_id='$curestr_id'";
	$sql = mysql_query("SELECT table_id FROM ".TABLE_TABLE." WHERE $ff AND parent=0 AND page_id=$page_id") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	if(@$arr[0]) $table_id = $arr[0];
	else
	{
		mysql_query("INSERT INTO ".TABLE_TABLE." SET $ff, page_id=$page_id") or Error(1, __FILE__, __LINE__);
		$table_id = mysql_insert_id();
	}
	
	$sql = mysql_query("SELECT max(ord) FROM ".TABLE_TABLE." WHERE parent=$table_id") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0]+1;	
	
	foreach($text_arr as $v)
	{
		$list = explode("\t", $v);
		if(isset($list[0]) && $list[0]!='')
		{			
			$name = escape_string($list[0]);
			$price = str_replace("-00", '', escape_string(@$list[1]));
			
			mysql_query("INSERT INTO ".TABLE_TABLE." SET parent=$table_id, ord=$ord, tab=$tab, name='$name', name1='$price', 
				$ff, page_id=$page_id") 
				or Error(1, __FILE__, __LINE__);	
			$ord++;		
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

if(isset($inmenu))
{
	$cure_id = (int)@$cure_id;
	$subcure_id = (int)@$subcure_id;
	$curestr_id = (int)@$curestr_id;
	$page_id = (int)@$page_id;
	$inmenu = (int)@$inmenu;
	
	if($page_id)
	{
		$sql_f = mysql_query("SELECT count(*) FROM ".TABLE_CUREHOTEL." 	
			WHERE page_id=$page_id AND cure_id=$subcure_id") or Error(1, __FILE__, __LINE__);	
		$info = @mysql_fetch_array($sql_f);	 		
		if(!$info[0] && $inmenu)
			mysql_query("INSERT INTO ".TABLE_CUREHOTEL." SET cure_id=$subcure_id, page_id='$page_id'") 
			or Error(1, __FILE__, __LINE__);
		elseif($info[0] && !$inmenu)
			mysql_query("DELETE FROM ".TABLE_CUREHOTEL." WHERE cure_id=$subcure_id AND page_id='$page_id'") 
			or Error(1, __FILE__, __LINE__);
	}
	else
		mysql_query("UPDATE ".TABLE_CURE." SET inmenu='$inmenu' WHERE cure_id='$subcure_id'") or Error(1, __FILE__, __LINE__);
				
	$url = "?p=$part&cure_id=$cure_id&curestr_id=$curestr_id&service&page_id=$page_id";
	
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
	
	/*$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_CURESTR." WHERE parent=$curestr_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) 
	{
		$_SESSION['message'] = "Раздел не может быть удален, в нем есть подразделы";
		Header("Location: ".$url);
		exit;
	}*/
	
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
	mysql_query("DELETE FROM ".TABLE_CURESTR." WHERE parent='$curestr_id'") or Error(1, __FILE__, __LINE__);	
		
	Header("Location: ".$url);
	exit;
}

if(isset($addtable) && ($subcure_id || $page_id))
{	
	$addtable = (int)@$addtable;
	$page_id = (int)@$page_id;
	$curestr_id = (int)@$curestr_id;
	$url = $subcure_id ? "?p=$part&cure_id=$cure_id&subcure_id=$subcure_id&descr=$page_id" :
		"?p=$part&cure_id=$cure_id&service&curestr_id=$curestr_id&page_id=$page_id";
	
	$ff = $subcure_id ? "cure_id=$subcure_id" : "curestr_id=$curestr_id";
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_TABLE." WHERE parent=$addtable AND $ff AND page_id=$page_id") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr[0] + 1;
	
	mysql_query("INSERT INTO ".TABLE_TABLE." SET ord=$ord, parent=$addtable, $ff, page_id=$page_id") or Error(1, __FILE__, __LINE__);
	$id = mysql_insert_id();
	mysql_query("INSERT INTO ".TABLE_TABLE." SET ord=1, parent=$id, $ff, page_id=$page_id") or Error(1, __FILE__, __LINE__);
		
	Header("Location: $url"."#link$id");
	exit;
}

if(@$savetable)
{
	$table_id = (int)@$table_id;
	$page_id = (int)@$page_id;
	$curestr_id = (int)@$curestr_id;
	$url = $subcure_id ? "?p=$part&cure_id=$cure_id&subcure_id=$subcure_id&descr=$page_id" :
		"?p=$part&cure_id=$cure_id&service&curestr_id=$curestr_id&page_id=$page_id";
	
	$sql = mysql_query("SELECT ord, parent, cure_id FROM ".TABLE_TABLE." WHERE table_id='$table_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$oldord = (int)@$arr['ord'];
	$parent = (int)@$arr['parent'];
	$subcure_id = (int)@$arr['cure_id'];
	
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_TABLE." WHERE parent=$parent AND cure_id=$subcure_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	
	$ord = (int)@$ord;
	if($ord < 1 || $ord > $count) 
	{
		$_SESSION['message'] = "Неверное значение порядкового номера (от 1 до $count)";
		Header("Location: ".$url);
		exit;
	}
	
	$name = escape_string(from_form(@$name));
	$name1 = escape_string(from_form(@$name1));
	$title = (int)@$title;
	$tab = (int)@$tab;
	$rowspan = (int)@$rowspan;
	
	mysql_query("UPDATE ".TABLE_TABLE." SET name='$name', name1='$name1', title='$title', tab='$tab', ord='$ord', rowspan='$rowspan' ".
				"WHERE table_id='$table_id'") or Error(1, __FILE__, __LINE__);
				
	if($ord > $oldord) mysql_query("UPDATE ".TABLE_TABLE." SET ord=ord-1 ".
		"WHERE ord>'$oldord' AND ord<='$ord' AND parent=$parent AND cure_id=$subcure_id AND  page_id='$page_id' AND  table_id!='$table_id'") 
		or Error(1, __FILE__, __LINE__);
	elseif($ord < $oldord) mysql_query("UPDATE ".TABLE_TABLE." SET ord=ord+1 ".
		"WHERE ord>='$ord' AND ord<'$oldord' AND parent=$parent AND cure_id=$subcure_id AND  page_id='$page_id' AND table_id!='$table_id'") 
		or Error(1, __FILE__, __LINE__);
	
	if($parent) $url .= "#link$parent";
	
	Header("Location: ".$url);
	exit;
}


if(@$deltable)
{
	$table_id = (int)@$table_id;
	
	$sql = mysql_query("SELECT ord, parent, cure_id, page_id, curestr_id FROM ".TABLE_TABLE." 
		WHERE table_id=$table_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$ord = (int)@$arr['ord']; 
	$parent = (int)@$arr['parent']; 
	$subcure_id = (int)@$arr['cure_id'];
	$page_id = (int)@$arr['page_id'];
	$curestr_id = (int)@$arr['curestr_id'];
	
	$url = $subcure_id ? "?p=$part&cure_id=$cure_id&subcure_id=$subcure_id&descr=$page_id" :
		"?p=$part&cure_id=$cure_id&service&curestr_id=$curestr_id&page_id=$page_id";
	
	if($parent) $url .= "#link$parent";
	
	/*$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_TABLE." WHERE parent=$table_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$count = (int)@$arr[0];
	if($count) 
	{
		$_SESSION['message'] = "Раздел не может быть удален, в нем есть подразделы";
		Header("Location: ".$url);
		exit;
	}*/
			
	mysql_query("DELETE FROM ".TABLE_TABLE." WHERE table_id='$table_id'") or Error(1, __FILE__, __LINE__);
	mysql_query("UPDATE ".TABLE_TABLE." SET ord=ord-1 WHERE parent=$parent AND page_id=$page_id AND cure_id=$subcure_id AND ord>$ord") 
		or Error(1, __FILE__, __LINE__);	
	mysql_query("DELETE FROM ".TABLE_TABLE." WHERE parent='$table_id'") or Error(1, __FILE__, __LINE__);
		
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
		if(@$curestrd)
		{
			$replace['name'] = HtmlSpecialChars($replace['name']);		
			
			$curestr_id = (int)$curestrd;
			$sql = mysql_query("SELECT name, name_en, description, description_en FROM ".TABLE_CURESTR." WHERE curestr_id=$curestr_id") 
				or Error(1, __FILE__, __LINE__);
			$curestr = @mysql_fetch_array($sql);

			$replace['curestrd'] = $curestr_id;
				
			if(@$descr)
			{
				$page_id = (int)@$descr;
				$replace['descr'] = $page_id;
				
				$sql = mysql_query("SELECT cr.name, cr.name_en, cr.description, cr.description_en,  p.name as pname FROM ".TABLE_CURESTRHOTEL." cr 
					LEFT JOIN ".TABLE_PAGE." p ON p.page_id=cr.page_id
					WHERE cr.curestr_id=$curestr_id AND cr.page_id=$page_id") 
					or Error(1, __FILE__, __LINE__);
				$info = @mysql_fetch_array($sql);
					
				$curestr['page_id'] =  $page_id;
				$curestr['pname'] =  HtmlSpecialChars($info['pname']);
				$curestr['prname'] =  HtmlSpecialChars($info['name']);
				$curestr['prname_en'] =  HtmlSpecialChars($info['name_en']);
				$curestr['description'] = HtmlSpecialChars($info['description']);
				$curestr['description_en'] = HtmlSpecialChars($info['description_en']);
				$tinymce_elements = 'description, description_en';
				$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));				
				
			}
			else
			{
				
				$sql_photos = mysql_query("SELECT photo_id, ext, ext_b, ord FROM ".TABLE_PHOTO.
						" WHERE owner_id=$curestr_id AND owner='$photo_owner[curestr]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
				$replace['photo'] = '';
				if($arr_photos = @mysql_fetch_array($sql_photos)) {
					$photo_id = $arr_photos['photo_id'];
					$ext = $arr_photos['ext'];
					$w_small=0; $h_small=0;
					$f="../images/$photo_dir[curestr]/${photo_id}-s.$ext";
					list($w_small, $h_small) = @getimagesize($f);
					$replace['photo'] = $f;
					$replace['smallsize'] = "width='$w_small' height='$h_small'";
					$replace['photo_del_link'] = "?p=$part&delphoto=$photo_id&cure_id=$cure_id&curestr_id=$curestr_id";
				}		
				
				$tinymce_elements = 'description, description_en';
				$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
				
				
				$curehotel = array();
				$sql = mysql_query("SELECT page_id FROM ".TABLE_CURESTRHOTEL." WHERE curestr_id=$curestr_id") 
					or Error(1, __FILE__, __LINE__);
				while($info = @mysql_fetch_array($sql)) $curehotel[$info[0]] = 1;
					
				$sql_f = mysql_query("SELECT p.page_id, p.name, ct.name as city FROM ".TABLE_PAGE." p 
					LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
					WHERE p.parent=1 AND p.public='1' ORDER BY p.ord") 
					or Error(1, __FILE__, __LINE__);
				
				$i = 0;	
				$page_box = array();
				while($info = @mysql_fetch_array($sql_f))
				{ 
					$i++; 
					$ch = isset($curehotel[$info['page_id']]) ? 'checked' : '';
						$info['name'] .= " ($info[city])";
					
					$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 
							'checked'=>$ch, 'name'=>$info['name']);
				}			
				$replace['page_box'] = $page_box;
				
				$curestr['name'] = HtmlSpecialChars($curestr['name']);
				$curestr['name_en'] = HtmlSpecialChars($curestr['name_en']);
				$curestr['description'] = HtmlSpecialChars($curestr['description']);
				$curestr['description_en'] = HtmlSpecialChars($curestr['description_en']);
			
			}
			$replace['curestr'] = $curestr;
			
		}
		else
		{
		
			$replace['name'] = HtmlSpecialChars($replace['name']);		
			$replace['name_en'] = HtmlSpecialChars($replace['name_en']);
			$replace['name_extra'] = HtmlSpecialChars($replace['name_extra']);		
			$replace['name_extra_en'] = HtmlSpecialChars($replace['name_extra_en']);	
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
			if($cure_id==5 || $cure_id==8 || $cure_type==1)
			{
				$replace['description'] = HtmlSpecialChars($replace['description']);
				$replace['description_en'] = HtmlSpecialChars($replace['description_en']);
				$tinymce_elements = 'description, description_en';
				$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
				
			}
			if(!$replace['partof'])
			{
				$curehotel = array();
				$page_box = array();
				
				$sql = mysql_query("SELECT page_id FROM ".TABLE_CUREHOTEL." WHERE cure_id=$cure_id") 
					or Error(1, __FILE__, __LINE__);
				while($info = @mysql_fetch_array($sql)) $curehotel[$info[0]] = 1;
					
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
					$info['name'] .= " ($info[city])";
					
					$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 
							'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
				}
					
				$replace['page_box'] = $page_box;
				
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

					$replace['page_id'] = $page_id = (int)@$page_id;					
					$replace['san_select'] = mysql_select('page_id', 
						"SELECT p.page_id, concat(p.name, ' ', ct.name) as name FROM ".TABLE_PAGE." p 
						LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id WHERE p.parent=1 AND page_id!=663 ORDER BY p.ord",	
						$page_id, 1, 
						"id=\"s_id\" onchange=\"window.location='?p=cure&cure_id=$cure_id&service&curestr_id=$curestr_id&page_id='+this.value\"");
							
					if($page_id && $curestr_id)
					{
						$sql = mysql_query("SELECT * FROM ".TABLE_TABLE." 
							WHERE parent=0 AND curestr_id=$curestr_id AND page_id=$page_id ORDER BY ord") 
						or Error(1, __FILE__, __LINE__);
						
						$tables = array(); 
						while($info = @mysql_fetch_array($sql))
						{ 
							$info['name'] = HtmlSpecialChars($info['name']);		
							
							$sql1 = mysql_query("SELECT * FROM ".TABLE_TABLE." WHERE parent=$info[table_id] ORDER BY ord") 
							or Error(1, __FILE__, __LINE__);	
							$list = array(); 
							while($info1 = @mysql_fetch_array($sql1))
							{ 
								$info1['name'] = HtmlSpecialChars($info1['name']);		
								$info1['name1'] = HtmlSpecialChars($info1['name1']);							
								$list[] = $info1;
							}
							$info['list'] = $list;
						
							$tables[] = $info;
						}
					
						$replace['tables'] = $tables;
					}
					
					$sql = mysql_query("SELECT curestr_id, name FROM ".TABLE_CURESTR." WHERE parent=0 AND cure_id=$cure_id ORDER BY ord") 
						or Error(1, __FILE__, __LINE__);
					
					$select =  "<select name=\"curestr_id\" id=\"cs_id\" ".
						"onchange=\"document.location='?p=$part&cure_id=$cure_id&service&page_id=$page_id&curestr_id='+this.value\">\n";
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
						$where = "c.parent=$cure_id";
						if($curestr_id) $where .= " AND c.curestr_id=$curestr_id";
						$ord = $curestr_id ? 'c.ord' : 'c.name';
						$ltable = ''; $lfield = '';
						if($page_id)
						{
							$ltable = "LEFT JOIN ".TABLE_CUREHOTEL." ch ON (ch.page_id=$page_id AND ch.cure_id=c.cure_id)";
							$lfield = ", ch.cure_id as chotel";
						}
						$sql = mysql_query("SELECT c.cure_id, c.name, c.inmenu $lfield FROM ".TABLE_CURE." c 
							$ltable
							WHERE  $where ORDER BY $ord") 
							or Error(1, __FILE__, __LINE__);
						
						$cures = array(); 
						while($info = @mysql_fetch_array($sql))
						{ 
							$info['name'] = $info['name'] ? HtmlSpecialChars($info['name']) : NONAME;	
							
							$info['del_link'] = ""; $info['icount'] = 0;
							if($i=check_cure($info['cure_id'])) $info['icount'] = $i;
							else $info['del_link'] = "?p=$part&del_cure=$info[cure_id]&cure_id=$cure_id&curestr_id=$curestr_id&page_id=$page_id";
						
							if($page_id)
							{
								$info['edit_link'] = $info['chotel'] ? "?p=$part&cure_id=$cure_id&subcure_id=$info[cure_id]&descr=$page_id" : '';
								
								$info['inmenu'] = $info['chotel'];
								$info['inmenu_link'] = "?p=$part&cure_id=$cure_id&service&curestr_id=$curestr_id".
														"&subcure_id=$info[cure_id]&page_id=$page_id&inmenu=";
								$info['inmenu_link'] .= $info['chotel'] ? "0" : "1";
								
								$info['inmenu_alt'] = $info['chotel'] ? "убрать из списка" : "добавить в список";
							}
							else
							{
								$info['edit_link'] = "?p=$part&cure_id=$cure_id&subcure_id=$info[cure_id]";
								
								$info['inmenu_link'] = "?p=$part&cure_id=$cure_id&service&curestr_id=$curestr_id&subcure_id=$info[cure_id]&inmenu=";
								$info['inmenu_link'] .= $info['inmenu'] ? "0" : "1";
								
								$info['inmenu_alt'] = $info['inmenu'] ? "убрать из меню основного сайта" : "добавить в меню основного сайта";
							}
							
							$cures[] = $info;
						}
						$replace['cure_list'] = $cures;
					}
				}
				
				else
				{
					$sql = mysql_query("SELECT c.* FROM ".TABLE_CURESTR." c 
						WHERE c.parent=0 AND c.cure_id=$cure_id ORDER BY c.ord") 
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
							$info1['descrlink'] = $cure_type==2 ? 	"?p=cure&curestrd=$info1[curestr_id]&cure_id=$cure_id" : '';				
							$list[] = $info1;
						}
						$info['list'] = $list;
						
						//$info['descrlink'] = !count($list) ? "?p=cure&curestrd=$info[curestr_id]&cure_id=$cure_id" : '';	
						$info['descrlink'] = "?p=cure&curestrd=$info[curestr_id]&cure_id=$cure_id";	
					
						$cures[] = $info;
					}
				
					$replace['curestrs'] = $cures;
				}
			}
		
		}
	}
	
	$replace['subcure_id'] = $subcure_id;
	
	if($subcure_id)
	{	
		$sql = mysql_query("SELECT * FROM ".TABLE_CURE." WHERE cure_id=$subcure_id") or Error(1, __FILE__, __LINE__);
		$subcure = @mysql_fetch_array($sql);

		$sql1 = mysql_query("SELECT name FROM ".TABLE_CURE." WHERE cure_id=$cure_id") 
			or Error(1, __FILE__, __LINE__);		
		$info = @mysql_fetch_array($sql1);
		$subcure['cure_name'] = $info['name'] ? $info['name'] : NONAME;	
		
		if(@$descr)
		{
			$page_id = (int)@$descr;
			$replace['descr'] = $page_id;
			
			$sql = mysql_query("SELECT cr.name, cr.name_en, cr.description, cr.description_en, 
				cr.price, cr.price_en, cr.price1, cr.price1_en, cr.title, p.name as pname FROM ".TABLE_CUREHOTEL." cr 
				LEFT JOIN ".TABLE_PAGE." p ON p.page_id=cr.page_id
				WHERE cr.cure_id=$subcure_id AND cr.page_id=$page_id") 
				or Error(1, __FILE__, __LINE__);
			$info = @mysql_fetch_array($sql);
				
			$subcure['list_link'] = "?p=cure&cure_id=$cure_id&page_id=$page_id";
			if($subcure['curestr_id']) $subcure['list_link'] .= "&service&&curestr_id=".$subcure['curestr_id'];
			$subcure['page_id'] =  $page_id;
			$subcure['pname'] =  HtmlSpecialChars($info['pname']);
			$subcure['prname'] =  HtmlSpecialChars($info['name']);
			$subcure['prname_en'] =  HtmlSpecialChars($info['name_en']);
			$subcure['price'] =  HtmlSpecialChars($info['price']);
			$subcure['price_en'] =  HtmlSpecialChars($info['price_en']);
			$subcure['price1'] =  HtmlSpecialChars($info['price1']);
			$subcure['price1_en'] =  HtmlSpecialChars($info['price1_en']);
			$subcure['description'] = HtmlSpecialChars($info['description']);
			$subcure['description_en'] = HtmlSpecialChars($info['description_en']);
			$subcure['title'] =  $info['title'];
			$tinymce_elements = 'description, description_en';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
			
			
			$sql = mysql_query("SELECT * FROM ".TABLE_TABLE." WHERE parent=0 AND cure_id=$subcure_id AND page_id=$page_id ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
			
			$tables = array(); 
			while($info = @mysql_fetch_array($sql))
			{ 
				$info['name'] = HtmlSpecialChars($info['name']);		
				
				$sql1 = mysql_query("SELECT * FROM ".TABLE_TABLE." WHERE parent=$info[table_id] ORDER BY ord") 
				or Error(1, __FILE__, __LINE__);	
				$list = array(); 
				while($info1 = @mysql_fetch_array($sql1))
				{ 
					$info1['name'] = HtmlSpecialChars($info1['name']);		
					$info1['name1'] = HtmlSpecialChars($info1['name1']);							
					$list[] = $info1;
				}
				$info['list'] = $list;
			
				$tables[] = $info;
			}
		
			$replace['tables'] = $tables;
		}
		else
		{
			if($cure_type==7)
			{
				$sql_photos = mysql_query("SELECT photo_id, ext, ext_b, ord FROM ".TABLE_PHOTO.
						" WHERE owner_id=$subcure_id AND owner='$photo_owner[cure_part]' ORDER BY ord") or Error(1, __FILE__, __LINE__);
				$replace['photo'] = '';
				if($arr_photos = @mysql_fetch_array($sql_photos)) {
					$photo_id = $arr_photos['photo_id'];
					$ext = $arr_photos['ext'];
					$w_small=0; $h_small=0;
					$f="../images/$photo_dir[cure_part]/${photo_id}-s.$ext";
					list($w_small, $h_small) = @getimagesize($f);
					$replace['photo'] = $f;
					$replace['smallsize'] = "width='$w_small' height='$h_small'";
					$replace['photo_del_link'] = "?p=$part&delphoto=$photo_id&cure_id=$cure_id&subcure_id=$subcure_id";
				}	
			}	
			
			$subcure['list_link'] = "?p=cure&cure_id=$cure_id";
			if($subcure['curestr_id']) $subcure['list_link'] .= "&service&&curestr_id=".$subcure['curestr_id'];
			$subcure['ord_select'] = $cure_type==2 ? ord_select("SELECT name FROM ".TABLE_CURE.
				" WHERE parent=$cure_id AND curestr_id=$subcure[curestr_id] AND cure_id!=$subcure_id ORDER BY ord", 'ord', $subcure['ord']) : 
					ord_select("SELECT name FROM ".TABLE_CURE.
				" WHERE parent=$cure_id AND cure_id!=$subcure_id ORDER BY ord", 'ord', $subcure['ord']);
			$subcure['name'] = HtmlSpecialChars($subcure['name']);
			$subcure['name_en'] = HtmlSpecialChars($subcure['name_en']);
			$subcure['anons'] = HtmlSpecialChars($subcure['anons']);
			$subcure['anons_en'] = HtmlSpecialChars($subcure['anons_en']);
			$subcure['profile'] = HtmlSpecialChars($subcure['profile']);
			$subcure['profile_en'] = HtmlSpecialChars($subcure['profile_en']);
			$subcure['description'] = HtmlSpecialChars($subcure['description']);
			$subcure['description_en'] = HtmlSpecialChars($subcure['description_en']);
			$tinymce_elements = 'description, description_en, profile, profile_en';
			$tinymce_head = get_template('templ/tinymce_head.htm', array('tinymce_elements'=>$tinymce_elements));
			
			$curehotel = array();
			$page_box = array();
			if($cure_type<3 || $cure_type==4)
			{
				$field = $cure_type==4 ? 'description, description_en' : 'price, price1';
				$sql = mysql_query("SELECT page_id, $field FROM ".TABLE_CUREHOTEL." WHERE cure_id=$subcure_id") 
					or Error(1, __FILE__, __LINE__);
				while($info = @mysql_fetch_array($sql)) 
					$curehotel[$info[0]] = $cure_type==4 ? array($info['description'], $info['description_en'])
						: array($info['price'], $info['price1']);
					
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
					
					if($cure_type==4)
					{
						if(isset($curehotel[$info['page_id']])) list($description, $description_en) = $curehotel[$info['page_id']];
						else {$description=''; $description_en='';}
						$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 
								'description'=>htmlspecialchars($description), 'description_en'=>htmlspecialchars($description_en),
								'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
					}
					else
					{
						if(isset($curehotel[$info['page_id']]))  list($price, $price1) = $curehotel[$info['page_id']];
						else {$price=''; $price1='';}
						$page_box[] = array('i'=>$i, 'page_id'=>$info['page_id'], 
						 		'price'=>htmlspecialchars($price), 'price1'=>htmlspecialchars($price1),
								'newcol'=>$newcol, 'checked'=>$ch, 'name'=>$info['name']);
					}
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
			
			$subcure['podrazdel'] = 0;
			if($cure_type==1 && $cure_id!=1 && $cure_id==$subcure['parent'])
			{
				$subcure['podrazdel'] = 1;
				
				$ord = 'ord';
				$sql = mysql_query("SELECT cure_id, name FROM ".TABLE_CURE." WHERE parent=$subcure_id ORDER BY $ord") 
					or Error(1, __FILE__, __LINE__);
				
				$cures = array(); 
				while($info = @mysql_fetch_array($sql))
				{ 
					$info['name'] = $info['name'] ? HtmlSpecialChars($info['name']) : NONAME;	
					
					$info['del_link'] = ""; $info['icount'] = 0;
					if($i=check_cure($info['cure_id'])) $info['icount'] = $i;
					else $info['del_link'] = ADMIN_URL."?p=$part&del_cure=$info[cure_id]&cure_id=$cure_id&subcure_id=$subcure_id";
				
					$info['edit_link'] = ADMIN_URL."?p=$part&cure_id=$cure_id&subcure_id=$info[cure_id]";
					
					$cures[] = $info;
				}
			
				$replace['cure_list'] = $cures;
			}
			elseif($cure_id!=$subcure['parent'])
			{		
				$sql1 = mysql_query("SELECT cure_id, name FROM ".TABLE_CURE." WHERE cure_id=$subcure[parent]") 
					or Error(1, __FILE__, __LINE__);		
				$info = @mysql_fetch_array($sql1);
				$subcure['subcure_parent'] = $info['name'] ? $info['name'] : NONAME;	
				$subcure['subcure_link'] = "?p=$part&cure_id=$cure_id&subcure_id=$subcure[parent]";
			}
		}
			
		
		$replace['subcure'] = $subcure;
	}
	
	//$content = get_template("templ/cure$cure_type.htm", $replace);
	$content = get_template("templ/cure.htm", $replace);
	return;
}


?>