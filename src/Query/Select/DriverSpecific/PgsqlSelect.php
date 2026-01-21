<?php

namespace Pantono\Database\Query\Select\DriverSpecific;

use Pantono\Database\Query\Select\Select;
use Pantono\Database\Adapter\PgsqlDb;

class PgsqlSelect extends Select
{
    public function __construct()
    {
        parent::__construct(PgsqlDb::class);
    }

    public function renderQuery(): string
    {
        $columns = [];
        $esc = $this->getTableEscapeString();
        foreach ($this->getColumns() as $column) {
            if ($column['table'] !== null) {
                $columns[] = $esc . $column['table'] . $esc . '.' . $column['column'];
            } else {
                $columns[] = $column['column'];
            }
        }
        $select = 'SELECT ' . implode(', ', $columns);
        if ($this->table instanceof Select) {
            $select .= ' FROM (' . $this->table->renderQuery() . ') as ' . $this->alias;
            foreach ($this->table->getParameters() as $name => $parameter) {
                $this->setParameter($name, $parameter);
            }
        } else {
            if ($this->alias) {
                $select .= ' FROM ' . $esc . $this->table . $esc . ' as ' . $this->alias;
            } else {
                $select .= ' FROM ' . $esc . $this->table . $esc;
            }
        }
        if (!empty($this->joins)) {
            foreach ($this->joins as $join) {
                $select .= $this->renderJoin($join);
            }
        }
        if (!empty($this->where)) {
            $select .= ' WHERE ';
            $whereIndex = 0;
            foreach ($this->where as $where) {
                if ($whereIndex > 0) {
                    $select .= ' ' . $where->getOperand() . ' ';
                }
                $select .= $this->renderWhere($where);
                $whereIndex++;
            }
        }

        if (!empty($this->group)) {
            $select .= ' GROUP BY ' . implode(', ', $this->group);
        }

        if (!empty($this->order)) {
            $select .= ' ORDER BY ';
            foreach ($this->order as $order) {
                $select .= $order . ' ';
            }
        }

        if ($this->limit) {
            $select .= ' LIMIT ' . $this->limit;
        }
        if ($this->offset) {
            $select .= ' OFFSET ' . $this->offset;
        }
        foreach ($this->union as $union) {
            if ($union instanceof Select) {
                $select .= PHP_EOL . ' UNION ALL ' . $union->renderQuery();
            } elseif (is_string($union)) {
                $select .= PHP_EOL . ' UNION ALL ' . $union;
            }
        }

        if ($this->lockForShare === true) {
            $select .= ' FOR SHARE';
        }

        if ($this->lockForUpdate === true) {
            $select .= ' FOR UPDATE';
        }
        return trim($select);
    }
}
