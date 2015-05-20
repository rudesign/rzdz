<?php

if($medicine) $news_dir = $extrasite_id ? $request[3] : $request[2];
else $news_dir = $extrasite_id ? $request[2] : $request[1];
		
$dir_id=0;
$news_id = 0;

if(ereg("^n([[:digit:]]+)", $news_dir, $F)) $news_id = $F[1];
elseif($news_dir)
{
	$news_dir = escape_string($news_dir);
	$sql = mysql_query("SELECT n.news_id, n.dir_id FROM ".TABLE_NEWS." n ".
		"LEFT JOIN ".TABLE_DIR." d ON (d.dir='$news_dir' AND n.dir_id=d.dir_id) ".
		"WHERE d.dir_id and n.public='1'") or Error(1, __FILE__, __LINE__);
	if($info = @mysql_fetch_array($sql))
	{ 
		$news_id = $info['news_id'];
		$dir_id = $info['dir_id'];
	}
	else {page404();return;}
}

if($news_id && $medicine) $navig[count($navig)-1]['link'] = $link_medicine."$cure_id/";
	

$link_root = $extrasite_id ? "$lprefix/$request[0]/$request[1]/" : "$lprefix/$request[0]/";	
if($medicine) $link_root .= $extrasite_id ? "$request[2]/" : "$request[1]/";

if($news_id)
{
	$replace['news_id'] = $news_id;
	
	$sql = mysql_query("SELECT n.news_id, n.name$englang as name, n.description$englang as description, 
			n.date, n.page_id, f.photo_id, f.ext FROM ".TABLE_NEWS." n ".
		"LEFT JOIN ".TABLE_PHOTO." f ON (f.owner_id=n.news_id AND f.owner='$photo_owner[news]') ".
		"WHERE news_id=$news_id and n.public='1'") 
		or Error(1, __FILE__, __LINE__);

	if($info = @mysql_fetch_array($sql))
	{ 
		$replace['name'] = HtmlSpecialChars($info['name']);
		if(!$replace['name']) $replace['name'] = NONAME;
		
		$replace['description'] = $info['description'];
		
		list($y, $m, $d) = @split("-", $info['date']);
		$replace['date1'] = $info['date'];
		$replace['date'] = (int)$d.".$m.".substr($y,-2,2);
		$replace['page_id'] = $info['page_id'];
		
		$replace['photo'] = ''; 
		if($info['photo_id'])
		{ 
			$photo_id = $info['photo_id'];
			$ext = $info['ext'];
			if(is_file($f="/images/$photo_dir[news]/${photo_id}-s.$ext")) {
				list($w_small, $h_small) = @getimagesize($f);
				$replace['photo'] = $f;
				$replace['photosize'] = "width='$w_small' height='$h_small'";
			}
		}
		
		$where = "public='1'";  
		
		if($medicine) $where  .= " AND FIND_IN_SET('-1', pages)"; 
		elseif($extra_parent_id) $where  .= " AND FIND_IN_SET('$extra_parent_id', pages)"; 
		else $where  .= " AND FIND_IN_SET('0', pages)";

		$sql = mysql_query("SELECT count(*) FROM ".TABLE_NEWS.
				" WHERE $where  and (date>'$info[date]' or (date='$info[date]' and news_id>$news_id))") 
				or Error(1, __FILE__, __LINE__);
		$info = @mysql_fetch_array($sql);
		$ord = @(int)$info[0]+1;
		
		$on_page = $settings['news_count'];
	
		$page = ($ord%$on_page) ? (int)($ord/$on_page)+1 : $ord/$on_page;
		$replace['link'] = $link_root."?page=$page";
		
		if($replace['page_id'])
		{
			$sql1 = mysql_query("SELECT p.page_id, p.name$englang as name, d.dir FROM  ".TABLE_PAGE."  p 
				LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
				WHERE p.page_id=$replace[page_id]") or Error(1, __FILE__, __LINE__);
			$arr = @mysql_fetch_array($sql1);
			
			if(!@$arr['page_id'])	{page404(); return;} 
			
			$replace['page_name'] = $arr['name'];
			$replace['page_link'] = "$lprefix/$dir_sanatorium/$arr[dir]/";
		}
		
		$replace['order_link'] = $replace['page_id'] ? "$lprefix/order/?tour_id=$replace[page_id]" : '';
		
		$sql1 = mysql_query("SELECT n.name$englang as name, n.news_id, n.date, d.dir FROM ".TABLE_NEWS." n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id)
					 WHERE $where AND ( (date='$replace[date1]' AND news_id<$news_id) OR date<'$replace[date1]' )
					ORDER BY date desc, news_id desc LIMIT 1") or Error(1, __FILE__, __LINE__); 
		if(!mysql_num_rows($sql1))
		$sql1 = mysql_query("SELECT n.name$englang as name, news_id, date, d.dir FROM ".TABLE_NEWS."  n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id) 
					 WHERE $where AND news_id!=$news_id
					ORDER BY date desc, news_id desc LIMIT 1") or Error(1, __FILE__, __LINE__); 
		if($arr1 = @mysql_fetch_array($sql1))
		{
			$replace['next_link'] = $arr1['dir'] ? $link_root.$arr1['dir'] : $link_root."n$arr1[news_id]/";
			$replace['next_name'] = HtmlSpecialChars(@$arr1['name']);
			list($y, $m, $d) = @split("-", $arr1['date']);
			$replace['next_date'] = (int)$d.".$m.".substr($y,-2,2);
		} else $replace['next_link'] = '';
		
		$sql1 = mysql_query("SELECT n.name$englang as name, n.news_id, n.date, d.dir FROM ".TABLE_NEWS." n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id)
					 WHERE $where AND ( (date='$replace[date1]' AND news_id>$news_id) OR date>'$replace[date1]' )
					ORDER BY date, news_id  LIMIT 1") or Error(1, __FILE__, __LINE__); 
		if(!mysql_num_rows($sql1))
		$sql1 = mysql_query("SELECT n.name$englang as name, news_id, date, d.dir FROM ".TABLE_NEWS." n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id) 
					 WHERE $where AND news_id!=$news_id
					ORDER BY date, news_id LIMIT 1") or Error(1, __FILE__, __LINE__); 
		if($arr1 = @mysql_fetch_array($sql1))
		{
			$replace['prev_link'] = $arr1['dir'] ? $link_root.$arr1['dir'] : $link_root."n$arr1[news_id]/";
			$replace['prev_name'] = HtmlSpecialChars(@$arr1['name']);
			list($y, $m, $d) = @split("-", $arr1['date']);
			$replace['prev_date'] = (int)$d.".$m.".substr($y,-2,2);
		
		} else $replace['prev_link'] = '';
	
		
		if(!$medicine)	$navig[] = array('name'=>$lang_phrases['news'], 'link'=>$replace['link']);			
		$navig[] = array('name'=>$replace['name'], 'link'=>'');
	
		$content = get_template('templ/news.htm', $replace);
	
	}
	else 
	{
		page404();
	}
// list
} else {
	$replace = array();
	
	if(!$medicine) $navig[] = array('name'=>$lang_phrases['news'], 'link'=>'');
	
	$where = "n.public='1'";  
	if($medicine) $where  .= " AND FIND_IN_SET('-1', pages)"; 
	elseif($extra_parent_id) $where  .= " AND FIND_IN_SET('$extra_parent_id', pages)"; 
	else $where  .= " AND FIND_IN_SET('0', pages)";
	 
	$order = 'date desc, news_id desc';
	
	$sql = mysql_query("SELECT count(*) FROM ".TABLE_NEWS." n WHERE $where") 
		or Error(1, __FILE__, __LINE__);
	$info = @mysql_fetch_array($sql);
	$all = @(int)$info[0];
	
	$on_page = $settings['news_count'];

	$url = $link_root."?";
	list($limit, $replace['pages']) = user_pages($all, $url, $on_page);
	
	$sql = mysql_query("SELECT n.news_id, n.name$englang as name, n.descr$englang as descr, n.date, d.dir, f.photo_id, f.ext FROM ".TABLE_NEWS." n ".
		"LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id) ".
		"LEFT JOIN ".TABLE_PHOTO." f ON (f.owner_id=n.news_id AND f.owner='$photo_owner[news]') ".
		"WHERE $where ORDER BY $order LIMIT $limit") 
		or Error(1, __FILE__, __LINE__);
	
	$list = array();
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
				
		$info['descr'] = nl2br($info['descr']);
		
		list($y, $m, $d) = @split("-", $info['date']);
		$info['date'] = (int)$d.".$m.".substr($y,-2,2);
		
		$info['link'] =  $info['dir'] ? $link_root.$info['dir'] : $link_root."n$info[news_id]/";
		
		$info['photo'] = ''; 
		if($info['photo_id'])
		{ 
			$photo_id = $info['photo_id'];
			$ext = $info['ext'];
			if(is_file($f="/images/$photo_dir[news]/${photo_id}-s.$ext")) {
				list($w_small, $h_small) = @getimagesize($f);
				$info['photo'] = $f;
				$info['photosize'] = "width='$w_small' height='$h_small'";
			}
		}
		
		$list[] = $info;
	}
	
	$replace['list'] = $list;

	$content = get_template('templ/news_list.htm', $replace);

}

if(!$medicine) get_page_info($part, $dir_id, @$replace['name']);
?>