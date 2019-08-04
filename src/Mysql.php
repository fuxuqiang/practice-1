<?php

namespace src;

class Mysql
{
    public $mysqli;
    
    private $table, $cols, $relation, $cond, $params = [];

    public function __construct(\mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
    }

    /**
     * 执行查询
     */
    public function query($sql, $types = '', array $vars = [])
    {
        if ($stmt = $this->mysqli->prepare($sql)) {
            if ($this->params) {
                $types .= str_repeat('s', count($this->params));
                $vars = array_merge($vars, $this->params);
            }
            $types && $stmt->bind_param($types, ...array_values($vars));
            $stmt->execute() || trigger_error($this->mysqli->error, E_USER_ERROR);
        } else {
            trigger_error($this->mysqli->error, E_USER_ERROR);
        }
        $rst = $stmt->get_result() ?: true;
        $stmt->close();
        return $rst;
    }

    /**
     * 设置表名
     */
    public function from($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * 设置查询列
     */
    public function select(...$cols)
    {
        $this->cols = $cols;
        return $this;
    }

    /**
     * 设置关联查询
     */
    public function with(array $relation)
    {
        $this->relation = $relation;
        return $this;
    }

    /**
     * 添加WHERE条件
     */
    public function where($col, $val = null)
    {
        if (is_array($col)) {
            foreach ($col as $item) {
                $this->cond[] = '`'.$item[0].'` '.$item[1].' ?';
                $this->params[] = $item[2];
            }
        } else {
            $this->cond[] = '`'.$col.'`=?';
            $this->params[] = $val;
        }
        return $this;
    }

    /**
     * 添加 WHERE {COLUMN} IS NULL 条件
     */
    public function whereNull($col)
    {
        $this->cond[] = '`'.$col.'` IS NULL';
        return $this;
    }

    /**
     * 添加 WHERE {COLUMN} IN 条件
     */
    public function whereIn($col, array $vals)
    {
        $this->cond[] = '`'.$col.'` IN ('.rtrim(str_repeat('?,', count($vals)), ',').')';
        $this->params = array_merge($this->params, $vals);
        return $this;
    }

    /**
     * 获取查询结果集
     */
    public function get()
    {
        return $this->query($this->getDqlSql())->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * 数据是否存在
     */
    public function exists()
    {
        return $this->query($this->getDqlSql($this->cols ? null : '`id`').' LIMIT 1')->num_rows;
    }

    /**
     * 分页查询
     */
    public function paginate($page, $perPage)
    {
        $data = $this->query(
            $this->getDqlSql().' LIMIT '.($page - 1) * $perPage.','.$perPage
        )->fetch_all(MYSQLI_ASSOC);
        if ($this->relation && ($table = key($this->relation))
            && $foreignKeysVal = array_column($data, $table.'_id')) {
            $relationData = array_column(
                (new self($this->mysqli))->select(...$this->relation[$table])
                ->from($table)->whereIn('id', $foreignKeysVal)->get(),
                null,
                'id'
            );
            $data = array_map(function ($item) use ($table, $relationData) {
                $item[$table] = $relationData[$item[$table.'_id']];
                return $item;
            }, $data);
        }
        return [
            'data' => $data,
            'total' => $this->query($this->getDqlSql('COUNT(*)'))->fetch_row()[0]
        ];
    }

    /**
     * update,insert,replace方法
     */
    public function __call($name, $args)
    {
        if (!in_array($name, ['update', 'insert', 'replace'])) {
            trigger_error('调用未定义的方法'.self::class.'::'.$name.'()', E_USER_ERROR);
        }
        return $this->query(
            $name.' `'.$this->table.'` SET '.$this->gather(array_keys($args[0]), '`%s`=?')
            .($name == 'update' ? $this->getWhere() : ''),
            str_repeat('s', count($args[0])),
            $args[0]
        );
    }

    /**
     * 获取WHERE子句
     */
    private function getWhere()
    {
        return $this->cond ? ' WHERE '.implode(' AND ', $this->cond) : '';
    }

    /**
     * 格式化数组元素后用,连接成字符串
     */
    private function gather(array $arr, $format)
    {
        return implode(',', array_map(function ($val) use ($format) {
            return sprintf($format, $val);
        }, $arr));
    }

    /**
     * 获取查询sql
     */
    private function getDqlSql($cols = null)
    {
        return sprintf(
            'SELECT %s FROM %s %s',
            $cols ?: ($this->cols ? $this->gather($this->cols, '`%s`') : '*'),
            '`'.$this->table.'`',
            $this->getWhere()
        );
    }
}
