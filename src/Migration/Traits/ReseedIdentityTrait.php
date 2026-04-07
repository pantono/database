<?php

namespace Pantono\Database\Migration\Traits;

trait ReseedIdentityTrait
{
    protected function saveData(string $table, array $data, string $idColumn = 'id'): void
    {
        if (empty($data)) {
            return;
        }
        $reseed = false;
        if (isset($data[0][$idColumn])) {
            $reseed = true;
        }
        $this->table($table)->insert($data)->saveData();
        if ($reseed) {
            $this->reseedIdentity($table);
        }
    }

    protected function reseedIdentity(string $table, string $column = 'id'): void
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
SELECT MAX(id) + 1 INTO @next_id FROM `{$table}`;

SET @sql = CONCAT('ALTER TABLE `{$table}` AUTO_INCREMENT = ', @next_id);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
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
