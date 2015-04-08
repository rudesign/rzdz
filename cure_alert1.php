<style>
    a{
        color: #008c95;
    }
    .u{
        text-transform: uppercase;
    }
    .content{
        width: 600px;
        height: 400px;
        color: #636363;
        font-family: Tahoma,Arial,sans-serif;
        font-size: 13px;
        line-height: 1.3;
    }
    .centered{
        text-align: center;
    }
</style>

<?php

try{
    $config = $data = array();

    if(!@include('admin/config_site.php')) throw new \Exception('Отсутствует файл конфигурации');

    if(!$connect = @mysql_connect($config['dbhost'], $config['dblogin'], $config['dbpassword'])) throw new \Exception('Нет подключения к базе данных');
    if(!@mysql_select_db($config['dbname'], $connect)) throw new \Exception('Не выбрана база данных');

    $query = "SELECT name, description as description FROM zdor_cure WHERE cure_id=14";

    if($queryResult = @mysql_query($query)) {
        if($result = @mysql_fetch_array($queryResult)) {
            $data['att_name'] = $result['name'];
            $data['att_description'] = $result['description'];
        }else throw new \Exception;
    }else throw new \Exception;

    $query = "SELECT cure_id, name FROM zdor_cure WHERE parent=14";

    if($queryResult = @mysql_query($query)) {
        while ($result = @mysql_fetch_array($queryResult)) {
            $list[] = array('name' => $result['name'], 'link' => "/medicine/14/" . $result['cure_id']);
        }
        $data['att_list'] = $list;
    }else throw new \Exception;

    show($data);

}catch (\Exception $e){
    echo $e->getMessage() ? $e->getMessage() : 'Ошибка в строке '.$e->getLine();
}

function show($data){
    if(!empty($data)) {
        echo '
        <div class="content">
            <p class="centered u"><b>'.$data['att_name'].'</b></p>
            <p>&nbsp;</p>
            <p>'.$data['att_description'].'</p>
            <p>&nbsp;</p>
            <p class="centered"><a href="/medicine/1238" target="_blank"><b>Полный список противопоказаний</b></a></p>
        </div>
        </div>';
    }else{
        echo 'Нет данных';
    }
}
