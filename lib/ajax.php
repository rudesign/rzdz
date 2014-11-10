<?

if(@$vote)
{
	
	$photo_id = (int)@$photo_id;
	$vote = (int)@$vote;
	
	if($vote > 5 || $vote < 1) exit;
	
	if (!empty($_SERVER['HTTP_CLIENT_IP']))  $ip=$_SERVER['HTTP_CLIENT_IP'];
	elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	else $ip=$_SERVER['REMOTE_ADDR'];
	
	$sql = mysql_query("SELECT count(*) FROM ".TABLE_RATINGIP." WHERE ip='$ip' AND photo_id=$photo_id") or Error(1, __FILE__, __LINE__);
	$arr = @mysql_fetch_array($sql);
	if($arr[0]>0)
	{
		echo 1;
		exit;
	}
	
	$cookname = 'rating'.$photo_id;
	$days = 30;
	
	if(isset($HTTP_COOKIE_VARS[$cookname]))
	{
		echo 1;
		exit;
	}
	setcookie($cookname,'1',time()+60*60*24*$days); 
	
	mysql_query("UPDATE ".TABLE_PHOTO." SET rating=rating+".$vote." WHERE photo_id=$photo_id") or Error(1, __FILE__, __LINE__);
	mysql_query("INSERT INTO ".TABLE_RATINGIP." SET ip='$ip', photo_id=$photo_id, vote=$vote") or Error(1, __FILE__, __LINE__);
	
	echo 2;
	exit;

}

if(@$photo_id)
{
	session_register('photoview');
	
	$photo_id = (int)@$photo_id;
	
	$arr = @unserialize($photoview);
	if(!is_array($arr)) $arr = array();
	
	if(ereg("(^|,)$photo_id(,|$)", $photoview)) exit;
	
	$photoview .= $photoview ? ",$photo_id" : $photo_id;

	mysql_query("UPDATE ".TABLE_PHOTO." SET view=view+1 WHERE photo_id=$photo_id") or Error(1, __FILE__, __LINE__);

}


?>