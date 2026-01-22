<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Query\Select\DriverSpecific\MysqlSelect;

class MysqlDb extends Db
{
    public function quoteTable(string $table): string
    {
        return '`' . $table . '`';
    }

    public function quoteColumn(string $table, ?string $column = null): string
    {
        if ($column === null) {
            return $this->quoteTable($table);
        }
        if ($column === '*') {
            return '`' . $table . '`.*';
        }
        if (preg_match('/(.*)\s+as\s+(.*)/i', $column, $matches)) {
            return '`' . $table . '`.`' . trim($matches[1]) . '` AS ' . trim($matches[2]);
        }
        return '`' . $table . '`.`' . $column . '`';
    }

    public function select(): MysqlSelect
    {
        return new MysqlSelect($this);
    }

    public function foreignKeyChecks(bool $enabled): void
    {
        $this->query('SET FOREIGN_KEY_CHECKS = ' . ($enabled ? '1' : '0'));
    }

    public function lastInsertId(?string $table = null, ?string $primaryKey = null): false|string|int|null
    {
        if ($table !== null) {
            return $this->pdo->lastInsertId($this->quoteTable($table));
        }
        return $this->pdo->lastInsertId();
    }
}
