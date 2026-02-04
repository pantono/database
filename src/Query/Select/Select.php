<?php

declare(strict_types=1);

namespace Pantono\Database\Query\Select;

use Doctrine\SqlFormatter\SqlFormatter;
use Pantono\Database\Query\Select\Parts\Join;
use Pantono\Database\Query\Select\Parts\Where;
use Pantono\Database\Adapter\MssqlDb;
use Pantono\Database\Adapter\MysqlDb;
use Pdo\Mysql;
use Pantono\Database\Adapter\PgsqlDb;
use Pantono\Database\Adapter\Db;
use Pantono\Database\Query\Select\Parts\Expression;

class Select
{
    protected string|Select $table;
    /**
     * @var array<mixed>|null
     */
    protected ?array $columns = null;

    /**
     * @var Where[]
     */
    protected array $where = [];
    /**
     * @var string[]|Select[]
     */
    protected array $union = [];

    protected ?int $limit = null;

    protected ?int $offset = null;

    /**
     * @var Join[]
     */
    protected array $joins = [];
    /**
     * @var array<string, mixed>
     */
    protected array $manualParams = [];

    public int $parameterIndex = 0;
    /**
     * @var array<mixed>
     */

    protected array $order = [];
    /**
     * @var array<mixed>
     */
    protected array $group = [];

    protected ?string $alias = null;
    public string $uniqueId;

    protected bool $lockForUpdate = false;

    protected bool $lockForShare = false;
    private Db $adapter;

    public function __construct(Db $adapter)
    {
        $this->uniqueId = uniqid();
        $this->adapter = $adapter;
    }

    public function quoteTable(string $table): string
    {
        return $this->adapter->quoteTable($table);
    }

    public function quoteColumn(string $table, ?string $column = null): string
    {
        return $this->adapter->quoteColumn($table, $column);
    }

    /**
     * @param string|array<mixed> $table
     * @param array<mixed>|string|null $columns
     * @return $this
     */
    public function from(string|array $table, array|string|null $columns = null): self
    {
        if (is_array($table)) {
            $alias = array_keys($table)[0];
            $table = array_values($table)[0];
            $this->alias = $alias;
        }
        $this->table = $table;
        if (is_string($columns)) {
            $this->columns[] = [
                'table' => $this->alias ?? $table,
                'column' => $columns
            ];
            return $this;
        }
        if (is_null($columns)) {
            $this->columns[] = [
                'table' => $this->alias ?? $table,
                'column' => '*'
            ];
            return $this;
        }
        foreach ($columns as $column) {
            if (str_contains($column, ' ')) {
                $this->columns[] = [
                    'table' => null,
                    'column' => $column
                ];
            } else {
                $this->columns[] = [
                    'table' => $this->alias ?? $table,
                    'column' => $column
                ];
            }
        }
        return $this;
    }

    /**
     * @param string $query
     * @param array<mixed>|string|null $parameters
     * @return $this
     */
    public function where(string|Expression $query, array|string|null|int $parameters = null): self
    {
        $this->where[] = new Where($query, $parameters, $this);
        return $this;
    }

    /**
     * @param string $query
     * @param array<mixed>|string|null $parameters
     * @return $this
     */
    public function orWhere(string $query, array|string|null|int $parameters = null): self
    {
        $this->where[] = new Where($query, $parameters, $this, 'or');
        return $this;
    }

    public function union(Select|string $select): self
    {
        $this->union[] = $select;
        return $this;
    }

    public function limit(int $limit, ?int $offset = null): self
    {
        $this->offset = null;
        $this->limit = $limit;
        if ($offset) {
            $this->offset = $offset;
        }
        return $this;
    }

    public function limitPage(int $page, int $perPage): self
    {
        $this->limit = $perPage;
        $this->offset = ($page - 1) * $perPage;
        return $this;
    }

    public function order(string $condition): self
    {
        if (preg_match('/^([\w.]+)\s*(asc|desc)?$/i', trim($condition), $matches)) {
            $column = $matches[1];
            $direction = $matches[2] ?? '';
            if (str_contains($column, '.')) {
                [$table, $col] = explode('.', $column, 2);
                $condition = $this->quoteColumn($table, $col);
            } else {
                $condition = $this->quoteTable($column);
            }
            if ($direction) {
                $condition .= ' ' . strtoupper($direction);
            }
            $this->order[] = $condition;
        }
        return $this;
    }

    public function group(string $spec): self
    {
        if (preg_match('/^([\w.]+)$/', trim($spec), $matches)) {
            $column = $matches[1];
            if (str_contains($column, '.')) {
                [$table, $col] = explode('.', $column, 2);
                $spec = $this->quoteColumn($table, $col);
            } else {
                $spec = $this->quoteTable($column);
            }
            $this->group[] = $spec;
        }
        return $this;
    }

    /**
     * @param string|array<mixed> $table
     * @param array<mixed>|null $columns
     */
    public function joinLeft(string|array $table, string $joinString, ?array $columns = null): self
    {
        return $this->addJoin(
            'left',
            $table,
            $joinString,
            $columns
        );
    }

    /**
     * @param string|array<mixed> $table
     * @param array<mixed>|null $columns
     */
    public function joinInner(string|array $table, string $joinString, ?array $columns = null): self
    {
        return $this->addJoin(
            'inner',
            $table,
            $joinString,
            $columns
        );
    }

    /**
     * @param string|array<mixed> $table
     * @param array<mixed>|null $columns
     */
    public function joinRight(string|array $table, string $joinString, ?array $columns = null): self
    {
        return $this->addJoin(
            'right',
            $table,
            $joinString,
            $columns
        );
    }

    /**
     * @param string|array<mixed> $table
     * @param array<mixed>|null $columns
     */
    public function join(string|array $table, string $joinString, ?array $columns = null): self
    {
        return $this->addJoin(
            'inner',
            $table,
            $joinString,
            $columns
        );
    }

    /**
     * @param string|array<mixed> $table
     * @param array<mixed>|null $columns
     */
    public function addJoin(string $joinType, string|array $table, string $joinString, ?array $columns = null): self
    {
        $alias = null;
        if (is_array($table)) {
            $alias = array_keys($table)[0];
            $table = array_values($table)[0];
        }
        if (is_int($alias)) {
            $alias = (string)$alias;
        }
        $this->joins[] = new Join($joinType, $table, $alias, $joinString, $columns);
        return $this;
    }

    /**
     * @return array<mixed>
     */
    protected function getColumns(): array
    {
        $columns = $this->columns ?? [];
        foreach ($this->joins as $join) {
            foreach ($join->getSelectColumns() as $column) {
                $columns[] = $column;
            }
        }
        return $columns;
    }

    protected function renderJoin(Join $join): string
    {
        if ($join->getType() === 'left') {
            $joinStr = ' LEFT ';
        } elseif ($join->getType() === 'right') {
            $joinStr = ' RIGHT ';
        } elseif ($join->getType() === 'inner') {
            $joinStr = ' INNER ';
        } else {
            throw new \RuntimeException('Invalid join type: ' . $join->getType());
        }
        if ($join->getTable() instanceof Select) {
            $joinStr .= 'JOIN (' . $join->getTable() . ')';
        } else {
            $joinStr .= 'JOIN ' . $this->quoteTable($join->getTable());
        }
        if ($join->getAlias()) {
            $joinStr .= ' as ' . $join->getAlias() . ' ';
        }
        $joinStr .= ' on ' . $join->getJoinString();

        return $joinStr;
    }

    protected function renderWhere(Where $where): string
    {
        return $where->getWhere();
    }

    public function __toString(): string
    {
        $query = $this->renderQuery();
        foreach ($this->where as $where) {
            foreach ($where->getParameters() as $name => $value) {
                $quoted = $this->quoteValue($value);
                if (is_int($quoted)) {
                    $quoted = (string)$quoted;
                }
                $query = str_replace($name, $quoted, $query);
            }
        }
        foreach ($this->union as $union) {
            if ($union instanceof Select) {
                foreach ($union->getParameters() as $name => $value) {
                    $quoted = $this->quoteValue($value);
                    if (is_int($quoted)) {
                        $quoted = (string)$quoted;
                    }
                    $query = str_replace($name, $quoted, $query);
                }
            }
        }

        $formatter = new SqlFormatter();
        return $formatter->compress($query);
    }

    public function prettify(): string
    {
        $query = $this->renderQuery();
        foreach ($this->where as $where) {
            foreach ($where->getParameters() as $name => $value) {
                $quoted = $this->quoteValue($value);
                if (is_int($quoted)) {
                    $quoted = (string)$quoted;
                }
                $query = str_replace($name, $quoted, $query);
            }
        }

        $formatter = new SqlFormatter();
        return $formatter->format($query);
    }

    /**
     * @return string|array<mixed>|int
     */
    public function quoteValue(string|array|int $value): string|array|int
    {
        if (is_int($value)) {
            return $value;
        }
        if (is_string($value)) {
            return '\'' . $value . '\'';
        }
        if (is_array($value)) {
            $parts = [];
            foreach ($value as $item) {
                $parts[] = $this->quoteValue($item);
            }
            return $parts;
        }
    }

    public function renderQuery(): string
    {
        $columns = [];
        foreach ($this->getColumns() as $column) {
            if ($column['table'] !== null) {
                $columns[] = $this->quoteColumn($column['table'], $column['column']);
            } else {
                $columns[] = $column['column'];
            }
        }
        $select = 'SELECT ' . implode(', ', $columns);
        if ($this->table instanceof Select) {
            $select .= ' FROM (' . $this->table->renderQuery() . ') as ' . $this->alias;
            foreach ($this->table->getParameters() as $name => $parameter) {
                $this->setParameter($name, $parameter);
            }
        } else {
            $select .= ' FROM ' . $this->quoteTable($this->table);
        }
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $select .= $this->renderJoin($join);
            }
        }
        if (!empty($this->where)) {
            $select .= ' WHERE ';
            $whereIndex = 0;
            foreach ($this->where as $where) {
                if ($whereIndex > 0) {
                    $select .= ' ' . $where->getOperand() . ' ';
                }
                $select .= $this->renderWhere($where);
                $whereIndex++;
            }
        }

        if (!empty($this->group)) {
            $select .= ' GROUP BY ' . implode(', ', $this->group);
        }

        if (!empty($this->order)) {
            $select .= ' ORDER BY ';
            foreach ($this->order as $order) {
                $select .= $order . ' ';
            }
        }

        if ($this->limit) {
            $select .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset) {
            $select .= ' OFFSET ' . $this->offset;
        }
        foreach ($this->union as $union) {
            if ($union instanceof Select) {
                $select .= PHP_EOL . ' UNION ALL ' . $union->renderQuery();
            } elseif (is_string($union)) {
                $select .= PHP_EOL . ' UNION ALL ' . $union;
            }
        }
        return trim($select);
    }

    /**
     * @return array<mixed>
     */
    public function getParameters(): array
    {
        $params = $this->manualParams;
        foreach ($this->where as $where) {
            foreach ($where->getParameters() as $name => $param) {
                $params[$name] = $param;
            }
        }
        foreach ($this->union as $union) {
            if ($union instanceof Select) {
                foreach ($union->getParameters() as $name => $param) {
                    $params[$name] = $param;
                }
            }
        }
        return $params;
    }

    public function setParameter(string $name, mixed $value): void
    {
        $this->manualParams[$name] = $value;
    }

    public function getNextParameterName(): string
    {
        $this->parameterIndex = $this->parameterIndex + 1;
        return 'param_' . $this->parameterIndex;
    }

    public function reset(string $name): void
    {
        if ($name === 'order') {
            $this->order = [];
            return;
        }
        if ($name === 'join') {
            $this->joins = [];
            return;
        }
        if ($name === 'columns') {
            $this->columns = null;
            return;
        }

        throw new \RuntimeException('Invalid reset ' . $name);
    }

    public function setLockForUpdate(bool $value): self
    {
        $this->lockForUpdate = $value;
        return $this;
    }

    public function setLockForShare(bool $value): self
    {
        $this->lockForShare = $value;
        return $this;
    }
}
