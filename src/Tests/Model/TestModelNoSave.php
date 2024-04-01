<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\NoSave;

class TestModelNoSave
{
    use SavableModel;

    #[NoSave]
    private string $string = 'test';
    private int $number;

    public function getString(): string
    {
        return $this->string;
    }

    public function setString(string $string): void
    {
        $this->string = $string;
    }

    public function getNumber(): int
    {
        return $this->number;
    }

    public function setNumber(int $number): void
    {
        $this->number = $number;
    }
}
