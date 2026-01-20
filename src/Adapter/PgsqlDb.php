<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\DriverSpecific\PgsqlSelect;

class PgsqlDb extends Db
{
    public function select(): PgsqlSelect
    {
        return new PgsqlSelect();
    }
}
