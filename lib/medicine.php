<?php

$page_name = '';

if(!$extrasite_id )
{
	$page_dir = mysql_escape_string($request[0]);
	$sql = mysql_query("
		SELECT 
			i.page_id, i.name$englang as name, d2.dir_id
		FROM 
			".TABLE_PAGE." i 
			LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id AND d2.dir='$page_dir') 
		WHERE 
			i.parent=0 AND d2.dir_id IS NOT NULL AND i.public='1'") or Error(1, __FILE__, __LINE__);
	if(!$page = @mysql_fetch_array($sql)) {page404();return;}
	
}
else
{
	$weather_informer = get_template("templ/weather_informer_$extrasite_id.htm", array());

	$page_dir = mysql_escape_string($request[1]);
	$parent_dir = mysql_escape_string($request[0]);

	$sql = mysql_query("
		SELECT 
			c.name$englang as parent_name,  c.page_id as pid,
			i.page_id, i.parent, i.name$englang as name, i.description, d2.dir_id
		FROM 
			".TABLE_PAGE." c
			LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND d1.dir='$parent_dir') 
			LEFT JOIN ".TABLE_PAGE." i ON (i.parent=c.page_id) 
			LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id AND d2.dir='$page_dir') 
		WHERE 
			c.parent=0 AND d1.dir_id IS NOT NULL AND c.public='1' AND 
			d2.dir_id IS NOT NULL AND i.public='1'") or Error(1, __FILE__, __LINE__); 
	if(!$page = @mysql_fetch_array($sql)) {page404();return;}
}

$replace = array();

$replace['cure_id'] = $cure_id = $extrasite_id ? (int)$request[2] : (int)$request[1];
$replace['subcure_id'] = $subcure_id = $extrasite_id ? (int)$request[3] : (int)$request[2];

$link_medicine =  "$lprefix/$request[0]/";
if($extrasite_id) $link_medicine .= "$request[1]/";
$link = $cure_id ? $link_medicine : '';
$navig[] = array('link'=>$link, 'name'=>$page['name']);

$cure = array(); $subcure = array();
	
if($cure_id)
{
	$fields = "cure_id, name$englang as name, type";
	if(!$subcure_id)  $fields .=  ", description$englang as description";
	if($subcure_id)  $fields .=  ", inhotel$englang as inhotel";
	
	$sql = mysql_query("SELECT $fields 
		FROM ".TABLE_CURE." WHERE cure_id=$cure_id AND public") or Error(1, __FILE__, __LINE__);
	if(!($cure = @mysql_fetch_array($sql))) {page404();return;}
	
	$page_name = $cure['name'];
		
	if($cure['type']==3)
	{
		$sql = mysql_query("SELECT $fields FROM ".TABLE_CURE." WHERE partof=$cure_id AND public ORDER BY ord LIMIT 1") 
			or Error(1, __FILE__, __LINE__);
		if(!($cure = @mysql_fetch_array($sql))) {page404();return;}
		$cure_id = $cure['cure_id'];
		if($extrasite_id) $request[2] = $cure_id;
		else $request[1] = $cure_id;
	}
	
	$cure['name'] = htmlspecialchars($cure['name']);

	$link = $subcure_id || ($cure['type']==6 && @$s_id) ? $link_medicine."$cure_id/" : '';
	$navig[] = array('link'=>$link, 'name'=>$cure['name']);
	
	if(!$subcure_id)  
	{
		if($cure['type']<3)
		{
			$ord = $cure['type']==2 ? 'c.name' : 'c.ord';
			if($extrasite_id) 
				$query ="SELECT c.cure_id, c.name$englang as name, anons$englang as anons,  h.price FROM ".TABLE_CURE." c 
				LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=c.cure_id AND h.page_id=$extrasite_id)
				WHERE c.parent=$cure_id AND c.public AND h.cure_id
				GROUP BY c.cure_id
				ORDER BY $ord";
			else
				$query ="SELECT c.cure_id, c.name FROM ".TABLE_CURE." c WHERE c.parent=$cure_id AND c.public ORDER BY $ord";
			$sql = mysql_query($query) or Error(1, __FILE__, __LINE__);
			
			$cures = array(); 
			$sql_count = mysql_num_rows($sql); $col_count = 3;
			$in_col = ($sql_count%$col_count) ? (int)($sql_count/$col_count)+1 : $sql_count/$col_count; 
			$k=0;
			while($info = @mysql_fetch_array($sql))
			{ 
				$k++; 
				$info['name'] = $info['name'] ? HtmlSpecialChars($info['name']) : NONAME;		
				
				$info['url'] = $link_medicine."$cure_id/"."$info[cure_id]/";
				
				$info['newcol'] = !(($k+$in_col)%$in_col) && $k!=$sql_count ? 1 : 0; 
				$cures[] = $info;
			}
			$replace['cure_list'] = $cures;
		}
		elseif($cure['type']==5)
		{	
			require("lib/news.php");
			get_page_info($part, 0, $page_name);
			return;
		}
		elseif($cure['type']==6)
		{	
			$m='cure';
			if($extrasite_id) $s_id = $extrasite_id;
			$link_medicine .= "$cure_id/";
			require("lib/media.php");
			get_page_info($part, 0, $page_name);
			return;
		}
		elseif($cure['type']==7)
		{	
			if($extrasite_id)
			{			
				$sql = mysql_query("SELECT cr.description$englang as description
					FROM ".TABLE_CURE." cr 
					WHERE cr.page_id=$extrasite_id AND cr.parent=$cure_id") 
					or Error(1, __FILE__, __LINE__);
				$info = @mysql_fetch_array($sql); 
				$cure['description'] = @$info['description'];
			}
			else
			{			
				$curehotel = array();
				$sql = mysql_query("SELECT p.page_id, cr.name$englang as name, cr.cure_id, ct.name$englang as city,  
					fb.photo_id as fb_id, fb.ext as fb_ext, sd.dir as sp_dir
					FROM ".TABLE_PAGE." p
					LEFT JOIN ".TABLE_CURE." cr ON (cr.page_id=p.page_id AND cr.parent=$cure_id)
					LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
					LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
					LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
					LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$photo_owner[brochure])
					WHERE p.parent=1 AND p.public AND cr.page_id
					GROUP BY p.page_id
					ORDER BY cr.ord") 
					or Error(1, __FILE__, __LINE__);
				while($info = @mysql_fetch_array($sql)) 
				{
					$info['photo'] = file_exists($fb="images/$photo_dir[brochure]/$info[fb_id]-s.$info[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
					$info['name'] = htmlspecialchars($info['name']);
					$info['city'] = htmlspecialchars($info['city']);
					$info['page_link'] = "$lprefix/medicine/$cure_id/$info[cure_id]"; 
					//$info['page_link'] = $info['sp_dir'] ?  $info['sp_dir']."/medicine/$cure_id" : "$lprefix/medicine/$cure_id/$info[cure_id]"; 
					$curehotel[] = $info;	
				}
				$replace['curehotel'] = $curehotel;
			}
		}
	}
}
	
if($subcure_id)
{
	$sql = mysql_query("SELECT cure_id, name$englang as name, profile$englang as profile, description$englang as description
		FROM ".TABLE_CURE." WHERE cure_id=$subcure_id AND public") or Error(1, __FILE__, __LINE__);
	if(!($subcure = @mysql_fetch_array($sql))) {page404();return;}
	
	$subcure['name'] = htmlspecialchars($subcure['name']);
	$page_name = $subcure['name'];
	
	$link = ''; $link_medicine."$cure_id/$subcure_id/";
	$navig[] = array('link'=>$link, 'name'=>$subcure['name']);
	
	$cure['inhotel'] = str_replace("[service]", $subcure['name'], $cure['inhotel']);
	
	$curehotel = array();
	if(!$extrasite_id)
	{
		$sql = mysql_query("SELECT p.page_id, p.name$englang as name, ct.name$englang as city, h.price , 
			fb.photo_id as fb_id, fb.ext as fb_ext, sd.dir as sp_dir
			FROM ".TABLE_PAGE." p
			LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=$subcure_id AND h.page_id=p.page_id)
			LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
			LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
			LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
			LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$photo_owner[brochure])
			WHERE p.parent=1 AND p.public AND h.cure_id
			GROUP BY p.page_id
			ORDER BY p.ord") 
			or Error(1, __FILE__, __LINE__);
		while($info = @mysql_fetch_array($sql)) 
		{
			$info['photo'] = file_exists($fb="images/$photo_dir[brochure]/$info[fb_id]-s.$info[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
			$info['name'] = htmlspecialchars($info['name']);
			$info['city'] = htmlspecialchars($info['city']);
			$info['price'] = htmlspecialchars($info['price']);
			$info['page_link'] = $info['sp_dir'] ?  $info['sp_dir']."/medicine" : "$lprefix/media/?s_id=$info[page_id]"; 
			$curehotel[] = $info;	
		}
	}
	$replace['curehotel'] = $curehotel;		
	
}

if(!$cure_id && !$subcure_id)
{	
	$blocks = array();
	$sql = mysql_query("SELECT c.cure_id, c.name$englang as name, f.photo_id, f.ext FROM ".TABLE_CURE." c 
		LEFT JOIN ".TABLE_PHOTO." f ON (f.owner=$photo_owner[cure_part] AND f.owner_id=c.cure_id)
		WHERE !c.partof AND c.public AND f.photo_id
		ORDER by c.ord") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{ 	
		$f="images/$photo_dir[cure_part]/$arr[photo_id]-s.$arr[ext]";
		list($w_small, $h_small) = @getimagesize($f);
		$blocks[] = array(
			'photo'=>$f,
			'url'=>$link_medicine."$arr[cure_id]/",
			'name'=>htmlspecialchars($arr['name'], null, 'cp1251')
		);
	}
	$replace['blocks'] = $blocks;
}

$replace['extrasite_id'] = $extrasite_id;
$replace['cure'] = $cure;
$replace['subcure'] = $subcure;
$replace['link_medicine'] = $link_medicine."1/";
if(($cure_id==1 || (!$cure_id && !$subcure_id)) && !$extrasite_id) 
	$replace['profile'] =  get_template("templ/page_medicine_profile.htm", $replace);
else $replace['profile'] = '';

$content = get_template("templ/page_medicine.htm", $replace);

get_page_info($part, 0, $page_name);

?>