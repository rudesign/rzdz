<?php

if($request[1])
{
	$page_dir = mysql_escape_string($request[1]);

	$sql = mysql_query("
		SELECT 
			c.city_id, c.name, c.description, c.dir_id, c.gallery_id, c.photocount, d1.dir, d2.dir as pdir
		FROM 
			".TABLE_CITY." c
			LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND d1.dir='$page_dir') 
			LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=d1.parent) 
		WHERE 
			d1.dir_id IS NOT NULL AND 
			d2.dir_id IS NOT NULL ") or Error(1, __FILE__, __LINE__);
	if($arr_page = @mysql_fetch_array($sql))
	{
	
		$page['name'] = HtmlSpecialChars($arr_page["name"]);
		if(!$page['name']) $page['name'] = NONAME;
		
		$page['description'] = $arr_page["description"];
		$page['city_id'] = $arr_page["city_id"];
			
		$photos=array(); 
		if($arr_page['gallery_id'])
		{
			$s = 'gallery';
			$owner_id = $arr_page['gallery_id'];
			$limit = $arr_page['photocount'];
			$sql_photos = mysql_query("SELECT photo_id, ext, ext_b, alt FROM ".TABLE_PHOTO.
					" WHERE owner_id=$owner_id AND owner='$photo_owner[$s]' ORDER BY ord LIMIT $limit") 
					or Error(1, __FILE__, __LINE__);
					
			$count = mysql_num_rows($sql_photos);
			$i=0;
			while($arr_photos = @mysql_fetch_array($sql_photos)) {
				$photo_id = $arr_photos['photo_id'];
				$ext = $arr_photos['ext'];
				$ext_b = $arr_photos['ext_b'];
				$alt = HtmlSpecialChars($arr_photos['alt']);
				$w_big=0; $h_big=0; $w_small=0; $h_small=0; $bigsize = ""; $bigphoto = ""; $biglink = ""; 
				if(is_file($f="/images/$photo_dir[$s]/$photo_id.$ext_b"))
				{
					$bigphoto = "/images/$photo_dir[$s]/$photo_id.$ext_b";
					@list($w_big, $h_big) = @getimagesize($f);
					if($w_big && $h_big) $bigsize = "$w_big,$h_big";
				}
				if(is_file($f="/images/$photo_dir[$s]/${photo_id}-s.$ext"))
				{
					$i++; 
					list($w_small, $h_small) = @getimagesize($f);
				
					$photos[] = array('i'=>$i, 'bigsize'=>$bigsize, 'bigphoto'=>$bigphoto, 'bigfile'=>"$photo_id.$ext_b",
										'smallsize'=>"width='$w_small' height='$h_small'", 
										'photo'=>$f, 'alt'=>$alt, 'ext_b'=>$ext_b);
				}
			}
		}
		$page['photos'] = $photos;

		$content = get_template("templ/city.htm", $page); 
		
		$navig[] = array('name'=>CITIES_PAGE, 'link'=>"$lprefix/$arr_page[pdir]/");
		$navig[] = array('name'=>$page['name'], 'link'=>'');
		
		get_page_info('', @$arr_page['dir_id'], @$page['name']); 
	}
	else page404();

}
else
{
	/*$sql = mysql_query("
		SELECT 
			c.name,  d1.dir as city_dir, d2.dir as pcity_dir
		FROM 
			".TABLE_CITY." c
			LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id) 
			LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=d1.parent) 
		WHERE 
			d1.dir_id IS NOT NULL AND 
			d2.dir_id IS NOT NULL
		ORDER by
			c.ord") or Error(1, __FILE__, __LINE__);
	
	$citys = array(); 
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
		
		$info['link'] = "$lprefix/$info[pcity_dir]/$info[city_dir]/";
	
		$citys[] = $info;
	}

	$replace['citys'] = $citys;
	$replace['city_id'] = 0;*/
	
	$sql_text = "SELECT text FROM ".TABLE_DIR." WHERE dir_id=7";
	$sql = mysql_query($sql_text) or Error(1, __FILE__, __LINE__); 
	$arr = @mysql_fetch_array($sql);
	$replace['description'] = @$arr[0];
	
	
	$content = get_template("templ/city.htm", $replace); 
	
	get_page_info($part); 
		
		
	$navig[] = array('name'=>CITIES_PAGE, 'link'=>"");

}
		

?>