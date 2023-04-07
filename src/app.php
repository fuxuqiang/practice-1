<?php

require __DIR__ . '/../vendor/autoload.php';

date_default_timezone_set(env('timezone'));

if (env('debug')) {
    ini_set('display_errors', 'On');
    error_reporting(-1);
}

// 报错处理
set_error_handler(
    /**
     * @throws ErrorException
     */
    function ($no, $str, $file, $line) {
        if (error_reporting() & $no) {
            throw new ErrorException($str, 0, $no, $file, $line);
        }
    }
);
register_shutdown_function(function () {
    if ($error = error_get_last()) {
        logError(new ErrorException($error['message'], 0, 1, $error['file'], $error['line']));
    }
});

// 设置模型的数据库连接
\Fuxuqiang\Framework\Model\Model::setConnector(\Src\Mysql::getInstance());

return runtimePath('route.php');
