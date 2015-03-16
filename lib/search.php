<?php

function BoldWord($name, $word)
{	
	$word_len = strlen($word);
	$name_low = strtolower_ru($name);
	$word_low = strtolower_ru($word);
	
	$result = ''; $i = 0;
	$ereg_string = ($word_len < 3) ? "(^| |[[:punct:]])(".RegExpSim($word_low).")( |[[:punct:]]|$)" : "(.?)(".RegExpSim($word_low).")(.?)";

	while(ereg($ereg_string, $name_low, $F))
	{
		$ereg_pos = strpos($name_low, $F[0]);
		$ereg_len = strlen($F[0]);
		
		$span1='<span>'; $span2='</span>';
		$result .= 	substr($name, $i, $ereg_pos + strlen($F[1])).
					$span1.substr($name, $i + $ereg_pos + strlen($F[1]), strlen($F[2])).$span2.
					substr($name, $i + $ereg_pos + strlen($F[1]) + strlen($F[2]), strlen($F[3]));
		
		$i += $ereg_pos + $ereg_len;
		$name_low = substr($name_low, $ereg_pos + $ereg_len);
	}
	$result .= (substr($name, $i));
	return $result;
}

function cat_description($info_description, $word_arr)
{
	$descr = $info_description;
	$descr = str_replace("<", " <", $descr);
	$descr = strip_tags($descr);
	$descr = ereg_replace("\n", " ", $descr);
	$descr = ereg_replace("\r", "", $descr);
	$descr = ereg_replace("\t", "", $descr);
	$descr = ereg_replace("[[:space:]]+", " ", $descr);
	
	$info_description = '';
	$max_word = 7;
	foreach($word_arr as $k=>$v) 
	{
		$ereg_string = (strlen($v) < 3) ? "(^| |[[:punct:]])".RegExpSim(strtolower_ru($v))."( |[[:punct:]]|$)" : 
										RegExpSim(strtolower_ru($v));
		
		if(ereg($ereg_string, strtolower_ru($info_description))) 
		{ 
			$info_description = BoldWord($info_description, $v);
			continue;
		}
		if(ereg("([^ .!?]+[ ]*){0,$max_word}".$ereg_string."([ ]*[^ .!?]+){0,$max_word}", strtolower_ru($descr), $F))
		{ 
			$pos = strpos(strtolower_ru($descr), $F[0]); 
			$F[0] = substr($descr, $pos, strlen($F[0]));
			if($pos != 0 || count($word_arr) > 1) $info_description .= " ...";
			$info_description .= $F[0]; 
			$info_description = BoldWord($info_description, HtmlSpecialChars($v));
		}
	}
	if($info_description) $info_description .= "...";
	return 	$info_description;
}

$word = trim(get_post('word'));
$word = ereg_replace("[[:punct:]]", "", $word);
$word_arr = split("[[:space:]]+", $word); 
$word_arr = array_slice($word_arr, 0, 5);
$word = $replace['word'] = join(" ", $word_arr); 
	
$list = @unserialize($_SESSION['searchlist']);
if(!is_array($list)) $list = array();
if(!isset($list[$word]) && $word)
{
	$list[$word] = 1;
	$_SESSION['searchlist'] = serialize($list);

	$sql = mysql_query("SELECT count(*) FROM ".TABLE_SEARCHSTAT." WHERE word='$word' AND date=CURDATE()") 
		or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);

	if(@$arr[0]) 
	{
		mysql_query("UPDATE ".TABLE_SEARCHSTAT." SET amount=amount+1 WHERE word='$word' AND date=CURDATE()") 
		or Error(1, __FILE__, __LINE__);
	}
	else 
	{
		mysql_query("INSERT INTO ".TABLE_SEARCHSTAT." SET word='$word', date=CURDATE(), amount=1") 
		or Error(1, __FILE__, __LINE__);
	}	
}

$replace = array();
$only = from_form(@$only);
$all = 0;
$page_count = 0; $item_count = 0; $cpart_count = 0; $news_count = 0;
$replace['page_count'] = 0; 
$replace['news_count'] = 0; 

$replace['only_page'] = ($only == 'page') ? 'selected' : ''; 
$replace['only_news'] = ($only == 'news') ? 'selected' : ''; 

$replace['word'] = HtmlSpecialChars($word); 

$replace['all_link'] = "$part/?word=".UrlEncode($word);
	
if($word && strlen($word) >= 2)
{	
	$page_ord_arr = array();
	$page_arr = array();
	$news_ord_arr = array();
	$news_arr = array();
	$cure_ord_arr = array();
	$cure_arr = array();
	foreach($word_arr as $k=>$v) {
		$ereg_string = (strlen($v) < 3) ? "[[:<:]]".escape_string(RegExpSim($v))."[[:>:]]" : escape_string(RegExpSim($v));
		if(strlen($v) < 3)
		{
			$ereg_string = "[[:<:]]".escape_string(RegExpSim($v))."[[:>:]]";
			$page_arr[] = "(p.name$englang regexp '$ereg_string' OR ".
							"p.description$englang regexp '$ereg_string')";
			$news_arr[] = "(i.name$englang regexp '$ereg_string' OR ".
							"i.description$englang regexp '$ereg_string')";
			$cure_arr[] = "(i.name$englang regexp '$ereg_string' OR ".
							"i.description$englang regexp '$ereg_string')";
		}
		else
		{
			$ereg_string = escape_string(RegExpSim($v));
			$page_arr[] = "(p.name$englang regexp '$ereg_string' OR p.description$englang regexp '$ereg_string')";
			$news_arr[] = "(i.name$englang regexp '$ereg_string' OR i.description$englang regexp '$ereg_string')";
			$cure_arr[] = "(p.name$englang regexp '$ereg_string' OR p.description$englang regexp '$ereg_string')";
							
			$ereg_string_ord = "[[:<:]]".escape_string(RegExpSim($v))."[[:>:]]";
			$page_ord_arr[] = "p.name$englang regexp '$ereg_string_ord' desc, p.description$englang regexp '$ereg_string_ord' desc";
			$news_ord_arr[] = "i.name$englang regexp '$ereg_string_ord' desc, i.description$englang regexp '$ereg_string_ord' desc";
			$cure_ord_arr[] = "p.name$englang regexp '$ereg_string_ord' desc, p.description$englang regexp '$ereg_string_ord' desc";
		}
	}


	$word_sql = join(" AND ", $page_arr);
	$page_ord_sql = join(", ", $page_ord_arr);
	if($page_ord_sql) $page_ord_sql .= ",";
	$where_page = "p.public='1' AND \n($word_sql)";
		
	if($extra_parent_id>0) $where_page .= " AND (c.page_id=$extra_parent_id 
		OR r.page_id=$extra_parent_id OR r1.page_id=$extra_parent_id)";
	else $where_page .= " AND (!p.site OR p.site IS NULL) AND (!c.site OR c.site IS NULL) 
		AND (!r.site OR r.site IS NULL) AND (!r1.site OR r1.site IS NULL)";

	$sql = mysql_query("
		SELECT 
			count(*) 
		FROM 
			".TABLE_PAGE." p
			LEFT JOIN ".TABLE_PAGE." c ON (c.page_id=p.parent)
			LEFT JOIN ".TABLE_PAGE." r ON (r.page_id=c.parent)
			LEFT JOIN ".TABLE_PAGE." r1 ON (r1.page_id=r.parent)
		WHERE 
			$where_page 
			") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$replace['page_count'] = $page_count = @$arr[0]; 
	$replace['page_link'] = "$part/?word=".UrlEncode($word)."&only=page";
	
	
	$word_sql = join(" AND ", $news_arr);
	$news_ord_sql = join(", ", $news_ord_arr);
	if($news_ord_sql) $news_ord_sql .= ",";
	$where_news = "i.public='1' \n AND ($word_sql)";

	if($extra_parent_id) $where_news  .= " AND FIND_IN_SET('$extra_parent_id', pages)"; 
	else $where_news  .= " AND FIND_IN_SET('0', pages)";
	 
	$sql = mysql_query("
		SELECT 
			count(*) 
		FROM 
			".TABLE_NEWS." i
		WHERE 
			$where_news 
			") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$replace['news_count'] = $news_count = @$arr[0];
	$replace['news_link'] = "$part/?word=".UrlEncode($word)."&only=news";
	
		
	$word_sql = join(" AND ", $cure_arr);
	$cure_ord_sql = join(", ", $cure_ord_arr);
	if($cure_ord_sql) $cure_ord_sql .= ",";
	$where_cure = "p.public='1' AND \n($word_sql)";
		
	$left_table = ''; $subdescr = '';
	if($extrasite_id>0) 
	{
		$left_table = "LEFT JOIN ".TABLE_CUREHOTEL." ch ON (ch.cure_id=p.cure_id AND ch.page_id=$extrasite_id)";
		// или корневой раздел, или есть эта услуга в санатории
		$where_cure .= " AND (ch.cure_id IS NOT NULL OR c.cure_id IS NULL)";
		$subdescr = "ch.description$englang as subdescription,";
	}
		
	$sql = mysql_query("
		SELECT 
			count(*) 
		FROM 
			".TABLE_CURE." p
			LEFT JOIN ".TABLE_CURE." c ON (c.cure_id=p.parent)
			$left_table
		WHERE 
			$where_cure 
			") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$replace['cure_count'] = $cure_count = @$arr[0]; 
	$replace['cure_link'] = "$part/?word=".UrlEncode($word)."&only=cure";
	
	
}

$replace['all'] = $all =  $page_count +  $news_count + $cure_count;
$replace['sect'] = ($page_count == $all || $news_count == $all || $cure_count == $all) ? 0 : 1;

$replace['all_only'] = 0;
if($only == 'page') $replace['all_only'] = $page_count;
elseif($only == 'news') $replace['all_only'] = $news_count;
elseif($only == 'cure') $replace['all_only'] = $cure_count;
else $replace['all_only'] = $replace['all'];

$replace['pages'] = '';
$replace['pagelist'] = array();
$replace['newss'] = array();
$replace['curelist'] = array();


$on_page = $settings['search_count'];
$i = ($current_page-1)*$on_page;
$result_on_page = 0;
$result_before = 0;


setlocale(LC_CTYPE, 'ru_RU.CP1251');
if((!$only || $only == 'page' || $only == 'site'))
{
	if($page_count && $i >= $result_before)
	{
		
		$limit = ($i - $result_before).", ".($on_page - $result_on_page); 
		$items = array(); 
		$sql = mysql_query("
			SELECT 
				p.page_id, p.name$englang as name, p.description$englang as description, d.dir, ct.name$englang as city,
				c.page_id as parent_id, c.name$englang as parent_name, dc.dir as dirc, r.page_id as pp_id , sd.dir as sp_dir
			FROM 
				".TABLE_PAGE." p
				LEFT JOIN ".TABLE_CITY." ct ON (ct.city_id=p.city_id)
				LEFT JOIN ".TABLE_PAGE." c ON (c.page_id=p.parent)
				LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id)
				LEFT JOIN ".TABLE_DIR." dc ON (dc.dir_id=c.dir_id)
				LEFT JOIN ".TABLE_PAGE." r ON (r.page_id=c.parent)
				LEFT JOIN ".TABLE_PAGE." r1 ON (r1.page_id=r.parent)
				LEFT JOIN ".TABLE_PAGE." s ON (s.site=p.page_id AND s.public='1') 
				LEFT JOIN ".TABLE_DIR." sd ON (sd.dir_id=s.dir_id) 
			WHERE 
				(c.page_id IS NULL OR c.public='1') 
				AND $where_page 
			GROUP BY 
				p.page_id
			ORDER BY 
				$page_ord_sql c.public, c.ord, p.ord 
			LIMIT 
				$limit") or Error(1, __FILE__, __LINE__);
		while($info = @mysql_fetch_array($sql))
		{ 
			$i++;
			$info['i'] = $i;
			
			if(!$info['name']) $info['name'] = NONAME;
			$info['name'] = HtmlSpecialChars($info['name']);
			if($info['city']) $info['name'] .= " ($info[city])";
			
			if($info['parent_id']==1)
			{
				$info['link'] = $info['sp_dir'] ?  "$lprefix/".$info['sp_dir']."/\" target=\"_blank" : "$lprefix/media/?s_id=$info[page_id]";
			}
			else
			{
				$info['link'] = ($info['parent_id']) ? 
					($info['pp_id'] && $extrasite_id ?  "$lprefix/$request[0]/$info[dirc]" : "$lprefix/$info[dirc]") : "";	
				$info['link'] .= "/$info[dir]/";	
			}
			
			$info['description'] = cat_description($info['description'], $word_arr);
			
			if($info['parent_id'])
			{
				$info['parent_link'] = $info['pp_id'] && $extrasite_id ? "$lprefix/$request[0]/$info[dirc]" :  "$lprefix/$info[dirc]/";	
				if(!$info['parent_name']) $info['parent_name'] = NONAME;
			}
			
			$items[] = $info;
		}
		$replace['pagelist'] = $items;
		$result_on_page += count($items);
	}
	$result_before += $page_count;
}


if((!$only || $only == 'news' || $only == 'site'))
{
	if($news_count && $i >= $result_before)
	{
		$limit = ($i - $result_before).", ".($on_page - $result_on_page);  
		$sql = mysql_query("
			SELECT 
				i.news_id, i.name$englang as name, i.description$englang as description, i.date 
			FROM 
				".TABLE_NEWS." i
			WHERE 
				$where_news 
			ORDER BY 
				$news_ord_sql i.ord 
			LIMIT 
				$limit") or Error(1, __FILE__, __LINE__);
		$newss = array(); 
		while($info = @mysql_fetch_array($sql))
		{ 
			$i++;
			$info['i'] = $i;
			
			$info['name'] = HtmlSpecialChars($info['name']);
			/*foreach($word_arr as $k=>$v) 
				$info['name'] = BoldWord($info['name'], HtmlSpecialChars($v));*/
				
			$info['description'] = cat_description($info['description'], $word_arr);
			
			
			$info['link'] = $extrasite_id ? "$request[0]/news/n$info[news_id]/" : "news/n$info[news_id]/";	
			
			$info['news_link'] = "$lprefix/news/";
			
			
			$date_ref = "&date=$info[date]";
			list($y, $m, $d) = @split("-", $info['date']);
			$info['date'] = (int)$d.".$m.$y";
		
			$newss[] = $info;
		}
		
		$replace['newss'] = $newss;
		$result_on_page += count($newss);
	}
	$result_before += $news_count;
}

if((!$only || $only == 'cure' || $only == 'site'))
{
	if($cure_count && $i >= $result_before)
	{		
		$limit = ($i - $result_before).", ".($on_page - $result_on_page); 
		$items = array(); 
		$sql = mysql_query("
			SELECT 
				p.cure_id, p.name$englang as name, p.description$englang as description, $subdescr 
				c.cure_id as parent_id, c.name$englang as parent_name
			FROM 
				".TABLE_CURE." p
				LEFT JOIN ".TABLE_CURE." c ON (c.cure_id=p.parent)
				$left_table
			WHERE 
				$where_cure 
			GROUP BY 
				p.cure_id
			ORDER BY 
				$cure_ord_sql c.ord, p.ord 
			LIMIT 
				$limit") or Error(1, __FILE__, __LINE__);
		while($info = @mysql_fetch_array($sql))
		{ 
			$i++;
			$info['i'] = $i;
			
			if(!$info['name']) $info['name'] = NONAME;
			$info['name'] = HtmlSpecialChars($info['name']);
			$info['parent_name'] = HtmlSpecialChars($info['parent_name']);
			
			
			if($info['parent_id']) 	
				$info['link'] = $extrasite_id ?  
					"$lprefix/$request[0]/medicine/$info[parent_id]/$info[cure_id]" : 
					"$lprefix/medicine/$info[parent_id]/$info[cure_id]";
			else 
				$info['link'] = $extrasite_id ?  
					"$lprefix/$request[0]/medicine/$info[cure_id]" : 
					"$lprefix/medicine/$info[cure_id]";
			
			if(@$info['subdescription']) $info['description'] = $info['subdescription'];
			$info['description'] = cat_description($info['description'], $word_arr);
			
			if($info['parent_id'])
			{
				$info['parent_link'] = $extrasite_id ? "$lprefix/$request[0]/medicine/$info[parent_id]" : 
					 "$lprefix/medicine/$info[parent_id]";	
			}
			
			$items[] = $info;
		}
		$replace['curelist'] = $items;
		$replace['medicine_link'] = $extrasite_id ? "$lprefix/$request[0]/medicine/" : 
					 "$lprefix/medicine/";	
		$result_on_page += count($items);
	}
	$result_before += $cure_count;
}

list($limit, $replace['pages']) = user_pages($result_before, "$part/?word=".UrlEncode($word)."&only=$only&", $on_page);

$content = get_template('templ/search.htm', $replace); 
	
?>