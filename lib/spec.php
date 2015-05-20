<?php

$spec_dir = $extrasite_id ? $request[2] : $request[1];
		
$dir_id=0;
$spec_id = (int)@$spec_id;

if(ereg("^n([[:digit:]]+)", $spec_dir, $F)) $spec_id = $F[1];
elseif($spec_dir)
{
	$spec_dir = escape_string($spec_dir);
	$sql = mysql_query("SELECT n.spec_id, n.dir_id FROM ".TABLE_SPEC." n ".
		"LEFT JOIN ".TABLE_DIR." d ON (d.dir='$spec_dir' AND n.dir_id=d.dir_id) ".
		"WHERE d.dir_id and n.public='1'") or Error(1, __FILE__, __LINE__);
	if($info = @mysql_fetch_array($sql))
	{ 
		$spec_id = $info['spec_id'];
		$dir_id = $info['dir_id'];
	}
	else {page404();return;}
}

$link_root = $extrasite_id ? "$lprefix/$request[0]/$request[1]/" : "$lprefix/$request[0]/";	

if($spec_id)
{
		$replace['spec_id'] = $spec_id;
		
		$sql = mysql_query("SELECT n.spec_id, n.name$englang as name, n.description$englang as description, 
			n.date, n.page_id, n.dir_id, f.photo_id, f.ext FROM ".TABLE_SPEC." n ".
			"LEFT JOIN ".TABLE_PHOTO." f ON (f.owner_id=n.spec_id AND f.owner='$photo_owner[spec]') ".
			"WHERE spec_id=$spec_id and n.public='1'") 
			or Error(1, __FILE__, __LINE__);
	
		if($info = @mysql_fetch_array($sql))
		{ 
			$dir_id = $info['dir_id'];
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
				if(is_file($f="/images/$photo_dir[spec]/${photo_id}-s.$ext")) {
					list($w_small, $h_small) = @getimagesize($f);
					$replace['photo'] = $f;
					$replace['photosize'] = "width='$w_small' height='$h_small'";
				}
			}
			
			$sql = mysql_query("SELECT count(*) FROM ".TABLE_SPEC.
					" WHERE public='1' and (date>'$info[date]' or (date='$info[date]' and spec_id>$spec_id))") 
					or Error(1, __FILE__, __LINE__);
			$info = @mysql_fetch_array($sql);
			$ord = @(int)$info[0]+1;
			
			$on_page = $settings['spec_count'];
		
			$page = ($ord%$on_page) ? (int)($ord/$on_page)+1 : $ord/$on_page;
			$replace['link'] = "$lprefix/spec/?page=$page";
			
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
			
			$sql1 = mysql_query("SELECT n.name$englang as name, spec_id, date, d.dir FROM ".TABLE_SPEC." n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id)
						 WHERE public='1' AND ( (date='$replace[date1]' AND spec_id<$spec_id) OR date<'$replace[date1]' )
						ORDER BY date desc, spec_id desc LIMIT 1") or Error(1, __FILE__, __LINE__); 
			if(!mysql_num_rows($sql1))
			$sql1 = mysql_query("SELECT n.name$englang as name, spec_id, date, d.dir FROM ".TABLE_SPEC." n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id)
						 WHERE public='1' AND spec_id!=$spec_id
						ORDER BY date desc, spec_id desc LIMIT 1") or Error(1, __FILE__, __LINE__); 
			if($arr1 = @mysql_fetch_array($sql1))
			{
				$replace['next_link'] = $arr1['dir'] ? $link_root.$arr1['dir'] : $link_root."n$arr1[spec_id]/";
				$replace['next_name'] = HtmlSpecialChars(@$arr1['name']);
				list($y, $m, $d) = @split("-", $arr1['date']);
				$replace['next_date'] = (int)$d.".$m.".substr($y,-2,2);
			} else $replace['next_link'] = '';
			
			$sql1 = mysql_query("SELECT n.name$englang as name, spec_id, date, d.dir FROM ".TABLE_SPEC." n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id)
						 WHERE public='1' AND ( (date='$replace[date1]' AND spec_id>$spec_id) OR date>'$replace[date1]' )
						ORDER BY date, spec_id  LIMIT 1") or Error(1, __FILE__, __LINE__); 
			if(!mysql_num_rows($sql1))
			$sql1 = mysql_query("SELECT n.name$englang as name, spec_id, date, d.dir FROM ".TABLE_SPEC." n 
					LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id)
						 WHERE public='1' AND spec_id!=$spec_id
						ORDER BY date , spec_id  LIMIT 1") or Error(1, __FILE__, __LINE__); 
			if($arr1 = @mysql_fetch_array($sql1))
			{
				$replace['prev_link'] = $arr1['dir'] ? $link_root.$arr1['dir'] : $link_root."n$arr1[spec_id]/";
				$replace['prev_name'] = HtmlSpecialChars(@$arr1['name']);
				list($y, $m, $d) = @split("-", $arr1['date']);
				$replace['prev_date'] = (int)$d.".$m.".substr($y,-2,2);
			
			} else $replace['prev_link'] = '';
		
			
			$navig[] = array('name'=>$lang_phrases['spec'], 'link'=>$replace['link']);
				
			$navig[] = array('name'=>$replace['name'], 'link'=>'');
		
			$content = get_template('templ/news.htm', $replace);
		
		}
		else 
		{
			page404();
		}
			
}
else
{	 
	$replace = array();
	
	$navig[] = array('name'=>$lang_phrases['spec'], 'link'=>'');
	
	$where = "n.public='1'";  $order = 'date desc, spec_id desc';
	
	$sql = mysql_query("SELECT count(*) FROM ".TABLE_SPEC." n WHERE $where") 
		or Error(1, __FILE__, __LINE__);
	$info = @mysql_fetch_array($sql);
	$all = @(int)$info[0];
	
	$on_page = $settings['spec_count'];
	
	list($limit, $replace['pages']) = user_pages($all, "$lprefix/$part/?", $on_page);
	
	$sql = mysql_query("SELECT n.spec_id, n.name$englang as name, n.descr$englang as descr, n.date, d.dir, f.photo_id, f.ext FROM ".TABLE_SPEC." n ".
		"LEFT JOIN ".TABLE_DIR." d ON (n.dir_id=d.dir_id) ".
		"LEFT JOIN ".TABLE_PHOTO." f ON (f.owner_id=n.spec_id AND f.owner='$photo_owner[spec]') ".
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
		
		$info['link'] =  $info['dir'] ? $link_root.$info['dir'] : $link_root."n$info[spec_id]/";
		
		$info['photo'] = ''; 
		if($info['photo_id'])
		{ 
			$photo_id = $info['photo_id'];
			$ext = $info['ext'];
			if(is_file($f="/images/$photo_dir[spec]/${photo_id}-s.$ext")) {
				list($w_small, $h_small) = @getimagesize($f);
				$info['photo'] = $f;
				$info['photosize'] = "width='$w_small' height='$h_small'";
			}
		}
		
		$list[] = $info;
	}
	
	$replace['list'] = $list;
	
	$file = 'templ/news_list.htm';
	
	$content = get_template($file, $replace);
}

get_page_info($part, $dir_id, @$replace['name']);
?>