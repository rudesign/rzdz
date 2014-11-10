<?php

$gallery_id = (int)$request[1];
$photo_id = (int)$request[2];

if(!$gallery_id)
{
	$sql = mysql_query("
		SELECT 
			gallery_id
		FROM 
			".TABLE_GALLERY." 
		WHERE 
			public='1'
		ORDER BY
			ord
		LIMIT
			1") or Error(1, __FILE__, __LINE__); 
	if($arr = @mysql_fetch_array($sql))	$gallery_id = $arr['gallery_id'];
}
	
if($gallery_id)
{
	$sql = mysql_query("SELECT name FROM ".TABLE_GALLERY." WHERE gallery_id='$gallery_id'") or Error(1, __FILE__, __LINE__); 
	$arr = @mysql_fetch_array($sql);
	$gallery_name = @$arr['name']; 
	
	$gallery = array();
	
		
	$sql_photos = mysql_query("SELECT f.* FROM ".TABLE_PHOTO." f ".
			"WHERE owner_id='$gallery_id' AND owner='$photo_owner[gallery]' ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
	
	$gallery['photo'] = '';
	$photos=array(); 
	$i=0;
	while($arr_photos = @mysql_fetch_array($sql_photos)) {
		if(!$photo_id) $photo_id = $arr_photos['photo_id'];
		
		$ext = $arr_photos['ext'];
		$ext_b = $arr_photos['ext_b'];
		$alt = HtmlSpecialChars($arr_photos["alt"]);
		
		$sel = ($arr_photos['photo_id'] == $photo_id) ? 1 : 0;
		$i++; 
		
		$f="images/$photo_dir[gallery]/$arr_photos[photo_id]-s.$ext";
		list($w_small, $h_small) = @getimagesize($f); 
		
		if($sel)
		{
			$bf="images/$photo_dir[gallery]/$arr_photos[photo_id].$ext_b";
			$gallery['photo'] = $bf;
			list($w, $h) = @getimagesize($bf);
			$gallery['photosize'] = "width='$w' height='$h'";
			$gallery['photoname'] = $alt; 
		}
		
		$biglink = "$lprefix/$part/$gallery_id/$arr_photos[photo_id]/";
		
		$photos[] = array('i'=>$i, 'biglink'=>$biglink, 
						'smallsize'=>"width='$w_small' height='$h_small'", 
						'photo'=>$f, 'alt'=>$alt);

		$gallery['photo_count'] = $i;
	}
	$gallery['photos'] = $photos;
	
	$content = get_template("templ/gallery.htm", $gallery);
	
}

get_page_info('gallery', 0, @$gallery_name, 1); 

?>