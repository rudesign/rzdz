<?

if(isset($pdf))
{

	$name = @$name ? str_replace(" ","_", @$name) : $pdf;
	$ext = @$ext;
	$path = "/images/$photo_dir[license]/$pdf.$ext";
	
	if (file_exists($path)) {
		// ���������� ����� ������ PHP, ����� �������� ������������ ������ ���������� ��� ������
		// ���� ����� �� ������� ���� ����� �������� � ������ ���������!
		if (ob_get_level()) {
		  ob_end_clean();
		}
		// ���������� ������� �������� ���� ���������� �����
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		if($request[0]!='listen') header('Content-Disposition: attachment; filename=' . "$name.$ext");
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate');
		header('Pragma: public');
		header('Content-Length: ' . filesize($path));
		// ������ ���� � ���������� ��� ������������
		readfile($path);
		exit;
	}

}

if(@$photo_id)
{
	$photo_id = (int)@$photo_id;

	$sql = mysql_query("SELECT description, owner, ext_b, photo_id, rating FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	
	if($arr['owner']==$photo_owner['video']) echo @$arr['description'];
	
	elseif($arr['owner']==$photo_owner['pdf']) echo '
	
	<iframe   src="'.$arr['description'].'" width="730" height="450" style="border:none;">
				  ��� ������� �� ������������ ��������� ������!
				</iframe>
	';
	
	else 
	{
		$m_list = array('', 'item', 'panor', 'video', 'wallpaper', 'other');
		$mm = $m_list[$arr['owner']];  
		
		$f_big = "/images/$photo_dir[$mm]/$arr[photo_id].$arr[ext_b]";
		if(!file_exists($f_big)) {echo "�� ������� ���� $f_big"; exit;}
		list($w, $h) = getimagesize($f_big);
		
		$bigphoto = "/".$f_big;
		
?>
		<iframe src="<?=$lprefix?>/video/?other=<?=$arr['photo_id']?>" width="<?=$w+20?>" height="<?=$h+50?>" style="border:none;overflow:hidden"></iframe>
<?
	
	}
}

if(@$other)
{
	$photo_id = (int)@$other;

	$sql = mysql_query("SELECT description, owner, ext_b, photo_id, rating FROM ".TABLE_PHOTO." WHERE photo_id='$photo_id'") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	
	if($arr['owner']==$photo_owner['video']) echo @$arr['description'];
	
	else 
	{
		$m_list = array('', 'item', 'panor', 'video', 'wallpaper', 'other');
		$mm = $m_list[$arr['owner']];  
		
		$f_big = "/images/$photo_dir[$mm]/$arr[photo_id].$arr[ext_b]";
		if(!file_exists($f_big)) {echo "�� ������� ���� $f_big"; exit;}
		list($w, $h) = getimagesize($f_big);
		
		$bigphoto = "/".$f_big;
		$superphoto = is_file($bf="/images/big/$arr[photo_id].jpg") ?  "/".$bf : '';
		
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<base href="http://<?=DOMAIN?>/">
<style>
body{
	font-family:Tahoma, sans-serif;
	line-height:1.3;
	color:#636363;
}
@font-face {
	font: 13px/18px Tahoma, Verdana, sans-serif;
}
.orang {
	font-size:13px;
  color: #008C95;
  margin-top:10px;
  text-align:center;
}
</style>
<meta content="text/html; charset=windows-1251" http-equiv=Content-Type>
<script type="text/javascript" src="js/func.js"></script> 
<script type="text/javascript" src="js/colorbox/jquery-1.6.4.min.js"></script> 
<script src="js/rateit/jquery.rateit.js" type="text/javascript"></script>
</head>
<body>
		<img src="<?=$bigphoto?>" width="<?=$w?>" height="<?=$h?>">
	<?if($superphoto){?><a href="<?=$superphoto?>" target="_blank" class="orang" style="display:block;text-align:center"><?=$lang_phrases['big_quality']?></a><?}?>
</body>
</html>
<?
	
	}
}


