<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Query\Select\Select;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Tests\BaseCases\AbstractMysqlAdapterTestCase;

class MysqlQuerySelectTest extends AbstractMysqlAdapterTestCase
{
    public function testSimpleSelectPrint(): void
    {
        $select = (new Select($this->db))->from('table')->where('test_column = ?', 'test');

        $this->assertEqualsIgnoringCase('SELECT `table`.* FROM `table` WHERE `test_column` = \'test\'', (string)$select);
    }

    public function testSimpleSelect(): void
    {
        $select = (new Select($this->db))->from('table')->where('test_column = ?', 'test');

        $this->assertEqualsIgnoringCase('SELECT `table`.* FROM `table` WHERE `test_column` = :param_1_' . $select->uniqueId, $select->renderQuery());
    }

    public function testLeftJoinPrint(): void
    {
        $select = (new Select($this->db))->from('table')->joinInner('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test');
        $this->assertEqualsIgnoringCase('SELECT `table`.*, `joined_table`.`id` FROM `table` INNER JOIN `joined_table` ON joined_table.id=table.join_id WHERE `test_column` = \'test\'', (string)$select);
    }

    public function testRightJoin(): void
    {
        $select = (new Select($this->db))->from('table')->joinRight('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test');
        $this->assertEqualsIgnoringCase('SELECT `table`.*, `joined_table`.`id` FROM `table` RIGHT JOIN `joined_table` ON joined_table.id=table.join_id WHERE `test_column` = :param_1_' . $select->uniqueId, $select->renderQuery());
    }

    public function testLimit(): void
    {
        $select = (new Select($this->db))->from('table')->joinInner('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test')->limit(10);
        $this->assertEqualsIgnoringCase('SELECT `table`.*, `joined_table`.`id` FROM `table` INNER JOIN `joined_table` ON joined_table.id=table.join_id WHERE `test_column` = \'test\' LIMIT 10', (string)$select);
    }

    public function testLimitPage(): void
    {
        $select = (new Select($this->db))->from('table')->joinInner('joined_table', 'joined_table.id=table.join_id', ['id'])->where('test_column = ?', 'test')->limitPage(2, 10);
        $this->assertEqualsIgnoringCase('SELECT `table`.*, `joined_table`.`id` FROM `table` INNER JOIN `joined_table` ON joined_table.id=table.join_id WHERE `test_column` = \'test\' LIMIT 10 OFFSET 10', (string)$select);
    }

    public function testOrderBy(): void
    {
        $select = (new Select($this->db))->from('table')->order('col1');
        $this->assertEqualsIgnoringCase('SELECT `table`.* FROM `table` ORDER BY `col1`', (string)$select);
    }

    public function testGroup(): void
    {
        $select = (new Select($this->db))->from('table')->order('col1 DESC ')->group('col1');
        $this->assertEqualsIgnoringCase('SELECT `table`.* FROM `table` GROUP BY `col1` ORDER BY `col1` DESC', (string)$select);
    }

    public function testMultiJoinQueryMysql(): void
    {
        $select = (new Select($this->db))->from('res_reservation')
            ->order('start_time')
            ->joinleft('res_reservation_table', 'res_reservation_table.reservation_id=res_reservation.id', ['id AS test'])
            ->joinLeft('res_table', 'res_reservation_table.table_id=res_table.id', [])
            ->joinLeft('res_area', 'res_reservation_table.area_id=res_area.id', [])
            ->joinLeft('res_level', 'res_reservation_table.level_id=res_level.id', [])
            ->joinLeft(['area_level' => 'res_level'], 'res_area.level_id=area_level.id', []);

        $this->assertEqualsIgnoringCase(
            'SELECT `res_reservation`.*, `res_reservation_table`.`id` AS test FROM `res_reservation` ' .
            'LEFT JOIN `res_reservation_table` ON res_reservation_table.reservation_id=res_reservation.id ' .
            'LEFT JOIN `res_table` ON res_reservation_table.table_id=res_table.id ' .
            'LEFT JOIN `res_area` ON res_reservation_table.area_id=res_area.id ' .
            'LEFT JOIN `res_level` ON res_reservation_table.level_id=res_level.id ' .
            'LEFT JOIN `res_level` AS area_level ON res_area.level_id=area_level.id ORDER BY `start_time`',
            (string)$select
        );
    }

    public function testMultiInSelect()
    {
        $scopes = ['user', 'locations', 'permissions'];
        $select = (new Select($this->db))->from('bcr_scopes')
            ->where('scope_name in (?)', $scopes);
        $this->assertEqualsIgnoringCase('SELECT `bcr_scopes`.* from `bcr_scopes` where `scope_name` in (\'user\', \'locations\', \'permissions\')', (string)$select);
    }

    public function testMultiWhereQuery()
    {
        $select = (new Select($this->db))->from('bcr_scopes')
            ->where('enabled = ?', 1)
            ->where('test = ?', 2);

        $this->assertEqualsIgnoringCase('SELECT `bcr_scopes`.* from `bcr_scopes` where `enabled` = 1 and `test` = 2', (string)$select);
        $this->assertEquals([
            1,
            2
        ], array_values($select->getParameters()));
    }

    public function testProperTableInWhere()
    {
        $select = (new Select($this->db))->from('rb_customer', ['rb_customer_id as id', 'password'])
            ->joinInner('rb_customer_details', 'rb_customer.rb_details_id=rb_customer_details.rb_details_id', ['forename', 'surname', 'email'])
            ->where('rb_customer.rb_customer_id=?', 1)
            ->where('rb_customer.brand_id=?', 2);

        $this->assertEqualsIgnoringCase('SELECT rb_customer_id AS id, `rb_customer`.`password`, `rb_customer_details`.`forename`, `rb_customer_details`.`surname`, `rb_customer_details`.`email` FROM `rb_customer` INNER JOIN `rb_customer_details` ON rb_customer.rb_details_id=rb_customer_details.rb_details_id WHERE `rb_customer`.`rb_customer_id` = 1 AND `rb_customer`.`brand_id` = 2', (string)$select);
    }

    public function testCountQuery()
    {
        $select = (new Select($this->db))->from(['c' => (new Select($this->db))->from('test')->where('`column` = \'test\'')], ['COUNT(1) as cnt']);

        $this->assertEqualsIgnoringCase('SELECT COUNT(1) as cnt from (SELECT `test`.* from `test` where `column` = \'test\') AS c', (string)$select);
    }

    public function testSubQueryJoins()
    {
        $subSelect = (new Select($this->db))->from('sub')->where('test=1');

        $select = (new Select($this->db))->from('main')->joinLeft(['t' => $subSelect], 't.column=main.column', []);
        $this->assertEqualsIgnoringCase('SELECT `main`.* FROM `main` LEFT JOIN (SELECT `sub`.* from `sub` where test=1) as t on t.column=main.column', (string)$select);
    }

    public function testGreaterThanEqualTo()
    {
        $select = (new Select($this->db))->from('tfa_attempt')
            ->where('code=?', 'test')
            ->where('date_created>=?', '2023-01-01 00:00:00');
        $this->assertEqualsIgnoringCase('SELECT `tfa_attempt`.* from `tfa_attempt` where `code` = \'test\' and `date_created` >= \'2023-01-01 00:00:00\'', (string)$select);
    }

    public function testIsNull()
    {
        $select = (new Select($this->db))->from('table')
            ->where('code is null');

        $this->assertEqualsIgnoringCase('SELECT `table`.* from `table` where code is null', (string)$select);
    }

    public function testInQuery()
    {
        $select = (new Select($this->db))->from('table')
            ->where('code in (?)', [1, 2, 3]);

        $this->assertEqualsIgnoringCase('SELECT `table`.* from `table` where `code` in (1, 2, 3)', (string)$select);
    }

    public function testOrWhere()
    {
        $select = (new Select($this->db))->from('table')
            ->where('(test = ?', 1)
            ->orWHere('test = ?)', 2);

        $this->assertEqualsIgnoringCase('SELECT `table`.* from `table` where (`test` = 1 or `test` = 2)', (string)$select);
    }

    public function testExpressionColumns()
    {
        $columns = ['COALESCE(t2.col1, t3.col1) as col', 'COALESCE(t2.col2, t3.col2) as col2'];
        $select = (new Select($this->db))->from('t1', [])
            ->joinLeft('t2', 't1.first_id=t2.id', $columns)
            ->joinLeft('t3', 't1.other_id=t3.id', []);

        $this->assertEqualsIgnoringCase('SELECT COALESCE(t2.col1, t3.col1) as col, COALESCE(t2.col2, t3.col2) as col2 FROM `t1` LEFT JOIN `t2` on t1.first_id=t2.id LEFT JOIN `t3` on t1.other_id=t3.id', (string)$select);
    }

    public function testUnionSelect(): void
    {
        $union = (new Select($this->db))->from('t1')->where('test=?', 'union');
        $select = (new Select($this->db))->from('table')
            ->where('(test = ?', 1)
            ->orWhere('test = ?)', 2)
            ->union($union);
        $this->assertEqualsIgnoringCase("SELECT `table`.* FROM `table` WHERE (`test` = 1 OR `test` = 2) UNION ALL SELECT `t1`.* FROM `t1` WHERE `test` = 'union'", (string)$select);
    }

    public function testNotLike(): void
    {
        $select = (new Select($this->db))->from('t1');
        $select->where('test not like ?', 'test');

        $this->assertEqualsIgnoringCase('SELECT `t1`.* FROM `t1` WHERE `test` not like \'test\'', (string)$select);
    }

    public function testSubQueryCount(): void
    {
        $subSelect = (new Select($this->db))->from('t1')
            ->where('code not like ?', 'SKU%3P');

        $countSelect = (new Select($this->db))->from(['c' => $subSelect], ['COUNT(1) as cnt']);
        $this->assertEqualsIgnoringCase('SELECT COUNT(1) as cnt from (SELECT `t1`.* FROM `t1` WHERE `code` not like :param_1_' . $subSelect->uniqueId . ') as c', $countSelect->renderQuery());
    }
}
