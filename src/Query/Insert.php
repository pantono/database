<?php

declare(strict_types=1);

namespace Pantono\Database\Query;

use Pantono\Database\Traits\QueryBuilderTraits;
use Pantono\Database\Adapter\MssqlDb;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Adapter\PgsqlDb;

class Insert
{
    use QueryBuilderTraits;

    private string $table;
    /**
     * @var array<mixed>
     */
    private array $parameters;
    /**
     * @var string
     */
    private string $driverClass;

    /**
     * @param array<mixed> $parameters
     */
    public function __construct(string $table, array $parameters, string $driverClass)
    {
        $this->table = $table;
        $this->parameters = $parameters;
        $this->driverClass = $driverClass;
    }

    public function renderQuery(): string
    {
        $esc = $this->getTableEscapeString();
        $query = 'INSERT INTO ' . $esc . $this->table . $esc . ' (';
        $columNames = [];
        foreach ($this->parameters as $name => $value) {
            $columNames[] = $esc . $name . $esc;
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

    public function getTableEscapeString(): string
    {
        if ($this->driverClass === MssqlDb::class) {
            return MssqlDb::ESCAPE_STRING;
        }
        if ($this->driverClass === MysqlDb::class) {
            return MysqlDb::ESCAPE_STRING;
        }
        if ($this->driverClass === PgsqlDb::class) {
            return PgsqlDb::ESCAPE_STRING;
        }
        return '';
    }
}
