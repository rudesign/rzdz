<?

// îáùèå ôóíêöèè

function get_post($varname, $ind=0)
{
	$value = isset($_GET[$varname]) ? $_GET[$varname] : (isset($_POST[$varname]) ? $_POST[$varname] : '');
	if($ind==1) $value = (int)$value;
	elseif(get_magic_quotes_gpc())	$value = stripslashes($value);
	
	if($ind==2) $value = escape_string($value);
	
	return $value;
}

function escape_string($string)
{
	return mysql_real_escape_string($string);
}

function from_form($string)
{
	if(get_magic_quotes_gpc())
		return StripSlashes($string);
	else 
		return $string;
}

function sql_to_text_date($date, $rus=0, $full=0)
{
	global $rus_month_1, $day_array;
	
	$arr = explode('-', $date);
	if(!is_array($arr)) return '';
	if(count($arr)<3) return '';
	
	$y = @$arr[0]; $m = @$arr[1]; $d = @$arr[2];
	
	if(!checkdate($m, $d, $y)) return '';
	
	if($rus==1) return (int)$d.' '.$rus_month_1[(int)$m]." ".$y;
	elseif($rus==2) return $day_array[date('l', mktime(0,0,0,$m,$d,$y))]. ", ".(int)$d.' '.$rus_month_1[(int)$m]." ".$y;
	
	if(!$full) $y = substr($y, -2);	
	return "$d.$m.$y";
}

function Error($code, $file="", $line="") { 
	$str=date("d/m/Y H:i "). $file . ":" . $line . " ";
	$str_touser='';
	if($code == 1) {
		$str .= "SQL_ERROR: ". mysql_error();
		$str_touser .= "Îøèáêà çàïðîñà ê áàçå";
	}
	$f=fopen('error.log', 'a');
	fwrite($f, $str."\r\n");
	fclose($f);
	print $str_touser;
	exit;
}

function dbconnect($dbhost, $dbname, $dblogin, $dbpassword) {
	$lnk = @mysql_connect($dbhost, $dblogin, $dbpassword) or Error(1);
	if(!@mysql_select_db ($dbname,$lnk)) Error(1);
	mysql_query("SET NAMES cp1251");
	return 1;
}

function pages($count, $url, $on_page='') {
	global $current_page; 
	
	if(!$on_page) $on_page = $_SESSION['on_page'];

	if($count<2 || $current_page<1) return array('1', '');
	
	$on_period = ON_PERIOD;
	
	if($count%$on_page) $pagecount=(int) ($count/$on_page) + 1;
	else $pagecount=(int) $count/$on_page;
	
	if($current_page%$on_period) $period=(int) ($current_page/$on_period) + 1;
	else $period=(int) ($current_page/$on_period); 
	
	if($pagecount%$on_period) $periodcount=(int) ($pagecount/$on_period) + 1;
	else $periodcount=(int) ($pagecount/$on_period);
	
	$pages='Ñòðàíèöû: ';
	$arr=array();
	$limit=(($current_page-1)*$on_page).", ".$on_page;
	
	if($pagecount < 2) return array($on_page, '');
	
	for($i=1;$i<=$periodcount;$i++) {
		$start_page = ($i-1)*$on_period+1;
		$end_page = $i*$on_period;
		if($end_page > $pagecount) $end_page = $pagecount;
		if($period == $i) {
			for($j=$start_page;$j<=$end_page;$j++) {
				if($current_page == $j) $arr[] = "$j";
				else $arr[] = "<a href=\"".$url."page=$j\">$j</a>";
			}
		}
		else {
			if($start_page == $end_page) $link = $start_page;
			else $link = "[$start_page..$end_page]";
			if($current_page == $start_page) $arr[] = $link;
			else $arr[] = "<a href=\"".$url."page=$start_page\">$link</a>";
		}
	}
	
	$pages .= implode(" | ", $arr);
	
	return array($limit, $pages);
}

function digit_select($name, $start, $end, $choise, $onchange="") {
	$select="<select name=\"$name\" $onchange>\n";
	for($i=$start;$i<=$end;$i++) {
		$sel='';
		if($choise == $i) $sel='selected';
		$select.="<option value='$i' $sel>$i</option>\n";
	}
	$select.='</select>';
	return $select;
}

function array_select($name, $array, $choise, $null=0, $onchange="") {
	$select="<select name=\"$name\" $onchange>\n";
	if($null) $select.="<option value='0'></option>\n";
	foreach($array as $k=>$v) {
		$sel = ($choise == $k) ? 'selected' : ''; 
		$select.="<option value='$k' $sel>$v</option>\n";
	}
	$select.='</select>';
	return $select;
}

function mysql_select($name, $query, &$choise, $null=0, $onchange="") {
	$select="<select name=\"$name\" $onchange>\n";
	if($null) $select.="<option value='0'></option>\n";
	$sql = mysql_query($query) or Error(1); 
	while($arr = @mysql_fetch_array($sql))
	{
		if(!$choise && !$null) $choise = $arr[0];
		$sel = ($arr[0] == $choise) ? 'selected' : '';
		$select.="<option value='$arr[0]' $sel>".HtmlSpecialChars($arr[1], null, 'cp1251')."</option>\n";
	}
	$select.='</select>';
	return $select;
}

function ord_select($sql, $sel_name, $choise, $null=0, $onchange="") {
	$sql_ord = mysql_query($sql) or Error(1, __FILE__, __LINE__);
	$ord_array = array();
	$ord_array[1] = "Ïåðâûé";
	$i = 1;
	while($ord_info = @mysql_fetch_array($sql_ord)) 
	{
		$i++;
		if(!$ord_info['name']) $ord_info['name'] = NONAME;
		$ord_array[$i] = "Ïîñëå ". $ord_info['name'];
	}
	
	return array_select($sel_name, $ord_array, $choise, $null, $onchange);
}

function date_select($date, $day_name, $month_name, $year_name, $year_minus=1, $year_plus=1, $abs_ref_point=0, $style='') {
    global $rus_month_1;
	
	if(!$day_name) $day_name = 'd';
	if(!$month_name) $month_name = 'm';
	if(!$year_name) $year_name = 'y';
	
	$d = split('-', $date);
	$d[0]=@(int)$d[0]; $d[1]=@(int)$d[1]; $d[2]=@(int)$d[2];
	
	$day = "<select name=\"$day_name\" $style>\n";
	for($i=1;$i<=31;$i++) {
    	$sel = ($d[2] == $i) ? 'selected' : '';
		$day .= "<option value=\"$i\" $sel>$i</option>\n";
	}
	$day .= "</select>";
	
	$month = "<select name=\"$month_name\" $style>\n";
	for($i=1;$i<=12;$i++) {
    	$sel = ($d[1] == $i) ? 'selected' : '';
		$month .= "<option value=\"$i\" $sel>".$rus_month_1[$i]."</option>\n";
	}
	$month .= "</select>";
	
	$start_year = (!$d[0] || $abs_ref_point) ? date("Y") : $d[0];
	$ys = $start_year - $year_minus;
	$ye = $start_year + $year_plus;
	
	$year = "<select name=\"$year_name\" $style>\n";
	for($i=$ys;$i<=$ye;$i++) {
    	$sel = ($d[0] == $i) ? 'selected' : '';
		$year .= "<option value=\"$i\" $sel>$i</option>\n";
	}
	$year .= "</select>";
	
	return "$day $month $year";
}

function array_checkbox($name, $array, $choise) {
	$select="";
	foreach($array as $v) {
		$sel='';
		if(ereg("(^|,)$v[id](,|$)", $choise)) $sel='checked';
		$select.=
			"<input type=\"checkbox\" name=\"commun[$v[id]]\" value=\"1\" $sel style=\"border: 0px;\"> $v[name]<br>\n";
	}
	return $select;
}

function get_list_value($array, $id) {
	foreach($array as $v) {
		if($v['id'] == $id) { return $v['name']; }
	}
	return '';
}

function send_mail($to, $subj, $mess, $type='html') 
{
	// $type = 'plain';
	$head="Content-type: text/$type; charset=windows-1251";
	if(MAIL_FROM) $head.="\nFrom: ".MAIL_FROM;
	
	mail($to, $subj, $mess, $head);
	return true;
}

function get_template($file, $replace) {	
	global $lang_phrases, $englang, $lprefix, $print_page;

	foreach($replace as $k=>$v) ${$k} = $v; 
	ob_start();
	require $file;
	$content = ob_get_contents();
	ob_end_clean();
	return $content;
}

function RegExpSim($str)
{
	$arr = array('^', '$', '*', '+', '?', '|', '(', ')', '{', '}', '[', ']', '-', ':', '<', '>', '.');
	foreach($arr as $v) $str = str_replace($v, "\\".$v, $str);
	return $str;
}

function strtolower_ru($str)
{
	return strtr($str, 
		"ABCDEFGHIJKLMNOPQRSTUVWXYZÀÁÂÃÄÅ¨ÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß", 
		"abcdefghijklmnopqrstuvwxwzàáâãäå¸æçèéêëìíîïðñòóôõö÷øùúûüýþÿ");
}


function calendar($table, $year, $month, $templ_file, $link, $public)
{
	global $rus_month, $part;
	
	if(!checkdate($month, 1, $year)) { $year = date("Y"); $month = date("m"); }
	
	$sql = mysql_query("SELECT YEAR(date) FROM $table ORDER BY YEAR(date) LIMIT 1") or  Error(1, __FILE__, __LINE__);
	$info = @mysql_fetch_array($sql);
	$y_first = (int)@$info[0];
	
	// ñåãîäíÿ
	$yt = date("Y"); $mt = date("m"); $dt = date("j");
    
	$w = ($public) ? " and public='1'" : '';
	$sql = mysql_query("SELECT count(*) FROM ".TABLE_NEWS.
			" WHERE date=CURDATE() $w") or  Error(1, __FILE__, __LINE__);
	$today_link = '';
	if($info = @mysql_fetch_array($sql))
		if($info[0])
		{
			$today_link = ($public) ? $link."$yt/$mt/$dt/" : $link."&date=$yt-$mt-$dt"; 
		}
	
	$where = ($public) ? " AND public='1'" : '';
	$sql = mysql_query("SELECT date, count(*) as count FROM $table
		 WHERE YEAR(date)='$year' AND MONTH(date)='$month' $where GROUP BY date") or  Error(1, __FILE__, __LINE__);
	
	$date_count = array(); 
	while($info = @mysql_fetch_array($sql)) $date_count[$info['date']] = $info['count']; 
    
	for($dim=31;$dim>0;$dim--) if(checkdate($month, $dim, $year)) break;
	
	$dow1 = date("w", mktime(0, 0, 0, $month, 1, $year));
	if(!$dow1) $dow1 = 7;
	
	$dim -= (7 - $dow1 + 1);
	$wim = ($dim%7) ? (int)($dim/7) + 1 : $wim = $dim/7;
	$wim++;
	
	$week = array(); $day=0;
	$yt = date("Y"); $mt = date("m"); $dt = date("j");
	for($i=1;$i<=$wim;$i++) 
	{
		$days = array();
		for($dow=1;$dow<=7;$dow++) 
		{
			$day_current = 0; $date = ''; $today=0;
			if($day || (!$day && $dow1 == $dow)) 
			{
				$day++;
				if(checkdate($month, $day, $year)) {
					$date = sprintf("%04d-%02d-%02d", $year, $month, $day);
					if(!@$date_count[$date]) $date = '';
					if($day == $dt && $month == $mt && $year == $yt) $today = 1;
					$day_current = $day;
				}
			}
			if($date)  $date_link = ($public) ? $link."$year/$month/$day/" : $link."&date=$date"; 
			else $date_link = '';
			$days[] = array('day'=>$day_current, 'date_link'=>$date_link, 'dow'=>$dow, 'today'=>$today);
			
		} # for days of week
		
		$week[] = array('days'=>$days, 'i'=>$i); 
	} # for $week
	
	$arr_month = array();
	foreach($rus_month as $k=>$v) if($k) $arr_month[$k] = $v;

	$replace = array();
	
	$replace['today_link'] = $today_link;
	$replace['year'] = $year;
	$replace['year_prev'] = "/$part/".($year-1)."/12/";
	$replace['year_next'] = "/$part/".($year+1)."/1/";
	$replace['month'] = @$rus_month[(int)$month];
	$month_prev = mktime(0,0,0,$month-1,1,$year);
	$replace['month_prev'] = "/$part/".date("Y",$month_prev)."/".date("m",$month_prev)."/";
	$month_next = mktime(0,0,0,$month+1,1,$year);
	$replace['month_next'] = "/$part/".date("Y",$month_next)."/".date("m",$month_next)."/";
	$replace['month_link'] = "/$part/$year/$month/";
	
	$replace['week'] = $week;
	$s = ($year - 1 > $y_first) ? $y_first : $year - 1;
	$e = ($year + 1 < $yt) ? $yt : $year + 1;
	$replace['select_year'] = digit_select('sy', $s, $e, $year);
	$replace['select_month'] = array_select('sm', $arr_month, $month);

	$replace['part'] = $part;
    return  get_template($templ_file, $replace);
}

function separ_float($digit)
{
	$digit = (float)$digit;
	
	@list($whole, $fractional) = split("\\.", $digit);
	
	$arr = array();
	while(strlen($whole)>3)
	{
		$arr[] = substr($whole, -3);
		$whole = substr($whole, 0, strlen($whole)-3);
	} 
	if(strlen($whole)>0) $arr[] = $whole;
	
	$whole = join(" ", array_reverse($arr));
	
	return ($fractional) ? $whole.".".$fractional : $whole;
}

function renew_objects($root_dir)
{
	$sql = mysql_query("SELECT name, cure_id FROM ".TABLE_CURE."  WHERE parent=90 ORDER BY ord") 
		or Error(1, __FILE__, __LINE__, 1);
	
	$f = fopen($root_dir.'/js/autocomplete/objects.js', 'w');
	flock($f, LOCK_EX);
	
	fwrite($f, "var objects = {\n");
		
	while($info = @mysql_fetch_array($sql))
	{ 
		$str = "    \"$info[cure_id]\":  \"".addslashes($info['name'])."\",\n";
		fwrite($f, $str);
	}
	
	
	fwrite($f, "}");
	
	fflush($f);
	flock($f, LOCK_UN);
	fclose($f);

}

/**
 * Get meta tag postfix depending on the current month
 * 1.09-1.03 - winter time
 */
function getMetaTagsPostfix(){
    try{
        $month = date('n');

        $postfix =  ($month < 3 && $month >= 9) ? '1' : '';

        return $postfix;
    }catch (\Exception $e){
        return '';
    }
}