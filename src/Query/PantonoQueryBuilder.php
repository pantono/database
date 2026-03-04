<?php

namespace Pantono\Database\Query;

use Doctrine\DBAL\Query\QueryBuilder;

class PantonoQueryBuilder extends QueryBuilder
{
    public function whereParam(string $expression, string|array|int $value): self
    {
        $paramName = uniqid('param');
        $expression = str_replace('?', ':' . $paramName, $expression);
        $this->where($expression)
            ->setParameter($paramName, $value);
        return $this;
    }
}
