<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\Model;

use Pantono\Database\Traits\SavableModel;

class TestModelDateTime
{
    use SavableModel;

    private \DateTimeInterface $date;

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): void
    {
        $this->date = $date;
    }
}
