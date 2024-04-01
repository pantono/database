<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Query\Select\DriverSpecific\MssqlSelect;

class MssqlDb extends Db
{
    public function select(): Select
    {
        return new MssqlSelect();
    }
}
