<?php

function uslugi($curestr_id, $cure_id, $sid)
{
	global $extrasite_id, $englang, $lprefix, $request;
	
	
	$link_medicine =  "$lprefix/$request[0]/";
	if($extrasite_id) 
	{
		$link_medicine = "$lprefix/$request[1]/";
		$link_extrasite = "$lprefix/$request[0]/$request[1]/";
	}
	
	$uslugi = array();
	
	$left_join = $extrasite_id ? "
		LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=c.cure_id AND h.page_id=$extrasite_id)
		LEFT JOIN ".TABLE_TABLE." t ON (t.parent=0 AND t.cure_id=c.cure_id AND t.page_id=$extrasite_id)
		" : '';
	$fields = $extrasite_id ? ", c.inmenu, h.cure_id, h.price$englang as  price, 
		h.name$englang as prname, h.description$englang as prdescription, h.title, t.table_id" : '';
	$where = $extrasite_id ? " AND h.cure_id" : " AND c.inmenu";
	
	$sql_uslugi = mysql_query("SELECT c.cure_id, c.name$englang as name, c.description$englang as description $fields FROM ".TABLE_CURE." c
		$left_join
		WHERE c.curestr_id=$curestr_id  $where
		ORDER BY c.ord") 
		or Error(1, __FILE__, __LINE__); 
	while($info_uslugi = @mysql_fetch_array($sql_uslugi))
	{ 
		$info_uslugi['name'] = $extrasite_id && @$info_uslugi['prname'] ?  HtmlSpecialChars($info_uslugi['prname']) :
			HtmlSpecialChars($info_uslugi['name']);
		if(!$info_uslugi['name']) $info_uslugi['name'] = NONAME;
				
		if($extrasite_id)  
		{
			if($info_uslugi['prdescription']) $info_uslugi['url'] =  $link_extrasite."$cure_id/$info_uslugi[cure_id]/";
			elseif($cure_id==6 || $cure_id==9) $info_uslugi['url'] = '';
			else $info_uslugi['url'] =  $info_uslugi['inmenu'] ?  $link_medicine."$cure_id/$info_uslugi[cure_id]/\" target=\"_blank" : '';
			
		}
		else $info_uslugi['url'] = $link_medicine."$cure_id/$info_uslugi[cure_id]/";
		
		$info_uslugi['sel'] = $sid==$info_uslugi['cure_id'] ? 1 :0;
		$info_sect['sub'] = 0;

		$uslugi[] = $info_uslugi;
				
		if(@$info_uslugi['table_id']) 
		{						
			$sql_sect = mysql_query("SELECT table_id, name$englang as name, name1$englang as name1, title,  tab FROM ".TABLE_TABLE." 
				WHERE parent=$info_uslugi[table_id] ORDER BY ord") 
				or Error(1, __FILE__, __LINE__);
			while($info_sect = @mysql_fetch_array($sql_sect))
			{ 
				$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
				$info_sect['price'] = HtmlSpecialChars($info_sect['name1']);
				$info_sect['url'] = '';
				$info_sect['sel'] = $sid==$info_sect['table_id'] ? 1 :0;
				$info_sect['sub'] = 1;
				
				$uslugi[] = $info_sect;
			}
		}
	}
	
	if($extrasite_id)
	{
		$sql_uslugi = mysql_query("SELECT t.table_id, t.name$englang as name, t.name1$englang as name1, t.title,  t.tab FROM ".TABLE_TABLE." t
			LEFT JOIN ".TABLE_TABLE." tp ON (t.parent=tp.table_id)
			WHERE tp.table_id AND tp.parent=0 AND t.curestr_id=$curestr_id AND t.page_id=$extrasite_id
			ORDER BY t.ord") 
			or Error(1, __FILE__, __LINE__); 
		while($info_sect = @mysql_fetch_array($sql_uslugi))
		{ 
			$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
			$info_sect['price'] = HtmlSpecialChars($info_sect['name1']);
			$info_sect['url'] = '';
			$info_sect['sel'] = $sid==$info_sect['table_id'] ? 1 :0;
			$info_sect['sub'] = 1;
			$uslugi[] = $info_sect;			
		}
	}
	
	return $uslugi;
}

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
	$fn = $extrasite_id ? "if(name_extra$englang!='',name_extra$englang,name$englang)" : "name$englang";
	$fields = "cure_id, $fn as name, type, inhotel$englang ";
	if(!$subcure_id)  $fields .=  ", description$englang as description";
	if($subcure_id && @$curestr_id)  $fields .=  ", inhotel$englang as inhotel";
	  
	$sql = mysql_query("SELECT $fields 
		FROM ".TABLE_CURE." WHERE cure_id=$cure_id AND public") or Error(1, __FILE__, __LINE__);
	if(!($cure = @mysql_fetch_array($sql))) {page404();return;}
	
	$page_name = $cure['name'];
	
	if($extrasite_id && $cure_id==11) $cure['description'] = str_replace("medicine/11/49", "$request[0]/medicine/11/49",$cure['description']);
		
	/*if($cure['type']==3 && ! ( ($cure['cure_id']==5 || $cure['cure_id']==8) && !$extrasite_id ) )
	{
		$sql = mysql_query("SELECT $fields FROM ".TABLE_CURE." WHERE partof=$cure_id AND public ORDER BY ord LIMIT 1") 
			or Error(1, __FILE__, __LINE__);
		if(!($cure = @mysql_fetch_array($sql))) {page404();return;}
		$cure_id = $cure['cure_id'];
		if($extrasite_id) $request[2] = $cure_id;
		else $request[1] = $cure_id;
	}*/
	
	if($cure['cure_id']==1 || $cure_id==90)
	{
        $q = "SELECT name$englang as name, description$englang as description FROM ".TABLE_CURE." WHERE cure_id=14";
		$sql1 = mysql_query($q) or Error(1, __FILE__, __LINE__);
		$arr = @mysql_fetch_array($sql1);
		$cure['att_name'] = 	$arr['name'];	
		$cure['att_description'] = $arr['description'];	
		
	}
	if($cure['cure_id']==14)
	{
		$list = array();
        $q = "SELECT cure_id, name$englang as name FROM ".TABLE_CURE." WHERE parent=14 ORDER BY ord";
		$sql1 = mysql_query($q) or Error(1, __FILE__, __LINE__);
		while($info = @mysql_fetch_array($sql1))
		{ 
			$list[] = array('name'=>$info['name'], 'link'=>$link_medicine."14/".$info['cure_id']);
		}
		$cure['att_list'] = $list;
	}
	
	$cure['name'] = htmlspecialchars($cure['name']);

	$link = $cure['cure_id']==9 && @$curestr_id && !$extrasite_id ?  $link_medicine."8/"  :
		( $subcure_id || ($cure['type']==6 && @$s_id) ? $link_medicine."$cure_id/" : '' );
	$navig[] = array('link'=>$link, 'name'=>$cure['name']);
	
	if(!$subcure_id || $cure['type']==1)  
	{
		if($cure['type']==1 || $cure['type']==4)
		{ 
			$ord = $cure['type']==2 ? 'c.name' : 'c.ord';
			
			if($extrasite_id && $cure['type']!=4) 
				$query ="SELECT c.cure_id, c.name$englang as name, anons$englang as anons, h.cure_id, h.price$englang as  price
					FROM ".TABLE_CURE." c 
				LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=c.cure_id AND h.page_id=$extrasite_id)
				WHERE c.parent=$cure_id AND c.public
				GROUP BY c.cure_id
				ORDER BY $ord";
			else
				$query ="SELECT c.cure_id, anons$englang as anons, c.name$englang as name , c.description$englang as description, 
					f.photo_id, f.ext
					FROM ".TABLE_CURE." c 
					LEFT JOIN ".TABLE_PHOTO." f ON (f.owner=$photo_owner[cure_part] AND f.owner_id=c.cure_id)
					WHERE c.parent=$cure_id AND c.public ORDER BY $ord";
			$sql = mysql_query($query) or Error(1, __FILE__, __LINE__);
			
			$cures = array(); 
			$sql_count = mysql_num_rows($sql); $col_count = 3;
			$in_col = ($sql_count%$col_count) ? (int)($sql_count/$col_count)+1 : $sql_count/$col_count; 
			$k=0; $title = '';
			while($info = @mysql_fetch_array($sql))
			{ 
				if($extrasite_id && !@$info['cure_id']) 
				{
					$title = HtmlSpecialChars($info['anons']);
					continue;
				}
				$k++; 
				$info['name'] = $info['name'] ? HtmlSpecialChars($info['name'], ENT_COMPAT, 'cp1251') : NONAME;
				
				if($cure['type']!=2 )
				{
					$info['title'] = HtmlSpecialChars($info['anons'], ENT_COMPAT, 'cp1251');
					if($extrasite_id && !$info['anons'] && $title)
					{
						$info['title'] = $title; $title = '';
					}
				}
				else $info['title'] = '';
				
				$info['url'] = $link_medicine."$cure_id/"."$info[cure_id]/";
				$info['sel'] = $subcure_id==$info['cure_id'] ? 1 : 0;
				
				$info['newcol'] = !(($k+$in_col)%$in_col) && $k!=$sql_count ? 1 : 0; 
				
				$price = (int)@$info['price'];
				$info['price'] = $price>0 && @$info['price']==$price ? $price." ���." : htmlspecialchars(@$info['price']);
				
				$f= @$info['photo_id'] ? "images/$photo_dir[cure_part]/$info[photo_id]-s.$info[ext]" : '';
				if($f && file_exists($f))
				{
					$info['photo'] = $f;
				}
				else $info['photo'] = '';

                $info['teaser'] = HtmlSpecialChars($info['anons'], ENT_COMPAT, 'cp1251');
				
				$info['podrazdel'] = array();
				if($cure['type']==1 && $cure_id!=4)
				{
					if($extrasite_id) 
						$query ="SELECT c.cure_id, c.name$englang as name
							FROM ".TABLE_CURE." c 
						LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=c.cure_id AND h.page_id=$extrasite_id)
						WHERE c.parent=$info[cure_id] AND c.public AND h.cure_id
						GROUP BY c.cure_id
						ORDER BY $ord";
					else
						$query ="SELECT c.cure_id, c.name$englang as name  
							FROM ".TABLE_CURE." c 
							WHERE c.parent=$info[cure_id] AND c.public ORDER BY $ord";
					$sql1 = mysql_query($query) or Error(1, __FILE__, __LINE__);
					$list = array();
					while($info1 = @mysql_fetch_array($sql1))
					{
						$info1['name'] = $info1['name'] ? HtmlSpecialChars($info1['name']) : NONAME;
						$info1['url'] = $link_medicine."$cure_id/"."$info1[cure_id]/";
						$list[] = $info1;
					}	
					$info['podrazdel'] = 	$list;			
				}
				
				$cures[] = $info;
			}
			$replace['cure_list'] = $cures; 
		}
		elseif( ($cure['type']==3 || ($cure['type']==2 && 
			(($cure['cure_id']==9) || ($cure['cure_id']!=9 && @$curestr_id)) )) && !$extrasite_id)
		{
			$curestr_id = (int)@$curestr_id;
			if($curestr_id)
			{
				$sql = mysql_query("SELECT name$englang as name, description$englang as description, parent 
					FROM ".TABLE_CURESTR." WHERE curestr_id=$curestr_id") 
					or Error(1, __FILE__, __LINE__);
				$curestr = @mysql_fetch_array($sql);
				$cure['description'] = $curestr['description'];
				
				$navig[count($navig)-1]['link'] = $cure['cure_id']==9 ? $link_medicine."8" : $link_medicine."$cure_id";
				$page_name = $curestr['name'];
					
				if($curestr['parent'])
				{
					$sql1 = mysql_query("SELECT name$englang as name, description$englang as description
						FROM ".TABLE_CURESTR." WHERE curestr_id=$curestr[parent]") 
						or Error(1, __FILE__, __LINE__);
					$info = @mysql_fetch_array($sql1);
					
					$navig[] = array('name'=>$info['name'], 'link'=>$link_medicine."$cure_id/?curestr_id=".$curestr['parent']);
				}
				$navig[] = array('name'=>$curestr['name'], 'link'=>'');
			}
			if($cure['cure_id']==8 && !$curestr_id)
			{
				$sql = mysql_query("SELECT c.curestr_id, c.name$englang as name, f.photo_id, f.ext FROM ".TABLE_CURESTR." c 					
					LEFT JOIN ".TABLE_PHOTO." f ON (f.owner=$photo_owner[curestr] AND f.owner_id=c.curestr_id)
					WHERE c.parent=$curestr_id AND c.cure_id=9 ORDER BY c.ord") 
					or Error(1, __FILE__, __LINE__);
				
				$curestr =  array();
				while($info = @mysql_fetch_array($sql))
				{ 
					$info['name'] = HtmlSpecialChars($info['name']);
					if(!$info['name']) $info['name'] = NONAME;
					
					$info['link'] = "$lprefix/medicine/9/?curestr_id=$info[curestr_id]";
					
					$info['photo'] = @$info['photo_id'] ? "images/$photo_dir[curestr]/$info[photo_id]-s.$info[ext]" :	"";
					
					$curestr[] = $info;
				}
				$replace['curestr'] = $curestr;
			}
			
			if($curestr_id)
			{
				$replace['curestr_id'] = $curestr_id;
				$arr = array();
				$table = TABLE_CURESTRHOTEL;
				$where  = "h.curestr_id=$curestr_id";
				$hf = "h.curestr_id";
				/*$sql = mysql_query("SELECT cure_id FROM ".TABLE_CURE." WHERE curestr_id=$curestr_id") 
					or Error(1, __FILE__, __LINE__);
				while($info = @mysql_fetch_array($sql)) $arr[] = "h.cure_id=$info[cure_id]";
				if(count($arr)) $where = "(".join(" OR ", $arr).")";
				else $where = "h.cure_id=-1";*/
			}
			else {$table = TABLE_CUREHOTEL; $where = "h.cure_id=$cure[cure_id]"; $hf = "h.cure_id";}
			
			$cure['inhotel'] = str_replace("[service]", @$curestr['name'], $cure['inhotel']);
			$curehotel = array(); 
			
			if($cure_id!=8 || $curestr_id)
			{
				$sql = mysql_query("SELECT $hf, p.page_id, p.name$englang as name, ct.name$englang as city,  
					fb.photo_id as fb_id, fb.ext as fb_ext, sd.dir as sp_dir, t.table_id
					FROM ".TABLE_PAGE." p
					LEFT JOIN $table h  ON ($where AND h.page_id=p.page_id)
					LEFT JOIN ".TABLE_TABLE." tp  ON (tp.parent=0 AND tp.curestr_id=$curestr_id AND tp.page_id=p.page_id)
					LEFT JOIN ".TABLE_TABLE." t  ON (t.parent=tp.table_id)
					LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
					LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
					LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
					LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$photo_owner[brochure])
					WHERE p.parent=1 AND p.public AND ($hf OR (tp.curestr_id AND t.table_id))
					GROUP BY p.page_id
					ORDER BY p.name") 
					or Error(1, __FILE__, __LINE__);
					
				while($info = @mysql_fetch_array($sql)) 
				{
					$info['photo'] = file_exists($fb="images/$photo_dir[brochure]/$info[fb_id]-s.$info[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
					$info['name'] = htmlspecialchars($info['name']);
					$info['city'] = htmlspecialchars($info['city']);
					
					if($info['sp_dir'])
					{
						if($cure_id==9 && @$curestr_id) 
							$info['page_link'] = $info['sp_dir']."/medicine/9/\" target=\"_blank";
						
						elseif($curestr_id) 
							$info['page_link'] = $info['table_id'] ? $info['sp_dir']."/medicine/$cure_id/?sid=$info[table_id]#price\" target=\"_blank" :
								$info['sp_dir']."/medicine/$cure_id/?curestr_id=$info[curestr_id]\" target=\"_blank";
								
						else $info['page_link'] = $info['sp_dir']."/medicine/$cure_id/\" target=\"_blank";
					}
					else $info['page_link'] = "$lprefix/media/?s_id=$info[page_id]";
					
					$curehotel[] = $info;	
				}
			}
			$replace['curehotel'] = $curehotel;
			
		}
		elseif($cure['type']==3 && $extrasite_id)
		{
			$link_medicine =  "$lprefix/$request[0]/$request[1]/";
			$cure_list = array();
			$sql1 = mysql_query("SELECT cure_id, name$englang as name, type FROM ".TABLE_CURE."  
				WHERE parent=0 AND partof=$cure_id AND public
				ORDER BY ord") or Error(1, __FILE__, __LINE__);
			while($arr1 = @mysql_fetch_array($sql1))
			{
				$arr1['name'] = HtmlSpecialChars($arr1['name'], null, 'cp1251');				
				$arr1['url'] =  $link_medicine."$arr1[cure_id]";
				
				$cure_list[] = $arr1;
			}		
			$replace['cure_list'] = $cure_list;
		}
		elseif($cure['type']==2)
		{	
			$curestr_id = (int)@$curestr_id;
			if($curestr_id)
			{
				if($extrasite_id)
					$sql = mysql_query("
					SELECT 
						c.name$englang as name, c.description$englang as description , c.parent,
						h.name$englang as ename, h.description$englang as edescription 
					FROM 
						".TABLE_CURESTR." c
						LEFT JOIN ".TABLE_CURESTRHOTEL." h ON (h.curestr_id=c.curestr_id AND h.page_id=$extrasite_id)
					WHERE c.curestr_id=$curestr_id") 
					or Error(1, __FILE__, __LINE__);
				else
					$sql = mysql_query("SELECT name$englang as name, description$englang as description, parent
					FROM ".TABLE_CURESTR." WHERE curestr_id=$curestr_id") 
					or Error(1, __FILE__, __LINE__);
				$curestr = @mysql_fetch_array($sql);
				$cure['description'] = @$curestr['edescription'] ? $curestr['edescription'] : $curestr['description'];
				$curestr['name'] = @$curestr['ename'] ? $curestr['ename'] : $curestr['name'];
				
				$navig[count($navig)-1]['link'] = $cure_id==9 && !$extrasite_id ? $link_medicine."8" : $link_medicine."$cure_id";
				$page_name = $curestr['name'];
					
				if($curestr['parent'])
				{
					$sql1 = mysql_query("SELECT name$englang as name, description$englang as description
						FROM ".TABLE_CURESTR." WHERE curestr_id=$curestr[parent]") 
						or Error(1, __FILE__, __LINE__);
					$info = @mysql_fetch_array($sql1);
					
					$navig[] = array('name'=>$info['name'], 'link'=>$link_medicine."$cure_id/?curestr_id=".$curestr['parent']);
				}
				$navig[] = array('name'=>$curestr['name'], 'link'=>'');
			}
			$replace['sid'] = $sid = (int)@$_GET['sid'];
			$cures =  array();
			
			$replace['pdf'] = '';
			if($extrasite_id)
			{
				$sql = mysql_query("SELECT f.photo_id, f.ext
					FROM ".TABLE_PHOTO."  f 
					WHERE f.owner=$photo_owner[cure_pdf] AND f.owner_id=$extrasite_id") 
					or Error(1, __FILE__, __LINE__);
				$arr = @mysql_fetch_array($sql);
				
				$replace['pdf'] = file_exists($f="images/$photo_dir[cure_pdf]/$arr[photo_id]-s.$arr[ext]") ? "/".$f : "";
			}
			elseif($cure_id==2)
			{
				$curehotel = array();
				$ord = 'p.name';
				$sql = mysql_query("SELECT p.page_id, p.name$englang as name, ct.name$englang as city, 
					fb.photo_id as fb_id, fb.ext as fb_ext, sd.dir as sp_dir
					FROM ".TABLE_PAGE." p
					LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=$cure_id AND h.page_id=p.page_id)
					LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
					LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
					LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
					LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$photo_owner[brochure])
					WHERE p.parent=1 AND p.public AND h.cure_id 
					GROUP BY p.page_id
					ORDER BY $ord") 
					or Error(1, __FILE__, __LINE__); 
				while($info = @mysql_fetch_array($sql)) 
				{
					$info['photo'] = file_exists($fb="images/$photo_dir[brochure]/$info[fb_id]-s.$info[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
					$info['name'] = htmlspecialchars($info['name']);
					$info['city'] = htmlspecialchars($info['city']);
					
					$info['page_link'] = $info['sp_dir'] ?  
						($cure['type']==2 ? $info['sp_dir']."/medicine/$cure_id/?sid=$subcure_id#price\" target=\"_blank"
							: ($cure_id==1 ? $info['sp_dir']."/medicine/\" target=\"_blank" : 
								$info['sp_dir']."/medicine/$cure_id/$subcure_id\" target=\"_blank")  )
						: "$lprefix/media/?s_id=$info[page_id]"; 
					$curehotel[] = $info;	
				}
				$replace['curehotel'] = $curehotel;
			}
			
			if(!$replace['pdf'])
			{
				$where = $curestr_id ? " AND curestr_id=$curestr_id" : '';
				$replace['showall'] = $curestr_id ? 1 : 0;
				$sql = mysql_query("SELECT curestr_id, name$englang as name FROM ".TABLE_CURESTR." 
					WHERE parent=0 AND cure_id=$cure_id $where ORDER BY ord") 
					or Error(1, __FILE__, __LINE__);
				
				while($info = @mysql_fetch_array($sql))
				{ 
					$info['name'] = HtmlSpecialChars($info['name']);
					if(!$info['name']) $info['name'] = NONAME;
					
					$info['url'] = $link_medicine."$cure_id/"."?curestr_id=$info[curestr_id]";
					
					$list = array();
					
					$info['uslugi'] = uslugi($info['curestr_id'], $cure_id, $sid);
					
					$sql_sect = mysql_query("SELECT cs.curestr_id, cs.name$englang as name, tb.table_id FROM ".TABLE_CURESTR."  cs
						LEFT JOIN ".TABLE_TABLE."  tb ON (tb.curestr_id=cs.curestr_id)
						WHERE cs.parent=$info[curestr_id] 
						GROUP BY cs.curestr_id
						ORDER BY cs.ord") 
						or Error(1, __FILE__, __LINE__);
					while($info_sect = @mysql_fetch_array($sql_sect))
					{ 
						$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
						if(!$info_sect['name']) $info_sect['name'] = NONAME;
								
						$info_sect['uslugi'] = uslugi($info_sect['curestr_id'], $cure_id, $sid);
						if(!count($info_sect['uslugi']) && $extrasite_id) continue;
						
						$sub = 0;
						foreach($info_sect['uslugi'] as $v) {if(@$v['cure_id']) break; if(@$v['sub']) {$sub=1;break;} }
						
						if($info_sect['curestr_id']==69)
							$info_sect['url'] = "$lprefix/medicine/11/49/";
						elseif($sub)  
							$info_sect['url'] = $extrasite_id ? "$lprefix/medicine/$cure_id/"."?curestr_id=$info_sect[curestr_id]\" target=\"_blank" : '';
						elseif($info_sect['table_id'] && !$extrasite_id) 
							$info_sect['url'] = "$lprefix/medicine/$cure_id/"."?curestr_id=$info_sect[curestr_id]";
						else $info_sect['url'] = '';
						
						$list[] = $info_sect;
					}
					$info['list'] = $list;
					if(!count($info['uslugi']) && !count($info['list']) && $extrasite_id) continue;
					
					$cures[] = $info;
				}
				$replace['cure_list'] = $cures; 
			}
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
				$sql = mysql_query("SELECT cr.description$englang as description, fl.photo_id as fl_id, fl.ext as fl_ext, fl.ext_b
					FROM ".TABLE_CURE." cr 
					LEFT JOIN ".TABLE_PHOTO." fl ON (fl.owner_id=cr.page_id AND fl.owner=$photo_owner[license])
					WHERE cr.page_id=$extrasite_id AND cr.parent=$cure_id") 
					or Error(1, __FILE__, __LINE__);
				$info = @mysql_fetch_array($sql); 
				$cure['description'] = @$info['description'];
				if($info['ext_b'])
				{
					$cure['pdf_photo'] = $info['ext_b'] && file_exists($fl="images/$photo_dir[license]/$info[fl_id]-s.$info[fl_ext]") ? $fl
						: (file_exists($fb="images/$photo_dir[brochure]/$info[fb_id]-s.$info[fb_ext]") ? "/".$fb : "/images/brochure.jpg");
					$cure['pdf_link'] = $info['ext_b'] && file_exists($pdf="images/$photo_dir[license]/$info[fl_id].$info[ext_b]") ? 
						 "/$pdf\" target=\"_blank" : ''; 
					$cure['license_link'] = 
						$info['ext_b'] && file_exists($pdf) ? 
						"/video/?pdf=$info[fl_id]&ext=$info[ext_b]&name=".urldecode($navig[0]['name']." ".$cure['name']) : '';	
				}
				else $cure['pdf_photo'] = '';
			}
			else
			{			
				$curehotel = array();
				$sql = mysql_query("SELECT p.page_id, cr.name$englang as name, cr.cure_id, ct.name$englang as city,  
					fl.photo_id as fl_id, fl.ext as fl_ext, fl.ext_b, fb.photo_id as fb_id, fb.ext as fb_ext, sd.dir as sp_dir
					FROM ".TABLE_PAGE." p
					LEFT JOIN ".TABLE_CURE." cr ON (cr.page_id=p.page_id AND cr.parent=$cure_id)
					LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
					LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
					LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
					LEFT JOIN ".TABLE_PHOTO." fl ON (fl.owner_id=p.page_id AND fl.owner=$photo_owner[license])
					LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$photo_owner[brochure])
					WHERE p.parent=1 AND p.public AND cr.page_id
					GROUP BY p.page_id
					ORDER BY cr.ord") 
					or Error(1, __FILE__, __LINE__);
				while($info = @mysql_fetch_array($sql)) 
				{
					$info['photo_logo'] = file_exists($fb="images/$photo_dir[brochure]/$info[fb_id]-s.$info[fb_ext]")
						 ? "/".$fb : "/images/brochure.jpg";
					$info['photo_license'] = file_exists($fl="images/$photo_dir[license]/$info[fl_id]-s.$info[fl_ext]") ? $fl : '';
					$info['city'] = htmlspecialchars($info['city']);
					$info['page_link'] = 
						$info['ext_b'] && file_exists($pdf="images/$photo_dir[license]/$info[fl_id].$info[ext_b]") ? 
						 "/$pdf\" target=\"_blank" : 
						($info['sp_dir'] ?  $info['sp_dir']."/medicine/$cure_id\" target=\"_blank" : 
						"$lprefix/medicine/$cure_id/$info[cure_id]"); 
					$info['license_link'] = 
						$info['ext_b'] && file_exists($pdf) ? 
						"/video/?pdf=$info[fl_id]&ext=$info[ext_b]&name=".urldecode($info['name']." ".$cure['name']) : '';	
					$info['name'] = htmlspecialchars($info['name']);
					$curehotel[] = $info;	
				}
				$replace['curehotel'] = $curehotel;
			}
		}
	}
}
	
if($subcure_id)
{
	$sql = mysql_query("SELECT cure_id, parent, name$englang as name, profile$englang as profile, description$englang as description
		FROM ".TABLE_CURE." WHERE cure_id=$subcure_id AND public") or Error(1, __FILE__, __LINE__);
	if(!($subcure = @mysql_fetch_array($sql))) {page404();return;}
	
	$subcure['name'] = htmlspecialchars($subcure['name']);
	$page_name = $subcure['name'];
	
	if($subcure['parent']!=$cure_id)
	{
		$sql1 = mysql_query("SELECT name$englang as name FROM ".TABLE_CURE." WHERE cure_id=$subcure[parent]") or Error(1, __FILE__, __LINE__);
		$info = @mysql_fetch_array($sql1);
		$link = $link_medicine."$cure_id/$subcure[parent]/";
		$navig[] = array('link'=>$link, 'name'=>$info['name']);
	}
	else
	{
		$subcure_list = array();
		
		$ord = 'c.ord';
		if($extrasite_id && $cure['type']==1) 
			$query ="SELECT c.cure_id, c.name$englang as name
				FROM ".TABLE_CURE." c 
			LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=c.cure_id AND h.page_id=$extrasite_id)
			WHERE c.parent=$subcure_id AND c.public
			GROUP BY c.cure_id
			ORDER BY $ord";
		else
			$query ="SELECT c.cure_id, c.name$englang as name,  sd.dir
				FROM ".TABLE_CURE." c 
				LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=c.cure_id)
				LEFT JOIN ".TABLE_PAGE." s ON (s.site=h.page_id AND s.public='1') 
				LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 				
				WHERE c.parent=$subcure_id AND c.public 
				GROUP BY c.cure_id
				ORDER BY $ord";
		$sql1 = mysql_query($query) or Error(1, __FILE__, __LINE__);
		
		while($info = @mysql_fetch_array($sql1))
		{ 
			$info['url'] = @$info['dir'] && $cure_id==4 ? "$lprefix/$info[dir]/medicine/$cure_id/$info[cure_id]\" target=\"_blank" : 
				$link_medicine."$cure_id/$info[cure_id]/";
			$subcure_list[] = $info;
		}
		$subcure['subcure_list'] = $subcure_list;
	
	}
	
	$link = $subcure['parent']==$cure_id ? '' : $link_medicine."$cure_id/$subcure_id/";
	$navig[] = array('link'=>$link, 'name'=>$subcure['name']);
	
	$cure['inhotel'] = str_replace("[service]", $subcure['name'], $cure['inhotel']);
	
	if($extrasite_id && $cure['type']!=4)
	{ 
		$sql = mysql_query("SELECT description$englang as description, h.price$englang as  price 
			FROM ".TABLE_CUREHOTEL." h  
			WHERE (h.cure_id=$subcure_id AND h.page_id=$extrasite_id)") or Error(1, __FILE__, __LINE__);
		$info = @mysql_fetch_array($sql);
		
		if(@$info['description']) $subcure['description'] = $info['description'];
		elseif($cure['type']==2)
		{
			Header("Location: ".$link_medicine."$cure_id/?sid=$subcure_id#price");
			exit;
		}
	}
	
	$curehotel = array();
	if(!$extrasite_id || $cure['type']==4)
	{
		$field = $cure['type']==4 ? "h.description$englang as  description" : "h.price1$englang as  price";
		$where =  ($cure['type']==4 && $extrasite_id) ? " AND p.page_id=$extrasite_id" : '';
		$ord = 'p.name';// : 'p.ord';
		$sql = mysql_query("SELECT p.page_id, p.name$englang as name, ct.name$englang as city, $field  , 
			fb.photo_id as fb_id, fb.ext as fb_ext, sd.dir as sp_dir
			FROM ".TABLE_PAGE." p
			LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=$subcure_id AND h.page_id=p.page_id)
			LEFT JOIN ".TABLE_CITY." ct ON ct.city_id=p.city_id
			LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
			LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
			LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$photo_owner[brochure])
			WHERE p.parent=1 AND p.public AND h.cure_id $where
			GROUP BY p.page_id
			ORDER BY $ord") 
			or Error(1, __FILE__, __LINE__); 
		while($info = @mysql_fetch_array($sql)) 
		{
			$info['photo'] = file_exists($fb="images/$photo_dir[brochure]/$info[fb_id]-s.$info[fb_ext]") ? "/".$fb : "/images/brochure.jpg";
			$info['name'] = htmlspecialchars($info['name']);
			$info['city'] = htmlspecialchars($info['city']);
			if($cure['type']==4)
			{
				$list = explode("\n", $info['description']);
				for($i=1;$i<=5;$i++)
				{
					$info["col$i"] = @$list[$i-1];
				}
			}
			else 
			{
				$price = (int)$info['price'];
				$info['price'] = $price>0 && $info['price']==$price ? $price." ���." : htmlspecialchars($info['price']);
			}
			$info['page_link'] = $info['sp_dir'] ?  
				($cure['type']==2 ? $info['sp_dir']."/medicine/$cure_id/?sid=$subcure_id#price\" target=\"_blank"
					: ($cure_id==1 ? $info['sp_dir']."/medicine/\" target=\"_blank" : 
						$info['sp_dir']."/medicine/$cure_id/$subcure_id\" target=\"_blank")  )
				: "$lprefix/media/?s_id=$info[page_id]"; 
			$curehotel[] = $info;	
		}
	}
	$replace['curehotel'] = $curehotel;		
	
	if($extrasite_id)
	{	
		$sql = mysql_query("SELECT table_id, name$englang as name FROM ".TABLE_TABLE." 
			WHERE parent=0 AND cure_id=$subcure_id AND page_id=$extrasite_id ORDER BY ord") 
			or Error(1, __FILE__, __LINE__);
		
		$tables =  array();
		while($info = @mysql_fetch_array($sql))
		{ 
			$info['name'] = HtmlSpecialChars($info['name']);
			if(!$info['name']) $info['name'] = NONAME;
			
			$list = array();
						
			$rowspan_index = -1; $rowspan = 1; $k=0;
			$sql_sect = mysql_query("SELECT table_id, name$englang as name, name1$englang as name1, title, tab, rowspan FROM ".TABLE_TABLE." 
				WHERE parent=$info[table_id] ORDER BY ord") 
				or Error(1, __FILE__, __LINE__);
			while($info_sect = @mysql_fetch_array($sql_sect))
			{ 
				$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
				$info_sect['name1'] = nl2br(HtmlSpecialChars($info_sect['name1']));
				
				if($info_sect['rowspan']) 
				{
					if($rowspan_index==-1) $rowspan_index = $k;
					else $info_sect['rowspan'] = 1;
					$rowspan++;
				}
				elseif($rowspan>1)
				{
					$info_sect['rowspan'] = 1;
					 $list[$rowspan_index]['rowspan'] = $rowspan;
					 $rowspan = 1; 
					 $rowspan_index = -1;
				}
				
				
				$list[$k] = $info_sect;
				$k++;
			}
			$info['list'] = $list;
			
			$tables[] = $info;
		}
		$replace['tables'] = $tables; 
	}
	
}

if(!$cure_id && !$subcure_id)
{
	$blocks = array();
	$and = $extrasite_id ? " AND c.inmenu AND c.cure_id!=1" : '';
	$fn = $extrasite_id ? "if(c.name_extra$englang!='',c.name_extra$englang,c.name$englang)" : "c.name$englang";
	
	$table_plus = '';
	if($extrasite_id)
	{
		$table_plus = "LEFT JOIN ".TABLE_CUREHOTEL." ch ON (ch.cure_id=c.cure_id AND ch.page_id=$extrasite_id)";
		$and .= " AND ch.cure_id IS NOT NULL";
	}
	
	$sql = mysql_query("SELECT c.cure_id, c.type, $fn as name, f.photo_id, f.ext FROM ".TABLE_CURE." c 
		LEFT JOIN ".TABLE_PHOTO." f ON (f.owner=$photo_owner[cure_part] AND f.owner_id=c.cure_id)
		$table_plus 
		WHERE !c.partof AND !c.parent AND c.public AND f.photo_id $and
		ORDER by c.ord") or Error(1, __FILE__, __LINE__);
	while($arr = @mysql_fetch_array($sql))
	{ 	
		if($extrasite_id && $arr['type']==7)
		{
			$sql_e = mysql_query("SELECT f.photo_id, f.ext FROM ".TABLE_CURE." c 
				LEFT JOIN ".TABLE_PHOTO." f ON (f.owner=$photo_owner[cure_part] AND f.owner_id=c.cure_id)
				WHERE c.parent=$arr[cure_id] AND c.page_id=$extrasite_id AND c.public AND f.photo_id ") or Error(1, __FILE__, __LINE__);
			$arr_e = @mysql_fetch_array($sql_e);
			
			$f = @$arr_e['photo_id'] ? "images/$photo_dir[cure_part]/$arr_e[photo_id]-s.$arr_e[ext]" :
			"images/$photo_dir[cure_part]/$arr[photo_id]-s.$arr[ext]";
		}
		else $f="images/$photo_dir[cure_part]/$arr[photo_id]-s.$arr[ext]";
		//list($w_small, $h_small) = @getimagesize($f);
		$blocks[] = array(
			'photo'=>$f,
			'url'=>$link_medicine."$arr[cure_id]/",
			'name'=>htmlspecialchars($arr['name'], null, 'cp1251')
		);
	}
	$replace['blocks'] = $blocks;
		
	if($extrasite_id)
	{
		$query ="SELECT c.cure_id, c.name$englang as name
			FROM ".TABLE_CURE." c 
			LEFT JOIN ".TABLE_CUREHOTEL." h  ON (h.cure_id=c.cure_id AND h.page_id=$extrasite_id)
			WHERE c.parent=1 AND c.public AND h.cure_id IS NOT NULL
			GROUP BY c.cure_id
			ORDER BY c.ord";
		$sql = mysql_query($query) or Error(1, __FILE__, __LINE__);
		
		$cures = array(); 
		while($info = @mysql_fetch_array($sql))
		{ 
			$info['name'] = $info['name'] ? HtmlSpecialChars($info['name'], ENT_COMPAT, 'cp1251') : NONAME;
						
			$info['url'] = $link_medicine."1/"."$info[cure_id]/";
			
			$cures[] = $info;
		}
		$replace['cure_list'] = $cures;
	}
}

$replace['extrasite_id'] = $extrasite_id;
$replace['cure'] = $cure;
$replace['subcure'] = $subcure;
$replace['link_medicine'] = $link_medicine."1/";

if(($cure_id==1 || (!$cure_id && !$subcure_id)) && !$extrasite_id) {
    $replace['profile'] = get_template("templ/page_medicine_profile.htm", $replace);
}else{
    $replace['profile'] = '';
}

$content = get_template("templ/page_medicine.htm", $replace);

get_page_info($part, 0, $page_name);
?>