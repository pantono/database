<?php

namespace Pantono\Database\Migration;

use Phinx\Db\Table as PhinxTable;

class Table extends PhinxTable
{
    public function addLinkedColumn(string $columnName, string $linkedTable, string $linkedColumn, array $columnOptions = [], array $keyOptions = []): self
    {
        $this->addColumn($columnName, 'integer', $columnOptions);
        $this->addForeignKey($columnName, $linkedTable, $linkedColumn, $keyOptions);
        return $this;
    }
}
