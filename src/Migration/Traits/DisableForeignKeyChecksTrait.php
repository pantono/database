<?php

namespace Pantono\Database\Migration\Traits;

trait DisableForeignKeyChecksTrait
{
    protected function disableForeignKeyChecks(): void
    {
        $adapter = $this->getAdapter()->getAdapterType();

        switch ($adapter) {

            case 'mysql':
                $this->execute('SET FOREIGN_KEY_CHECKS = 0;');
                break;

            case 'pgsql':
                // Disables FK checks for the current session
                $this->execute('SET session_replication_role = replica;');
                break;

            case 'sqlsrv':
                // Disable all constraints
                $this->execute("
                    EXEC sp_msforeachtable 'ALTER TABLE ? NOCHECK CONSTRAINT ALL';
                ");
                break;

            case 'sqlite':
                $this->execute('PRAGMA foreign_keys = OFF;');
                break;
        }
    }

    protected function enableForeignKeyChecks(): void
    {
        $adapter = $this->getAdapter()->getAdapterType();

        switch ($adapter) {

            case 'mysql':
                $this->execute('SET FOREIGN_KEY_CHECKS = 1;');
                break;

            case 'pgsql':
                // Restore normal FK behaviour
                $this->execute('SET session_replication_role = DEFAULT;');
                break;

            case 'sqlsrv':
                // Re-enable and validate constraints
                $this->execute("
                    EXEC sp_msforeachtable 'ALTER TABLE ? WITH CHECK CHECK CONSTRAINT ALL';
                ");
                break;

            case 'sqlite':
                $this->execute('PRAGMA foreign_keys = ON;');
                break;
        }
    }
}
