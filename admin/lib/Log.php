<?php

class Log{

    public $userName = '';

    public function __construct($userName = ''){
        if(!empty($userName)) $this->userName = $userName;
    }

    public function store($actionName = '', $objectName = ''){
        try{
            if(empty($actionName)) throw new Exception;
            if(empty($objectName)) throw new Exception;

            $query = "INSERT INTO `zdor_userlog` (`id`, `name`, `object_name`, `created_time`) VALUES (0, '{$actionName}', '{$objectName}', UNIX_TIMESTAMP());";

            if(!mysql_query($query)) throw new Exception;

            return true;
        }catch (Exception $e){
            return false;
        }
    }


    public function getActionName($userName = '', $actionType = ''){
        $actionName = array();

        $actionName[] = $userName;

        switch($actionType){
            case 'add':
                $actionName[] = 'добавил(а)';
            break;
            case 'edit':
                $actionName[] = 'отредакировал(а)';
            break;
            case 'del':
                $actionName[] = 'удалил(а)';
            break;
        }

        $actionName = implode(' ', $actionName);

        return $actionName;
    }
}