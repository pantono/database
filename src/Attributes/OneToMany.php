<?php
declare(strict_types=1);

namespace Pantono\Database\Attributes;

#[\Attribute]
class OneToMany
{
    public string $targetModel;
    public string $mappedBy;

    public function __construct(string $targetModel, string $mappedBy)
    {
        $this->targetModel = $targetModel;
        $this->mappedBy = $mappedBy;
    }
}
