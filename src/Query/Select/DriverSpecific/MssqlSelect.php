<?php

declare(strict_types=1);

namespace Pantono\Database\Query\Select\DriverSpecific;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Exception\InvalidQueryException;

class MssqlSelect extends Select
{
    public function renderQuery(): string
    {
        $columns = [];
        foreach ($this->getColumns() as $column) {
            $columns[] = $column['table'] . '.' . $column['column'];
        }
        $select = 'SELECT ';
        if ($this->limit && !$this->offset) {
            $select .= 'TOP ' . $this->limit . ' ';
        }
        $select .= implode(', ', $columns);
        $select .= ' FROM ' . $this->table;
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $select .= $this->renderJoin($join);
            }
        }
        if (!empty($this->where)) {
            $select .= ' WHERE ';
            foreach ($this->where as $where) {
                $select .= $this->renderWhere($where) . ' ';
            }
        }

        if (!empty($this->group)) {
            $select .= ' GROUP BY ' . implode(', ', $this->group);
        }
        if (empty($this->order) && $this->limit && $this->offset !== null) {
            throw new InvalidQueryException('When using SQL Server you must specify an order by to use limit');
        }
        if (!empty($this->order)) {
            $select .= ' ORDER BY ';
            foreach ($this->order as $order) {
                $select .= $order . ' ';
            }
        }
        if ($this->offset !== null) {
            $select .= ' OFFSET ' . $this->offset . ' ROWS';
        }

        if ($this->limit && $this->offset !== null) {
            $select .= ' FETCH NEXT ' . $this->limit . ' ROWS ONLY';
        }
        return trim($select);
    }
}
