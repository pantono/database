<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\SqlInjection;

use Pantono\Database\Tests\BaseCases\AbstractMysqlAdapterTestCase;
use Pantono\Database\Query\Insert;
use Pantono\Database\Query\Update;

class MysqlInjectionTest extends AbstractMysqlAdapterTestCase
{
    public function testTableInjection()
    {
        // Vulnerability: quoteTable only wraps in backticks, doesn't escape backticks.
        $table = 'users` (id) VALUES (1); -- ';
        $insert = new Insert($table, ['name' => 'test'], $this->db);

        $sql = $insert->renderQuery();
        // Expected "safe" output: `users` (id) VALUES (1); -- `
        // Actual output will allow breaking out of backticks
        $this->assertEquals('INSERT INTO `users` (id) VALUES (1); -- ` (`name`) VALUES (:name)', $sql);
    }

    public function testColumnInjection()
    {
        // Vulnerability: quoteTable (used for columns in Insert) doesn't escape backticks.
        $insert = new Insert('users', ['name`=1, `id' => 'test'], $this->db);
        $sql = $insert->renderQuery();

        $this->assertEquals('INSERT INTO `users` (`name`=1, `id`) VALUES (:name`=1, `id)', $sql);
    }

    public function testSelectWhereValueInjection()
    {
        // Vulnerability: Select::quoteValue only wraps in single quotes, doesn't escape single quotes.
        $select = $this->db->select()->from('users')->where('name = ?', "' OR 1=1");
        // __toString() uses quoteValue to replace parameters
        $sql = (string)$select;

        // SqlFormatter might change spacing/casing, but the injection should be there
        $this->assertStringContainsString("' OR 1=1'", $sql);
    }

    public function testOrderByInjection()
    {
        // Vulnerability: order() used to append condition directly.
        // After fix, it should only allow safe column names and directions.
        $select = $this->db->select()->from('users')->order('id; DROP TABLE users; --');
        $sql = $select->renderQuery();

        $this->assertStringNotContainsString('DROP TABLE users', $sql);
    }

    public function testGroupByInjection()
    {
        // Vulnerability: group() used to append spec directly.
        // After fix, it should only allow safe column names.
        $select = $this->db->select()->from('users')->group('id; DROP TABLE users; --');
        $sql = $select->renderQuery();

        $this->assertStringNotContainsString('DROP TABLE users', $sql);
    }

    public function testUpdateWhereInjection()
    {
        // Vulnerability: Update::formatInput uses regex that can be bypassed or manipulated.
        // It also doesn't escape backticks in column names.
        $update = new Update('users', ['name' => 'test'], ['1 = 1 -- ' => 'dummy'], $this->db);
        $sql = $update->renderQuery();

        $this->assertStringContainsString('WHERE `1` = 1 --', $sql);
    }
}
