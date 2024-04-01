<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\Model;

use Pantono\Database\Traits\SavableModel;
use Pantono\Contracts\Attributes\Filter;

class TestModelJson
{
    use SavableModel;

    #[Filter('json_decode')]
    private array $data;

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
