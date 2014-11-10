<?
require $_SERVER['DOCUMENT_ROOT'].'/admin/config.php';
/*
require $_SERVER['DOCUMENT_ROOT'].'/lib/func.php';
require $_SERVER['DOCUMENT_ROOT'].'/lib/func_user.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/lang.php';
require $_SERVER['DOCUMENT_ROOT'].'/admin/settings.php';

session_name(SES_NAME);
session_start();

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);
*/


$message = '';

try{
    if(empty($_POST['selection'])) throw new Exception('Отсутствует текст');

    $addressee = 'bushy@yandex.ru';
    //$addressee = '220619@gmail.com';
    $theme = 'Неточность на сайте '.$_SERVER['HTTP_HOST'];

    $body = array(
        '<b>Выделенный текст:</b>'.$_POST['selection'],
    );
    if($_POST['body']) $body[] = '<b>Предлагаемая замена:</b> '.$_POST['body'];
    $body[] = '<b>Адрес страницы:</b> <a href="'.$_SERVER['HTTP_REFERER'].'">'.$_SERVER['HTTP_REFERER'].'</a>';

    $body = implode("<br /><br />", $body);

    $headers  = "Content-type: text/html; charset=utf-8 \r\n";
    $headers .= "From: <mailer@".$_SERVER['HTTP_HOST']."\r\n";

    if(!@mail(
        $addressee,
        $theme,
        $body,
        $headers
    )) throw new Exception('Ошибка при отправке сообщения');

}catch (Exception $e){
    $message = $e->getMessage();
}

echo json_encode(array(
    'message'=>$message,
));
?>