<?php

namespace Pantono\Database\Tests\BaseCases;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Adapter\MssqlDb;

abstract class AbstractMssqlAdapterTestCase extends TestCase
{
    protected MssqlDb $db;

    public function setUp(): void
    {
        $this->db = new MssqlDb('dsn', 'user', 'pass');
    }
}
