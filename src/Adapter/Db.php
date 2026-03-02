<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use PDO;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Tools\DsnParser;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;

abstract class Db
{
    private bool $connected = false;
    protected PDO $pdo;
    private string $dsn;
    private string $user;
    private string $pass;
    /**
     * @var mixed[]|null
     */
    private ?array $options;
    public static int $fetchMode = PDO::FETCH_ASSOC;
    private ?Connection $doctrineConnection = null;

    /**
     * @param array<mixed>|null $options
     */
    public function __construct(string $dsn, string $user, string $pass, ?array $options = null)
    {
        if ($options === null) {
            $options = [];
        }
        $this->dsn = $dsn;
        $this->user = $user;
        $this->pass = $pass;
        $this->options = $options;
    }

    public function select(string ...$expressions): QueryBuilder
    {
        return $this->createQueryBuilder()->select(...$expressions);
    }

    /**
     * @param array<mixed> $parameters
     * @param array<mixed> $where
     */
    public function update(string $table, array $parameters, array $where): int
    {
        $qb = $this->createQueryBuilder()
            ->update($table);
        foreach ($parameters as $column => $value) {
            $qb->set($column, ':' . $column)
                ->setParameter(':' . $column, $value);
        }
        $count = 0;
        foreach ($where as $expression => $value) {
            $placeholder = ':column_' . $count;
            $qb->where($expression, ':' . $placeholder)
                ->setParameter(':' . $placeholder, $value);
            $count++;
        }
        return (int)$qb->executeQuery()->rowCount();
    }

    public function getConnection(): \PDO
    {
        $this->checkConnection();
        return $this->pdo;
    }

    /**
     * @param array<mixed> $parameters
     */
    public function delete(string $table, array $parameters): int
    {
        $qb = $this->createQueryBuilder()
            ->delete($table);
        $count = 0;
        foreach ($parameters as $expression => $value) {
            $placeholder = ':column_' . $count;
            $qb->where($expression, ':' . $placeholder)
                ->setParameter(':' . $placeholder, $value);
            $count++;
        }
        return (int)$qb->executeQuery()->rowCount();
    }

    /**
     * @param array<mixed> $parameters
     */
    public function insert(string $table, array $parameters): int
    {
        $qb = $this->createQueryBuilder()
            ->insert($table);
        foreach ($parameters as $column => $value) {
            $qb->set($column, ':' . $column)
                ->setParameter(':' . $column, $value);
        }
        return (int)$qb->executeQuery()->rowCount();
    }

    /**
     * @param array<mixed> $parameters
     * @return array<string,mixed>|null
     */
    public function fetchRow(string|QueryBuilder $select, array $parameters = []): ?array
    {
        if ($select instanceof QueryBuilder) {
            $result = $select->executeQuery();
            $row = $result->fetchAssociative();
            if ($row === false) {
                return null;
            }
            return $row;
        }
        $statement = $this->getDoctrineConnection()->prepare($select);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $result = $statement->executeQuery();
        $row = $result->fetchAssociative();
        if ($row === false) {
            return null;
        }
        return $row;
    }

    /**
     * @param array<mixed> $parameters
     * @return array<mixed>
     */
    public function fetchAll(string|QueryBuilder $select, array $parameters = []): array
    {
        if ($select instanceof QueryBuilder) {
            $result = $select->executeQuery();
            return $result->fetchAllAssociative();
        }
        $this->checkConnection();

        $statement = $this->getDoctrineConnection()->prepare($select);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $result = $statement->executeQuery();
        return $result->fetchAllAssociative();
    }

    public function query(string|QueryBuilder $query, array $parameters = []): int
    {
        if ($query instanceof QueryBuilder) {
            $result = $query->executeQuery();
            return (int)$result->rowCount();
        }
        $this->checkConnection();
        $statement = $this->getDoctrineConnection()->prepare($query);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $result = $statement->executeQuery();

        return (int)$result->rowCount();
    }

    /**
     * @param array<mixed> $parameters
     */
    public function runQuery(string|QueryBuilder $query, array $parameters): mixed
    {
        if ($query instanceof QueryBuilder) {
            $result = $query->executeQuery();
            return $result->fetchAllAssociative();
        }
        $this->checkConnection();
        $statement = $this->pdo->prepare($query);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        return $statement->execute($parameters);
    }


    public function lastInsertId(?string $table = null): false|string|int|null
    {
        return $this->getConnection()->lastInsertId($table);
    }

    public function beginTransaction(): void
    {
        $this->getDoctrineConnection()->beginTransaction();
    }

    public function endTransaction(): void
    {
        $attempts = 0;
        $maxAttempts = 3;
        do {
            try {
                $this->getDoctrineConnection()->commit();
                return;
            } catch (\PDOException $e) {
                $sqlState = $e->errorInfo[0] ?? null;
                $driverCode = (int)($e->errorInfo[1] ?? 0);
                $message = $e->getMessage();

                $isDeadlock = ($sqlState === '40001' && ($driverCode === 1213 || $driverCode === 1205))
                    || stripos($message, 'deadlock') !== false;

                if (!$isDeadlock || ++$attempts >= $maxAttempts) {
                    // Best-effort rollback if still in transaction
                    try {
                        if ($this->getDoctrineConnection()->isTransactionActive()) {
                            $this->getDoctrineConnection()->rollBack();
                        }
                    } catch (\Throwable $_) {
                        // swallow
                    }
                    throw $e;
                }

                // Small backoff before retrying
                usleep(100000 * $attempts); // 100ms, 200ms, 300ms
            }
        } while (true);
    }

    protected function checkConnection(): void
    {
        if ($this->connected === false) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->options);
            $this->connected = true;
        }
    }

    abstract public function foreignKeyChecks(bool $enabled): void;

    public function quoteTable(string $table): string
    {
        return $this->getDoctrineConnection()->quote($table);
    }

    public function quoteColumn(string $table, ?string $column = null): string
    {
        $con = $this->getDoctrineConnection();
        if ($column === null) {
            return $con->quoteSingleIdentifier($table);
        }
        return $con->quoteSingleIdentifier($table) . '.' . $con->quoteSingleIdentifier($column);
    }

    public function getDoctrineConnection(): Connection
    {
        if (!$this->doctrineConnection) {
            $params = (new DsnParser())->parse($this->dsn);
            $this->doctrineConnection = DriverManager::getConnection($params);
        }
        return $this->doctrineConnection;
    }

    public function createQueryBuilder(): QueryBuilder
    {
        return $this->getDoctrineConnection()->createQueryBuilder();
    }
}
