<?php

namespace Pantono\Database\Tests\BaseCases;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Adapter\MysqlDb;

abstract class AbstractMysqlAdapterTestCase extends TestCase
{
    protected MysqlDb $db;

    public function setUp(): void
    {
        $this->db = new MysqlDb('dsn', 'user', 'pass');
    }
}
