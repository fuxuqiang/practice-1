<?php
namespace controller;

class RouteController
{
    public function index(int $page = 1, int $perPage = 5)
    {
        return [
            'data' => mysql('route')->paginate($page, $perPage)
        ];
    }
}