<?

// ������������ ���� ������
$config['dbhost']='localhost';
$config['dbname']='';
$config['dblogin']='';
$config['dbpassword']='';

// �����
define('DOMAIN', $_SERVER['HTTP_HOST']);
define('MAIN_URL', 'http://'.DOMAIN.'/');
define('ADMIN_URL', 'http://'.DOMAIN.'/admin/');

extract($_GET);
extract($_POST);
extract($_SERVER);

// �������� email ��� ����������� ����� 
define('MAIL_FROM', '"��� ��������" <php-sender-beta.rzdz.ru@undeliverable.masterhost.ru>');

?>