<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\PgsqlDb;
use Pantono\Database\Query\Insert;

abstract class PgsqlRepository extends AbstractPdoRepository
{
    public function getDb(): PgsqlDb
    {
        /**
         * @var PgsqlDb $db
         */
        $db = $this->db;
        return $db;
    }

    public function insertIgnore(string $table, array $data): void
    {
        $insert = new Insert($table, $data);
        $query = $insert->renderQuery();
        $query .= ' ON CONFLICT DO NOTHING';
        $this->getDb()->runQuery($query, $insert->getParameters());
    }
}
