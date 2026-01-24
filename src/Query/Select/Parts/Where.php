<?php

declare(strict_types=1);

namespace Pantono\Database\Query\Select\Parts;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Traits\QueryBuilderTraits;
use Pantono\Database\Exception\InvalidQueryException;

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
    public function __construct(string|Expression $where, string|array|int|null $parameters, Select $select, string $operand = 'and')
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
    private function convertQuestionMarks(string|int|Expression $key, string|array|int|null $value): array
    {
        if (is_null($value)) {
            return ['where' => (string)$key, 'parameters' => []];
        }
        if ($key instanceof Expression) {
            $queryPart = $key->getExpression();
            $values = $value;
        } elseif (is_int($key)) {
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
        /** @var array{expression?: string, table?: string, column?: string, operand?: string, value?: string} $matches */
        $matches = [];
        if (preg_match('/(?<expression>[\w\.]+\(.*\))\s*(?<operand>=|<>|in|not in|>=|<=|!=|>|<|is null|like|between|not like)\s*(?<value>.*)/i', $queryPart, $matches)) {
            $expression = $matches['expression'];
            $operand = trim($matches['operand']);
            $parameter = trim($matches['value']);
            $parameters = $this->parseParameters($values, $parameter);
            return [
                'where' => ($hasBracket ? '(' : '') . $expression . ' ' . $operand . ' ' . $parameters['parameter'],
                'parameters' => $parameters['parameters']
            ];
        }

        preg_match('/(?:(?<table>\w+)\.)?(?<column>\w+)\s*(?<operand>=|<>|in|not in|>=|<=|!=|>|<|is null|like|between|not like)\s*(?<value>.*)/i', $queryPart, $matches);
        $column = $matches['column'] ?? null;
        $operand = isset($matches['operand']) ? trim($matches['operand']) : '';
        $parameter = isset($matches['value']) ? trim($matches['value']) : '';
        $table = isset($matches['table']) ? trim($matches['table']) : '';

        $parameters = $this->parseParameters($values, $parameter);
        $parameter = $parameters['parameter'];
        $parameters = $parameters['parameters'];

        if ($column === null) {
            throw new InvalidQueryException('Unable to ascertain column');
        }
        if ($table) {
            return [
                'where' => ($hasBracket ? '(' : '') . $this->select->quoteColumn($table, $column) . ' ' . $operand . ' ' . $parameter,
                'parameters' => $parameters
            ];
        }
        return [
            'where' => ($hasBracket ? '(' : '') . $this->select->quoteTable($column) . ' ' . $operand . ' ' . $parameter,
            'parameters' => $parameters
        ];
    }

    /**
     * @param string|array<mixed>|int|null $values
     * @return array{parameter: string, parameters: array<mixed>}
     */
    private function parseParameters(string|array|int|null $values, string $parameter): array
    {
        $parameterReplacement = '';
        $parameters = [];
        if (is_array($values)) {
            $parts = [];
            foreach ($values as $inputValue) {
                $this->select->parameterIndex++;
                $parameters[':param_' . $this->select->parameterIndex . '_' . $this->select->uniqueId] = $inputValue;
                $parts[] = ':param_' . $this->select->parameterIndex . '_' . $this->select->uniqueId;
            }
            $parameterReplacement = implode(', ', $parts);
        } elseif ($values !== '' && $values !== null) {
            $this->select->parameterIndex++;
            $parameters[':param_' . $this->select->parameterIndex . '_' . $this->select->uniqueId] = $values;
            $parameter = str_replace('?', ':param_' . $this->select->parameterIndex . '_' . $this->select->uniqueId, $parameter);
        }
        $pos = strpos($parameter, '?');
        if ($pos !== false) {
            $parameter = substr_replace($parameter, $parameterReplacement, $pos, 1);
        }

        return ['parameter' => $parameter, 'parameters' => $parameters];
    }

    public function getOperand(): string
    {
        return $this->operand;
    }
}
