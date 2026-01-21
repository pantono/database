<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Query\Select\DriverSpecific\PgsqlSelect;

class PgsqlQuerySelectTest extends TestCase
{
    public function testSimpleSelectPgsql(): void
    {
        $select = (new PgsqlSelect())->from('table')->where('test_column = ?', 'test');

        $this->assertEqualsIgnoringCase('SELECT "table".* FROM "table" WHERE "test_column" = \'test\'', (string)$select);
    }

    public function testLimitPgsql(): void
    {
        $select = (new PgsqlSelect())->from('table')->limit(10);
        $this->assertEqualsIgnoringCase('SELECT "table".* FROM "table" LIMIT 10', (string)$select);
    }

    public function testLimitPagePgsql(): void
    {
        $select = (new PgsqlSelect())->from('table')->limitPage(2, 10);
        $this->assertEqualsIgnoringCase('SELECT "table".* FROM "table" LIMIT 10 OFFSET 10', (string)$select);
    }
}
