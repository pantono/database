<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\Db;
use Pantono\Database\Query\Select\Select;
use Pantono\Contracts\Application\Interfaces\SavableInterface;
use Pantono\Utilities\StringUtilities;
use Pantono\Utilities\ReflectionUtilities;
use Pantono\Contracts\Attributes\DatabaseTable;

abstract class AbstractPdoRepository
{
    protected Db $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function getDb(): Db
    {
        return $this->db;
    }

    public function quoteColumn(string $table, string $column): string
    {
        return $this->db->quoteColumn($table, $column);
    }

    public function concat(array $parts, ?string $as = null): string
    {
        $concat = 'CONCAT(';
        $quotedParts = [];
        foreach ($parts as $part) {
            if (str_contains($part, '.')) {
                [$table, $column] = explode('.', $part);
                $quotedParts[] = $this->quoteColumn($table, $column);
                continue;
            }
            $quotedParts[] = $this->quoteTable($part);
        }
        $concat .= implode(',', $quotedParts) . ')';
        if ($as) {
            return $concat . ' as ' . $this->quoteTable($as);
        }
        return $concat;
    }


    public function quoteTable(string $table): string
    {
        return $this->db->quoteTable($table);
    }

    /**
     * @return array<mixed>|null
     */
    public function selectSingleRow(string $table, string $column, string|int|null $value = null): ?array
    {
        if ($value === null) {
            return null;
        }
        $select = $this->getDb()->select()->from($table)->where($column . '=?', $value);
        $row = $this->getDb()->fetchRow($select);

        return empty($row) ? null : $row;
    }

    /**
     * @return array<mixed>|null
     */
    public function selectSingleRowLock(string $table, string $column, string|int|null $value = null): ?array
    {
        if ($value === null) {
            return null;
        }
        $select = $this->getDb()->select()->from($table)->where($column . '=?', $value);
        $select .= ' FOR UPDATE';
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

            return intval($this->getDb()->lastInsertId($table, $idColumn));
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
            $data[$idColumn] = $id;
            $this->getDb()->insert($table, $data);
            return $id;
        }
        $this->getDb()->update($table, $data, [$idColumn . '=?' => $id]);
        return null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function insert(string $table, array $data, string $idColumn = 'id'): int
    {
        $this->getDb()->insert(
            $table,
            $data
        );

        return intval($this->getDb()->lastInsertId($table, $idColumn));
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

    public function beginTransaction(): void
    {
        $this->getDb()->beginTransaction();
    }

    public function endTransaction(): void
    {
        $this->getDb()->endTransaction();
    }

    public function saveModel(SavableInterface $model, ?string $table = null, ?string $idColumn = null): void
    {
        [$table, $idColumn] = $this->getModelTables($model::class, $table, $idColumn);
        $getter = 'get' . lcfirst(StringUtilities::camelCase($idColumn));
        $setter = 'set' . lcfirst(StringUtilities::camelCase($idColumn));
        if (!method_exists($model::class, $getter)) {
            throw new \RuntimeException('Unable to determine getter method for ' . get_class($model));
        }
        if (!method_exists($model::class, $setter)) {
            throw new \RuntimeException('Unable to determine setter method for ' . get_class($model));
        }
        $id = $this->insertOrUpdateCheck($table, $idColumn, $model->$getter(), $model->getAllData());
        if ($id) {
            $model->$setter($id);
        }
    }

    /**
     * @param class-string $model
     * @param array<int|string> $ids
     * @param string|null $table
     * @param string|null $idColumn
     * @return array<int, mixed>
     */
    public function lookupRecords(string $model, array $ids = [], ?string $table = null, ?string $idColumn = null): array
    {
        [$table, $idColumn] = $this->getModelTables($model, $table, $idColumn);
        $select = $this->getDb()->select()->from($table)->where($idColumn . ' IN (?)', $ids);
        return $this->getDb()->fetchAll($select);
    }

    /**
     * @param class-string $model
     * @return mixed[]|null
     */
    public function lookupRecord(string $model, int|string $id, ?string $table = null, ?string $idColumn = null): ?array
    {
        [$table, $idColumn] = $this->getModelTables($model, $table, $idColumn);
        return $this->selectSingleRow($table, $idColumn, $id);
    }

    /**
     * @param class-string $model
     * @return array<int,string>
     */
    private function getModelTables(string $model, ?string $table = null, ?string $idColumn = null): array
    {
        $reflection = new \ReflectionClass($model);
        $interfaces = $reflection->getInterfaceNames();
        if (!in_array(SavableInterface::class, $interfaces)) {
            throw new \RuntimeException('Model ' . $model . ' does not implement ' . SavableInterface::class);
        }
        $classAttributes = ReflectionUtilities::getClassAttributes($model);
        foreach ($classAttributes as $attribute) {
            if ($attribute->getName() === DatabaseTable::class) {
                $instance = $attribute->newInstance();
                /** @var DatabaseTable $instance */
                if (!$table) {
                    $table = $instance->table;
                }
                if ($instance->idColumn) {
                    if (!$idColumn) {
                        $idColumn = $instance->idColumn;
                    }
                }
            }
        }

        if (!$table || !$idColumn) {
            throw new \RuntimeException('Unable to determine table and id column for model ' . $model);
        }
        return [$table, $idColumn];
    }

    protected function appendTablePrefix(string $input): string
    {
        $prefix = isset($_ENV['TABLE_PREFIX']) ? $_ENV['TABLE_PREFIX'] . '_' : '';
        return $prefix . $input;
    }

    /**
     * @param array<string,mixed> $data
     */
    abstract public function insertIgnore(string $table, array $data): void;
}
