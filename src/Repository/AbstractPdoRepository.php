<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\Db;
use Pantono\Contracts\Application\Interfaces\SavableInterface;
use Pantono\Utilities\StringUtilities;
use Pantono\Utilities\Model\PantonoReflectionModel;
use Doctrine\DBAL\Query\QueryBuilder;
use Pantono\Contracts\Filter\PageableInterface;
use Doctrine\DBAL\ArrayParameterType;

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
        return $this->getDb()->quoteTable($table);
    }

    /**
     * @return array<mixed>|null
     */
    public function selectSingleRow(string $table, string $column, string|int|null $value = null): ?array
    {
        if ($value === null) {
            return null;
        }
        $qb = $this->getDb()->createQueryBuilder();
        $qb->select('t.*')->from($this->quoteTable($table), 't')->where('t.' . $column . '=:value');
        $qb->setParameter('value', $value);
        $row = $this->getDb()->fetchRow($qb);

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
        $qb = $this->getDb()->createQueryBuilder()->forUpdate();
        $qb->select('t.*')->from($this->quoteTable($table), 't')->where('t.' . $column . '=:value');
        $qb->setParameter('value', $value);
        $sql = $qb->getSQL();
        $row = $this->getDb()->fetchRow($sql, $qb->getParameters());

        return empty($row) ? null : $row;
    }

    /**
     * @return array<mixed>
     */
    public function selectAll(string $table, ?string $order = null): array
    {
        $qb = $this->getDb()->createQueryBuilder();
        $qb->select('t.*')->from($this->quoteTable($table), 't');
        if ($order !== null) {
            $qb->addOrderBy($order);
        }

        return $this->getDb()->fetchAll($qb);
    }

    /**
     * @param array<string,string|int|null> $fields
     * @return array<mixed>
     */
    public function selectRowsByValues(string $table, array $fields, ?string $order = null, ?int $limit = null): array
    {
        $qb = $this->getDb()->createQueryBuilder();
        $qb->select('t.*')->from($this->quoteTable($table), 't');
        $index = 0;
        foreach ($fields as $key => $field) {
            if ($field === null) {
                $qb->where($key . ' IS NULL');
            } else {
                $key = 'value_' . $index;
                $qb->where($key . ' =:' . $key)
                    ->setParameter($key, $field);
            }
            $index++;
        }
        if ($order !== null) {
            $qb->addOrderBy($order);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $this->getDb()->fetchAll($qb);
    }

    /**
     * @param array<string,string|int|null> $fields
     * @return array<mixed>|null
     */
    public function selectRowByValues(string $table, array $fields, ?string $order = null, ?int $limit = null): ?array
    {
        $qb = $this->getDb()->createQueryBuilder();
        $qb->select('t.*')->from($this->quoteTable($table), 't');
        $index = 0;
        foreach ($fields as $key => $field) {
            if ($field === null) {
                $qb->where($key . ' IS NULL');
            } else {
                $key = 'value_' . $index;
                $qb->where($key . ' =?', $key)
                    ->setParameter($key, $field);
            }
            $index++;
        }
        if ($order !== null) {
            $qb->addOrderBy($order);
        }
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        $row = $this->getDb()->fetchRow($qb);
        return !empty($row) ? $row : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function insertOrUpdate(string $table, string $idColumn, mixed $id, array $data): ?int
    {
        if ($id === null) {
            $this->getDb()->insert(
                $this->quoteTable($table),
                $data
            );

            return intval($this->getDb()->lastInsertId());
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
            $this->getDb()->insert($this->quoteTable($table), $data);
            return $id;
        }
        $this->getDb()->update($this->quoteTable($table), $data, [$idColumn . '=?' => $id]);
        return null;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function insert(string $table, array $data): int
    {
        $this->getDb()->insert(
            $this->quoteTable($table),
            $data
        );

        return intval($this->getDb()->lastInsertId());
    }

    /**
     * @param array<string,string|int|null> $parameters
     */
    public function selectCount(string $table, array $parameters, ?string $groupBy = null): int
    {
        $qb = $this->getDb()->createQueryBuilder();
        $qb->select('COUNT(1) as cnt')->from($this->quoteTable($table));
        $index = 0;
        foreach ($parameters as $name => $value) {
            $key = ':value_' . $index;
            $qb->where($name . '= ' . $key)
                ->setParameter($key, $value);
            $index++;
        }

        if ($groupBy !== null) {
            $qb->groupBy($groupBy);
        }

        $row = $this->getDb()->fetchRow($qb);

        return $row ? intval($row['cnt']) : 0;
    }

    /**
     * @param array<string>|string $columns
     */
    public function select(string $table, array|string $columns = '*'): QueryBuilder
    {
        if (is_string($columns)) {
            return $this->getDb()->select($columns)->from($this->quoteTable($table));
        }
        return $this->getDb()->select(...$columns)->from($this->quoteTable($table));
    }

    public function applyLimit(QueryBuilder $queryBuilder, PageableInterface $filter): QueryBuilder
    {
        $queryBuilder->setMaxResults($filter->getPerPage());
        $queryBuilder->setFirstResult(($filter->getPage() - 1) * $filter->getPerPage());
        return $queryBuilder;
    }

    public function applyCountAndLimit(QueryBuilder $queryBuilder, PageableInterface $filter): QueryBuilder
    {
        $filter->setTotalResults($this->getCount($queryBuilder));
        return $this->applyLimit($queryBuilder, $filter);
    }

    public function getCount(QueryBuilder $queryBuilder): int
    {
        $qb = $this->getDb()->createQueryBuilder();
        $qb->with('count_query', $queryBuilder);
        $qb->select('COUNT(1) as cnt')->from('count_query');

        $row = $qb->fetchAssociative();
        return $row ? intval($row['cnt']) : 0;
    }

    /**
     * @param array<string,mixed> $params
     */
    public function deleteRetry(string $table, array $params, int $tries = 0): void
    {
        try {
            $this->getDb()->delete($this->quoteTable($table), $params);
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
        $qb = $this->getDb()->createQueryBuilder();
        $qb->select('*')->from($table)
            ->where($idColumn . ' IN (:ids)')
            ->setParameter(':ids', $ids, ArrayParameterType::INTEGER);

        return $this->getDb()->fetchAll($qb);
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
        if (!$table || !$idColumn) {
            $reflection = new PantonoReflectionModel($model);
            if (!$table) {
                $table = $reflection->getDatabaseTable();
            }
            if (!$idColumn) {
                $idColumn = $reflection->getDatabaseIdColumn();
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
        return $this->quoteTable($prefix . $input);
    }

    protected function pt(string $input): string
    {
        return $this->appendTablePrefix($input);
    }

    /**
     * @param array<string,mixed> $data
     */
    abstract public function insertIgnore(string $table, array $data): void;
}
