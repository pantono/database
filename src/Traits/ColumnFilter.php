<?php

namespace Pantono\Database\Traits;

trait ColumnFilter
{
    private array $columns = [];

    public function addColumn(string $column, string|array $value, string $operator = '='): void
    {
        $operator = strtoupper($operator);
        $allowedOperators = ['=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'];
        if (is_array($value)) {
            $allowedOperators = ['IN', 'NOT IN'];
        }
        if (!in_array($operator, $allowedOperators)) {
            throw new \RuntimeException('Invalid operator');
        }
        $placeholder = '?';
        if ($operator === 'IN' || $operator === 'NOT IN') {
            $placeholder = '(?)';
        }
        $this->columns[] = [
            'name' => $column,
            'value' => $value,
            'operator' => $operator,
            'placeholder' => $placeholder
        ];
    }

    public function getColumns(): array
    {
        return $this->columns;
    }
}
