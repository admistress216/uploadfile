<?php
require_once '../vendor/autoload.php';
require_once './config/const.php';

use Anker\cls\Config;

Config::load('db.config', 'my_db');  //加载配置
var_dump(Config::item('my_db', 'docker'));  //获取加载配置的元素