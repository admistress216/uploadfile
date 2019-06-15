<?php
namespace Anker\db;

class DB_Result
{
    protected  $pdo_result;
    protected $db;

    public function __construct(\PDOStatement $result,DB $db)
    {
        $this->pdo_result = $result;
        $this->db = $db;
    }
    public function one($style=\PDO::FETCH_ASSOC)
    {
        $result =  $this->pdo_result->fetch($style);
        if($result === FALSE){
            return $this->throwResultError($this->pdo_result);
        }
        return $result;
    }
    public function all($style=\PDO::FETCH_ASSOC)
    {
        $result =  $this->pdo_result->fetchAll($style);
        if($result === FALSE){
            return $this->throwResultError($this->pdo_result);
        }
        return $result;
    }
    private function throwResultError(\PDOStatement $query)
    {
        $errorInfo = $query->errorInfo();
        if($errorInfo[0] === "00000"){
            return [];
        }
        throw new DbException("获取结果集错误:错误码:==>{$errorInfo[0]};错误消息:==>{$errorInfo[2]};");
    }
}
