<?php

declare(strict_types=1);

namespace Pantono\Database\Adapter;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Query\Select\DriverSpecific\MssqlSelect;

class MssqlDb extends Db
{
    public const string ESCAPE_STRING = '[';

    public function select(): MssqlSelect
    {
        return new MssqlSelect();
    }

    public function quoteTable(string $table): string
    {
        return self::ESCAPE_STRING . $table . self::ESCAPE_STRING;
    }

    public function foreignKeyChecks(bool $enabled): void
    {
        if ($enabled) {
            $this->query("EXEC sp_msforeachtable 'ALTER TABLE ? WITH CHECK CHECK CONSTRAINT ALL'");
            return;
        }
        $this->query("EXEC sp_msforeachtable 'ALTER TABLE ? NOCHECK CONSTRAINT ALL'");
    }
}
