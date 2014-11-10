<?

$cure_content = '';

$sanatorii = array();

$sql = mysql_query("SELECT p.page_id, p.name, p.cures, d.dir, f.photo_id, f.ext, ct.name as city FROM ".TABLE_PAGE."  p
	LEFT JOIN  ".TABLE_PHOTO."  f ON (f.owner_id=p.page_id AND f.owner=$photo_owner[logo])
	LEFT JOIN ".TABLE_CITY." ct ON (ct.city_id=p.city_id) 
	LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
	WHERE p.parent=1 AND p.public='1' 
	GROUP BY 	p.page_id
	ORDER BY p.ord") or Error(1, __FILE__, __LINE__);
while($arr = @mysql_fetch_array($sql))
{
	$f = "images/$photo_dir[logo]/$arr[photo_id]-s.$arr[ext]"; 
	//if(!file_exists($f)) continue;
	$arr['photo'] = "/".$f;

	$arr['name'] = HtmlSpecialChars($arr['name']);	
	$arr['city'] = eregi("область", $arr['city']) ? $arr['city'] : "г. ".$arr['city'];
	$arr['link'] = "$lprefix/$dir_sanatorium/$arr[dir]/";
	
	$sanatorii[] = $arr; 
}
	
$sql_c = mysql_query("SELECT cure_id, name FROM ".TABLE_CURE." WHERE parent=0 ORDER BY ord") or Error(1, __FILE__, __LINE__);
$cure_box = array(); $j=0;
while($cure = @mysql_fetch_array($sql_c))
{
	$cure['list'] = array();
	$sql_f = mysql_query("SELECT cure_id, name FROM ".TABLE_CURE." WHERE parent=$cure[cure_id] ORDER BY ord") 
		or Error(1, __FILE__, __LINE__);
	while($info = @mysql_fetch_array($sql_f))
	{ 
		$cure['list'][] = $info;
	}
	$cure_box[] = $cure;
}

$cure_content = get_template('templ/cure.htm', array('cure_box'=>$cure_box, 'sanatorii'=>$sanatorii));
	
?>