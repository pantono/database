<?php
declare(strict_types=1);
namespace Pantono\Database\Attributes;

#[\Attribute]
class OneToOne
{
    public string $targetModel;

    public function __construct(string $targetModel)
    {
        $this->targetModel = $targetModel;
    }
}
