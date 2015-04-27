<?php

require 'config.php';
require '../lib/func.php';
require 'lib/func_admin.php';

session_name(SES_NAME."_admin");
session_start();

global $section_list, $section_name;

$section_list = array(
						'site', 
						'site_extra', 
						'cure',
						'meta',
						'menu',
						'gallery', 
						//'field', 
						//'field2', 
						//'order', 
						'news',
						'spec',
						'topimg',
						'slider',
						'banner',   
						'gest', 
						'opinion', 
						'quest',   
						'searchstat',
						'phone', 
						'settings', 
						'english', 
						'user');
$section_name = array(	'�������� �����',
						'�������������� �����',
						'��������',
						'Meta-����',
						'����',
						'�����������',
						//'����� ������',
						//'�����',
						//'������',
						'�������',
						'���������������',
						'�������',
						'��������� �� �������',
						'�������',
						'�������',
						'������',
						'��������',
						'���������� ������',
						'��������',
						'���������',
						'English',
						'������������');

// ���������
global $settings_list; 
$settings_list = array(
	array('field'=>'opinion_anons', 'name'=>'���������� ������� �� �������', 'type'=>'int'),
	array('field'=>'opinion_anons_count', 'name'=>'���������� ������� �� �������� ������', 'type'=>'int'),
	array('field'=>'opinion_count', 'name'=>'���������� ������� �� �������� �������', 'type'=>'int'),
	array('field'=>'news_anons', 'name'=>'���������� �������� �� �������', 'type'=>'int'),
	array('field'=>'news_count', 'name'=>'���������� �������� �� ��������', 'type'=>'int'),
	array('field'=>'spec_anons', 'name'=>'���������� ��������������� �� �������', 'type'=>'int'),
	array('field'=>'spec_count', 'name'=>'���������� ��������������� �� ��������', 'type'=>'int'),
	array('field'=>'gest_count', 'name'=>'���������� �������� �� ��������', 'type'=>'int'),
	array('field'=>'search_count', 'name'=>'���������� �����  �� �������� ������', 'type'=>'int'),
	array('field'=>'admin_email', 'name'=>'E-Mail ������ (��� ������)', 'type'=>'email'),
	array('field'=>'site_email', 'name'=>'E-Mail (������������ �� �����)', 'type'=>'email'),
	//array('field'=>'admin1_email', 'name'=>'E-Mail ������ (��� ���������)', 'type'=>'email'),
);
 
global $tinymce_elements, $tinymce_head;

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);

if(isset($_GET['enter']) || isset($_GET['logout'])) {require 'lib/enter.php';}
if(!@$_SESSION['admin_id']) {$title = ADMIN_TITLE; $login=HtmlSpecialChars(@$login, ENT_COMPAT, 'cp1251'); require 'templ/enter.htm'; exit;}

// ������ ��� �������
define('ON_PERIOD', 10);

global $onpage_list;
$onpage_list = array(
	10 => 10,
	15 => 15,
	20 => 20,
	25 => 25,
	30 => 30,
	40 => 40,
	50 => 50
);

$p = get_post('p');
global $part, $nav, $left_menu, $current_page;
$part = $p;
$current_page = ((int)@$page < 1) ? 1 : (int)$page;
if(isset($_GET['onpage'])) $_SESSION['on_page'] = (int)$onpage;
else $_SESSION['on_page'] = @$_SESSION['on_page'];
if($_SESSION['on_page'] < 1) { $_SESSION['on_page'] = 25; /*Reset($onpage_list); $k=key($onpage_list); $_SESSION['on_page'] = $onpage_list[$k];*/}

$nav[] = array('link'=>'', 'name'=>ADMIN_TITLE);
$content = '';

$ses_arr = array('im_width', 'im_height', 'photo_load', 'watermark');
foreach($ses_arr as $v) if(!isset(${$v})) ${$v} = @$_SESSION[$v];

foreach($section_list as $v)
{
	if($part == $v) 
		if(access($v)) 
		{
			if(file_exists("lib/$v.php")) require "lib/$v.php"; 
			else $content = "�� ������ ���� admin/lib/$v.php"; 
			break;
		}
}

$menu = get_menu();

$title = '';
foreach($nav as $v)
{
	$s = ($title) ? " - " : "";
	$title .= $s. $v['name'];
}

if(@$_SESSION['message']) $message = "
<script language=\"JavaScript\">
alert('$_SESSION[message]');
</script>";
else $message = '';

if(@$_SESSION['confirm']) 
{
	$confirm = "
	<script language=\"JavaScript\">
	if(confirm('$_SESSION[confirm]')) window.location='$_SESSION[confirm_url]';";
	if($_SESSION['confirm_nourl']) $confirm .= "
	else window.location='$_SESSION[confirm_nourl]';";
	$confirm .= "</script>";
}
else $confirm = '';

require 'templ/index.htm';
$_SESSION['message']='';
$_SESSION['confirm']='';

?>