<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Query\Insert;

abstract class MysqlRepository extends AbstractPdoRepository
{
    public function getLock(string $lockName, int $timeoutSeconds = 5): bool
    {
        $statement = $this->getDb()->getDoctrineConnection()->prepare('SELECT GET_LOCK(:n, :t) as `lock`');
        $statement->bindValue('n', $lockName);
        $statement->bindValue('t', $timeoutSeconds);
        $result = $statement->executeQuery();
        $row = $result->fetchAssociative();
        if (!isset($row['lock'])) {
            return false;
        }
        return $row['lock'] === '1';
    }

    public function releaseLock(string $name): void
    {
        $this->getDb()->runQuery('DO RELEASE_LOCK(:n)', ['n' => $name]);
    }
}
