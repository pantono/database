<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\FieldName;

class TestModelDifferentField
{
    use SavableModel;

    #[FieldName('test')]
    private string $string;

    public function getString(): string
    {
        return $this->string;
    }

    public function setString(string $string): void
    {
        $this->string = $string;
    }
}
