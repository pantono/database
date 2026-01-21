<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Query\Delete;
use Pantono\Database\Adapter\MysqlDb;

class QueryDeleteTest extends TestCase
{
    public function testSimpleDelete()
    {
        $update = new Delete('table', ['test=?' => 2], MysqlDb::class);
        $this->assertEquals('DELETE FROM `table` WHERE `test` = :param1', $update->renderQuery());
        $this->assertEquals([':param1' => 2], $update->getParameters());
    }

    public function testMultiColumnDelete()
    {
        $update = new Delete('table', ['test=?' => 2, 'test3=?' => 4], MysqlDb::class);
        $this->assertEquals('DELETE FROM `table` WHERE `test` = :param1 AND `test3` = :param2', $update->renderQuery());
        $this->assertEquals([':param1' => 2, ':param2' => 4], $update->getParameters());
    }

    public function testInDelete()
    {
        $update = new Delete('table', ['test in (?)' => [2, 4, 5]], MysqlDb::class);
        $this->assertEquals('DELETE FROM `table` WHERE `test` in (:param1, :param2, :param3)', $update->renderQuery());
        $this->assertEquals([':param1' => 2, ':param2' => 4, ':param3' => 5], $update->getParameters());
    }

    public function testInDeleteNoParams()
    {
        $update = new Delete('table', ['test in (1,2,3)'], MysqlDb::class);
        $this->assertEquals('DELETE FROM `table` WHERE `test` in (1,2,3)', $update->renderQuery());
        $this->assertEquals([], $update->getParameters());
    }
}
