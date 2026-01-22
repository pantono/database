<?php

namespace Pantono\Database\Migration\Base;

use Phinx\Migration\AbstractMigration;
use Pantono\Database\Migration\Traits\DisableForeignKeyChecksTrait;
use Pantono\Database\Migration\Traits\ReseedIdentityTrait;
use Phinx\Db\Table;

class BasePantonoMigration extends AbstractMigration
{
    use DisableForeignKeyChecksTrait;
    use ReseedIdentityTrait;

    public function addLinkedColumn(Table $table, string $columnName, string $linkedTable, string $linkedColumn): void
    {
        $table->addColumn($columnName, 'integer')
            ->addForeignKey($columnName, $linkedTable, $linkedColumn);
    }
}
