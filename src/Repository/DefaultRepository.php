<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\PgsqlDb;
use Pantono\Database\Adapter\MysqlDb;

abstract class DefaultRepository extends AbstractPdoRepository
{
    public function insertIgnore(string $table, array $data): void
    {
        if (get_class($this->getDb()) === PgsqlDb::class) {
            $query = $this->getDb()->createQueryBuilder()->insert($table)->values($data)->getSql();
            $query .= ' ON CONFLICT DO NOTHING';
            $this->getDb()->getDoctrineConnection()->executeQuery($query);
        }
        if (get_class($this->getDb()) === MysqlDb::class) {
            $query = $this->getDb()->createQueryBuilder()->insert($table)->values($data)->getSql();
            if (str_starts_with($query, 'INSERT INTO')) {
                $query = 'INSERT IGNORE INTO ' . substr($query, 11);
            }
            $this->getDb()->getDoctrineConnection()->executeQuery($query);
            return;
        }
        throw new \RuntimeException('Insert ignore Not implemented in driver');
    }
}
