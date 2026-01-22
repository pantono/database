<?php

namespace Pantono\Database\Tests\BaseCases;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Adapter\MssqlDb;
use Pantono\Database\Adapter\PgsqlDb;

abstract class AbstractPgsqlAdapterTestCase extends TestCase
{
    protected PgsqlDb $db;

    public function setUp(): void
    {
        $this->db = new PgsqlDb('dsn', 'user', 'pass');
    }
}
