<?php

function get_page_info($dir, $dir_id=0, $name='', $addname=0)
{	
	global $meta_tags, $part, $photo_owner, $englang; 
	
	$where = ($dir_id) ? "d.dir_id=$dir_id" : "d.dir='$dir' AND parent=0";
	
	$sql_text = "
		SELECT 
			d.parent,
			d.title$englang as title,
			d.mdescription$englang as description,
			d.keywords$englang as keywords
		FROM 
			".TABLE_DIR." d 
		WHERE ";
	
	$sql = mysql_query($sql_text." $where") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	
	if(!$meta_tags['title'] && @$arr['title']) 
	{
		$meta_tags['title'] = $arr['title'];
		if($name && (!$dir_id || $addname)) $meta_tags['title'] = ($meta_tags['title']) ? 
			$name.TITLE_SEPAR.$meta_tags['title'] : $meta_tags['title'];
	}
	if(!$meta_tags['description']) $meta_tags['description'] = @$arr['description'];
	if(!$meta_tags['keywords']) $meta_tags['keywords'] = @$arr['keywords'];
	
	$parent_dir_id = (int)@$arr['parent'];
	
	while(!$meta_tags['title'] || !$meta_tags['description'] || !$meta_tags['keywords'])
	{
		$sql = mysql_query($sql_text." d.dir_id=$parent_dir_id") or Error(1, __FILE__, __LINE__); 
		$arr = @mysql_fetch_array($sql);
		$parent_dir_id = (int)@$arr['parent'];
		
		if(!$meta_tags['title'] && @$arr['title']) 
		{
			$meta_tags['title'] = $arr['title'];
			if($name) $meta_tags['title'] = ($meta_tags['title']) ? 
				$name.TITLE_SEPAR.$meta_tags['title'] : $meta_tags['title'];
		}
		if(!$meta_tags['description']) $meta_tags['description'] = @$arr['description'];
		if(!$meta_tags['keywords']) $meta_tags['keywords'] = @$arr['keywords'];
		
		if(!$meta_tags['title'] || !$meta_tags['description'] || !$meta_tags['keywords']) 
		{
			$sql = mysql_query($sql_text." d.dir_id=$parent_dir_id") or Error(1, __FILE__, __LINE__); 
			$arr = @mysql_fetch_array($sql);
			$parent_dir_id = (int)@$arr['parent'];
			
			if(!$meta_tags['title'] && @$arr['title']) 
			{
				$meta_tags['title'] = $arr['title'];
				if($name) $meta_tags['title'] = ($meta_tags['title']) ? 
					$name.TITLE_SEPAR.$meta_tags['title'] : $meta_tags['title'];
			}
			if(!$meta_tags['description']) $meta_tags['description'] = @$arr['description'];
			if(!$meta_tags['keywords']) $meta_tags['keywords'] = @$arr['keywords'];
			
			if(!$parent_dir_id && (!$meta_tags['title'] || !$meta_tags['description'] || !$meta_tags['keywords'] ) )
			{
				$sql = mysql_query($sql_text." d.parent=0 AND dir=''") or Error(1, __FILE__, __LINE__);
				$arr = @mysql_fetch_array($sql);
				$parent_dir_id = (int)@$arr['parent'];
				
				if(!$meta_tags['title'] && @$arr['title']) 
				{
					$meta_tags['title'] = $arr['title'];
					if($name) $meta_tags['title'] = ($meta_tags['title']) ? 
						$name.TITLE_SEPAR.$meta_tags['title'] : $meta_tags['title'];
				}
				if(!$meta_tags['description']) $meta_tags['description'] = @$arr['description'];
				if(!$meta_tags['keywords']) $meta_tags['keywords'] = @$arr['keywords'];
				
				break;
			}
		}
	}
}


function get_topimg(){
	try{
        global $photo_owner, $photo_dir, $extrasite_id, $extra_parent_id;

        $photo_list = array();

        $where = "public='1'";
        if($extra_parent_id && $extrasite_id) $where  .= " AND FIND_IN_SET('$extra_parent_id', pages)"; else $where  .= " AND FIND_IN_SET('0', pages)";
        $query = "SELECT topimg_id, url, name FROM ".TABLE_TOPIMG." WHERE  $where ORDER BY ord";

        if(!$sql = mysql_query($query)) throw new Exception;

        while($arr = @mysql_fetch_array($sql)){

            $sql_photos = mysql_query("SELECT photo_id, ext FROM ".TABLE_PHOTO." WHERE owner_id='$arr[topimg_id]' AND owner='$photo_owner[topimg]'") or Error(1, __FILE__, __LINE__);

            if(!$arr_photos = @mysql_fetch_array($sql_photos)) throw new Exception;

                $photo_id = $arr_photos['photo_id'];
                $ext = $arr_photos['ext'];
                if(is_file($f="images/$photo_dir[topimg]/${photo_id}-s.$ext")) {
                    list($w_small, $h_small) = @getimagesize($f);
                    $photo_list[] = array('photo'=>$f, 'url'=>$arr['url'], 'name'=>htmlspecialchars($arr['name'], null, 'cp1251'));
                }
        }

        return $photo_list;
    }catch (Exception $e){

        Error(1, __FILE__, $e->getLine());
    }
}


function get_banners()
{
	global $extra_parent_id, $englang;
	
	$p_id = $extra_parent_id;
	
	$where = $p_id ? "FIND_IN_SET('$p_id', pages)" : 'defaultban=1';	
	
	$sql = mysql_query("SELECT description$englang as description FROM ".TABLE_BANNER." WHERE $where AND public=1 ORDER BY RAND() LIMIT 1") 
		or Error(1, __FILE__, __LINE__);
		
	if(!mysql_num_rows($sql))		
		$sql = mysql_query("SELECT description FROM ".TABLE_BANNER." WHERE defaultban=1 AND public=1 ORDER BY RAND() LIMIT 1") 
			or Error(1, __FILE__, __LINE__);
	
	$ban_arr = array();
	$arr = @mysql_fetch_array($sql);
	
	return @$arr['description']; 
}

function get_menu1($type) 
{	global $part, $parent, $page_id, $request, $englang, $lprefix;
	
	$list = array();
	
	$sql = mysql_query("SELECT menu_id, name$englang as name, url, submenu FROM ".TABLE_MENU." WHERE public='1' AND type=$type ORDER BY ord") 
		or Error(1, __FILE__, __LINE__);
		
	while($info = @mysql_fetch_array($sql))
	{ 
		$r = ereg_replace("^(\/)?", "", $info['url']);  
		$r = ereg_replace("(\/)?(\?.*)?$", "", $r); 
		$r_array = split("(\/)", $r);
		$current_p = @$r_array[0];
		$current_p2 = @$r_array[1];

		$info['name'] = HtmlSpecialChars($info['name'], null, 'cp1251');
		if(!$info['name']) $info['name'] = NONAME;

		$info['sel'] = ($request[0] == $current_p 
			&& ((!$request[1] && !$current_p2) || $request[1]==$current_p2 )
			&& $part != '404') ? 1 : 0;

		$info['link'] = $lprefix.$info['url'];
		
		$list[] = $info;
	}
			
	switch($type)
	{
		case 1:
			$file = 'menu_top';
			break;
		case 2:
			$file = 'menu_center';
			break;
		case 3:
			$file = 'menu_bottom_left';
			break;
		case 4:
			$file = 'menu_bottom_right';
			break;
	}

    //echo "templ/$file.htm<br />";

	return get_template("templ/$file.htm", array('list'=>$list, 'part'=>$part));
}


function get_menu2() 
{	
	global $part, $parent, $page_id, $request, $englang, $lprefix, $lang_phrases, $navig;
	
	$list = array(); $pname = '';
	if($request[0]=='faq')
	{
	
		$sql = mysql_query("
			SELECT 
				i.gtema_id, i.name$englang as name
			FROM 
				".TABLE_GTEMA." i
			ORDER BY
				i.ord") or Error(1, __FILE__, __LINE__);
			
		while($info = @mysql_fetch_array($sql))
		{ 
			$info['name'] = HtmlSpecialChars($info['name'], null, 'cp1251');
			if(!$info['name']) $info['name'] = NONAME;
						
			$info['sel'] = (@$_GET['tema'] == $info['gtema_id']) ? 1 : 0;
			if($info['sel'])
			{			
				$navig[1] = array('name'=>$navig[1]['name'], 'link'=>$request[0]);
				$navig[2] = array('name'=>$info['name'], 'link'=>'');
			}
			
			$info['link'] = "$lprefix/$request[0]/?tema=".$info['gtema_id'];
				
			$list[] = $info;
		}
		$pname = $lang_phrases['gtema'];
	}
	elseif($part == 'site')
	{
		$region_dir = mysql_escape_string($request[0]);
		$parent_dir = mysql_escape_string($request[1]);
		$page_dir = mysql_escape_string($request[2]);
		
		$sql = mysql_query("
			SELECT 
				i.page_id, i.name$englang as name, c.name$englang as pname, d2.dir
			FROM 
				".TABLE_PAGE." i
				LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id) 
				LEFT JOIN ".TABLE_PAGE." c ON (i.parent=c.page_id) 
				LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND d1.dir='$region_dir') 
			WHERE 
				c.parent=0 AND c.public='1' AND d1.dir_id IS NOT NULL AND 
				d2.dir_id IS NOT NULL AND i.public='1'
			ORDER BY
				i.ord") or Error(1, __FILE__, __LINE__);
			
		while($info = @mysql_fetch_array($sql))
		{ 
			$info['name'] = HtmlSpecialChars($info['name'], null, 'cp1251');
			if(!$info['name']) $info['name'] = NONAME;
			
			if(!$pname) $pname = HtmlSpecialChars($info['pname'], null, 'cp1251');
			
			$info['sel'] = ($info['dir'] == $parent_dir) ? 1 : 0;
			
			$info['link'] = ($page_id == $info['page_id']) ? '' : "$lprefix/$region_dir/$info[dir]/";
			
				
			$list1 = array();
			if($info['sel'])
			{
				$sql1 = mysql_query("
					SELECT 
						i.page_id, i.name$englang as name, d2.dir
					FROM 
						".TABLE_PAGE." i
						LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id) 
						LEFT JOIN ".TABLE_PAGE." c ON (i.parent=c.page_id) 
						LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND d1.dir='$parent_dir') 
					WHERE 
						c.public='1' AND d1.dir_id IS NOT NULL AND 
						i.parent=$info[page_id] AND i.public='1' AND d2.dir_id IS NOT NULL
					ORDER BY
						i.ord") or Error(1, __FILE__, __LINE__);
					
				while($info1 = @mysql_fetch_array($sql1))
				{ 
					$info1['name'] = HtmlSpecialChars($info1['name'], null, 'cp1251');
					if(!$info1['name']) $info1['name'] = NONAME;
					
					$info1['sel'] = ($info1['dir'] == $page_dir) ? 1 : 0;
					
					$info1['link'] = "$lprefix/$region_dir/$parent_dir/$info1[dir]/";
							
					$list1[] = $info1;
				}
			}
			
			$info['list'] = $list1; 
			$list[] = $info;
		}
	}	
	else
	{
		$sql_text = "SELECT page_id FROM ".TABLE_DIR." WHERE parent=0 AND dir='$part'";
		$sql = mysql_query($sql_text) or Error(1, __FILE__, __LINE__); 
		$arr = @mysql_fetch_array($sql);
	
		if($page_id = (int)@$arr[0])
		{	
			$sql = mysql_query("
				SELECT 
					i.page_id, i.name$englang as name, d2.dir, d1.dir as parent_dir
				FROM 
					".TABLE_PAGE." i
					LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id) 
					LEFT JOIN ".TABLE_PAGE." c ON (i.parent=c.page_id AND c.page_id=$page_id) 
					LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id) 
				WHERE 
					c.parent=0 AND c.public='1' AND d1.dir_id IS NOT NULL AND 
					d2.dir_id IS NOT NULL AND i.public='1'
				ORDER BY
					i.ord") or Error(1, __FILE__, __LINE__);
				
			while($info = @mysql_fetch_array($sql))
			{ 
				$info['name'] = HtmlSpecialChars($info['name'], null, 'cp1251');
				if(!$info['name']) $info['name'] = NONAME;
				
				$info['sel'] = 0;
					
				$info['link'] = "$lprefix/$info[parent_dir]/$info[dir]/";
				if($english && $info['link']) $info['link'] = '/en'.$info['link'];
					
				$info['list'] = array();
				$list[] = $info;
			}
		}
	}

	return count($list) ? get_template('templ/menu2.htm', array('list'=>$list, 'pname'=>$pname, 'plink'=>"$lprefix/".$request[0])) : ''; 
}


function get_menu_extra($bottom=0) 
{	
	global $extrasite_id, $request, $extra_parent_id, $englang, $lprefix;
	
	$list = array(); 
	
	$sql = mysql_query("
		SELECT 
			i.page_id, i.name$englang as name, d2.dir
		FROM 
			".TABLE_PAGE." i
			LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id) 
		WHERE 
			i.parent=$extra_parent_id AND d2.dir_id IS NOT NULL AND i.public='1'
		ORDER BY
			i.ord") or Error(1, __FILE__, __LINE__);
			
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name'], null, 'cp1251');
		if(!$info['name']) $info['name'] = NONAME;
					
		$info['sel'] = ($info['dir'] == $request[1]) ? 1 : 0;
		
		if($info['dir']=='media') $info['link'] = "/media/?s_id=$extrasite_id\" target=\"_blank";
		else $info['link'] = "$lprefix/$request[0]/$info[dir]/";
			
			
		$list[] = $info;
	}
	if($bottom) return $list;

	return count($list) ? get_template('templ/menu_center_extra.htm', array('list'=>$list)) : ''; 
}


function get_menu2_extra() 
{	
	global $part, $parent, $page_id, $extrasite_id, $request, $extra_parent_id, $englang, $lprefix, $lang_phrases;
	
	$list = array(); $pname = '';
	
	$region_dir = mysql_escape_string($request[1]);
	$parent_dir = mysql_escape_string($request[2]);
	
	$r_where = $region_dir=='news' || $region_dir=='opinion' ? "d1.dir='about'" : "d1.dir='$region_dir'";

	$sql = mysql_query("
		SELECT 
			i.page_id, i.name$englang as name, c.name$englang as pname,  c.ord as cord, d2.dir, d1.dir as rdir
		FROM 
			".TABLE_PAGE." i
			LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id) 
			LEFT JOIN ".TABLE_PAGE." c ON (i.parent=c.page_id) 
			LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND $r_where) 
			LEFT JOIN ".TABLE_PAGE." p ON (c.parent=p.page_id) 
		WHERE 
			c.public='1' AND d1.dir_id IS NOT NULL AND 
			d2.dir_id IS NOT NULL AND i.public='1' AND p.public='1' AND p.page_id=$extra_parent_id
		ORDER BY
			i.ord") or Error(1, __FILE__, __LINE__);
		
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name'], null, 'cp1251');
		if(!$info['name']) $info['name'] = NONAME;
		
		if(!$pname) $pname = HtmlSpecialChars($info['pname'], null, 'cp1251');
		
		$info['sel'] = ($info['dir'] == $parent_dir) ? 1 : 0;
		
		$info['link'] = ($page_id == $info['page_id']) ? '' : "$lprefix/$request[0]/$info[rdir]/$info[dir]/";
			
			
		$list1 = array();
		if($info['sel'])
		{
			$sql1 = mysql_query("
				SELECT 
					i.page_id, i.name$englang as name, d2.dir
				FROM 
					".TABLE_PAGE." i
					LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id) 
					LEFT JOIN ".TABLE_PAGE." c ON (i.parent=c.page_id) 
					LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND d1.dir='$parent_dir') 
				WHERE 
					c.public='1' AND d1.dir_id IS NOT NULL AND 
					i.parent=$info[page_id] AND i.public='1' AND d2.dir_id IS NOT NULL
				ORDER BY
					i.ord") or Error(1, __FILE__, __LINE__);
				
			while($info1 = @mysql_fetch_array($sql1))
			{ 
				$info1['name'] = HtmlSpecialChars($info1['name'], null, 'cp1251');
				if(!$info1['name']) $info1['name'] = NONAME;
				
				$info1['sel'] = ($info1['dir'] == $page_dir) ? 1 : 0;
				
				$info1['link'] = "$lprefix/$request[0]/$info[rdir]/$info[dir]/$info1[dir]/";
						
				$list1[] = $info1;
			}
		}
		
		$info['list'] = $list1;
		$list[] = $info;
		
		if($info['cord']==1 && count($list)==mysql_num_rows($sql)) 
		{
			$sel = $request[1] == 'news' ? 1 : 0;
			$list[] = array('name'=>$lang_phrases['news'], 'link'=>$request[0].'/news', 'sel'=>$sel);
			$sel = $request[1] == 'opinion' ? 1 : 0;
			$list[] = array('name'=>$lang_phrases['opinion'], 'link'=>$request[0].'/opinion', 'sel'=>$sel);
		}
	}
	

	return count($list) ? get_template('templ/menu2_extra.htm', 
		array('list'=>$list, 'pname'=>$pname, 'plink'=>"$lprefix/$request[0]/$request[1]", 'part'=>$request[0])) : ''; 
}

function recom_menu($dir_sanatorium, $sanat_id)
{	
	global $photo_dir, $photo_owner;
	
	$recom_images = array();

	$sql = mysql_query("SELECT p.page_id, p.name, ct.name as city, d.dir, f.photo_id, f.ext 
		FROM ".TABLE_PAGE."  p
		LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)
		LEFT JOIN ".TABLE_CITY." ct ON (ct.city_id=p.city_id)
		LEFT JOIN  ".TABLE_RECOM." r ON (r.page_id1=$sanat_id AND r.page_id2=p.page_id) 
		LEFT JOIN  ".TABLE_PHOTO."  f ON (f.owner_id=p.page_id AND f.owner=$photo_owner[logo])
		WHERE p.parent=1 AND p.public='1' AND r.page_id2>0
		GROUP BY 	p.page_id
		ORDER BY r.ord") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{
		$f = "images/$photo_dir[logo]/$arr[photo_id]-s.$arr[ext]"; 
		if(!file_exists($f)) continue;
		$arr['photo'] = "/".$f;

		$arr['name'] = HtmlSpecialChars($arr['name'], null, 'cp1251');
		$arr['link'] = "$lprefix/$dir_sanatorium/$arr[dir]";
		$arr['city'] = eregi("???????", $arr['city']) ? $arr['city'] : "?. ".$arr['city'];
		
		$recom_images[] = $arr; 
	}

	if(!count($recom_images)) return;
	return get_template("templ/menu_recom.htm", array('recom_images'=>$recom_images)); 
}

function user_pages($count, $url, $on_page='', $after='') {
	global $current_page; 

	if($count<2 || $current_page<1) return array('1', '');

	if($count%$on_page) $pagecount=(int) ($count/$on_page) + 1;
	else $pagecount=(int) $count/$on_page;
	
	if($pagecount < 2) return array($on_page, '');

	$arr=array();
	$limit=(($current_page-1)*$on_page).", ".$on_page;
	
	$begin = 1; $to = $pagecount;
	$delta = 4;
	if($pagecount > $delta*2)
	{
		$begin = ($current_page - $delta > 0) ? $current_page - $delta : 1;
		$to = ($current_page + $delta < $pagecount) ? $current_page + $delta : $pagecount;
	}
	
	if($begin > 1) 
	{
		$arr[] = array('i'=>"1", 'sel'=>0, 'link'=>$url."page=1".$after);
		if($begin != 2)
		{
			$i = ($begin - $delta > 1) ? $begin - $delta : (int)(($begin-1)/2) + 1;
			$arr[] = array('i'=>"...", 'sel'=>0, 'link'=>$url."page=$i".$after);
		}
	}
	for($i=$begin;$i<=$to;$i++) {
		$sel = ($i == $current_page) ? 1 : 0;
		$arr[] = array('i'=>$i, 'sel'=>$sel, 'link'=>$url."page=$i".$after);
	}
	if($to < $pagecount) 
	{
		$i = ($to + $delta < $pagecount) ? $to + $delta : $pagecount;
		$arr[] = array('i'=>"...", 'sel'=>0, 'link'=>$url."page=$i".$after);
	}
	
	$next_link = ($pagecount > $current_page) ? $url."page=".($current_page+1) : 'javascript:void(0)'; 
	if($after) $next_link .= $after;
	
	$prev_link = $current_page > 1 ? $url."page=".($current_page-1) : 'javascript:void(0)';
	if($after) $prev_link .= $after;
	
	$pages = get_template('templ/pagelink.htm', array('list'=>$arr, 'next_link'=>$next_link, 'prev_link'=>$prev_link)); 
	
	return array($limit, $pages);
}


function page404()
{
	global $content, $part, $navig, $lang_phrases, $lprefix;
	
	$navig = array();
	$navig[] = array('name'=>$lang_phrases['home'], 'link'=>$lprefix.'/');
	$navig[] = array('name'=>$lang_phrases['404'], 'link'=>'');
	
	$content = get_template("templ/404.htm", array()); 
	$part = '404';
	get_page_info('');
}


function sanat_list()
{
	global $englang;
	
	$data_arr = @$_SESSION['order_data'] ? @Unserialize($_SESSION['order_data']) : @Unserialize($_SESSION['opinion_data']);

	$sanat_list = array();
	
	$sql = mysql_query("SELECT p.page_id, p.name$englang as name, ct.name$englang as city FROM ".TABLE_PAGE."  p
		LEFT JOIN  ".TABLE_CITY."  ct ON (ct.city_id=p.city_id)
		WHERE p.parent=1 AND p.public='1' 	
		GROUP BY 	p.page_id
		ORDER BY p.ord") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{
		$arr['selected'] = (@$data_arr['fsanator'] == $arr['page_id']) ? 'selected' : '';
		$sanat_list[] = $arr;
	}
	
	return $sanat_list;
}
 
function menu_sanat($dir_sanatorium, $media=0)
{	
	global $san_id, $s_id, $englang, $lprefix;

	$regions = array();

	$region_id = 0;
	$region_k = -1;
	
	$sql = mysql_query("SELECT p.page_id, p.name$englang as name, p.region_id, d.dir, 
		ct.name$englang as city, r.name$englang as region , sd.dir as sp_dir
		FROM ".TABLE_PAGE."  p
		LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
		LEFT JOIN ".TABLE_CITY." ct ON (ct.city_id=p.city_id) 
		LEFT JOIN ".TABLE_REGION." r ON (r.region_id=p.region_id) 
		LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
		LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
		WHERE p.parent=1 AND p.public='1' 
		GROUP BY 	p.page_id
		ORDER BY r.ord, p.ord") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{
		if($arr['region_id'] != $region_id) 
		{
			$region_k++;
			$region_id = $arr['region_id'];
			$regions[$region_k]['name'] = $arr['region'];
			$regions[$region_k]['region_id'] = $arr['region_id'];
			$regions[$region_k]['sel'] = 0;
			$regions[$region_k]['list'] = array();
		}
		if($media) $arr['sel'] = ($s_id == $arr['page_id']) ? 1 : 0;
		else $arr['sel'] = ($san_id == $arr['page_id']) ? 1 : 0;
		
		if($arr['sel'] && !$regions[$region_k]['sel']) $regions[$region_k]['sel'] = 1;
		
		$arr['name'] = HtmlSpecialChars($arr['name'], null, 'cp1251');
		$arr['city'] = eregi("область", $arr['city']) ||  $englang ? $arr['city'] : $arr['city'];
		
		$arr['link'] =  $arr['sp_dir'] && !$media ?  "$lprefix/".$arr['sp_dir']."/" : "$lprefix/media/?s_id=$arr[page_id]"; 
		
		$regions[$region_k]['list'][] = $arr;
	}

	return  get_template("templ/menu_sanat.htm", array('regions'=>$regions, 'media'=>$media)); 
}

function menu_medicine()
{	
	global $englang, $lprefix, $request, $lang_phrases, $extrasite_id;
	
	$cure_id = $extrasite_id ? (int)$request[2] : (int)$request[1];
	$subcure_id = $extrasite_id ? (int)$request[3] : (int)$request[2];

	$link_medicine =  "$lprefix/$request[0]/";
	if($extrasite_id) $link_medicine .= "$request[1]/";

	$list = array();
	
	$nf = $extrasite_id ? "(if(p.name_extra$englang!='',p.name_extra$englang,p.name$englang))" : "p.name$englang";
	$w =  $extrasite_id ? " AND p.inmenu" : '';
	
	$table_plus = '';
	if($extrasite_id)
	{
		$table_plus = "LEFT JOIN ".TABLE_CUREHOTEL." ch ON (ch.cure_id=p.cure_id AND ch.page_id=$extrasite_id)";
		$w .= " AND ch.cure_id IS NOT NULL";
	}
	
	$sql = mysql_query("SELECT p.cure_id, $nf as name, p.type
		FROM ".TABLE_CURE."  p
		$table_plus
		WHERE p.parent=0 AND !p.partof AND p.public $w 
		ORDER BY p.ord") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{
		$arr['sel'] = $cure_id==$arr['cure_id'] ? 1 : 0;
		$arr['name'] = HtmlSpecialChars($arr['name'], null, 'cp1251');
		
		$arr['link'] =  $link_medicine."$arr[cure_id]/";
		
		if($arr['type']==3 && !( ($arr['cure_id']==5 || $arr['cure_id']==8) &&  !$extrasite_id ) )
		{
			$arr['list'] = array();
			$sql1 = mysql_query("SELECT cure_id, name$englang as name, type FROM ".TABLE_CURE."  
				WHERE parent=0 AND partof=$arr[cure_id] AND public
				ORDER BY ord") or Error(1, __FILE__, __LINE__);
			while($arr1 = @mysql_fetch_array($sql1))
			{
				$arr1['sel'] = $cure_id==$arr1['cure_id'] ? 1 : 0;
				if($arr1['sel']) $arr['sel']=1;
				$arr1['name'] = HtmlSpecialChars($arr1['name'], null, 'cp1251');
				
				$arr1['link'] =  $link_medicine."$arr1[cure_id]";
				
				if(!($arr['cure_id']==5 || $arr['cure_id']==8) ) $arr['list'][] = $arr1;
			}				
		}
		
		if($arr['type']==4)// || ($arr['type']==7 && !$extrasite_id && $cure_id==$arr['cure_id']))
		{
			$arr['list'] = array();
			if($arr['type']==7)
			$sql1 = mysql_query("SELECT c.cure_id, c.name$englang as name,  d.dir FROM ".TABLE_CURE." c 
				LEFT JOIN ".TABLE_PAGE." ps ON (ps.site=c.page_id AND ps.public)  
				LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=ps.dir_id) 
				WHERE c.parent=$arr[cure_id] AND c.public AND c.inmenu
				ORDER BY c.ord") or Error(1, __FILE__, __LINE__);
			else
			$sql1 = mysql_query("SELECT cure_id, name$englang as name FROM ".TABLE_CURE."  
				WHERE parent=$arr[cure_id] AND public AND inmenu
				ORDER BY ord") or Error(1, __FILE__, __LINE__);
			while($arr1 = @mysql_fetch_array($sql1))
			{
				$arr1['sel'] = $subcure_id==$arr1['cure_id'] ? 1 : 0;
				if($arr1['sel']) $arr['sel']=1;
				$arr1['name'] = HtmlSpecialChars($arr1['name'], null, 'cp1251');
				
				if($arr['type']==7 && @$arr1['dir']) $arr1['link'] =  "$lprefix/$arr1[dir]/medicine/$arr[cure_id]\" target=\"_blank";
				else $arr1['link'] =  $link_medicine."$arr[cure_id]/$arr1[cure_id]";
				
				$arr['list'][] = $arr1;
			}				
		}
		
		if($arr['type']==7 && $extrasite_id)
		{
			$sql1 = mysql_query("SELECT count(*) FROM ".TABLE_CURE."  
				WHERE parent=$arr[cure_id] AND public AND page_id=$extrasite_id") or Error(1, __FILE__, __LINE__);
			$arr1 = @mysql_fetch_array($sql1);
			if(!$arr1[0]) continue;
		}

		$list[] = $arr;
	}
	
	/*$sel = $request[1]=='news' ? 1 : 0;
	$list[] = array('name'=>$lang_phrases['news'], 'link'=>"$lprefix/$request[0]/news", 'sel'=>$sel, 'type'=>4);*/

	//$file = $extrasite_id ? "menu_medicine_extra.htm" : "menu_medicine.htm";
	$file = "menu_medicine.htm";
	return  get_template("templ/$file", array('list'=>$list, 'extrasite_id'=>$extrasite_id)); 
}

function slider_images($dir_sanatorium)
{
	global $photo_owner, $photo_dir, $san_id, $lprefix;
	
	$slider_images = array();
	
	$ord = $san_id ? "p.page_id='$san_id' desc, RAND()" : 'RAND()';
	
	$id = 0;
	$sql = mysql_query("SELECT p.page_id, p.name, p.slide_text, p.slide_name, d.dir, f.photo_id, f.ext FROM ".TABLE_PAGE."  p
		LEFT JOIN  ".TABLE_PHOTO."  f ON (f.owner_id=p.page_id AND f.owner=$photo_owner[slide])
		LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
		WHERE (p.parent=1 OR p.page_id=41) AND p.public='1'  AND f.photo_id>0
		GROUP BY 	p.page_id
		ORDER BY $ord") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{
		if($san_id == $arr['page_id']) $id = 1;
		
		$f = "images/$photo_dir[slide]/$arr[photo_id]-s.$arr[ext]"; 
		if(!file_exists($f)) continue;
		$arr['photo'] = "/".$f;

		$arr['name'] = $arr['slide_name'] ? HtmlSpecialChars($arr['slide_name'], null, 'cp1251') : HtmlSpecialChars($arr['name'], null, 'cp1251');
		$arr['slide_text'] = HtmlSpecialChars($arr['slide_text'], null, 'cp1251');
		
		$arr['link'] = $arr['page_id']==41 ? "$lprefix/$arr[dir]/" : "$lprefix/$dir_sanatorium/$arr[dir]/";
		
		$slider_images[] = $arr;
	}
	if(!$id) $san_id = 0;

	return $slider_images;
}

function block_images($dir_sanatorium)
{
	global $photo_owner, $photo_dir;
	
	$block_images = array();

	$sql = mysql_query("SELECT p.page_id, p.name, p.url, p.stars, f.photo_id, f.ext FROM ".TABLE_PAGE."  p
		LEFT JOIN  ".TABLE_PHOTO."  f ON (f.owner_id=p.page_id AND f.owner=$photo_owner[logo])
		WHERE p.parent=1 AND p.public='1' AND p.url!='' AND f.photo_id>0
		GROUP BY 	p.page_id
		ORDER BY RAND()
		LIMIT 5") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{
		$f = "images/$photo_dir[logo]/$arr[photo_id]-s.$arr[ext]"; 
		if(!file_exists($f)) continue;
		$arr['photo'] = "/".$f;

		$arr['name'] = HtmlSpecialChars($arr['name'], null, 'cp1251');
		$arr['stars'] = str_repeat("*", $arr['stars']);
		$block_images[] = $arr; 
	}

	return $block_images;
}

function shortorder()
{
	// ????? ??????	
	$order_fields = array();
	
	$sql = mysql_query("SELECT * FROM ".TABLE_FIELD." WHERE public='1' AND short ORDER BY short") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{
		$order_fields[] = $arr;
	}
	
	$fields = array();
	foreach($order_fields as $v) 
	{
		$id = $v['field_id'];
		$v['name'] = HtmlSpecialChars($v['name'], null, 'cp1251');
		
		switch ($v['type'])
		{
			case 0:
				//$v['value'] = HtmlSpecialChars($v["value"]);
				break;
			case 1:
				//$v['value'] = HtmlSpecialChars($v["value"]);
				break;
			case 2:
				$options_arr = split("((\r)?\n(\r)?)+", $v['data']);
				$options = "\n<option value=\"0\">".$v['name']."</option>";
				foreach($options_arr as $opt)
				{
					$options .= "\n<option value=\"".HtmlSpecialChars($opt, null, 'cp1251')."\">".HtmlSpecialChars($opt, null, 'cp1251')."</option>";
				}
				$v['value'] = $options;
				break;
			case 3:
				@list($d, $m, $y) = @split('\\.', $v['value']);
				$v['value'] = (@checkdate($m, $d, $y+2000)) ? "$d.$m.$y" : '';
				break;
		}		
		
		$fields[] = $v;
	}
	return $fields;
}

function short_description($text, $strlen)
{
	$text = strip_tags($text);
	if(strlen($text) <= $strlen) return $text;
	
	$text = substr($text, 0, $strlen);
	$text = strtolower(substr($text, 0, strrpos($text, " ")))."...";
	
	return $text;
}


function imagecreatefromBMP($filename)
{ /*http://ru2.php.net/manual/en/function.imagecreate.php#53879*/
   if (! $f1 = fopen($filename,"rb")) return FALSE;
 
   $FILE = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1,14));
   if ($FILE['file_type'] != 19778) return FALSE;
 
   $BMP = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel'.
                 '/Vcompression/Vsize_bitmap/Vhoriz_resolution'.
                 '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1,40));
   $BMP['colors'] = pow(2,$BMP['bits_per_pixel']);
   if ($BMP['size_bitmap'] == 0) $BMP['size_bitmap'] = $FILE['file_size'] - $FILE['bitmap_offset'];
   $BMP['bytes_per_pixel'] = $BMP['bits_per_pixel']/8;
   $BMP['bytes_per_pixel2'] = ceil($BMP['bytes_per_pixel']);
   $BMP['decal'] = ($BMP['width']*$BMP['bytes_per_pixel']/4);
   $BMP['decal'] -= floor($BMP['width']*$BMP['bytes_per_pixel']/4);
   $BMP['decal'] = 4-(4*$BMP['decal']);
   if ($BMP['decal'] == 4) $BMP['decal'] = 0;
 
   $PALETTE = array();
   if ($BMP['colors'] < 16777216)
   {
    $PALETTE = unpack('V'.$BMP['colors'], fread($f1,$BMP['colors']*4));
   }
 
   $IMG = fread($f1,$BMP['size_bitmap']);
   $VIDE = chr(0);
 
   $res = imagecreatetruecolor($BMP['width'],$BMP['height']);
   $P = 0;
   $Y = $BMP['height']-1;
   while ($Y >= 0)
   {
    $X=0;
    while ($X < $BMP['width'])
    {
     if ($BMP['bits_per_pixel'] == 24)
        $COLOR = unpack("V",substr($IMG,$P,3).$VIDE);
     elseif ($BMP['bits_per_pixel'] == 16)
     {
        $COLOR = unpack("n",substr($IMG,$P,2));
        $COLOR[1] = $PALETTE[$COLOR[1]+1];
     }
     elseif ($BMP['bits_per_pixel'] == 8)
     {
        $COLOR = unpack("n",$VIDE.substr($IMG,$P,1));
        $COLOR[1] = $PALETTE[$COLOR[1]+1];
     }
     elseif ($BMP['bits_per_pixel'] == 4)
     {
        $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
        if (($P*2)%2 == 0) $COLOR[1] = ($COLOR[1] >> 4) ; else $COLOR[1] = ($COLOR[1] & 0x0F);
        $COLOR[1] = $PALETTE[$COLOR[1]+1];
     }
     elseif ($BMP['bits_per_pixel'] == 1)
     {
        $COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
        if     (($P*8)%8 == 0) $COLOR[1] =  $COLOR[1]        >>7;
        elseif (($P*8)%8 == 1) $COLOR[1] = ($COLOR[1] & 0x40)>>6;
        elseif (($P*8)%8 == 2) $COLOR[1] = ($COLOR[1] & 0x20)>>5;
        elseif (($P*8)%8 == 3) $COLOR[1] = ($COLOR[1] & 0x10)>>4;
        elseif (($P*8)%8 == 4) $COLOR[1] = ($COLOR[1] & 0x8)>>3;
        elseif (($P*8)%8 == 5) $COLOR[1] = ($COLOR[1] & 0x4)>>2;
        elseif (($P*8)%8 == 6) $COLOR[1] = ($COLOR[1] & 0x2)>>1;
        elseif (($P*8)%8 == 7) $COLOR[1] = ($COLOR[1] & 0x1);
        $COLOR[1] = $PALETTE[$COLOR[1]+1];
     }
     else
        return FALSE;
     imagesetpixel($res,$X,$Y,$COLOR[1]);
     $X++;
     $P += $BMP['bytes_per_pixel'];
    }
    $Y--;
    $P+=$BMP['decal'];
   }
 
   fclose($f1);
 
 return $res;
}

function news_anons($s, $limit)
{
	global $photo_owner, $photo_dir, $photo_limit, $lprefix, $englang, $extra_parent_id;
	
	$where = "n.public='1'";  
	if($s=='news')
	{
		if($extra_parent_id) $where  .= " AND FIND_IN_SET('$extra_parent_id', n.pages)"; 
		else $where  .= " AND FIND_IN_SET('0', n.pages)";
	}
	
	$replace['date_string'] = ''; $order = 'date desc, news_id';
	
	$table = $s=='news' || $s=='block' ? TABLE_NEWS : TABLE_SPEC;
	$firstfield = $s=='news' || $s=='block' ? 'news_id' : 'spec_id';
	
	$sql = mysql_query("
		SELECT 
			n.$firstfield, n.name$englang as name, n.descr$englang as descr, n.date,
			f.photo_id, f.ext
		FROM 
			$table n 
			LEFT JOIN ".TABLE_PHOTO." f ON (f.owner_id=n.$firstfield AND f.owner='$photo_owner[$s]')
		WHERE 
			$where AND f.photo_id
		GROUP BY
			n.$firstfield
		ORDER BY 
			n.date desc, n.$firstfield 
		LIMIT 
			$limit") or Error(1, __FILE__, __LINE__);
	$news_list = array();
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name'], null, 'cp1251');
		if(!$info['name']) $info['name'] = NONAME;
				
		$info['descr'] = nl2br($info['descr']);
		
		list($y, $m, $d) = @split("-", $info['date']);
		$y -= 2000;
		$info['date'] = (int)$d.".$m.$y";
		
		$dir = $s=='news' || $s=='block' ? 'news' : 'spec';
		$info['link'] = "$lprefix/$dir/n$info[$firstfield]/";
		
		$info['photo']="images/$photo_dir[$s]/$info[photo_id]-s.$info[ext]";
				
		$news_list[] = $info;
	}
	
	return $news_list;

}

function get_ip()
{
	if (!empty($_SERVER['HTTP_CLIENT_IP']))  $ip=$_SERVER['HTTP_CLIENT_IP'];
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	else $ip=$_SERVER['REMOTE_ADDR'];
	
	return $ip;
}

?>