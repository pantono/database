<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\Model;

class LinkedModel
{
    private int $id = 1;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }
}
