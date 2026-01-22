<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\DriverSpecific\PgsqlSelect;

class PgsqlDb extends Db
{
    public function quoteTable(string $table): string
    {
        return '"' . $table . '"';
    }

    public function quoteColumn(string $table, ?string $column = null): string
    {
        if ($column === null) {
            return $this->quoteTable($table);
        }
        if ($column === '*') {
            return '"' . $table . '".*';
        }
        if (preg_match('/(.*)\s+as\s+(.*)/i', $column, $matches)) {
            return '"' . $table . '"."' . trim($matches[1]) . '" AS ' . trim($matches[2]);
        }
        return '"' . $table . '"."' . $column . '"';
    }

    public function foreignKeyChecks(bool $enabled): void
    {
        if ($enabled === false) {
            $this->query('SET session_replication_role = replica;');
            return;
        }
        $this->query('SET session_replication_role = DEFAULT;');
    }

    public function lastInsertId(?string $table = null, ?string $primaryKey = null): false|string|int|null
    {
        if ($table === null) {
            return null;
        }
        $sequenceName = $table . '_' . ($primaryKey ?? 'id') . '_seq';
        $check = $this->pdo->prepare("SELECT 1 FROM pg_class WHERE relkind = 'S' AND relname = ?");
        $check->execute([$sequenceName]);
        if ($check->fetchColumn()) {
            return $this->pdo->lastInsertId($sequenceName);
        }
        return null;
    }
}
