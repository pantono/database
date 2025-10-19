<?php

declare(strict_types=1);

namespace Pantono\Database\Query\Select\Parts;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Traits\QueryBuilderTraits;

class Where
{
    use QueryBuilderTraits;

    private string $where;
    /**
     * @var array<string|int>
     */
    private array $parameters;
    private Select $select;
    private string $operand;

    /**
     * @param string|array<mixed>|int|null $parameters
     */
    public function __construct(string $where, string|array|int|null $parameters, Select $select, string $operand = 'and')
    {
        $this->select = $select;
        $details = $this->convertQuestionMarks($where, $parameters);
        $this->where = $details['where'];
        $this->parameters = $details['parameters'];
        $allowedOperands = ['and', 'or'];
        if (!in_array($operand, $allowedOperands)) {
            throw new \RuntimeException('Operand ' . $operand . ' is not supported');
        }
        $this->operand = $operand;
    }

    public function getWhere(): string
    {
        return $this->where;
    }

    public function setWhere(string $where): void
    {
        $this->where = $where;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @return array{where: string, parameters: array<mixed>}
     */
    private function convertQuestionMarks(string|int $key, string|array|int|null $value): array
    {
        if (is_null($value)) {
            return ['where' => (string)$key, 'parameters' => []];
        }
        if (is_int($key)) {
            $queryPart = $value;
            $values = '';
        } else {
            $queryPart = $key;
            $values = $value;
        }
        $hasBracket = false;
        if (!is_string($queryPart)) {
            throw new \RuntimeException('Invalid query value');
        }
        $queryPart = trim($queryPart);
        if (str_starts_with($queryPart, '(')) {
            $hasBracket = true;
        }
        preg_match('/(?:(?<table>\w+)\.)?(?<column>\w+)\s*(?<operand>=|<>|in|not in|>=|<=|!=|>|<|is null|like|between|not like)\s*(?<value>.*)/i', $queryPart, $matches);
        $column = $matches['column'] ?? null;
        $operand = trim($matches['operand']);
        $parameter = trim($matches['value']);
        $table = trim($matches['table']);
        $parameterReplacement = '';
        $parameters = [];
        if (is_array($values)) {
            $parts = [];
            foreach ($values as $inputValue) {
                $this->select->parameterIndex++;
                $parameters[':param_' . $this->select->uniqueId . $this->select->parameterIndex] = $inputValue;
                $parts[] = ':param_' . $this->select->uniqueId . $this->select->parameterIndex;
            }
            $parameterReplacement = implode(', ', $parts);
        } elseif ($values !== '') {
            $this->select->parameterIndex++;
            $parameters[':param_' . $this->select->uniqueId . $this->select->parameterIndex] = $values;
            $parameter = str_replace('?', ':param_' . $this->select->uniqueId . $this->select->parameterIndex, $parameter);
        }
        $pos = strpos($parameter, '?');
        if ($pos !== false) {
            $parameter = substr_replace($parameter, $parameterReplacement, $pos, 1);
        }
        if ($table) {
            return [
                'where' => ($hasBracket ? '(' : '') . '`' . $table . '`.`' . $column . '` ' . $operand . ' ' . $parameter,
                'parameters' => $parameters
            ];
        }
        return [
            'where' => ($hasBracket ? '(' : '') . '`' . $column . '` ' . $operand . ' ' . $parameter,
            'parameters' => $parameters
        ];
    }

    public function getOperand(): string
    {
        return $this->operand;
    }
}
