<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\SqlInjection;

use Pantono\Database\Tests\BaseCases\AbstractMssqlAdapterTestCase;
use Pantono\Database\Query\Insert;
use Pantono\Database\Query\Update;

class MssqlInjectionTest extends AbstractMssqlAdapterTestCase
{
    public function testTableInjection()
    {
        // Vulnerability: quoteTable only wraps in brackets, doesn't escape brackets.
        $table = 'users] (id) VALUES (1); -- ';
        $insert = new Insert($table, ['name' => 'test'], $this->db);

        $sql = $insert->renderQuery();
        $this->assertEquals('INSERT INTO [users] (id) VALUES (1); -- ] ([name]) VALUES (:name)', $sql);
    }

    public function testColumnInjection()
    {
        // Vulnerability: quoteTable (used for columns in Insert) doesn't escape brackets.
        $insert = new Insert('users', ['name]=1, [id' => 'test'], $this->db);
        $sql = $insert->renderQuery();

        $this->assertEquals('INSERT INTO [users] ([name]=1, [id]) VALUES (:name]=1, [id)', $sql);
    }

    public function testSelectWhereValueInjection()
    {
        // Vulnerability: Select::quoteValue only wraps in single quotes, doesn't escape single quotes.
        $select = $this->db->select()->from('users')->where('name = ?', "' OR 1=1");
        $sql = (string)$select;

        $this->assertStringContainsString("' OR 1=1'", $sql);
    }

    public function testOrderByInjection()
    {
        // Vulnerability: order() used to append condition directly.
        $select = $this->db->select()->from('users')->order('id; DROP TABLE users; --');
        $sql = $select->renderQuery();

        $this->assertStringNotContainsString('DROP TABLE users', $sql);
    }

    public function testUpdateWhereInjection()
    {
        $update = new Update('users', ['name' => 'test'], ['1 = 1 -- ' => 'dummy'], $this->db);
        $sql = $update->renderQuery();

        $this->assertStringContainsString('WHERE [1] = 1 --', $sql);
    }
}
