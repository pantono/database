<?php

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Tests\BaseCases\AbstractMysqlAdapterTestCase;
use Pantono\Database\Tests\Repository\DummyRepository;
use Pantono\Database\Tests\Model\AttributeSaveModel;
use PDO;
use Pantono\Database\Adapter\MysqlDb;
use Pantono\Database\Query\Select\Select;
use Pantono\Database\Query\Select\DriverSpecific\MysqlSelect;

class RepositorySavableModelTest extends AbstractMysqlAdapterTestCase
{
    public function testAttributeSaveNew()
    {
        $mock = $this->getMockBuilder(MysqlDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $repo = new DummyRepository($mock);
        $mock->expects($this->once())
            ->method('insert')
            ->with('test_table', ['name' => 'test'])
            ->willReturn(1);
        $mock->expects($this->once())
            ->method('lastInsertId')
            ->willReturn(1);
        $model = new AttributeSaveModel();
        $model->setName('test');
        $repo->saveModel($model);
        $this->assertEquals(1, $model->getId());
    }

    public function testAttributeSaveUpdate()
    {
        $mock = $this->getMockBuilder(MysqlDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dummySelect = new MysqlSelect($mock);
        $repo = new DummyRepository($mock);
        $mock->expects($this->once())
            ->method('select')
            ->willReturn($dummySelect);
        $mock->expects($this->once())
            ->method('fetchRow')
            ->with($dummySelect)
            ->willReturn(['id' => 1, 'name' => 'test2']);
        $mock->expects($this->once())
            ->method('update')
            ->with('test_table', ['name' => 'test'], ['id=?' => 1])
            ->willReturn(1);
        $mock->expects($this->never())
            ->method('lastInsertId')
            ->willReturn(1);
        $model = new AttributeSaveModel();
        $model->setId(1);
        $model->setName('test');
        $repo->saveModel($model);
        $this->assertEquals(1, $model->getId());
    }

    public function testLookupRecordSingle()
    {
        $mock = $this->getMockBuilder(MysqlDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo = new DummyRepository($mock);
        $dummySelect = new MysqlSelect($mock);
        $model = new AttributeSaveModel();
        $mock->expects($this->once())
            ->method('select')
            ->willReturn($dummySelect);
        $mock->expects($this->once())
            ->method('fetchRow')
            ->willReturn(['id' => 1]);
        $response = $repo->lookupRecord($model, 1);
        $this->assertEquals(['id' => 1], $response);
    }

    public function testLookupRecordMultiple()
    {
        $mock = $this->getMockBuilder(MysqlDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $repo = new DummyRepository($mock);
        $dummySelect = new MysqlSelect($mock);
        $model = new AttributeSaveModel();
        $mock->expects($this->once())
            ->method('select')
            ->willReturn($dummySelect);
        $mock->expects($this->once())
            ->method('fetchAll')
            ->willReturn([
                ['id' => 1], ['id' => 2]
            ]);
        $response = $repo->lookupRecords($model, [1, 2, 3]);
        $this->assertEquals([
            ['id' => 1], ['id' => 2]
        ], $response);
    }
}
