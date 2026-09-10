<?php

namespace Pantono\Database\Utility;

use Doctrine\DBAL\Query\QueryBuilder;

class QueryUtilities
{
    public static function normaliseSql(QueryBuilder $qb): string
    {
        //This should only be used for debugging purposes
        $sql = $qb->getSQL();
        $params = $qb->getParameters();
        $types = $qb->getParameterTypes();

        foreach ($params as $key => $value) {
            $type = $types[$key] ?? null;

            $formattedValue = self::formatDoctrineParameter($value, $type);

            // Doctrine DBAL parameters may be named (:foo) or positional (?).
            if (is_string($key)) {
                $sql = preg_replace(
                    '/:' . preg_quote($key, '/') . '\b/',
                    $formattedValue,
                    $sql
                );
                if ($sql === null) {
                    return '';
                }
            } else {
                $sql = preg_replace('/\?/', $formattedValue, $sql, 1);
                if ($sql === null) {
                    return '';
                }
            }
        }

        return $sql;
    }

    public static function formatDoctrineParameter(mixed $value, mixed $type = null): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        if ($value instanceof \DateTimeInterface) {
            return "'" . $value->format('Y-m-d H:i:s') . "'";
        }

        if (is_array($value)) {
            return implode(', ', array_map(
                fn($item) => self::formatDoctrineParameter($item),
                $value
            ));
        }

        // SQL-standard escaping for string literals.
        return "'" . str_replace("'", "''", (string)$value) . "'";
    }
}
