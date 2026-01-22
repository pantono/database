<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Query\Select\DriverSpecific\MssqlSelect;

class MssqlDb extends Db
{
    public function select(): MssqlSelect
    {
        return new MssqlSelect($this);
    }

    public function quoteTable(string $table): string
    {
        return '[' . $table . ']';
    }

    public function quoteColumn(string $table, ?string $column = null): string
    {
        if ($column === null) {
            return $this->quoteTable($table);
        }
        if ($column === '*') {
            return '[' . $table . '].*';
        }
        if (preg_match('/(.*)\s+as\s+(.*)/i', $column, $matches)) {
            return '[' . $table . '].[' . trim($matches[1]) . '] AS ' . trim($matches[2]);
        }
        return '[' . $table . '].[' . $column . ']';
    }

    public function foreignKeyChecks(bool $enabled): void
    {
        if ($enabled) {
            $this->query("EXEC sp_msforeachtable 'ALTER TABLE ? WITH CHECK CHECK CONSTRAINT ALL'");
            return;
        }
        $this->query("EXEC sp_msforeachtable 'ALTER TABLE ? NOCHECK CONSTRAINT ALL'");
    }

    public function lastInsertId(?string $table = null, ?string $primaryKey = null): false|string|int|null
    {
        $statement = $this->pdo->query('SELECT SCOPE_IDENTITY()');
        if (!$statement) {
            return null;
        }
        return $statement->fetchColumn();
    }
}
