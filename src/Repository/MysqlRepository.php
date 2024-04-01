<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

abstract class MysqlRepository extends AbstractPdoRepository
{
    /**
     * @param array<string,mixed> $data
     */
    public function insertIgnore(string $table, array $data): void
    {
        $query = 'INSERT IGNORE into ' . $table . ' SET ';
        $values = [];
        $parameters = [];
        foreach ($data as $key => $value) {
            $values[] = '`' . $key . '` = ?';
            $parameters[] = $value;
        }
        $query .= implode(', ', $values);
        $this->getDb()->runQuery($query, $parameters);
    }
}
