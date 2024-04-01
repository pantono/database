<?php

declare(strict_types=1);

namespace Pantono\Database\Query;

use Pantono\Database\Traits\QueryBuilderTraits;

class Update
{
    use QueryBuilderTraits;

    private string $table;
    /**
     * @var array<string,mixed>
     */
    private array $parameters;
    /**
     * @var array<string,string|int>
     */
    private array $where;

    private int $parameterIndex = 0;
    /**
     * @var array<int|string,mixed>
     */
    private array $computedParams = [];

    /**
     * @param array<string,mixed> $parameters
     * @param array<string,string|int> $where
     */
    public function __construct(string $table, array $parameters, array $where = [])
    {
        $this->table = $table;
        $this->parameters = $parameters;
        $this->where = $where;
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
                [$wherePart, $parameters] = $this->convertQuestionMarks($parameter, $value);
                foreach ($parameters as $name => $paramValue) {
                    $this->computedParams[$name] = $paramValue;
                }
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

    /**
     * @param array<mixed>|string $parameters
     * @return array<mixed>
     */
    private function convertQuestionMarks(string|int $input, array|string|int $parameters = []): array
    {
        if (is_int($input)) {
            return [$parameters, []];
        }
        if (str_contains($input, '?') === false) {
            return [$input, $parameters];
        }
        if (is_string($parameters) || is_int($parameters)) {
            $parameters = [$parameters];
        }
        $namedParameters = [];
        foreach ($parameters as $parameter) {
            $this->parameterIndex += 1;
            $paramName = ':param' . $this->parameterIndex;
            if (is_null($input)) {
                $input = '';
            }
            $input = preg_replace('/\?/', $paramName, $input, 1);
            $namedParameters[$paramName] = $parameter;
        }
        return [
            $input,
            $namedParameters
        ];
    }
}
