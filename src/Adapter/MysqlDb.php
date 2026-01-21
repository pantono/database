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

    public function quoteTable(string $table): string
    {
        return self::ESCAPE_STRING . $table . self::ESCAPE_STRING;
    }

    public function foreignKeyChecks(bool $enabled): void
    {
        $this->query('SET FOREIGN_KEY_CHECKS = ' . ($enabled ? '1' : '0'));
    }
}
