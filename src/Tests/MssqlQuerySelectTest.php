<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Query\Select\DriverSpecific\MssqlSelect;

class MssqlQuerySelectTest extends TestCase
{
    public function testSimpleSelectMssql(): void
    {
        $select = (new MssqlSelect())->from('table')->where('test_column = ?', 'test');

        $this->assertEqualsIgnoringCase('SELECT table.* FROM table WHERE `test_column` = :param_1', $select->renderQuery());
    }

    public function testLeftJoinPrintMssql(): void
    {
        $select = (new MssqlSelect())->from('table')->joinInner('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test');
        $this->assertEqualsIgnoringCase('SELECT table.*, joined_table.id FROM table INNER JOIN joined_table on joined_table.id=table.join_id WHERE `test_column` = \'test\'', (string)$select);
    }

    public function testRightJoinMssql(): void
    {
        $select = (new MssqlSelect())->from('table')->joinRight('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test');
        $this->assertEqualsIgnoringCase('SELECT table.*, joined_table.id FROM table RIGHT JOIN joined_table on joined_table.id=table.join_id WHERE `test_column` = :param_1', $select->renderQuery());
    }

    public function testGroupMssql(): void
    {
        $select = (new MssqlSelect())->from('table')->order('col1 DESC ')->group('col1');
        $this->assertEqualsIgnoringCase('SELECT table.* FROM table GROUP BY col1 ORDER BY col1 DESC', (string)$select);
    }

    public function testMultiJoinQueryMssql(): void
    {
        $select = (new MssqlSelect())->from('res_reservation')
            ->order('start_time')
            ->joinleft('res_reservation_table', 'res_reservation_table.reservation_id=res_reservation.id', ['id as test'])
            ->joinLeft('res_table', 'res_reservation_table.table_id=res_table.id', [])
            ->joinLeft('res_area', 'res_reservation_table.area_id=res_area.id', [])
            ->joinLeft('res_level', 'res_reservation_table.level_id=res_level.id', [])
            ->joinLeft(['area_level' => 'res_level'], 'res_area.level_id=area_level.id', []);

        $this->assertEqualsIgnoringCase(
            'SELECT res_reservation.*, res_reservation_table.id as test FROM res_reservation ' .
            'LEFT JOIN res_reservation_table on res_reservation_table.reservation_id=res_reservation.id ' .
            'LEFT JOIN res_table on res_reservation_table.table_id=res_table.id ' .
            'LEFT JOIN res_area on res_reservation_table.area_id=res_area.id ' .
            'LEFT JOIN res_level on res_reservation_table.level_id=res_level.id ' .
            'LEFT JOIN res_level as area_level on res_area.level_id=area_level.id ORDER BY start_time',
            (string)$select
        );
    }

    public function testLimit(): void
    {
        $select = (new MssqlSelect())->from('table')->joinInner('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test')->limit(10);
        $this->assertEqualsIgnoringCase('SELECT TOP 10 table.*, joined_table.id FROM table INNER JOIN joined_table on joined_table.id=table.join_id WHERE `test_column` = \'test\'', (string)$select);
    }

    public function testNoOrderThrowsException()
    {
        $this->expectExceptionMessage('When using SQL Server you must specify an order by to use limit');
        $select = (new MssqlSelect())->from('table')->limitPage(1, 10);
        $select->__toString();
    }

    public function testLimitPage(): void
    {
        $select = (new MssqlSelect())->from('table')->joinInner('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test')->order('id')->limitPage(2, 10);
        $this->assertEqualsIgnoringCase('SELECT table.*, joined_table.id FROM table INNER JOIN joined_table on joined_table.id=table.join_id WHERE `test_column` = \'test\' ORDER BY id OFFSET 10 ROWS FETCH NEXT 10 ROWS ONLY', (string)$select);
    }

//    public function testOrderBy(): void
//    {
//        $select = (new MssqlSelect())->from('table')->order('col1');
//        $this->assertEqualsIgnoringCase('SELECT table.* FROM table ORDER BY col1', (string)$select);
//    }
}
