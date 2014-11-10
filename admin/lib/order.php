<?php

$order_id = (int)@$order_id;


if(@$del_order)
{
	$del_order = (int)$del_order;
	
	mysql_query("DELETE FROM ".TABLE_ORDER." WHERE order_id='$del_order'") or Error(1, __FILE__, __LINE__);
	
	Header("Location: ".ADMIN_URL."?p=$part&page=$current_page");
	exit;
}

if(@$mode)
{
	$ordercount = (int)@$ordercount;
	
	if($mode == 'old' || $mode == 'del') 
	{
		$arr = array();
		for($i=1;$i<=$ordercount;$i++)
		{
			if(!$order_id = @$order[$i]) continue;
			$arr[] = "order_id=$order_id";
		}
		if(count($arr))
		{
			$str = join(" OR ", $arr);
			if($mode == 'old')
			{
				mysql_query("UPDATE ".TABLE_ORDER." SET new='0' WHERE $str") or Error(1, __FILE__, __LINE__);
			}
			else 
				mysql_query("DELETE FROM ".TABLE_ORDER." WHERE $str") or Error(1, __FILE__, __LINE__);
		}
		
		Header("Location: ".ADMIN_URL."?p=$part&page=$current_page");
		exit;
	}
	
}

$left_menu = " ";
	
if($order_id)
{
	$sql = mysql_query("SELECT * FROM ".TABLE_ORDER." WHERE order_id='$order_id'") or Error(1, __FILE__, __LINE__);
	if($order = @mysql_fetch_array($sql))
	{
		if($order['new'])  mysql_query("UPDATE ".TABLE_ORDER." SET new='0' WHERE order_id='$order_id'") or Error(1, __FILE__, __LINE__);
			
		list($date, $t) = split(" ", $order['date']);	
		$d = split("-", $date);
		$order['date'] = @$d[2].".".@$d[1].".".@$d[0];
	
		$order_data = array();
		$data = @Unserialize($order['data']);
		
		if(is_array($data)) 
		{
			foreach($data as $k=>$v) 
			{ 
				$v['name'] = nl2br(HtmlSpecialChars($v['name'])); 
				$v['value'] = nl2br(HtmlSpecialChars($v['value'])); 
				$data[$k] = $v; 
			}
			$order['data'] = $data;
		}
		else $order['data'] = array();
		
		
		$order['orders_link'] =  ADMIN_URL."?p=$part&page=$current_page";
		$order['current_page'] = $current_page;
		
		
		$content = get_template('templ/order.htm', $order);
	}
	
	return;
}


$replace = array();

$sql = mysql_query("SELECT COUNT(*) FROM ".TABLE_ORDER) or Error(1, __FILE__, __LINE__);
$arr = mysql_fetch_array($sql);
$replace['all'] = $all = $arr[0];

list($limit, $replace['pages']) = pages($all, ADMIN_URL."?p=$part&");
$replace['onpage_select'] = array_select('onpage', $onpage_list, $_SESSION['on_page'], 0, 
	"onchange=\"window.location='".ADMIN_URL."?p=$part&onpage='+this.value\"");
		
$sql = mysql_query("SELECT order_id, data, new, date FROM ".TABLE_ORDER.
	" ORDER BY date desc, order_id desc LIMIT $limit") or Error(1, __FILE__, __LINE__);

$orders = array(); $i = ($current_page-1)*$_SESSION['on_page']; $j = 0;
while($info = @mysql_fetch_array($sql))
{ 
	$i++; $j++;
	$info['i'] = $i;
	$info['j'] = $j;
	
	$data = @Unserialize($info['data']);
	$info['data'] = '';
	if(is_array($data))
	{
		$arr = array();
		foreach($data as $v)
		{
			if(trim($v['value'])) $arr[] = HtmlSpecialChars($v['value'])."\n";
		}
		$info['data'] = join(" | ", $arr);
		if(strlen($info['data'])>60) $info['data'] = substr($info['data'],0,60)."...";
	}
	
	
	list($date, $t) = split(" ", $info['date']);	
	$d = split("-", $date);
	$info['date'] = @$d[2].".".@$d[1].".".@$d[0];
	
	$info['edit_link'] = ADMIN_URL."?p=$part&order_id=$info[order_id]&page=$current_page";
	$info['del_link'] = ADMIN_URL."?p=$part&del_order=$info[order_id]&page=$current_page";
		
	$orders[] = $info;
}

$replace['orders'] = $orders;
$replace['ordercount'] = $j;
$replace['current_page'] = $current_page;

$content = get_template('templ/order_list.htm', $replace);

?>