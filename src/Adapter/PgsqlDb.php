<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

class PgsqlDb extends Db
{
    public function foreignKeyChecks(bool $enabled): void
    {
        if ($enabled === false) {
            $this->query('SET session_replication_role = replica;');
            return;
        }
        $this->query('SET session_replication_role = DEFAULT;');
    }
}
