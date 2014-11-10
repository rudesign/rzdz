<?php

$m_list = array('', 'item', 'video', 'pdf', 'virtual', 'cure');
$onlyvideo_id = 663;

$page_id = (int)@$s_id;	
/*if(!$page_id)
{
	$sql = mysql_query("SELECT p.page_id FROM ".TABLE_PAGE."  p WHERE p.parent=1 AND p.public='1' ORDER BY p.ord") or Error(1, __FILE__, __LINE__);
	if($arr = @mysql_fetch_array($sql))
	{
		$s_id = $page_id = $arr['page_id'];
	}
}*/
	
$m = @$m;
if($m!=='item' && $m!=='pdf' && $m!=='video' && $m!=='virtual' && $m!=='cure') $m = '';
if($page_id && !$m) $m='item';

$photo_list = array();
	
$where = "f.public='1' AND p.parent=1";
if($m!='item' && $m && !$page_id) $where .= " AND (p.public='1' OR p.page_id=$onlyvideo_id)";
else $where .= " AND p.public='1'";

if($page_id) $where .= " AND p.page_id=$page_id";
 
if($m) $where .= " AND f.owner=$photo_owner[$m]"; 
else $where .= " AND f.owner<=5";

if($m) $group = $page_id ? "f.photo_id" : ($m=='item' ?  "p.page_id" : "f.photo_id");
else $group = "p.page_id";

//$sort = $m=='pdf' || $m=='virtual' ? "p.page_id=$page_id desc, p.ord, f.ord" : 'f.ord';
//$sort = $page_id ? 'f.ord' : ($m ? "f.ord, p.ord" : "p.ord");
		
if($m) $sort = $page_id ? 'f.ord, p.ord' : "p.page_id=$onlyvideo_id, p.ord, f.ord";
else $sort = "p.ord, f.ord";
		
$limit = $page_id ? '' : "LIMIT 30";

$page = array(); $page['page_id'] = 0;
$sql = mysql_query("SELECT p.page_id, p.name$englang as name, p.url, 
		f.photo_id, f.ext, f.ext_b, f.alt$englang as alt, f.owner, f.description,
		d.dir, d.dir_id, 
		fb.photo_id as fb_id, fb.ext as fb_ext
	FROM ".TABLE_PHOTO."  f 
	LEFT JOIN  ".TABLE_PAGE."  p ON (p.page_id=f.owner_id)
	LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
	LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$photo_owner[brochure])
	WHERE $where
	GROUP BY $group
	ORDER BY $sort
	$limit") or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{ 
	$page['page_name'] = HtmlSpecialChars($arr["name"], null, 'cp1251');
	if(!$page['page_name']) $page['page_name'] = NONAME;
	$page['page_link'] = "$lprefix/$dir_sanatorium/$arr[dir]/";	
	$page['order_link'] = "$lprefix/order/?s_id=$page[page_id]";	
	$page['dir_id'] = $arr["dir_id"];	
	
	$page['page_id'] = $arr["page_id"];	
	$page['photo_id'] = $arr["photo_id"];
	
	$page['url'] = $arr['url'];
		
	$page['mm'] = $mm = $m ? $m_list[$arr['owner']] : ''; 
	
	if($page['mm'] && $page['mm']!='pdf')	
	{
		$f ="images/$photo_dir[$mm]/$arr[photo_id]-s.$arr[ext]";  
		if(!file_exists($f) && $page['mm']!='video') continue;
		list($page['w'], $page['h']) = @getimagesize($f);
	}
	
	
	if(!$page['mm'])
	{
		$page['photo'] = file_exists($fb="images/$photo_dir[brochure]/$arr[fb_id]-s.$arr[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
		$page['bigphoto'] = "$lprefix/media/?s_id=$page[page_id]";
	}
	elseif($page['mm']=='pdf')
	{
		$page['photo'] = file_exists($fb="images/$photo_dir[brochure]/$arr[fb_id]-s.$arr[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
		$page['bigphoto'] = $arr['ext'] ? "/images/$photo_dir[$mm]/$arr[photo_id].$arr[ext]" : "$lprefix/video/?photo_id=$page[photo_id]";
		$page['file'] =  $arr['ext'] ? 1 : 0;
        $page['exists'] = file_exists("images/$photo_dir[brochure]/pdf/$arr[photo_id].pdf") ? 1 : 0;
	}
	elseif($page['mm']=='virtual')
	{
		$f_big = "images/$photo_dir[$mm]/$arr[photo_id].$arr[ext_b]"; 
		if(!file_exists($f_big) && $mm=='item') continue;
		$page['photo'] = "/".$f;
		$page['bigphoto'] = $arr["description"];
		
	}
	else
	{
		$f_big = "images/$photo_dir[$mm]/$arr[photo_id].$arr[ext_b]"; 
		if(!file_exists($f_big) && $mm=='item') continue;
		$page['photo'] = "/".$f;
		if(!file_exists($f)) $page['photo'] = 
			file_exists($fb="images/$photo_dir[brochure]/$arr[fb_id]-s.$arr[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
		
		if($m=='item') $page['big'] =  is_file($bf="images/big/$arr[photo_id].jpg") ? "/".$bf : '';
		else $page['big'] =  '';
	
		if($medicine && !$page_id) $page['bigphoto'] = $link_medicine."?s_id=$page[page_id]";
		else $page['bigphoto'] = $page['mm']=='video' || ($page['mm']=='item' && $page['big']) ? "$lprefix/video/?photo_id=$page[photo_id]" : $f_big;
	}
	
	$page['fancy'] = $page['mm']=='item' || $page['mm']=='video' || ($page['mm']=='pdf' && !@$page['file']) || 
		($page['mm']=='cure' && $page_id) ? 1 : 0;
	$page["alt"] =  HtmlSpecialChars($arr["alt"], null, 'cp1251');
	if(!$m || (!$page_id && $m=='item') || !$page["alt"])  $page["alt"] = HtmlSpecialChars($page["page_name"], null, 'cp1251');
	
	$photo_list[] = $page;	
}


if($page_id && !$page['page_id']) 
{
	$sql = mysql_query("SELECT p.page_id, p.name$englang as name, p.url, d.dir FROM  ".TABLE_PAGE."  p 
		LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
		WHERE p.page_id=$page_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	
	if(!@$arr['page_id'])	{page404(); return;} 

	$page['url'] = $arr['url'];
	$page['page_name'] = $arr['name'];
	$page['page_link'] = "$lprefix/$dir_sanatorium/$arr[dir]/";	
}

$menu_link = array();
$m_sel = array();
if($page_id)
{
	$page_link = $page['page_link'];
	if(!$medicine) $navig[] = array('name'=>$lang_phrases['media'], 'link'=>'/media');
	if(!$medicine || ($page_id && !$extrasite_id)) $navig[] = array('name'=>$page['page_name'], 'link'=>"");
	if(!$medicine) get_page_info('', @$page['dir_id'], @$page['page_name']); 
	
	
	for($i=1;$i<=4;$i++)
	{
		$menu_link[$m_list[$i]] = "$lprefix/media/?s_id=$page_id&m=$m_list[$i]";
		$m_sel[$m_list[$i]] = $m == $m_list[$i] ? 1 : 0;
	}
}
else
{
	
	if(!$medicine)
	{
		$navig[] = array('name'=>$lang_phrases['media'], 'link'=>'');
		get_page_info($part); 
	}
	
	for($i=1;$i<=4;$i++)
	{
		$menu_link[$m_list[$i]] = "$lprefix/media/?m=$m_list[$i]";
		if(isset($lastest)) $menu_link[$m_list[$i]] .= "&lastest";
		if(isset($popular)) $menu_link[$m_list[$i]] .= "&popular";
		$m_sel[$m_list[$i]] = $m == $m_list[$i] ? 1 : 0;
	}
}

$peplace = array('photo_list'=>$photo_list, 'menu_link'=>$menu_link, 'm_sel'=>$m_sel, 
	'page_id'=>$page_id, 'page'=>$page, 'm'=>$m, 'medicine'=>$medicine);

$content = get_template("templ/media.htm", $peplace);

?>