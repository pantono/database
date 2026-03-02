<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

class MssqlDb extends Db
{
    public function foreignKeyChecks(bool $enabled): void
    {
        if ($enabled) {
            $this->query("EXEC sp_msforeachtable 'ALTER TABLE ? WITH CHECK CHECK CONSTRAINT ALL'");
            return;
        }
        $this->query("EXEC sp_msforeachtable 'ALTER TABLE ? NOCHECK CONSTRAINT ALL'");
    }
}
