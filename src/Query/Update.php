<?php

declare(strict_types=1);

namespace Pantono\Database\Query;

use Pantono\Database\Traits\QueryBuilderTraits;
use Pantono\Database\Adapter\MssqlDb;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Adapter\PgsqlDb;

class Update
{
    use QueryBuilderTraits;

    private string $table;
    /**
     * @var array<string,mixed>
     */
    private array $parameters;
    /**
     * @var array<string|int, string|int|array<mixed>>
     */
    private array $where;

    private int $parameterIndex = 0;
    /**
     * @var array<int|string,mixed>
     */
    private array $computedParams = [];
    private string $driverClass;

    /**
     * @param array<string,mixed> $parameters
     * @param array<string,string|int|array> $where
     */
    public function __construct(string $table, array $parameters, array $where = [], string $driverClass = '')
    {
        $this->table = $table;
        $this->parameters = $parameters;
        $this->where = $where;
        $this->driverClass = $driverClass;
    }

    public function renderQuery(): string
    {
        $query = 'UPDATE ' . $this->table . ' SET ';
        $updateParts = [];
        foreach ($this->parameters as $name => $value) {
            $updateParts[] = '`' . $name . '` = :' . $name;
            $this->computedParams[':' . $name] = $value;
        }
        $query .= implode(', ', $updateParts);
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
        $esc = $this->getTableEscapeString();
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
        /** @var array{column?: string, operand?: string, value?: string} $matches */
        $matches = [];
        preg_match('/(?<column>\w+)\s*(?<operand>=|<>|in|not in)\s*(?<value>.*)/i', $queryPart, $matches);
        $column = $matches['column'] ?? null;
        $operand = isset($matches['operand']) ? trim($matches['operand']) : '';
        $parameter = isset($matches['value']) ? trim($matches['value']) : '';
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
        return $esc . $column . $esc . ' ' . $operand . ' ' . $parameter;
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
