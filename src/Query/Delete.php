<?php

declare(strict_types=1);

namespace Pantono\Database\Query;

use Pantono\Database\Traits\QueryBuilderTraits;

class Delete
{
    use QueryBuilderTraits;

    private string $table;
    /**
     * @var array<mixed>
     */
    private array $where;
    private int $parameterIndex = 0;

    /**
     * @var array<mixed>
     */
    private array $computedParams = [];

    /**
     * @param array<mixed> $where
     */
    public function __construct(string $table, array $where)
    {
        $this->table = $table;
        $this->where = $where;
    }

    public function renderQuery(): string
    {
        $query = 'DELETE FROM `' . $this->table . '`';
        if (!empty($this->where)) {
            $query .= ' WHERE ';
            $whereParts = [];
            foreach ($this->where as $parameter => $value) {
                $wherePart = $this->formatInput($parameter, $value);
                $whereParts[] = $wherePart;
            }
            $query .= implode(' AND ', $whereParts);
        }

        return $query;
    }

    /**
     * @return mixed[]
     */
    public function getParameters(): array
    {
        return $this->computedParams;
    }

    /**
     * @return mixed[]
     */
    public function getComputedParams(): array
    {
        return $this->computedParams;
    }

    private function formatInput(string|int $key, string|int|array $value): string
    {
        if (is_int($key)) {
            $queryPart = $value;
            $values = '';
        } else {
            $queryPart = $key;
            $values = $value;
        }
        if (!is_string($queryPart)) {
            throw new \RuntimeException('Invalid query value');
        }
        preg_match('/(?<column>\w+)\s*(?<operand>=|<>|in|not in)\s*(?<value>.*)/i', $queryPart, $matches);
        $column = $matches['column'] ?? null;
        $operand = trim($matches['operand']);
        $parameter = trim($matches['value']);
        $parameterReplacement = '';
        if (is_array($values)) {
            $parts = [];
            foreach ($values as $inputValue) {
                $this->parameterIndex++;
                $this->computedParams[':param' . $this->parameterIndex] = $inputValue;
                $parts[] = ':param' . $this->parameterIndex;
            }
            $parameterReplacement = implode(', ', $parts);
        } elseif ($values !== '') {
            $this->parameterIndex++;
            $this->computedParams[':param' . $this->parameterIndex] = $values;
            $parameter = str_replace('?', ':param' . $this->parameterIndex, $parameter);
        }
        $pos = strpos($parameter, '?');
        if ($pos !== false) {
            $parameter = substr_replace($parameter, $parameterReplacement, $pos, 1);
        }
        return '`' . $column . '` ' . $operand . ' ' . $parameter;
    }
}
