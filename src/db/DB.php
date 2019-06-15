<?php
/**
 * 查询构造器
 */
namespace Anker\db;

use Anker\cls\Config;

class DB
{
    protected $pdo;//pdo对象
    protected $dsn;
    protected $userName;
    protected $password;
    protected $reconCount = 0; // 重连次数
    protected $_select = "SELECT *";//sql的select
    protected $_from = "";//sql的from
    protected $_where = [];//where条件的key
    protected $_where_value = [];//where条件需要被绑定的值
    protected $_group_by = "";//分组
    protected $_limit = "";//sql的limit
    protected $_join = [];//链表
    protected $_last_query = "";//最后一条查询
    protected $_table_prefix = "";//表前缀
    protected $escape = "'";//转义字符
    protected $charset = "utf8mb4";

    /**
     * @param bool $isRecon
     */
    public function setIsRecon($isRecon)
    {
        $this->isRecon = $isRecon;
    }

    /**
     * @return int
     */
    public function getReconCount()
    {
        return $this->reconCount;
    }


    //获取pdo对象
    public function getPdo()
    {
        return $this->pdo;
    }
    //最后使用的sql
    public function last_query()
    {
        return $this->_last_query;
    }

    /**
     * DB constructor.
     * @param string $resourceName
     * @throws \Exception
     */
    public function __construct($resourceName='')
    {
        Config::load("db.config","db");
        $config = Config::item("db",$resourceName);
        if(empty($config["database"])){
            throw new \Exception("数据库连接database不能为空");
        }
        if(empty($config["host"])){
            throw new \Exception("数据库连接host不能为空");
        }
        $dsn = "mysql:dbname={$config['database']};host={$config['host']};port={$config['port']}";

        $this->dsn = $dsn;
        $this->userName = $config["user"];
        $this->password = $config["password"];

        $this->pdo = new \PDO($dsn,$config["user"],$config["password"]);

        if(isset($config["db_prefix"])){
            $this->_table_prefix = $config["db_prefix"];
        }
        $this->init();
    }

    /**
     * 初始化工作
     */
    private function init()
    {
        if($this->charset){
            $this->query("set names {$this->charset}");
        }
    }

    /**
     * @desc 构造select条件
     * @example $this->db->select("id,name as name1")
     */
    public function select($select)
    {
        $this->_select = "SELECT ".$select;
        return $this;
    }

    /**
     * @desc 构造from条件
     * @param $from
     * @return $this
     */
    public function from($from)
    {
        $from = trim($from);
        $from = " FROM ".$this->_table_prefix.$from;

        $this->_from = $from;
        return $this;
    }

    /**
     * @desc 分组
     * @param $column string 要分组的列
     */
    public function group_by($column)
    {
        if($this->_group_by){
            $this->_group_by .= ",".$column;
        }else{
            $this->_group_by = "GROUP BY ".$column;
        }
    }

    /**
     * @param $table
     * @param $condition
     * @param string $type
     * @return $this
     */
    public function join($table,$condition,$type="")
    {
        $table = ltrim($table);
        $table = $this->_table_prefix.$table;

        $con = $condition;
        $join = " $type JOIN $table ON $con ";
        array_push($this->_join,$join);
        return $this;
    }

    /**
     * @dsec 构造where条件
     * @param array | string $where
     * @return $this
     * @example1 $this->db->where(["name"=>"tom","age"=>11])  or $this->db->where(["id"=>[1,2,3,4]]);
     * @example2 $this->db->where("name=? and age=?",['dxp',2]);
     */
    public function where($where,array $bindValues = [])
    {

        if(is_array($where)){
            foreach($where as $key=>$val){
                if(!is_array($val)){
                    $this->_where[] = $key;
                    $this->_where_value[] = $val;
                }else{
                    $str = $key." IN(";
                    $marks = [];
                    foreach($val as $k=>$v){
                        array_push($marks,"?");
                        array_push($this->_where_value,$v);
                    }
                    $markStr = implode(",",$marks);
                    $str = $str.$markStr.")";
                    $this->_where[] = $str;
                }
            }
        }
        if(is_string($where)){
            //转换:name为?
            //.......暂不实现
            $this->_where[] = $where;
            $this->_where_value = array_merge($this->_where_value,$bindValues);
        }
        return $this;
    }
    /**
     * @desc 排序
     * @param array $orders
     * @return DB
     */
    public function order_by(array $orders)
    {
        foreach($orders as $key=>$val){
            $order = strtolower($val);
            if($order != "asc" && $order != "desc"){
                continue;
            }
            $this->_orderBy[$key] = $val;
        }
        return $this;
    }

    //直接执行sql语句查询
    public function query($sql,$bindValue = [],$isReset = true)
    {
        $sql = trim($sql);
        $pattern = '/^(INSERT|UPDATE|DELETE)/i';
        $isWrite = preg_match($pattern,$sql,$m);

        if($bindValue){
            $n_sql = $sql;
            $last_query = "";
            $n_bindValue = $this->getSqlVal($bindValue);
            foreach ($n_bindValue as $v){
                if(($pos = strpos($n_sql,"?")) !== false ){
                    $last_query .= substr($n_sql,0,$pos).$v;
                    $n_sql = substr($n_sql,$pos+1);
                }
            }
            $this->_last_query = $last_query.$n_sql;
        }else{
            $this->_last_query = $sql;
        }
        $isReset ? $this->resetQuery() : NULL;
        if($bindValue){
            $statement = $this->pdo->prepare($sql);
            if($statement === FALSE){
                $this->throwPdoError($this->pdo);
            }
            if($statement->execute($bindValue) === FALSE){
                $this->throwResultError($statement);
            }
            if(!$isWrite){
                return new DB_Result($statement,$this);
            }else{
                return $statement->rowCount();
            }
        }elseif($isWrite){
            $result = $this->pdo->exec($sql);
            return $result;
        }else{
            $statement = $this->pdo->query($sql);
            if($statement === FALSE){
                $this->throwPdoError($this->pdo);
            }
            return new DB_Result($statement,$this);
        }
    }
    /**
     * @desc 生成查询结果集合
     */
    public function get()
    {
        $sql = $this->getPreSQL();
        $val = $this->whVals();
        return $this->query($sql,$val);
    }

    protected  $_last_insert_id;

    /**
     * @desc 获取最后插入的id
     */
    public function insert_id()
    {
        if(empty($this->_last_insert_id)){
            return $this->pdo->lastInsertId();
        }
        return $this->_last_insert_id;
    }

    /**
     * 插入数据
     * @param $table
     * @param $data
     * @return int
     */
    public function insert($table,$data)
    {
        $table = $this->_table_prefix.$table;
        $sql = "INSERT INTO {$table}";
        $keyArr = array_keys($data);
        $valArr  = array_values($data);
        $mark = array_fill(0,count($valArr),"?");
        $fields = "(".implode(",",$keyArr).")";
        $values = "(".implode(",",$mark).")";

        $sql1 = $sql.$fields." VALUES".$values;
        return $this->query($sql1,$valArr);
    }


    /**
     * @desc更新
     * @param $table
     * @param  $data
     * @param  $condition
     * @return int
     */
    public function update($table,array $data,array $condition)
    {
        $table = $this->_table_prefix.$table;

        $upKeys = $upVals = [];
        $conKey = $conVals = [];

        foreach ($data as $key=>$value){
            $value = trim($value);
            if(strpos($value,"!") === 0){
                $value = substr($value,1);
                array_push($upKeys,"{$key}={$value}");
            }else{
                array_push($upKeys,$key."="."?");
                array_push($upVals,$value);
            }
        }

        foreach($condition as $k=>$v){
            array_push($conKey,$k."="."?");
            array_push($conVals,$v);
        }
        $upStr = implode(",",$upKeys);
        $conStr = implode(" AND ",$conKey);
        $sql = "UPDATE {$table} SET {$upStr} WHERE {$conStr}";
        return $this->query($sql,array_merge($upVals,$conVals));
    }

    /**
     * @desc 删除数据
     * @param $table 表名
     * @param array $where 条件，如果为空就会使用$this->_where
     * @return boolean
     */
    public function delete($table,array $where=[])
    {
        if(empty($where) && empty($this->_where)){
            return FALSE;
        }
        $table = $this->_table_prefix.$table;
        $sql = "DELETE FROM $table ";

        $conKey = $conVals = [];
        if($where){
            foreach($where as $k=>$v){
                array_push($conKey,$k."="."?");
                array_push($conVals,$v);
            }
            $conStr = " WHERE ".implode(" AND ",$conKey);
        }else{
            $conStr = $this->getCompileWh();
            $conVals = $this->whVals();
        }
        $sql .= "{$conStr}";
        return $this->query($sql,$conVals);
    }
    /**
     * @desc 获取编译的where
     */
    protected function getCompileWh()
    {
        $temp = [];
        foreach($this->_where as $v){
            if(preg_match('/\band\b|\bor\b/i',$v) == 0){
                if(preg_match('/<|>|<=|>=|\!=|\blike\b|\bIN\b/i',$v) == 0){
                    array_push($temp,$v." = "." ? ");
                }elseif(preg_match('/\bIN\b/i',$v)){
                    array_push($temp,$v);
                } else{
                    array_push($temp,$v." ? ");
                }
            }else{
                array_push($temp,"(${v})");
            }
        }
        $andStr = implode(" AND ",$temp);
        $str = $andStr;
        return $str ? "WHERE ".$str:$str;
    }

    /**
     * @desc 查询数据满足条件的数据量
     * @param $isReset bool 是否重置查询
     * @return
     */
    public function count_all_results($isReset = false)
    {
        $select= " SELECT COUNT(*) AS nums ";
        $sql = $this->getPreSQL($select);
        $query = $this->query($sql,$this->whVals(),$isReset);
        $result = $query->one();
        return $result["nums"];
    }

    /**
     * @desc 重置查询
     */
    public function resetQuery()
    {
        $this->_select = "SELECT *";
        $this->_from = "";
        $this->_where = [];
        $this->_where_value = [];
        $this->_orderBy = [];
        $this->_group_by = "";
        $this->_limit = "";
        $this->_join = [];
    }

    /**
     * @param $limit
     * @param int $offset
     * @return $this
     */
    public function limit($limit,$offset=0)
    {
        $limit = intval($limit);
        $offset = intval($offset);

        $limit = " LIMIT $limit";
        if($offset){
            $limit .= " OFFSET $offset";
        }
        $this->_limit = $limit;
        return $this;
    }

    /**
     * @desc 获取所有预编译的值
     * @return array
     */
    protected  function whVals()
    {
        return $this->_where_value;
    }

    //得到预编译sQL
    protected function getPreSQL($sel=NULL)
    {
        if($sel){
            $select = $sel;
        }else{
            if(empty($this->_select)){
                $select = "SELECT *";
            }else{
                $select = $this->_select;
            }
        }
        $from = $this->_from;
        if(empty($from)){
            throw new \Exception("未指定表名");
        }
        $join = implode(" ",$this->_join);
        $where = $this->getCompileWh();

        $orderArr = [];
        foreach($this->_orderBy as $key=>$order){
            array_push($orderArr,$key." ".$order);
        }
        if(!empty($orderArr)){
            $orderBy = " ORDER BY ".implode(",",$orderArr);
        }else{
            $orderBy = "";
        }
        $limit = $this->_limit;
        $sql = $select." ".$from;
        $sql .= $join ? " $join" : "";
        $sql .= $where ? " $where": "";
        $sql .= $this->_group_by ? " {$this->_group_by}" :"";
        $sql .= $orderBy ? " $orderBy" :"";
        $sql .= $limit ? " $limit" : "";
        return $sql;
    }

    /**
     * 获取sql值
     */
    private function getSqlVal(array $val)
    {
        foreach($val as &$v){
            if(is_string($v)){
                $v  = "'{$v}'";
            }
        }
        return $val;
    }
    /**
     * 重新链接
     */
    public function reconnect(){
        $status = $this->pdo->getAttribute(\PDO::ATTR_SERVER_INFO);
        if($status == 'MySQL server has gone away')
        {
            //重连
            unset($this->pdo);
            $this->pdo = new \PDO($this->dsn,$this->userName,$this->password);
        }
    }
    public function beginTransaction(){
        $this->pdo->beginTransaction();
    }
    public function rollBack(){
        $this->pdo->rollBack();
    }
    public function commit(){
        $this->pdo->commit();
    }

    private function throwPdoError(\PDO $pdo)
    {
        $errorInfo = $pdo->errorInfo();
        if($errorInfo[0] === "00000"){
            return [];
        }
        throw new \Exception("执行sql语言产生错误错误码:==>{$errorInfo[0]};错误消息:==>{$errorInfo[2]};");
    }
    private function throwResultError(\PDOStatement $query)
    {
        $errorInfo = $query->errorInfo();
        if($errorInfo[0] === "00000"){
            return [];
        }
        throw new \Exception("获取结果集错误:错误码:==>{$errorInfo[0]};错误消息:==>{$errorInfo[2]};");
    }
}