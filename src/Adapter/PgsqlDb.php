<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\DriverSpecific\PgsqlSelect;

class PgsqlDb extends Db
{
    public const string ESCAPE_STRING = '"';

    public function select(): PgsqlSelect
    {
        return new PgsqlSelect();
    }

    public function quoteTable(string $table): string
    {
        return self::ESCAPE_STRING . $table . self::ESCAPE_STRING;
    }
}
