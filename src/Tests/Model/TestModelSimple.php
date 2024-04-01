<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\Model;

use Pantono\Database\Traits\SavableModel;

class TestModelSimple
{
    use SavableModel;

    private string $string = 'test';

    public function getString(): string
    {
        return $this->string;
    }
}
