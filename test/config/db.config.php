<?php
$config["docker"] = [
    "host"=>DB_MASTER_HOST,
    "database"=>DB_MASTER_DB_NAME,
    "user"=>DB_MASTER_USER,
    "port"=>DB_MASTER_PORT,
    "password"=>DB_MASTER_PASSWORD,
    "db_prefix"=>DB_MASTER_PREFIX,
];
return $config;