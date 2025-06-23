<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use PDO;
use PDOStatement;
use Pantono\Database\Query\Delete;
use Pantono\Database\Query\Insert;
use Pantono\Database\Query\Select\Select;
use Pantono\Database\Query\Update;

abstract class Db
{
    private bool $connected = false;
    private PDO $pdo;
    private string $dsn;
    private string $user;
    private string $pass;
    /**
     * @var mixed[]|null
     */
    private ?array $options;
    public static int $fetchMode = PDO::FETCH_ASSOC;

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

    public function select(): Select
    {
        return new Select();
    }

    /**
     * @param array<mixed> $parameters
     * @param array<mixed> $where
     */
    public function update(string $table, array $parameters, array $where): int
    {
        $this->checkConnection();
        $query = new Update($table, $parameters, $where);
        $statement = $this->pdo->prepare($query->renderQuery());

        $statement->execute($query->getComputedParams());
        return $statement->rowCount();
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
        $this->checkConnection();
        $query = new Delete($table, $parameters);
        $statement = $this->pdo->prepare($query->renderQuery());

        $statement->execute($query->getComputedParams());
        return $statement->rowCount();
    }

    /**
     * @param array<mixed> $parameters
     */
    public function insert(string $table, array $parameters): int
    {
        $this->checkConnection();
        $query = new Insert($table, $parameters);
        $statement = $this->pdo->prepare($query->renderQuery());
        $params = $query->getParameters();
        foreach ($params as $parameter => $value) {
            $statement->bindValue($parameter, $value);
        }

        $statement->execute();
        return $statement->rowCount();
    }

    /**
     * @param array<mixed> $parameters
     * @return array<mixed>|null
     */
    public function fetchRow(string|Select $select, array $parameters = []): ?array
    {
        $this->checkConnection();
        $statement = $this->prepareQuery($select);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();
        $result = $statement->fetch(self::$fetchMode);
        if ($result === false) {
            return null;
        }
        return $result;
    }

    /**
     * @param array<mixed> $parameters
     * @return array<mixed>
     */
    public function fetchAll(string|Select $select, array $parameters = []): array
    {
        $this->checkConnection();

        $statement = $this->prepareQuery($select);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        return $statement->fetchAll(self::$fetchMode);
    }

    public function query(string $query, array $parameters = []): int
    {
        $this->checkConnection();
        $statement = $this->pdo->prepare($query);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        $statement->execute();

        return $statement->rowCount();
    }

    private function prepareQuery(string|Select $select): PDOStatement
    {
        if ($select instanceof Select) {
            $statement = $this->pdo->prepare($select->renderQuery());
        } else {
            $statement = $this->pdo->prepare($select);
        }
        if ($select instanceof Select) {
            $values = $select->getParameters();
            foreach ($values as $param => $value) {
                $statement->bindValue($param, $values[$param]);
            }
        }
        return $statement;
    }

    /**
     * @param array<mixed> $parameters
     */
    public function runQuery(string $query, array $parameters): mixed
    {
        $this->checkConnection();
        $statement = $this->pdo->prepare($query);
        foreach ($parameters as $key => $value) {
            $statement->bindValue($key, $value);
        }
        return $statement->execute($parameters);
    }

    public function lastInsertId(?string $table = null): false|string|int
    {
        $this->checkConnection();
        return $this->pdo->lastInsertId($table);
    }

    private function checkConnection(): void
    {
        if ($this->connected === false) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->options);
            $this->connected = true;
        }
    }
}
