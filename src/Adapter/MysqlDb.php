<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

class MysqlDb extends Db
{
    public function foreignKeyChecks(bool $enabled): void
    {
        $this->query('SET FOREIGN_KEY_CHECKS = ' . ($enabled ? '1' : '0'));
    }
}
