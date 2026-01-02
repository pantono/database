<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Query\Update;

class QueryUpdateTest extends TestCase
{
    public function testSimpleUpdate()
    {
        $update = new Update('table', ['test' => 2], ['id=?' => 1]);
        $this->assertEquals('UPDATE table SET `test` = :test WHERE `id` = :param1', $update->renderQuery());
        $this->assertEquals([':param1' => 1, ':test' => 2], $update->getParameters());
    }

    public function testUpdateNoParams()
    {
        $update = new Update('table', ['test' => 2], ['id in (1,2)']);
        $this->assertEquals('UPDATE table SET `test` = :test WHERE `id` in (1,2)', $update->renderQuery());
        $this->assertEquals([':test' => 2], $update->getParameters());
    }

    public function testUpdateInParams()
    {
        $update = new Update('table', ['test' => 2], ['id in (?)' => [1, 2]]);
        $this->assertEquals('UPDATE table SET `test` = :test WHERE `id` in (:param1, :param2)', $update->renderQuery());
        $this->assertEquals([':test' => 2, ':param1' => 1, ':param2' => 2], $update->getParameters());
    }

    public function testMultiColumnUpdate()
    {
        $update = new Update('table', ['test' => 2, 'test2' => 4], ['id=?' => 1]);
        $this->assertEquals('UPDATE table SET `test` = :test, `test2` = :test2 WHERE `id` = :param1', $update->renderQuery());
        $this->assertEquals([':param1' => 1, ':test' => 2, ':test2' => 4], $update->getParameters());
    }

    public function testMultiWhereUpdate()
    {
        $update = new Update('table', ['test' => 2, 'test2' => 4], ['id=?' => 1, 'id2=?' => 2]);
        $this->assertEquals('UPDATE table SET `test` = :test, `test2` = :test2 WHERE `id` = :param1 AND `id2` = :param2', $update->renderQuery());
        $this->assertEquals([':param1' => 1, ':param2' => 2, ':test' => 2, ':test2' => 4], $update->getParameters());
    }
}
