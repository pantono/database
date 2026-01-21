<?php

namespace Pantono\Database\Migration\Traits;

trait ReseedIdentityTrait
{
    private function reseedIdentity(string $table, string $column = 'id')
    {
        $adapter = $this->getAdapter()->getAdapterType();

        if ($adapter === 'pgsql') {
            $this->execute("
            SELECT setval(
                pg_get_serial_sequence('public.\"{$table}\"', '{$column}'),
                (SELECT MAX({$column}) FROM public.\"{$table}\")
            );
        ");
        }

        if ($adapter === 'mysql') {
            $this->execute("
            ALTER TABLE `{$table}`
            AUTO_INCREMENT = (SELECT MAX({$column}) + 1 FROM `{$table}`);
        ");
        }

        if ($adapter === 'sqlsrv') {
            $this->execute("
            DECLARE @max INT;
            SELECT @max = MAX({$column}) FROM [{$table}];
            DBCC CHECKIDENT ('[{$table}]', RESEED, @max);
        ");
        }
    }
}
