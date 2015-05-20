<?
$replace = array();

$replace['news_anons'] = news_anons('news', $settings['news_anons']);
$replace['spec_anons'] = news_anons('spec', $settings['spec_anons']);

$partners = array();


$sql = mysql_query("SELECT p.page_id, p.name$englang as name , d.dir, f.photo_id, f.ext FROM ".TABLE_PAGE."  p
	LEFT JOIN  ".TABLE_PHOTO."  f ON (f.owner_id=p.page_id AND f.owner=$photo_owner[logo])
		LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
	WHERE p.parent=6 AND p.public='1' AND f.photo_id>0
	GROUP BY 	p.page_id
	ORDER BY p.ord") or Error(1, __FILE__, __LINE__);

while($arr = @mysql_fetch_array($sql))
{
	$f = "/images/$photo_dir[logo]/$arr[photo_id]-s.$arr[ext]";
	if(!file_exists($f)) continue;
	$arr['photo'] = "/".$f;

	$arr['name'] = HtmlSpecialChars($arr['name'], null, 'cp1251');
	$arr['link'] = "$lprefix/partners/$arr[dir]";
	$partners[] = $arr; 
}
$replace['partners'] = $partners;

// Sanatorium parallax slider
$sanatorii = array();

$query = "SELECT * FROM ".TABLE_SLIDER." WHERE public ORDER BY ord";

//echo $query;

$sql = mysql_query($query) or Error(1, __FILE__, __LINE__);

while($arr = @mysql_fetch_array($sql, MYSQL_ASSOC))
{
    $sanatorii[$arr['slider_id']] = $arr;
}

$replace['sanatorii'] = $sanatorii;

//print_r($sanatorii);

$content = get_template('templ/main.htm', $replace); 


?>