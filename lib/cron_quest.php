<?php 
// #!/usr/local/bin/php

$root_dir = "../"; //"/var/www/rudes/data/www/rs.tour-shop.ru";

require $root_dir."/admin/config.php";
require $root_dir.'/lib/func.php';

dbconnect($config['dbhost'], $config['dbname'], $config['dblogin'], $config['dbpassword']);

// отправляем писем за один раз
$letter_count = 2;

$sql = mysql_query("
	SELECT 
		qm.mail_id, qm.email, qm.secret, q.quest_id, q.name
	FROM 
		".TABLE_QUESTMAIL." qm 
		LEFT JOIN ".TABLE_QUESTIONNAIRE." q  on (qm.quest_id=q.quest_id) 
	WHERE
		qm.email !='' AND !qm.send AND !qm.done
	ORDER BY 
		q.quest_id, qm.mail_id
	LIMIT $letter_count") or Error(1, __FILE__, __LINE__, 1);
	
while($info = @mysql_fetch_array($sql))
{ 	
	$mess = get_template($root_dir."/templ/mail_questionnaire.htm", array(
			'name'=>HtmlSpecialChars($info['name']), 
			'link'=>MAIN_URL."questionnaire/?qid=$info[quest_id]&mid=$info[mail_id]&secret=$info[secret]"
			)); 
	
	send_mail($info['email'], "Опрос от \"РЖД Здоровье\"", $mess,  'html', MAIL_FROM);
	
	mysql_query("UPDATE	".TABLE_QUESTMAIL." SET send=1 WHERE mail_id='$info[mail_id]'") or Error(1, __FILE__, __LINE__, 1);
}

?>