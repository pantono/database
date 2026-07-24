<?php

namespace Pantono\Database\Traits;

use Doctrine\DBAL\Query\QueryBuilder;

trait ColumnFilter
{
    private array $columns = [];

    public function addColumn(string $column, string|array|int|null $value, string $operator = '='): void
    {
        $operator = strtoupper($operator);
        $allowedOperators = ['=', '>', '<', '>=', '<=', 'LIKE', 'NOT LIKE'];
        if (is_array($value)) {
            $allowedOperators = ['IN', 'NOT IN'];
        }
        if (!in_array($operator, $allowedOperators)) {
            throw new \RuntimeException('Invalid operator');
        }
        $placeholder = ':param_' . uniqid(true);
        if ($operator === 'IN' || $operator === 'NOT IN') {
            $placeholder = '(' . $placeholder . ')';
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

    public function applyColumnsToQueryBuilder(QueryBuilder $queryBuilder): void
    {
        $expressionBuilder = $queryBuilder->expr();

        foreach ($this->getColumns() as $column) {
            if ($column['value'] === null) {
                $operator = $column['operator'];
                if ($operator === '=') {
                    $queryBuilder->andWhere($expressionBuilder->isNull($column['name']));
                    continue;
                }
                if ($operator === '<>') {
                    $queryBuilder->andWhere($expressionBuilder->isNotNull($column['name']));
                    continue;
                }
            }

            $expression = match ($column['operator']) {
                '=' => $expressionBuilder->eq($column['name'], $column['placeholder']),
                '>' => $expressionBuilder->gt($column['name'], $column['placeholder']),
                '<' => $expressionBuilder->lt($column['name'], $column['placeholder']),
                '>=' => $expressionBuilder->gte($column['name'], $column['placeholder']),
                '<=' => $expressionBuilder->lte($column['name'], $column['placeholder']),
                'LIKE' => $expressionBuilder->like($column['name'], $column['placeholder']),
                'NOT LIKE' => $expressionBuilder->notLike($column['name'], $column['placeholder']),
                'IN' => $expressionBuilder->in($column['name'], $column['placeholder']),
                'NOT IN' => $expressionBuilder->notIn($column['name'], $column['placeholder'])
            };

            $queryBuilder->andWhere($expression)
                ->setParameter(trim($column['placeholder'], '():'), $column['value']);
        }
    }
}
