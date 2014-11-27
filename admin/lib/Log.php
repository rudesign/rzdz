<?php

class Log{

    protected $logTableName = 'zdor_userlog';
    public $userId = '';

    public function __construct($userId = ''){
        if(!empty($userId)) $this->userId = $userId;
    }

    public function store($actionName = '', $objectName = '', $userId = ''){
        try{
            if(empty($actionName)) throw new Exception;
            if(empty($objectName)) throw new Exception;

            $userId = empty($userName) ? $this->userId : $userId;

            $hash = $this->getHash($userId, $actionName, $objectName);

            $query = "SELECT hash FROM `{$this->logTableName}` ORDER BY id DESC LIMIT 1";
            if($result = mysql_query($query)){
                if($row = mysql_fetch_assoc($result)){
                    if($row['hash'] == $hash) throw new Exception;
                }
            }

            $query = "INSERT INTO `{$this->logTableName}` (`id`, `user_id`, `action_name`, `object_name`, `hash`, `created_time`) VALUES (0, '{$userId}', '{$actionName}', '{$objectName}', '{$hash}', UNIX_TIMESTAMP());";

            if(!mysql_query($query)) throw new Exception;

            return true;
        }catch (Exception $e){
            return false;
        }
    }

    public function getActionName($actionType = ''){
        $actionName = array();

        switch($actionType){
            case 'add':
                $actionName[] = 'создаёт';
            break;
            case 'edit':
                $actionName[] = 'редактирует';
            break;
            case 'del':
                $actionName[] = 'удаляет';
            break;
        }

        $actionName = implode(' ', $actionName);

        return $actionName;
    }

    protected function getHash($userId = '', $actionName = '', $objectName = ''){
        return md5($userId.$actionName.$objectName);
    }
}