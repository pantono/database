<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Query\Insert;

abstract class MysqlRepository extends AbstractPdoRepository
{
    public function getDb(): MysqlDb
    {
        /**
         * @var MysqlDb $db
         */
        $db = $this->db;
        return $db;
    }

    public function insertIgnore(string $table, array $data): void
    {
        $insert = new Insert($table, $data);
        $query = $insert->renderQuery();
        if (str_starts_with($query, 'INSERT INTO')) {
            $query = 'INSERT IGNORE INTO ' . substr($query, 11);
        }
        $this->getDb()->runQuery($query, $insert->getParameters());
    }

    public function getLock(string $lockName, int $timeoutSeconds = 5): bool
    {
        $statement = $this->getDb()->getConnection()->prepare('SELECT GET_LOCK(:n, :t) as `lock`');
        $statement->execute(['n' => $lockName, 't' => $timeoutSeconds]);
        return $statement->fetchColumn() === '1';
    }

    public function releaseLock(string $name): void
    {
        $this->getDb()->runQuery('DO RELEASE_LOCK(:n)', ['n' => $name]);
    }
}
