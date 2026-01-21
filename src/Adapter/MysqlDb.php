<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Query\Select\DriverSpecific\MysqlSelect;

class MysqlDb extends Db
{
    public const string ESCAPE_STRING = '`';

    public function select(): MysqlSelect
    {
        return new MysqlSelect(MysqlDb::class);
    }
}
