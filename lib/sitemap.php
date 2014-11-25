<?php
$replace = array();
$arr = array(); $name = '';


if($extra_parent_id) $where = " AND p.parent=$extra_parent_id"; 
else $where = " AND p.parent=0 AND !p.site";
// страницы 
$sql = mysql_query("SELECT p.page_id, p.name$englang as name,   d.dir FROM ".TABLE_PAGE." p 
				LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
				WHERE p.public='1' AND !p.nositemap $where ORDER BY p.ord") 
	or Error(1, __FILE__, __LINE__);

$pages = array(); 
while($info = @mysql_fetch_array($sql))
{ 
	$info['name'] = HtmlSpecialChars($info['name']);
	if(!$info['name']) $info['name'] = NONAME;
	
	$info['link'] = $extrasite_id ? 
		($info['dir']=='media' ? "$lprefix/media/?s_id=$extrasite_id\" target=\"_blank" : "$lprefix/$request[0]/$info[dir]/")
		 : "$lprefix/$info[dir]/";
	
	$info['level'] = 1;
	$pages[] = $info;
			
	$sql_sect = mysql_query("SELECT p.page_id, p.name$englang as name, p.parent, ct.name$englang as city, d.dir FROM ".TABLE_PAGE." p 
				LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
				LEFT JOIN ".TABLE_CITY." ct ON (ct.city_id=p.city_id)
				WHERE p.parent=$info[page_id] AND p.public='1' AND !p.nositemap ORDER BY p.ord") 
		or Error(1, __FILE__, __LINE__);
	while($info_sect = @mysql_fetch_array($sql_sect))
	{ 
		$info_sect['name'] = HtmlSpecialChars($info_sect['name']);
		if(!$info_sect['name']) $info_sect['name'] = NONAME;
		
		$info_sect['link'] = $info_sect['parent']==1 ? "$lprefix/media/?s_id=$info_sect[page_id]" : $info['link']."$info_sect[dir]/";
		
		$info_sect['level'] = 2;
		$pages[] = $info_sect;	
		
		$sql_par = mysql_query("SELECT p.page_id, p.name$englang as name, d.dir FROM ".TABLE_PAGE." p 
					LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
					WHERE p.parent=$info_sect[page_id] AND p.public='1' ORDER BY p.ord") 
			or Error(1, __FILE__, __LINE__);
		while($info_par = @mysql_fetch_array($sql_par))
		{ 
			$info_par['name'] = HtmlSpecialChars($info_par['name']);
			if(!$info_par['name']) $info_par['name'] = NONAME;
			
			$info_par['link'] = $info_sect['link']."$info_par[dir]/";
			
			$info_par['level'] = 3;
			$pages[] = $info_par;
		}

	}
}
$replace['pages'] = $pages;

$replace['homelink'] = $extra_parent_id ? "$lprefix/$request[0]/" : "$lprefix/";
$replace['extrasite_id'] = $extrasite_id;

$content =  get_template('templ/sitemap.htm', $replace); 

if($extrasite_id) {
	$weather_informer = get_template("templ/weather_informer_$extrasite_id.htm", array());
}

?>