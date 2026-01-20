<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\PgsqlDb;

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
}
