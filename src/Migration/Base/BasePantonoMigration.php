<?php

namespace Pantono\Database\Migration\Base;

use Phinx\Migration\AbstractMigration;
use Pantono\Database\Migration\Traits\DisableForeignKeyChecksTrait;
use Pantono\Database\Migration\Traits\ReseedIdentityTrait;
use Phinx\Db\Table as PhinxTable;
use Pantono\Database\Migration\Table;

class BasePantonoMigration extends AbstractMigration
{
    use DisableForeignKeyChecksTrait;
    use ReseedIdentityTrait;

    public function addLinkedColumn(PhinxTable $table, string $columnName, string $linkedTable, string $linkedColumn): void
    {
        $table->addColumn($columnName, 'integer')
            ->addForeignKey($columnName, $linkedTable, $linkedColumn);
    }

    public function table(string $tableName, array $options = []): PhinxTable
    {
        $table = new Table($tableName, $options, $this->getAdapter());
        $this->tables[] = $table;

        return $table;
    }
}
