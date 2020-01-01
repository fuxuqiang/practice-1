<?php

/**
 * 记录日志
 */
function logError($content)
{
    file_put_contents(
        __DIR__ . '/runtime/error.log',
        '[' . timestamp() . "]\n$content\n",
        FILE_APPEND | LOCK_EX
    );
}

/**
 * 获取时间
 */
function timestamp()
{
    return date('Y-m-d H:i:s');
}

/**
 * 启动会话
 */
function sessionStart()
{
    static $started;
    return $started || $started = session_start();
}
