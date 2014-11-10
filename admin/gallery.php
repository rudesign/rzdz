<?php

require 'config.php';
require '../lib/func.php';

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);

$photo_id = (int)@$photo_id;

$title = ADMIN_TITLE. " - ". ADMIN_GALLERY_PAGE;

$sql = mysql_query("SELECT gallery_id, name, public FROM ".TABLE_GALLERY." WHERE parent=0 ORDER BY ord") 
	or Error(1, __FILE__, __LINE__);

function get_level($parent=0, $level=1)
{
	$sql = mysql_query("SELECT gallery_id, name, public FROM ".TABLE_GALLERY." WHERE parent=$parent ORDER BY ord") 
		or Error(1, __FILE__, __LINE__);
	$galleries = array();
	while($info = @mysql_fetch_array($sql))
	{ 
		$info['name'] = HtmlSpecialChars($info['name']);
		if(!$info['name']) $info['name'] = NONAME;
		
		if($level <= 2) $info["level".($level+1)] = get_level($info['gallery_id'], $level+1);
		else $info["level".($level+1)] = array();
				
		$galleries[] = $info;
	}
	return $galleries;
}

$galleries = get_level();

$replace['galleries'] = $galleries;
$replace['photo_id'] = $photo_id;

$sql = mysql_query("SELECT owner_id FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
$arr = @mysql_fetch_array($sql);
$gallery_id = (int)@$arr[0];
	
$replace['gallery_id'] = $gallery_id;

require 'templ/galleries.htm';

?>