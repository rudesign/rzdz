<?php

require 'config.php';
require '../lib/func.php';
require 'lib/func_admin.php';

session_name(SES_NAME."_admin");
session_start();

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);

if(!@$_SESSION['admin_id']) {echo "Нет прав доступа"; exit;}

$default = 'imglib';
$default_dir = "../$default";
$field_name = @$field_name;

$type = @$type;

$cdir = @$cdir;
$cdir = ereg_replace("^(\/)?(.*)(\/)?$", "\\2", $cdir); 
$dirs = split("(\/)", $cdir);
if(@$dirs[0] == $default) $lib = @$dirs[1];
else $lib = '';


if(!$lib && (@$del_img || @$_FILES['photo']))
{
	$dg = @opendir($default_dir);
	while(($e = @readdir($dg)) !==false)
	{
		if($e=='.' || $e=="..") continue;
		
		if(is_dir($default_dir."/".$e))
		{
			$lib = $e;
			$cdir = $default."/".$lib;
			break;
		}
	}
	@closedir($dg); 
}
	
if(@$_FILES['photo'] ) {
	
	$photo = @$_FILES["photo"]["tmp_name"];
	$photo_name = @$_FILES["photo"]["name"];
	
	$_SESSION['watermark'] = $watermark = (int)@$wtm ? 1 : -1;
	
	$url = "filemanager.php?cdir=$cdir&type=$type&field_name=$field_name";
	
	if(!$lib)
	{
		$_SESSION['message'] = "Не найдена библиотека!"; 
		Header("Location: ".$url);
		exit;
	}
	
	$dir = $default_dir."/".$lib;
	
	if(!is_file($photo) || !($filename = @basename($photo_name))) 
	{
		$_SESSION['message'] = "Не найдена фотография!"; 
		Header("Location: ".$url);
		exit;
	}	
		
	if(is_file($dir."/".$filename)) 
	{
		$f = strtolower(escape_string(substr($filename, 0, strrpos($filename, "."))));
		$ext = strtolower(escape_string(substr($filename, strrpos($filename, ".")+1)));
		mt_srand();
		$filename = $f."_".substr(mt_rand(100, 999), 0, 4).".".$ext;
	}
	
	$big = $dir."/".$filename;
	
	$_SESSION['watermark'] = $watermark = (int)@$wtm ? 1 : -1;
	if($watermark > 0 && is_file($wm="../images/watermark.png"))
	{
		if($im = @imagecreatefromjpeg($photo))
		{
			if( $im_wm = imagecreatefrompng($wm) )
			{
		        require 'lib/watermark.class.php';
				$wtm = new watermark();
		        $img_new = $wtm->create_watermark($im, $im_wm);
				imageJpeg($img_new, $big, 80);
			}
			else { $_SESSION['message'] = "Не удается нанести водяной знак!\\nОшибка чтения файла формата PNG!"; copy($photo, $big); }
		}	
		else { $_SESSION['message'] = "Не удается нанести водяной знак!\\nФормат файла должен быть JPG!"; copy($photo, $big); }		
	}
	else copy($photo, $big);
		
	Header("Location: ".$url);
	exit;
}

if(@$del_img)
{
	$url = "filemanager.php?cdir=$cdir&type=$type&field_name=$field_name";
	@unlink($default_dir."/".$lib."/".$del_img);
	Header("Location: ".$url);
	exit;
}
	
$dg = @opendir($default_dir);
$lib_arr = array();
while(($e = @readdir($dg)) !==false)
{
	if($e=='.' || $e=="..") continue;
	
	if(is_dir($default_dir."/".$e))
	{
		$lib_arr[$e] = $e; 
		if(!$lib) $lib = $e;
	}
}
@closedir($dg); 

$water_mark = (@$_SESSION['watermark'] < 0) ? '' : 'checked';
		
$dir_select = array_select('public', $lib_arr, $lib, 0, 
	"onchange=\"window.location='filemanager.php?type=$type&field_name=$field_name&cdir=$default/'+this.value\"");

$lib = $default_dir."/".$lib;
$img_list = array();
$img_width = 100;
if(is_dir($lib))
{
	$dg = @opendir($lib);
	$lib_abs = ereg_replace("^\\.\\.\/", "/", $lib);
	while(($e = @readdir($dg)) !==false)
	{
		if($e=='.' || $e=="..") continue;
		
		if(is_file($file=$lib."/".$e))
		{
			$ext = strtolower(escape_string(substr($e, strrpos($e, ".")+1)));
			
			@list($w, $h, $t) = getimagesize($file);
			$del_link = "filemanager.php?cdir=$cdir&type=$type&field_name=$field_name&del_img=$e";
			
			if($ext == 'swf' && ($w>$img_width || !$w))
			{
				$k = ((float) $w)/$img_width;
				$wk = $img_width;
				$hk = (int) ($h/$k);
			}
			
			$img_list[] = array(
				'name'=>$e, 'src'=>$lib_abs."/".$e, 
				'ext'=>$ext, 'w'=>@$w, 'h'=>@$h, 'wk'=>@$wk, 'hk'=>@$hk, 
				'del_link'=>$del_link); 
		}
	}
	@closedir($dg); 
}

$message = (@$_SESSION['message']) ?  "
<script language=\"JavaScript\">
alert('$_SESSION[message]');
</script>" : '';


require 'templ/filemanager.htm';
$_SESSION['message']='';

?>