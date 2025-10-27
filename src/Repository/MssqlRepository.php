<?php

declare(strict_types=1);

namespace Pantono\Database\Repository;

use Pantono\Database\Adapter\MssqlDb;

abstract class MssqlRepository extends AbstractPdoRepository
{
    public function getDb(): MssqlDb
    {
        /**
         * @var MssqlDb $db
         */
        $db = $this->db;
        return $db;
    }
}
