<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\PgsqlDb;
use Pantono\Database\Query\Insert;
use Pantono\Database\Adapter\MysqlDb;

abstract class DefaultRepository extends AbstractPdoRepository
{
    public function insertIgnore(string $table, array $data): void
    {
        if (get_class($this->getDb()) === PgsqlDb::class) {
            $insert = new Insert($table, $data, PgsqlDb::class);
            $query = $insert->renderQuery();
            $query .= ' ON CONFLICT DO NOTHING';
            $this->getDb()->runQuery($query, $insert->getParameters());
            return;
        }
        if (get_class($this->getDb()) === MysqlDb::class) {
            $insert = new Insert($table, $data, MysqlDb::class);
            $query = $insert->renderQuery();
            if (str_starts_with($query, 'INSERT INTO')) {
                $query = 'INSERT IGNORE INTO ' . substr($query, 11);
            }
            $this->getDb()->runQuery($query, $insert->getParameters());
            return;
        }
        throw new \RuntimeException('Insert ignore Not implemented in driver');
    }
}
