<?php

declare(strict_types=1);

namespace Pantono\Database\Tests\Model;

use Pantono\Database\Traits\SavableModel;

class TestLinkedModel
{
    use SavableModel;

    private LinkedModel $model;

    public function getModel(): LinkedModel
    {
        return $this->model;
    }

    public function setModel(LinkedModel $model): void
    {
        $this->model = $model;
    }
}
