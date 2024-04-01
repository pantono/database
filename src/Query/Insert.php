<?php

declare(strict_types=1);

namespace Pantono\Database\Query;

use Pantono\Database\Traits\QueryBuilderTraits;

class Insert
{
    use QueryBuilderTraits;

    private string $table;
    /**
     * @var array<mixed>
     */
    private array $parameters;

    /**
     * @param array<mixed> $parameters
     */
    public function __construct(string $table, array $parameters)
    {
        $this->table = $table;
        $this->parameters = $parameters;
    }

    public function renderQuery(): string
    {
        $query = 'INSERT INTO ' . $this->table . ' (';
        $columNames = [];
        foreach ($this->parameters as $name => $value) {
            $columNames[] = '`' . $name . '`';
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
