<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\Db;
use Pantono\Database\Query\Select\Select;

abstract class AbstractPdoRepository
{
    private Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function getDb(): Db
    {
        return $this->db;
    }

    /**
     * @return array<mixed>|null
     */
    public function selectSingleRow(string $table, string $column, string|int $value = null): ?array
    {
        if ($value === null) {
            return null;
        }
        $select = $this->getDb()->select()->from($table)->where($column . '=?', $value);
        $row = $this->getDb()->fetchRow($select);

        return empty($row) ? null : $row;
    }

    /**
     * @return array<mixed>
     */
    public function selectSingleRowFromQuery(Select $query): ?array
    {
        $row = $this->getDb()->fetchRow($query);

        return empty($row) ? null : $row;
    }

    /**
     * @return array<mixed>
     */
    public function selectAll(string $table, ?string $order = null): array
    {
        $select = $this->getDb()->select()->from($table);
        if ($order !== null) {
            $select->order($order);
        }

        return $this->getDb()->fetchAll($select);
    }

    /**
     * @param array<string,string|int|null> $fields
     * @return array<mixed>
     */
    public function selectRowsByValues(string $table, array $fields, ?string $order = null, ?int $limit = null): array
    {
        $select = $this->getDb()->select()->from($table);
        foreach ($fields as $key => $field) {
            if ($field === null) {
                $select->where($key . ' IS NULL');
            } else {
                $select->where($key . ' =?', $field);
            }
        }
        if ($order !== null) {
            $select->order($order);
        }
        if ($limit !== null) {
            $select->limit($limit);
        }

        return $this->getDb()->fetchAll($select);
    }

    /**
     * @param array<string,string|int|null> $fields
     * @return array<mixed>|null
     */
    public function selectRowByValues(string $table, array $fields, ?string $order = null, ?int $limit = null): ?array
    {
        $select = $this->getDb()->select()->from($table);
        foreach ($fields as $key => $field) {
            $select->where($key . ' =?', $field);
        }
        if ($order !== null) {
            $select->order($order);
        }
        if ($limit !== null) {
            $select->limit($limit);
        }
        $row = $this->getDb()->fetchRow($select);
        return !empty($row) ? $row : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function insertOrUpdate(string $table, string $idColumn, mixed $id, array $data): ?int
    {
        if ($id === null) {
            $this->getDb()->insert(
                $table,
                $data
            );

            return intval($this->getDb()->lastInsertId($table));
        }

        $this->getDb()->update($table, $data, [$idColumn . '=?' => $id]);
        return null;
    }

    public function insertOrUpdateCheck(string $table, string $idColumn, mixed $id, array $data): ?int
    {
        if ($id === null) {
            return $this->insertOrUpdate($table, $idColumn, $id, $data);
        }
        $current = $this->selectSingleRow($table, $idColumn, $id);
        if (empty($current)) {
            $this->getDb()->insert($table, $data);
            return $id;
        }
        $this->getDb()->update($table, $data, [$idColumn . '=?' => $id]);
        return null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function insert(string $table, array $data): int
    {
        $this->getDb()->insert(
            $table,
            $data
        );

        return intval($this->getDb()->lastInsertId());
    }

    /**
     * @param array<string,string|int|null> $parameters
     */
    public function selectCount(string $table, array $parameters, ?string $groupBy = null): int
    {
        $select = $this->getDb()->select()->from($table, ['COUNT(1) as cnt']);
        foreach ($parameters as $name => $value) {
            $select->where($name . '=?', $value);
        }

        if ($groupBy !== null) {
            $select->group($groupBy);
        }

        $row = $this->getDb()->fetchRow($select);

        return $row ? intval($row['cnt']) : 0;
    }

    /**
     * @param array<string>|string $columns
     */
    public function select(string $table, array|string $columns = '*'): Select
    {
        return $this->getDb()->select()->from($table, $columns);
    }

    /**
     * @return array<mixed>
     */
    public function fetchAll(Select $select): array
    {
        return $this->getDb()->fetchAll($select);
    }

    public function getCount(Select $select): int
    {
        $select = clone($select);
        $select->reset('order');
        $countSelect = $this->getDb()->select()->from(['c' => $select], ['COUNT(1) as cnt']);

        $countRow = $this->getDb()->fetchRow($countSelect);
        if (isset($countRow['cnt'])) {
            return intval($countRow['cnt']);
        }
        return 0;
    }

    /**
     * @param array<string,mixed> $params
     */
    public function deleteRetry(string $table, array $params, int $tries = 0): void
    {
        try {
            $this->getDb()->delete($table, $params);
        } catch (\PDOException $e) {
            if ($tries > 5) {
                throw new \PDOException($e->getMessage(), $e->getCode(), $e->getPrevious());
            }
            $this->deleteRetry($table, $params, $tries + 1);
        }
    }
}
