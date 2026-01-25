<?php

namespace Pantono\Database\Tests\Model;

use Pantono\Contracts\Attributes\DatabaseTable;
use Pantono\Contracts\Application\Interfaces\SavableInterface;
use Pantono\Database\Traits\SavableModel;

#[DatabaseTable('test_table', 'id')]
class AttributeSaveModel implements SavableInterface
{
    use SavableModel;

    private ?int $id = null;
    private string $name;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): void
    {
        $this->id = $id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
