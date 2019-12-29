<?php

namespace src;

class Mysql
{
    private static $mysqli;

    public static function __callStatic($name, $args)
    {
        if (!self::$mysqli) {
            $config = config('mysql');
            self::$mysqli = new \mysqli($config['host'], $config['user'], $config['pwd'], $config['db']);
        }
        $mysql = new \vendor\Mysql(self::$mysqli);
        return $mysql->$name(...$args);
    }
}