<?php
namespace app\auth;

class Admin implements \src\Auth
{
    public static function handle($token)
    {
        if ($admin = mysql()->query(
                'SELECT `id`,`role_id` FROM `admin` WHERE `api_token`=? AND `token_expires`>NOW()',
                's',
                [$token]
            )->fetch_object(\src\Model::class, ['admin'])) {
            if (($route = mysql()->query(
                    'SELECT `id` FROM `route` WHERE `method`=? AND `uri`=?',
                    'ss',
                    [$_SERVER['REQUEST_METHOD'], ltrim($_SERVER['PATH_INFO'], '/')]
                )->fetch_row()) && ! mysql('role_route')->where([
                        ['role_id', '=', $admin->role_id], ['route_id', '=', $route[0]]]
                    )->select('route_id')->exists()) {
                return false;
            }
            return $admin;
        }
        return false;
    }
}