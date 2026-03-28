<?php

namespace Pantono\Database\Query;

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;

class PantonoQueryBuilder extends QueryBuilder
{
    private Connection $doctrineConnection;

    public function __construct(Connection $connection)
    {
        parent::__construct($connection);
        $this->doctrineConnection = $connection;
    }

    public function whereParam(string $expression, string|array|int $value): self
    {
        $paramName = uniqid('param');
        $expression = str_replace('?', ':' . $paramName, $expression);
        $this->andWhere($expression)
            ->setParameter($paramName, $value);
        return $this;
    }

    public function jsonWhere(string $jsonColumn, array $jsonParts, string|int|null $value): self
    {
        $paramName = uniqid('param_');
        $platform = $this->getConnection()->getDatabasePlatform();
        if ($platform instanceof AbstractMySQLPlatform) {
            $jsonPath = '$.' . implode('.', array_map(
                static fn(string $p) => str_replace('"', '\"', $p),
                $jsonParts
            ));
            $expr = sprintf(
                "JSON_UNQUOTE(JSON_EXTRACT(%s, %s)) = :%s",
                $jsonColumn,
                $this->getConnection()->quote($jsonPath),
                $paramName
            );
            $this->where($expr)
                ->setParameter($paramName, $value);
        } elseif ($platform instanceof PostgreSQLPlatform) {
            $pgPath = '{' . implode(',', array_map(
                static fn(string $p) => str_replace(['\\', '"'], ['\\\\', '\\"'], $p),
                $jsonParts
            )) . '}';

            $expr = sprintf(
                "%s #>> %s = :%s",
                $jsonColumn,
                $this->getConnection()->quote($pgPath),
                $paramName
            );

            $this->where($expr)
                ->setParameter($paramName, $value);
        } else {
            throw new \Exception('Unsupported database platform for json queries');
        }

        return $this;
    }

    public function getConnection(): Connection
    {
        return $this->doctrineConnection;
    }
}
