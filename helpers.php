<?php

/**
 * 获取请求参数
 */
function input(array $names = [])
{
    static $input;
    if (!$input) {
        if (! $input = $_REQUEST) {
            if ($_SERVER['CONTENT_TYPE'] == 'application/json') {
                $input = json_decode(file_get_contents('php://input'), true);
            } else {
                parse_str(file_get_contents('php://input'), $input);
            }
        }
    }
    return $names ? arrayOnly($input, $names) : $input;
}

/**
 * 获取Redis实例
 */
function redis()
{
    static $redis;
    if (!$redis) {
        $redis = new Redis;
        $config = config('redis');
        $redis->connect($config['host']);
        // $redis->auth($config['pwd']);
    }
    return $redis;
}

/**
 * 响应状态码并结束执行
 */
function response($code, $msg = null)
{
    http_response_code($code);
    die($msg ?: '');
}

/**
 * 设置/获取 认证的用户
 */
function auth(\src\Model $user = null)
{
    static $boundUser;
    $user && $boundUser = $user;
    return $boundUser;
}

/**
 * 记录日志
 */
function logError($content, $die = true)
{
    file_put_contents(
        __DIR__.'/log/error.log', '['.timestamp()."]\n".$content."\n", FILE_APPEND | LOCK_EX
    );
    $die && response(500);
}

/**
 * 获取配置
 */
function config($name)
{
    static $config;
    $config || $config = require __DIR__.'/app/config.php';
    return $config[$name] ?? null;
}

/**
 * 获取Mysql实例
 */
function mysql($table = null)
{
    static $mysqli;
    if (!$mysqli) {
        $config = config('mysql');
        $mysqli = new mysqli($config['host'], $config['user'], $config['pwd'], $config['name']);
    }
    $mysql = new \src\Mysql($mysqli);
    return $table ? $mysql->from($table) : $mysql;
}

/**
 * 获取时间
 */
function timestamp($time = null)
{
    return date('Y-m-d H:i:s', $time ? strtotime($time) : time());
}

/**
 * 获取数组中指定键的子集
 */
function arrayOnly(array $arr, array $keys)
{
    return array_intersect_key($arr, array_flip($keys));
}

/**
 * 获取分页参数
 */
function pageParams()
{
    $input = input(['page', 'per_page']);
    return [$input['page'] ?? 1, $input['per_page'] ?? 5];
}

/**
 * 验证请求参数
 */
function validate(array $rules)
{
    $validator = new \src\Validator(input(), function ($val, $table, $col) {
        return mysql($table)->exists($col, $val);
    });
    ($rst = $validator->handle($rules)) && response(400, '无效的'.$rst);
}