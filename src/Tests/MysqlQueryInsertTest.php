<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Query\Insert;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Adapter\PgsqlDb;
use Pantono\Database\Tests\BaseCases\AbstractMysqlAdapterTestCase;

class MysqlQueryInsertTest extends AbstractMysqlAdapterTestCase
{
    public function testSingleColumnInsertMysql()
    {
        $insert = new Insert('table', ['test' => 1], $this->db);

        $this->assertEqualsIgnoringCase('INSERT into `table` (`test`) VALUES (:test)', $insert->renderQuery());
    }

    public function testMultiColumnInsertMysql()
    {
        $insert = new Insert('table', ['test' => 1, 'test2' => 3], $this->db);

        $this->assertEqualsIgnoringCase('INSERT into `table` (`test`, `test2`) VALUES (:test, :test2)', $insert->renderQuery());
    }
}
