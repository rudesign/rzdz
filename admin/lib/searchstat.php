<?php


$sort_arr = array(
	'word'=>'поисковой фразе', 
	'amountsum'=>'количеству запросов'
);

$sort_direct_arr = array(
	'desc'=>'убывание',
	'asc'=>'возрастание'
);


$searchstat_arr = array('mindate', 'maxdate', 'sort', 'sortdirect');

if(@$search)
{
	$arr = array();
	foreach($searchstat_arr as $v)
	{
		if($v == 'mindate' || $v == 'maxdate')
		{
			if(@${$v})
			{
				$a = @split('\\.', from_form(${$v})); 
				$d = (int)@$a[0]; $m = (int)@$a[1]; $y = (int)@$a[2] + 2000;
				if(!checkdate($m, $d, $y))
				{
					$_SESSION['message'] = "Ќеверна€ дата $d/$m/$y";
					$arr[$v] = '';
				}
				else
				{
					$arr[$v] = "$y-$m-$d"; //from_form(@${$v}); 
				}
			}
			else  $arr[$v] = '';
		}
		else 
		{
			$arr[$v] = from_form(@${$v});
		}
	}
	$_SESSION['searchstat_data'] = serialize($arr);
	
	Header("Location: ?p=$part");
	exit;
}


$where = "1";

$data = @unserialize($_SESSION['searchstat_data'] );
foreach($searchstat_arr as $v) ${$v} = @$data[$v];

$replace = array();

$sort = isset($sort_arr[$sort]) ? $sort : 'amountsum';
$sortdirect = isset($sort_direct_arr[$sortdirect]) ? $sortdirect : 'desc';

$replace['sort'] = $sort;
$replace['sortdirect'] = $sortdirect;

if($sort == 'amountsum') $ord = "amountsum $sortdirect, word"; 
else $ord = "word $sortdirect, amountsum desc"; 

$d_field = 'date';
if(!$mindate || !$maxdate)
{
	$sql = mysql_query("SELECT min($d_field) as mindate, max($d_field) as maxdate
	FROM 
		".TABLE_SEARCHSTAT ."
	WHERE $where") or Error(1, __FILE__, __LINE__);
	$arr = mysql_fetch_array($sql);
	if(!$mindate) $mindate = @$arr['mindate'];
	if(!$maxdate) $maxdate = @$arr['maxdate'];
	if(!$mindate && $maxdate) 
	{
		list($y, $m, $d) = split("-", $maxdate);
		$mindate = date("Y-m-d", mktime(0, 0, 0, $m-1, $d, $y));
	} 
}
if($mindate) $where .= " AND $d_field>='$mindate'";
if($maxdate) $where .= " AND $d_field<='$maxdate'";
$replace['mindate'] = sql_to_text_date($mindate);
$replace['maxdate'] = sql_to_text_date($maxdate);

$sql = mysql_query("SELECT COUNT(distinct word) FROM ".TABLE_SEARCHSTAT." WHERE $where") or Error(1, __FILE__, __LINE__);
$arr = mysql_fetch_array($sql);
$replace['all'] = $all = $arr[0];

list($limit, $replace['pages']) = pages($all, ADMIN_URL."?p=$part&");
$replace['onpage_select'] = array_select('onpage', $onpage_list, $_SESSION['on_page'], 0, 
	"onchange=\"window.location='".ADMIN_URL."?p=$part&all=1&onpage='+this.value\"");
		
$replace['current_page'] = $current_page;
$i = ($current_page-1)*$_SESSION['on_page']; $j = 0;
$sql = mysql_query("
	SELECT 
		word, sum(amount) as amountsum 
	FROM 
		".TABLE_SEARCHSTAT ."
	WHERE
		$where
	GROUP BY
		word 
	ORDER BY
		$ord
	LIMIT $limit") or Error(1, __FILE__, __LINE__);

$list = array(); $i = ($current_page-1)*$_SESSION['on_page']; $j = 0;
while($info = @mysql_fetch_array($sql))
{ 
	$i++; $j++;
	$info['i'] = $i;
	$info['j'] = $j;
	$info['word'] = HtmlSpecialChars($info['word']);
	
	$list[] = $info;
}

$replace['list'] = $list;

$content = get_template('templ/searchstat.htm', $replace);




?>