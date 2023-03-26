<?php

namespace Src;

use Fuxuqiang\Framework\{Container, ResponseException, TestResponse};

/**
 * @method TestResponse get($uri, $param)
 */
class TestCase extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Http
     */
    protected static $http;

    /**
     * @var string
     */
    protected string $token = '', $ip = '127.0.0.1';

    /**
     * 设置测试基境
     */
    public static function setUpBeforeClass(): void
    {
        if (!self::$http) {
            self::$http = new Http(require __DIR__ . '/app.php');
        }
    }

    /**
     * 调用测试请求
     * @throws \ReflectionException|ResponseException
     */
    protected function request($requestMethod, $uri, $params = [], $token = null): TestResponse
    {
        $token = $token ?: $this->token;
        [$concrete, $method, $args] = self::$http->handle(
            [
                'REQUEST_METHOD' => $requestMethod,
                'REQUEST_URI' => $uri,
                'HTTP_AUTHORIZATION' => $token ? 'Bearer ' . $token : null,
                'REMOTE_ADDR' => $this->ip,
                'REQUEST_TIME' => time(),
            ],
            $params
        );
        if (!$controller = Container::get($concrete)) {
            $controller = Container::newInstance($concrete);
            Container::instance($concrete, $controller);
        }
        $response = $controller->$method(...$args);
        $status = 200;
        $this->token = null;
        return new TestResponse($response, $status);
    }

    /**
     * 使用指定ip地址
     */
    public function withIP($ip): static
    {
        $this->ip = $ip;
        return $this;
    }

    /**
     * 根据方法名调用request方法
     * @throws \ReflectionException|ResponseException
     */
    public function __call($name, $args)
    {
        return $this->request(strtoupper($name), ...$args);
    }

    /**
     * 断言数据库中数据是否存在
     */
    public function assertDatabaseHas($table, $data)
    {
        $this->assertTrue(Mysql::table($table)->where($data)->count() > 0);
    }
}
