<?php

declare(strict_types=1);

namespace Pantono\Database\Tests;

use PHPUnit\Framework\TestCase;
use Pantono\Database\Tests\Model\LinkedModel;
use Pantono\Database\Tests\Model\TestLinkedModel;
use Pantono\Database\Tests\Model\TestModelDateTime;
use Pantono\Database\Tests\Model\TestModelDifferentField;
use Pantono\Database\Tests\Model\TestModelJson;
use Pantono\Database\Tests\Model\TestModelNoSave;
use Pantono\Database\Tests\Model\TestModelSimple;

class SavableModelTest extends TestCase
{
    public function testSimpleModelSaveData(): void
    {
        $class = new TestModelSimple();
        $this->assertEquals(['string' => 'test'], $class->getAllData());
    }

    public function testJsonModelSave(): void
    {
        $class = new TestModelJson();
        $class->setData(['test' => 1, 'test2' => '2']);
        $this->assertEquals(['data' => '{"test":1,"test2":"2"}'], $class->getAllData());
    }

    public function testDateModelSave(): void
    {
        $class = new TestModelDateTime();
        $class->setDate(new \DateTime('2023-01-01 09:00:00'));
        $this->assertEquals(['date' => '2023-01-01 09:00:00'], $class->getAllData());
    }

    public function testNoSaveModel(): void
    {
        $class = new TestModelNoSave();
        $class->setNumber(2);
        $class->setString('test');
        $this->assertEquals(['number' => 2], $class->getAllData());
    }

    public function testDifferentFieldModel(): void
    {
        $class = new TestModelDifferentField();
        $class->setString('test');
        $this->assertEquals(['test' => 'test'], $class->getAllData());
    }

    public function testLinkedModel(): void
    {
        $linked = new LinkedModel();
        $linked->setId(2);
        $class = new TestLinkedModel();
        $class->setModel($linked);
        $this->assertEquals(['model' => 2], $class->getAllData());
    }
}
