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
	$f = "images/$photo_dir[logo]/$arr[photo_id]-s.$arr[ext]"; 
	if(!file_exists($f)) continue;
	$arr['photo'] = "/".$f;

	$arr['name'] = HtmlSpecialChars($arr['name'], null, 'cp1251');
	$arr['link'] = "$lprefix/partners/$arr[dir]";
	$partners[] = $arr; 
}
$replace['partners'] = $partners;
	

$sanatorii = array();

$query = "
	SELECT
		s.slider_id, s.url, s.name$englang as name, s.teaser ,
		f.photo_id, f.ext, s.page_id,
		p.photo_id as preview_id, p.ext as preview_ext
	FROM
		".TABLE_SLIDER." s
		LEFT JOIN ".TABLE_PHOTO." f ON (f.owner_id=s.slider_id AND f.owner='$photo_owner[slider]')
		LEFT JOIN ".TABLE_PHOTO." p ON (p.owner_id=s.slider_id AND p.owner='$photo_owner[slider_preview]')
	WHERE
	 	s.public AND f.photo_id
	GROUP BY
		s.slider_id
	ORDER BY
	 	s.ord";

$sql = mysql_query($query) or Error(1, __FILE__, __LINE__);

while($arr = @mysql_fetch_array($sql, MYSQL_ASSOC))
{
    $f="images/$photo_dir[slider]/$arr[photo_id]-s.$arr[ext]";
	$p="images/$photo_dir[slider_preview]/$arr[preview_id]-s$arr[preview_ext]";
	list($w_small, $h_small) = @getimagesize($f);
    $sanatorii[] = array(
        'photo'=>$f,
        'preview'=>$p,
        'url'=>$arr['url'],
        'id'=>$arr['page_id'],
        'photo_id'=>$arr['photo_id'],
        'name'=>htmlentities($arr['name'], ENT_QUOTES, 'cp1251'),
        'teaser'=>htmlentities($arr['teaser'], ENT_QUOTES, 'cp1251'),
    );
}
$replace['sanatorii'] = $sanatorii;

//print_r($sanatorii);

$content = get_template('templ/main.htm', $replace); 


?>