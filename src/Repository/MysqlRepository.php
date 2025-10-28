<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\MysqlDb;

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

    /**
     * @param array<string,mixed> $data
     */
    public function insertIgnore(string $table, array $data): void
    {
        $query = 'INSERT IGNORE into ' . $table . ' SET ';
        $values = [];
        $parameters = [];
        $index = 0;
        foreach ($data as $key => $value) {
            $named = ':param_' . $index;
            $values[] = '`' . $key . '` = ' . $named;
            $parameters[$named] = $value;
            $index++;
        }
        $query .= implode(', ', $values);
        $this->getDb()->runQuery($query, $parameters);
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
