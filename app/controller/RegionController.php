<?php
namespace app\controller;

class RegionController
{
    public function list($p_code = 0)
    {
        $factor = $p_code > 99999 ? 1000 : (in_array($p_code, [4419, 4420]) ? 100000 : 100);
        return [
            'data' => \src\Mysql::select(
                    'SELECT * FROM `region` WHERE `code` BETWEEN ? AND ?',
                    [$p_code * $factor, ($p_code + 1) * $factor]
                )
        ];
    }
}
