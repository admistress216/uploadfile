<?php
require_once '../vendor/autoload.php';
require_once './config/const.php';

use Anker\db\DB;

$db = new DB('docker');  //实例化数据库
$db->select("*");
$db->from("type");
$db->where(['id>'=>2]);
$db->order_by(["id"=>"desc"]);
$db->limit(10);
$query = $db->get();
$result = $query->all();
var_dump($result);

//$db->where(['job_id'=>$job_id]);  //删除
//return $db->delete($this->table);

//$db->insert($this->table,$data);  //插入

//return $db->update($this->table,['status'=>$status],$where);  //更新
