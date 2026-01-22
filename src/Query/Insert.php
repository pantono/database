<?php

declare(strict_types=1);

namespace Pantono\Database\Query;

use Pantono\Database\Traits\QueryBuilderTraits;
use Pantono\Database\Adapter\MssqlDb;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Adapter\PgsqlDb;
use Pantono\Database\Adapter\Db;

class Insert
{
    use QueryBuilderTraits;

    private string $table;
    /**
     * @var array<mixed>
     */
    private array $parameters;
    private Db $adapter;

    /**
     * @param array<mixed> $parameters
     */
    public function __construct(string $table, array $parameters, Db $adapter)
    {
        $this->table = $table;
        $this->parameters = $parameters;
        $this->adapter = $adapter;
    }

    public function renderQuery(): string
    {
        $query = 'INSERT INTO ' . $this->adapter->quoteTable($this->table) . ' (';
        $columNames = [];
        foreach ($this->parameters as $name => $value) {
            $columNames[] = $this->adapter->quoteTable($name);
        }
        $query .= implode(', ', $columNames);
        $query .= ') VALUES (';
        $values = [];
        foreach ($this->parameters as $name => $value) {
            $values[] = ':' . $name;
        }
        $query .= implode(', ', $values);
        $query .= ')';

        return $query;
    }

    /**
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }
}
