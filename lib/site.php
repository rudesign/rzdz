<?php

if($extrasite_id){

    $query = "SELECT facebook, vk FROM ".TABLE_PAGE." WHERE site={$extrasite_id} LIMIT 1";
    if($res = mysql_query($query)){
        if($row = mysql_fetch_assoc($res)){
            $facebook = $row["facebook"];
            $vk = $row["vk"];
        }
    }
}

$page = array();
$query = "SELECT p.*, o.opinion_id, COUNT(*) as opinioncount FROM ".TABLE_PAGE." p
			LEFT JOIN ".TABLE_OPINION." o ON (o.page_id=p.page_id AND o.public=1)
			 WHERE p.page_id='$page_id' AND p.public='1'
			GROUP BY p.page_id";

$sql = mysql_query($query) or Error(1, __FILE__, __LINE__);

if($arr_page = @mysql_fetch_array($sql))
{
    $page['name'] = HtmlSpecialChars($arr_page["name$englang"], null, 'cp1251');
	if(!$page['name']) $page['name'] = NONAME;
	
	$page['description'] = $arr_page["description$englang"];
	$page['parent'] = $arr_page["parent"];	
	$page['dir_id'] = $arr_page['dir_id'];	
	


	if(ereg("\\[\\:form([[:digit:]]+)\\:\\]", $page['description'], $F))
	{
		$form_id = $F[1];
		$order_url = "$lprefix/$request[0]/";
		if($request[1]) $order_url .= "$request[1]/";
		if($request[2]) $order_url .= "$request[2]/";
		
		require 'lib/order2.php';
		
		$page['description'] = str_replace("[:form$form_id:]", $form_content, $page['description']);
	}

	if(ereg("\\[\\:cure\\:\\]", $page['description'], $F))
	{
		require 'lib/cure.php';
		$width100 = 1;
		
		$page['description'] = str_replace("[:cure:]", $cure_content, $page['description']);
	}
	
	
	if(ereg("(<p>)?\\[\\:faq\\:\\](<\\/p>)?", $page['description'], $F))
	{
		$page_url = "$lprefix/$request[0]/";
		if($request[1]) $page_url .= "$request[1]/";
		if($request[2]) $page_url .= "$request[2]/";
		
		require 'lib/gest.php';
		
		$page['faq'] = 1;
		
		$page['description'] = str_replace($F[0], $form_content, $page['description']);
	} 
	else $page['faq'] = 0;
		
	$mainlink = "$lprefix/$request[0]/";
	if($request[1]) $mainlink .= "$request[1]/";
	if($request[2]) $mainlink .= "$request[2]/";
	
	$page['opinioncount'] = $arr_page['opinion_id'] ? $arr_page['opinioncount'] : 0;
	$page['opinionlink'] = $mainlink."#opinion";
	$page['opinion']=	$arr_page['opinion'];
	
	$where = "g.public=1 AND g.page_id=$page_id ";
	$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_OPINION." g WHERE $where") or Error(1, __FILE__, __LINE__);
	$arr1 = mysql_fetch_array($sql);
	$page['all'] = $all = $arr1[0];
	
	list($limit, $page['pages']) = user_pages($all, $mainlink."?", 
		$settings['opinion_anons_count'], '#opinion');

	$opinion_list = array();
	$sql = mysql_query("
		SELECT 
			g.* 
		FROM 
			".TABLE_OPINION." g 
		WHERE
			$where
		ORDER BY 
			g.date desc, g.opinion_id desc
		LIMIT $limit") or Error(1, __FILE__, __LINE__);
	while($arr1 = @mysql_fetch_array($sql))
	{ 
		$arr1['name'] = $arr1['client_name'];
		$arr1['text'] = nl2br(HtmlSpecialChars($arr1['text'], null, 'cp1251'));
		$arr1['date'] = sql_to_text_date($arr1['date'], 1);
		$opinion_list[] = $arr1;
	}
	$page['opinion_list'] = $opinion_list;
	$page['page_id'] = $page_id;
	
	$arr_data = @unserialize($_SESSION['opinion_data']);
	
	$page['u_name'] = @HtmlSpecialChars($arr_data['u_name'], null, 'cp1251');
	$page['u_email'] = @HtmlSpecialChars($arr_data['u_email'], null, 'cp1251');
	$page['u_phone'] = @HtmlSpecialChars($arr_data['u_phone'], null, 'cp1251');
	$page['opinion_text'] = @HtmlSpecialChars($arr_data['text'], null, 'cp1251');
	
	$photos=array(); 
	if($arr_page['gallery_id'] || $arr_page['parent']==1 || $extrasite_id)
	{
		$s = !$arr_page['gallery_id'] ? 'item' : 'gallery';
		$owner_id = !$arr_page['gallery_id'] ? $arr_page['page_id'] : $arr_page['gallery_id'];
		$limit = $arr_page['parent']==1 ? 3 : $arr_page['photocount'];
        $query = "SELECT photo_id, ext, ext_b, alt$englang as alt FROM ".TABLE_PHOTO." WHERE owner_id=$owner_id AND owner='$photo_owner[$s]' ORDER BY ord LIMIT $limit";

		$sql_photos = mysql_query($query) or Error(1, __FILE__, __LINE__);

		$count = mysql_num_rows($sql_photos);
		$i=0;
		while($arr_photos = @mysql_fetch_array($sql_photos)) {
			$photo_id = $arr_photos['photo_id'];
			$ext = $arr_photos['ext'];
			$ext_b = $arr_photos['ext_b'];
			$alt = HtmlSpecialChars($arr_photos['alt'], null, 'cp1251');
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

        $page['owner_id'] = $owner_id;
        $page['type'] = $arr_page['gallery_id'] ? 1 : 2;
	}
	$page['photos'] = $photos;
	
	$page['contacts'] = @$arr_page['contacts'];	
	$page['navig'] = $navig;	
	
	$page['city_link'] = '';
	if($arr_page['parent']==1)
	{
		$sql = mysql_query("
			SELECT 
				c.name as city_name,  d1.dir as city_dir, d2.dir as pcity_dir
			FROM 
				".TABLE_CITY." c
				LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id) 
				LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=d1.parent) 
			WHERE 
				d1.dir_id IS NOT NULL AND 
				d2.dir_id IS NOT NULL AND c.city_id='$arr_page[city_id]'") or Error(1, __FILE__, __LINE__);
		$info = mysql_fetch_array($sql);
		$page['city_link'] = "$lprefix/$info[pcity_dir]/$info[city_dir]/";
	}
	
	$content = get_template("templ/page.htm", $page);
	
	get_page_info('', @$page['dir_id'], @$page['name']);


}else page404();

?>