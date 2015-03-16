<?php

$page_id = (int)@$page_id;

$opinion_fields = array('theme', 'text');

if(@$del_mes)
{
	$del_mes = (int)$del_mes;
	mysql_query("DELETE FROM ".TABLE_OPINION." WHERE opinion_id='$del_mes'") or Error(1, __FILE__, __LINE__);
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;
}

if(@$publicop)
{
	$publicop = (int)$publicop;
	mysql_query("UPDATE ".TABLE_OPINION." SET public=1 WHERE opinion_id='$publicop'") or Error(1, __FILE__, __LINE__);
	Header("Location: ".ADMIN_URL."?p=$part");
	exit;
}

if(@$unpublicop)
{
	$unpublicop = (int)$unpublicop;
	mysql_query("UPDATE ".TABLE_OPINION." SET public=0 WHERE opinion_id='$unpublicop'") or Error(1, __FILE__, __LINE__);
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;
}

if(@$chdate)
{
	$a = @split('\\.', from_form(@$chdate)); 
	$d = (int)@$a[0]; $m = (int)@$a[1]; $y = (int)@$a[2];
	if(!checkdate($m, $d, $y))
	{
		$_SESSION['message'] = "Неверная дата $d/$m/$y";
		Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
		exit;
	}
	$mainsite = (int)@$mainsite;
	mysql_query("UPDATE ".TABLE_OPINION." SET date='".$y."-".$m."-".$d."', mainsite='$mainsite' WHERE opinion_id='$opinion_id'") 
		or Error(1, __FILE__, __LINE__);
	Header("Location: ".ADMIN_URL."?p=$part&page_id=$page_id");
	exit;	

}


$replace = array();

$replace['page_id'] = $page_id;

$page_list = array();
$sql = mysql_query("SELECT c.page_id, c.name, ct.name as city FROM ".TABLE_OPINION." m 
		LEFT JOIN ".TABLE_PAGE." c ON (c.page_id=m.page_id) 
		LEFT JOIN ".TABLE_CITY." ct ON (ct.city_id=c.city_id) 
		GROUP BY m.page_id ORDER BY (m.page_id>0), c.name") 
	or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{ 
	$arr['name'] = HtmlSpecialChars($arr['name']);
	
	$arr['sel'] = ($page_id == $arr['page_id']) ? 'selected' : '';
	$page_list[] = $arr;
}
$replace['page_list'] = $page_list;

$where = 'WHERE 1'; 
if($page_id>0)
{
	$where .=  " AND g.page_id=$page_id";
}

$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_OPINION." g $where") or Error(1, __FILE__, __LINE__);
$arr = mysql_fetch_array($sql);
$replace['all'] = $all = $arr[0];

list($limit, $replace['pages']) = pages($all, ADMIN_URL."?p=$part&");
$replace['onpage_select'] = array_select('onpage', $onpage_list, $_SESSION['on_page'], 0, 
	"onchange=\"window.location='".ADMIN_URL."?p=$part&onpage='+this.value\"");

$list = array();
$sql = mysql_query("
	SELECT 
		g.*, i.name as page_name
	FROM 
		".TABLE_OPINION." g 
		LEFT JOIN ".TABLE_PAGE." i ON (i.page_id=g.page_id) 
	$where 
		order by 
	opinion_id desc
		LIMIT $limit") 
	or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{ 
	$arr['name'] = $arr['client_name'];
	$arr['email'] = HtmlSpecialChars($arr['client_email']);
	$arr['phone'] = HtmlSpecialChars($arr['client_phone']);
	$arr['page_name'] = HtmlSpecialChars($arr['page_name']);
	$arr['text'] = nl2br(HtmlSpecialChars($arr['text']));
	$d = split("-", $arr['date']);
	$arr['date'] = "$d[2].$d[1].$d[0]";
	$arr['del_link'] = "?p=$part&del_mes=$arr[opinion_id]";
	$list[] = $arr;
}
$replace['list'] = $list;
	
$content = get_template('templ/opinion_list.htm', $replace);

?>