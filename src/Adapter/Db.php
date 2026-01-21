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

    private function getDriverClass(): string
    {
        if (str_starts_with($this->dsn, 'pgsql')) {
            return PgsqlDb::class;
        }
        if (str_starts_with($this->dsn, 'mssql')) {
            return MssqlDb::class;
        }
        if (str_starts_with($this->dsn, 'mysql')) {
            return MysqlDb::class;
        }
        throw new \RuntimeException('Invalid database connetion type');
    }

    public function select(): Select
    {
        return new Select($this->getDriverClass());
    }

    /**
     * @param array<mixed> $parameters
     * @param array<mixed> $where
     */
    public function update(string $table, array $parameters, array $where): int
    {
        $this->checkConnection();
        $query = new Update($table, $parameters, $where, $this->getDriverClass());
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
        $query = new Delete($table, $parameters, $this->getDriverClass());
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
        $query = new Insert($table, $parameters, $this->getDriverClass());
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

    public function lastInsertId(?string $table = null, ?string $primaryKey = null): false|string|int|null
    {
        $this->checkConnection();
        $driver = $this->getDriverClass();
        if ($driver === PgsqlDb::class && $table !== null) {
            $sequenceName = $table . '_' . ($primaryKey ?? 'id') . '_seq';
            $check = $this->pdo->prepare("SELECT 1 FROM pg_class WHERE relkind = 'S' AND relname = ?");
            $check->execute([$sequenceName]);
            if ($check->fetchColumn()) {
                return $this->pdo->lastInsertId($sequenceName);
            }
            return null;
        }
        if ($driver === MssqlDb::class) {
            $statement = $this->pdo->query('SELECT SCOPE_IDENTITY()');
            if (!$statement) {
                return false;
            }
            return $statement->fetchColumn();
        }
        if ($table) {
            return $this->pdo->lastInsertId($this->quoteTable($table));
        }

        return $this->pdo->lastInsertId();
    }

    public function beginTransaction(): void
    {
        $this->checkConnection();
        $this->pdo->beginTransaction();
    }

    public function endTransaction(): void
    {
        $this->checkConnection();
        $attempts = 0;
        $maxAttempts = 3;
        do {
            try {
                $this->pdo->commit();
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
                        if ($this->pdo->inTransaction()) {
                            $this->pdo->rollBack();
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

    private function checkConnection(): void
    {
        if ($this->connected === false) {
            $this->pdo = new PDO($this->dsn, $this->user, $this->pass, $this->options);
            $this->connected = true;
        }
    }

    abstract public function quoteTable(string $table): string;

    abstract public function foreignKeyChecks(bool $enabled): void;
}
