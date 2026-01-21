<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Query\Insert;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Adapter\PgsqlDb;

class QueryInsertTest extends TestCase
{
    public function testSingleColumnInsert()
    {
        $insert = new Insert('table', ['test' => 1], MysqlDb::class);

        $this->assertEqualsIgnoringCase('INSERT into `table` (`test`) VALUES (:test)', $insert->renderQuery());
    }

    public function testSingleColumnInsertPgsql()
    {
        $insert = new Insert('table', ['test' => 1], PgsqlDb::class);

        $this->assertEqualsIgnoringCase('INSERT into "table" ("test") VALUES (:test)', $insert->renderQuery());
    }

    public function testMultiColumnInsert()
    {
        $insert = new Insert('table', ['test' => 1, 'test2' => 3], MysqlDb::class);

        $this->assertEqualsIgnoringCase('INSERT into `table` (`test`, `test2`) VALUES (:test, :test2)', $insert->renderQuery());
    }
}
