<?

// конфигурация базы данных
$config['dbhost']='localhost';
$config['dbname']='';
$config['dblogin']='';
$config['dbpassword']='';

// домен
define('DOMAIN', $_SERVER['HTTP_HOST']);
define('MAIN_URL', 'http://'.DOMAIN.'/');
define('ADMIN_URL', 'http://'.DOMAIN.'/admin/');

extract($_GET);
extract($_POST);
extract($_SERVER);

// обратный email для отправления писем 
define('MAIL_FROM', '"РЖД Здоровье" <php-sender-beta.rzdz.ru@undeliverable.masterhost.ru>');

?>