<?php

namespace Pantono\Database\Migration;

use Phinx\Db\Table as PhinxTable;

class Table extends PhinxTable
{
    public function addLinkedColumn(string $columnName, string $linkedTable, string $linkedColumn, array $columnOptions = [], array $keyOptions = []): self
    {
        $this->addColumn($columnName, 'integer', $columnOptions);
        if (!isset($keyOptions['delete']) || !in_array($keyOptions['delete'], ['CASCADE', 'SET NULL', 'NO ACTION'])) {
            $keyOptions['delete'] = 'CASCADE';
        }
        $this->addForeignKey($columnName, $linkedTable, $linkedColumn, $keyOptions);
        return $this;
    }
}
