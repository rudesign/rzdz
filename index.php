<?php

require 'admin/config.php';
require 'lib/func.php';
require 'lib/func_user.php';
require 'admin/lang.php';
require 'admin/settings.php';

session_name(SES_NAME);
session_start();

global $englang, $lprefix, $lang_phrases;
$r_u = ereg_replace("^(\/)?", "", getenv('REQUEST_URI'));  
$r_u = ereg_replace("(\/)?(\?.*)?$", "", $r_u);  
$rus_url = preg_replace("/(^|\/)en\/?/", '', getenv('REQUEST_URI'));
$eng_url = preg_match("~^/~", $rus_url) ?  "/en".$rus_url : "/en/".$rus_url;

$request = split("(\/)", $r_u);
for($i=0;$i<10;$i++) if(!isSet($request[$i])) $request[$i] = '';
$englang = $request[0]=='en' ? '_en' : '';
if($englang) for($i=1;$i<10;$i++) $request[$i-1] = $request[$i];

$print_url = $englang ? $eng_url : $rus_url;
if(preg_match("~\?~", $print_url)) $print_url .= "&print";
elseif(preg_match("~/$~", $print_url)) $print_url .= "?print";
else $print_url .= "/?print";

// if en - show Under construction page with 404
/*if(!empty($englang)){
    header("HTTP/1.0 404 Not Found");
    require 'templ/under_construction.htm';
    die();
}*/

$lprefix = $englang ? '/en' : '';
$lang_phrases = array();
$nn = $englang ? "name$englang" : 'name';
foreach($lang_settings as $k=>$v) $lang_phrases[$k] = $v[$nn];

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);

if($request[0] == 'video') { require 'lib/video.php'; exit; }
if(isset($add_opinion)) { require 'lib/add_opinion.php'; }
if($request[0] == 'ajax') { require 'lib/ajax.php'; exit; }

global $part, $menu1, $page, $parent, $page_id, $current_page, $request, $san_id, 
	$navig, $print_page, $extrasite_id, $extra_parent_id, $medicine;

$print_page = isset($_GET['print']) ? 1 : 0;

$lang_arr = array();
for($i=0;$i<10;$i++) { if(!$request[$i]) break; $lang_arr[$i] = $request[$i]; }

$medicine = $request[0]=='medicine' || ($request[1]=='medicine') ? 1 : 0;
$part = $medicine ? 'medicine' : $request[0];

$current_page = ((int)@$page < 1) ? 1 : (int)@$page;

$meta_tags = array('title'=>'', 'description'=>'', 'keywords'=>'', 'topimg_id'=>'');

$content = '';
$san_id = 0;
$sanat_id = 0;
$width100 = 0;
$navig = array();

if($part)
{
	$end = 0;
	if(!$request[2] && !$request[1])
	{
		$page_dir = mysql_escape_string($request[0]);

		$sql = mysql_query("
			SELECT 
				i.page_id, i.site, i.name$englang as name, i.description, d2.dir_id
			FROM 
				".TABLE_PAGE." i 
				LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id AND d2.dir='$page_dir') 
			WHERE 
				i.parent=0 AND d2.dir_id IS NOT NULL AND i.public='1'") or Error(1, __FILE__, __LINE__);
		if($arr = @mysql_fetch_array($sql))
		{
			if(@$arr['description'])
			{
				$end = 1;
				if(!$medicine) $part = 'site';
				$page_id = (int)@$arr['page_id'];
				$page_name = @$arr['name'];
				$extrasite_id = (int)@$arr['site'];
				$extra_parent_id = $page_id;
			
				if(!$medicine) 
				{
					if(!$extrasite_id) $navig = array(0=>array('name'=>$lang_phrases['home'], 'link'=>"$lprefix/"));
					$navig[] = array('name'=>HtmlSpecialChars($page_name), 'link'=>'');
				}
			}
			else 
			{
				$sql = mysql_query("
					SELECT 
						d2.dir
					FROM 
						".TABLE_DIR." d2 
						LEFT JOIN ".TABLE_PAGE." i ON (d2.dir_id=i.dir_id) 
						LEFT JOIN ".TABLE_PAGE." c ON (c.page_id=i.parent)
					WHERE 
						d2.dir_id IS NOT NULL AND i.public='1' AND c.page_id='$arr[page_id]'
					ORDER BY
						i.ord
					LIMIT 1") or Error(1, __FILE__, __LINE__);
				if($arr = @mysql_fetch_array($sql))
				{
					$request[1] = $arr['dir'];
				} 
			}
		}
	}
	if($request[1] && (!$request[2] || $request[1]=='news' || $request[1]=='medicine') && !$end && $request[0]!='medicine')
	{
		$parent_dir = mysql_escape_string($request[0]);
		$page_dir = mysql_escape_string($request[1]);

		$sql = mysql_query("
			SELECT 
				c.name$englang as parent_name,  c.site, c.page_id as pid,
				i.page_id, i.parent, i.name$englang as name, i.description, d2.dir_id
			FROM 
				".TABLE_PAGE." c
				LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND d1.dir='$parent_dir') 
				LEFT JOIN ".TABLE_PAGE." i ON (i.parent=c.page_id) 
				LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id AND d2.dir='$page_dir') 
			WHERE 
				c.parent=0 AND d1.dir_id IS NOT NULL AND c.public='1' AND 
				d2.dir_id IS NOT NULL AND i.public='1'") or Error(1, __FILE__, __LINE__);
		if($arr = @mysql_fetch_array($sql))
		{
			if(@$arr['description'] || $request[1]=='medicine')
			{
				$end = 1;
				$parent_name = @$arr['parent_name'];
				
				$page_id = (int)@$arr['page_id'];
				$page_name = @$arr['name'];
				
				$extrasite_id = (int)@$arr['site'];
				$extra_parent_id = (int)@$arr['pid'];
				
				if($englang && $extrasite_id) $medicine = 0;
				if(!$medicine) $part = 'site';
			
				if(!$medicine || $extrasite_id) 
				{				
					if(!$extrasite_id) $navig = array(0=>array('name'=>$lang_phrases['home'], 'link'=>"$lprefix/"));
					$navig[] = array('name'=>HtmlSpecialChars($parent_name), 'link'=>"$lprefix/$parent_dir/");
					if(!$medicine) $navig[] = array('name'=>HtmlSpecialChars($page_name), 'link'=>'');
				}
				
				if($arr['parent']==1) {$san_id = $sanat_id = $page_id;}
			}
			else
			{
				if( ($page_dir=='news' || $page_dir=='opinion') && $extrasite_id = (int)@$arr['site'])
				{
					$extrasite_id = (int)@$arr['site'];
					$extra_parent_id = (int)@$arr['pid'];
					$parent_name = @$arr['parent_name'];
					
					$navig[] = array('name'=>HtmlSpecialChars($parent_name), 'link'=>"$lprefix/$parent_dir/");
					
					if(!$medicine) $part = $page_dir;
					
				}
				else
				{
					$sql = mysql_query("
						SELECT 
							d2.dir
						FROM 
							".TABLE_DIR." d2 
							LEFT JOIN ".TABLE_PAGE." i ON (d2.dir_id=i.dir_id) 
							LEFT JOIN ".TABLE_PAGE." c ON (c.page_id=i.parent)
						WHERE 
							d2.dir_id IS NOT NULL AND i.public='1' AND c.page_id='$arr[page_id]'
						ORDER BY
							i.ord
						LIMIT 1") or Error(1, __FILE__, __LINE__);
					if($arr = @mysql_fetch_array($sql))
					{
						$request[2] = $arr['dir'];
					} 
				}
			}
		}
		else
		{
			$sql = mysql_query("
				SELECT 
					i.page_id, i.site, i.name$englang as name
				FROM 
					".TABLE_PAGE." i 
					LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id AND d2.dir='$parent_dir') 
				WHERE 
					i.site AND i.parent=0 AND d2.dir_id IS NOT NULL AND i.public='1'") or Error(1, __FILE__, __LINE__);
				
			if($arr = @mysql_fetch_array($sql))
			{
				$extrasite_id = (int)@$arr['site'];
				$extra_parent_id = (int)@$arr['page_id'];
				
				if($page_dir=='sitemap' || $page_dir=='search')
				{
					$parent_name = @$arr['name'];
					
					$navig[] = array('name'=>HtmlSpecialChars($parent_name), 'link'=>"$lprefix/$parent_dir/");
					
					$part = $page_dir;
					
				}
			}
		}
	}
	if($request[2] && $request[1] && !$end && !$medicine)
	{
		$region_dir = mysql_escape_string($request[0]);
		$parent_dir = mysql_escape_string($request[1]);
		$page_dir = mysql_escape_string($request[2]);

		$sql = mysql_query("
			SELECT 
				r.name$englang as region_name, r.site, r.page_id as pid,
				c.name$englang as parent_name,  
				i.page_id, i.name$englang as name, 	d2.dir_id
			FROM 
				".TABLE_PAGE." r 
				LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=r.dir_id AND d.dir='$region_dir') 
				LEFT JOIN ".TABLE_PAGE." c ON (c.parent=r.page_id) 
				LEFT JOIN ".TABLE_DIR." d1 ON (d1.dir_id=c.dir_id AND d1.dir='$parent_dir') 
				LEFT JOIN ".TABLE_PAGE." i ON (i.parent=c.page_id) 
				LEFT JOIN ".TABLE_DIR." d2 ON (d2.dir_id=i.dir_id AND d2.dir='$page_dir') 
			WHERE 
				r.parent=0 AND d.dir_id IS NOT NULL AND r.public='1' AND
				d1.dir_id IS NOT NULL AND c.public='1' AND 
				d2.dir_id IS NOT NULL AND i.public='1'") or Error(1, __FILE__, __LINE__);
		if($arr = @mysql_fetch_array($sql))
		{
			$part = 'site';
			$region_name = @$arr['region_name'];
			$parent_name = @$arr['parent_name'];
			
			$page_id = (int)@$arr['page_id'];
			$page_name = @$arr['name'];
			
			$extrasite_id = (int)@$arr['site'];
			$extra_parent_id = (int)@$arr['pid'];
		
			if(!$extrasite_id) $navig = array(0=>array('name'=>$lang_phrases['home'], 'link'=>"$lprefix/"));
			$navig[] = array('name'=>HtmlSpecialChars($region_name), 'link'=>"$lprefix/$region_dir/");
			$navig[] = array('name'=>HtmlSpecialChars($parent_name), 'link'=>"$lprefix/$region_dir/$parent_dir/");
			$navig[] = array('name'=>HtmlSpecialChars($page_name), 'link'=>'');
		}
	}
}

if(!$extrasite_id && !count($navig)) $navig = array(0=>array('name'=>$lang_phrases['home'], 'link'=>"$lprefix/"));

$sql = mysql_query("SELECT d.dir FROM ".TABLE_PAGE."  p
	LEFT JOIN ".TABLE_DIR." d ON (d.dir_id=p.dir_id) 
	WHERE p.page_id=1 ") or Error(1, __FILE__, __LINE__);
$arr = @mysql_fetch_array($sql);
$dir_sanatorium = @$arr['dir'];

$sanat_list = sanat_list();

if(!$part) { 
	require 'lib/main.php';
	get_page_info('');
}

elseif($part == 'news') { 
	if($extrasite_id && ereg("^n([[:digit:]]+)", $request[2], $F)) $news_id = $F[1];
	elseif(ereg("^n([[:digit:]]+)", $request[1], $F)) $news_id = $F[1];
	
	require 'lib/news.php';
}

elseif($part == 'spec') { 
	if(ereg("^n([[:digit:]]+)", $request[1], $F)) $spec_id = $F[1];
	require 'lib/spec.php';
}

elseif($part == 'site') {
    // @TODO Remove this condition when ready
    if(isset($_GET['poll'])) {
        $content = get_template("templ/poll.htm", array());
    }else{
        require 'lib/site.php';
    }
}

elseif($medicine) { 
	require 'lib/medicine.php';
}

elseif($part == 'questionnaire') { 
	require 'lib/questionnaire.php';
	get_page_info($part);
}

elseif($part == 'cities') { 
	require 'lib/cities.php';
}

elseif($part == 'opinion') { 
	require 'lib/opinion.php';
	get_page_info($part);
	$navig[] = array('name'=>$lang_phrases['opinion'], 'link'=>'');
}

elseif($part == 'media') { 
	require 'lib/media.php';
}

elseif($part == 'search') { 
	require 'lib/search.php';
	get_page_info($part);
	$navig[] = array('name'=>$lang_phrases['search'], 'link'=>'');
}

elseif($part == 'sitemap') { 
	require 'lib/sitemap.php';
	get_page_info($part);
	$navig[] = array('name'=>$lang_phrases['sitemap'], 'link'=>'');
}

else
{ 
	page404();
}

//echo $meta_tags['title'];
//if(!$part) $meta_tags['topimg_id'] = 0; 
//list($topimg_photo, $topimg_size, $topimg_ext) = get_topimg($meta_tags['topimg_id'] );

if($extrasite_id)
{
	$menu_center = get_menu_extra();
	$menu_bottom = get_menu_extra(1);
	$menu2 = get_menu2_extra() ;
}
else
{
	$menu2 = $request[0]==$dir_sanatorium ? '' : get_menu2() ;
	$menu_top = get_menu1(1); 
	$menu_center = get_menu1(2); 
	$menu_bottom_left = get_menu1(3); 
	$menu_bottom_right = get_menu1(4); 
}

$slider = get_topimg();

if($part=='media')
{
	$menu_sanat = menu_sanat($dir_sanatorium, 1);
}
else
{
	if($extrasite_id) { if($medicine) $menu2 = menu_medicine(); }
	else $menu_sanat = $request[0]=='faq' ? '' : ($medicine ? menu_medicine() : menu_sanat($dir_sanatorium));
	$banner = '';
}

if($part){
	$news_anons = news_anons('block', 1);
	$banner = get_banners();
}


$title = $meta_tags['title'];
$description = $meta_tags['description'];
$keywords = $meta_tags['keywords'];

$message = @$_SESSION['message'];
if($message) $message = "alert('".addslashes($message)."')";

if($extrasite_id) 
{
	$rzd_id = $extrasite_id==8 ? 1525 : ($extrasite_id==10 ? 1526 : 0);
	
	$logo = $photo_owner["logo$englang"];
	$brochure = $photo_owner["brochure$englang"];
	$sql = mysql_query("SELECT f.photo_id, f.ext,
			fb.photo_id as fb_id, fb.ext as fb_ext
		FROM  ".TABLE_PAGE."  p 
		LEFT JOIN ".TABLE_PHOTO." f ON (f.owner_id=p.page_id AND f.owner=$logo)
		LEFT JOIN ".TABLE_PHOTO." fb ON (fb.owner_id=p.page_id AND fb.owner=$brochure)
		WHERE p.page_id=$extra_parent_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	$logo = $photo_dir["logo$englang"];
	$brochure = $photo_dir["brochure$englang"];
	$logo_img = "/images/$logo/$arr[photo_id]-s.$arr[ext]";
	$phone_img = "/images/$brochure/$arr[fb_id]-s.$arr[fb_ext]";
	
	$weather_informer = get_template("templ/extra/weather_informer{$englang}_$extrasite_id.htm", array());
	$footer = get_template("templ/extra/footer{$englang}_$extrasite_id.htm", array());
}

if(isset($_GET['print'])) $file =  "print.htm";
elseif($extrasite_id)  $file =  "index_extra.htm";
else  $file =  "index.htm" ;

require 'templ/'.$file;
$_SESSION['message']='';
?>