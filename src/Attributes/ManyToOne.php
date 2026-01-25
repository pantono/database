<?php
declare(strict_types=1);

namespace Pantono\Database\Attributes;

#[\Attribute]
class ManyToOne
{
    public string $targetModel;
    public string $inversedBy;

    public function __construct(string $targetModel, string $inversedBy)
    {
        $this->targetModel = $targetModel;
        $this->inversedBy = $inversedBy;
    }
}
