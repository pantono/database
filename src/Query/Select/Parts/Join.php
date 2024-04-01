<?php

declare(strict_types=1);

namespace Pantono\Database\Query\Select\Parts;

use Pantono\Database\Query\Select\Select;

class Join
{
    private string $type;
    private string|Select $table;
    private ?string $alias = null;
    private string $joinString;
    /**
     * @var string|array<mixed>|null
     */
    private string|array|null $columns = null;

    /**
     * @param array<mixed>|string|null $columns
     */
    public function __construct(string $type, string|Select $table, ?string $alias, string $joinString, array|string|null $columns)
    {
        $this->type = $type;
        $this->table = $table;
        $this->alias = $alias;
        $this->joinString = $joinString;
        $this->columns = $columns;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTable(): string|Select
    {
        return $this->table;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function getJoinString(): string
    {
        return $this->joinString;
    }

    /**
     * @return array<mixed>|string|null
     */
    public function getColumns(): array|string|null
    {
        return $this->columns;
    }

    /**
     * @return array<mixed>
     */
    public function getSelectColumns(): array
    {
        $table = $this->getAlias() ?: $this->getTable();
        if (is_string($this->columns)) {
            return [
                [
                    'table' => $table,
                    'column' => $this->getColumns()
                ]
            ];
        }
        if (is_null($this->getColumns())) {
            return [
                [
                    'table' => $table,
                    'column' => '*'
                ]
            ];
        }
        $columns = [];
        $definedColumns = $this->getColumns();
        if (is_string($definedColumns)) {
            $definedColumns = explode(',', $definedColumns);
        }
        foreach ($definedColumns as $column) {
            if (str_contains($column, '(')) {
                //Remove table as it's probably an expression
                $columns[] = [
                    'table' => null,
                    'column' => $column
                ];
            } else {
                $columns[] = [
                    'table' => $table,
                    'column' => $column
                ];
            }
        }

        return $columns;
    }
}
