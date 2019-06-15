<?php
/**
 * 配置加载与使用
 */
namespace Anker\cls;

class Config
{
    private static $configPath = APP_PATH."config/";
    private static $config;

    /**
     * 加载配置
     * @param $configFileName string 配置文件名称
     * @param string $reName string 重定向
     * @return mixed
     * @throws \Exception
     */
    public static  function load($configFileName, $reName = "")
    {
        $reName = empty($reName) ? strval($configFileName) : strval($reName);

        if (isset(static::$config[$reName])) {
            return static::$config[$reName];
        }

        $fullPath = static::$configPath.$configFileName.".php";
        if (file_exists($fullPath)) {
            static::$config[$reName] = include $fullPath;
            return static::$config[$reName];
        } else {
            throw new \Exception("config file not exists!");
        }
    }

    /**
     * 获得配置内元素
     * @param $key
     * @param string $itemName
     * @return bool|null
     */
    public static function item($key, $itemName="")
    {
        if (empty($itemName)) {
            return empty(static::$config[$key]);
        }
        if (isset(static::$config[$key][$itemName])) {
            return static::$config[$key][$itemName];
        } else {
            return Null;
        }
    }
}