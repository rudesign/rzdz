<?php

if(@$s_id)
{
	$_SESSION['order_data'] = '';
	$data_arr = @Unserialize($_SESSION['opinion_data']);
	$data_arr['fsanator'] = (int)@$s_id;
	$_SESSION['opinion_data'] = Serialize($data_arr); 
	Header("Location: $lprefix/$part/");
	exit;
}



$page_id = (int)@$page_id;

$replace = array();

$where = "i.public='1' AND g.public='1'";

if($page_id>0) $where .= " AND g.page_id=$page_id";

if($extrasite_id>0) $where .= " AND g.page_id=$extrasite_id";
else $where .= " AND g.mainsite";

$where .= $englang ? " AND english" : " AND !english";

$page_list = array();
/*$wh = $englang ? " AND english" : " AND !english";
$sql = mysql_query("SELECT c.page_id, c.name$englang as name FROM ".
	TABLE_OPINION." m LEFT JOIN ".TABLE_PAGE.
	" c ON (c.page_id=m.page_id) WHERE m.public='1' AND c.public='1' $wh GROUP BY m.page_id ORDER BY c.ord") 
	or Error(1, __FILE__, __LINE__);
$page_list[] = array('page_id'=>0, 'name'=>$lang_phrases['vse_sanatorii'], 'sel'=>'');
while($arr = @mysql_fetch_array($sql))
{ 
	$arr['name'] = HtmlSpecialChars($arr['name']);
	
	$arr['sel'] = ($page_id == $arr['page_id']) ? 'selected' : '';
	$page_list[] = $arr;
}
$replace['page_list'] = $page_list;*/
$page_list[] = array('page_id'=>0, 'name'=>$lang_phrases['vse_sanatorii'], 'sel'=>'');
foreach($sanat_list as $k=>$v) { $sel = $page_id==$v['page_id'] ? 'selected' : 0; $page_list[$k+1] = $v; $page_list[$k+1]['sel'] = $sel; }
$replace['page_list'] = $page_list;

$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_OPINION." g LEFT JOIN ".TABLE_PAGE." i ON (i.page_id=g.page_id) ".
		"WHERE $where") or Error(1, __FILE__, __LINE__);
$arr = mysql_fetch_array($sql);
$replace['all'] = $all = $arr[0];

$url = $extrasite_id ? "$lprefix/$request[0]/$part/?" : "$lprefix/$part/?";
list($limit, $replace['pages']) = user_pages($all, $url, $settings['opinion_count']);

$list = array();
$sql = mysql_query("
	SELECT 
		g.*, i.name$englang as page_name, i.parent, d.dir,  dc.dir as dirc, dc1.dir as pdir, ds.dir as san_dir
	FROM 
		".TABLE_OPINION." g 
		LEFT JOIN ".TABLE_PAGE." i ON (i.page_id=g.page_id) 
		LEFT JOIN ".TABLE_PAGE." c ON (c.page_id=i.parent)
		LEFT JOIN ".TABLE_PAGE." c1 ON (c1.page_id=c.parent)
		LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=i.dir_id)
		LEFT JOIN ".TABLE_DIR." dc ON (dc.dir_id=c.dir_id)
		LEFT JOIN ".TABLE_DIR." dc1 ON (dc1.dir_id=c1.dir_id)
		LEFT JOIN ".TABLE_PAGE." s ON (s.site=i.page_id AND s.public='1') 
		LEFT JOIN ".TABLE_DIR." ds ON (ds.dir_id=s.dir_id)
	WHERE 
		$where 
	ORDER BY
		opinion_id desc
	LIMIT 
		$limit") 
	or Error(1, __FILE__, __LINE__);
if($page_id && !mysql_num_rows($sql)) 
{
	page404();
	return;
}
while($arr = @mysql_fetch_array($sql))
{ 
	$arr['name'] = $arr['client_name'];
	$arr['page_name'] = HtmlSpecialChars($arr['page_name']);
	
	$arr['more'] = '';
	if (preg_match("/([^(\s)]*\s+){15}/i",$arr['text'],$F))
	{
		$text = $arr['text'];
		$arr['text'] = $F[0];
		$arr['more'] = str_replace($F[0], '', $text);
	}
	$arr['text'] = nl2br(HtmlSpecialChars($arr['text']));
	
	if($arr['parent']==1)
	{
		$arr['page_link'] = $arr['san_dir'] ? "$lprefix/$arr[san_dir]/opinion\" target=\"_blank" : "$lprefix/opinion/?page_id=$arr[page_id]";
	}
	else
	{
		$arr['page_link'] = ($arr['pdir']) ? "$lprefix/$arr[pdir]/$arr[dirc]" : (($arr['dirc']) ? "$lprefix/$arr[dirc]" : "");	
		$arr['page_link'] .= "$lprefix/$arr[dir]/";	
	}
	
	$d = split("-", $arr['date']);
	$arr['date'] = "$d[2].$d[1].$d[0]";
	$list[] = $arr;
}
$replace['opinion_list'] = $list;
	
$arr_data = @unserialize($_SESSION['opinion_data']);

$replace['u_name'] = @HtmlSpecialChars($arr_data['u_name']);
$replace['u_email'] = @HtmlSpecialChars($arr_data['u_email']);
$replace['u_phone'] = @HtmlSpecialChars($arr_data['u_phone']);
$replace['opinion_text'] = @HtmlSpecialChars($arr_data['text']);
$replace['date_from'] = @HtmlSpecialChars($arr_data['date_from']);
$replace['date_to'] = @HtmlSpecialChars($arr_data['date_to']);

if(!$englang && preg_match("~/~", $replace['date_from']))
{
	list($m,$d,$y) = explode("/", $replace['date_from']);
	$replace['date_from'] = "$d.$m.$y";
}
if(!$englang && preg_match("~/~", $replace['date_to']))
{
	list($m,$d,$y) = explode("/", $replace['date_to']);
	$replace['date_to'] = "$d.$m.$y";
}
if($englang && preg_match("~\.~", $replace['date_from']))
{
	list($d,$m,$y) = explode(".", $replace['date_from']);
	$replace['date_from'] = "$m/$d/$y";
}
if($englang && preg_match("~\.~", $replace['date_to']))
{
	list($d,$m,$y) = explode(".", $replace['date_to']);
	$replace['date_to'] = "$m/$d/$y";
}


$replace['opinionlink'] = $extrasite_id ? "$lprefix/$request[0]/opinion/" : "$lprefix/opinion/";

$replace['sanat_list'] = $sanat_list;
$replace['extrasite_id'] = $extrasite_id;
	
$content = get_template('templ/opinion_list.htm', $replace); 


?>