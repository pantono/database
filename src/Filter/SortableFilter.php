<?php

declare(strict_types=1);

namespace Pantono\Database\Filter;

use RuntimeException;
use Pantono\Contracts\Application\Interfaces\SortableInterface;

abstract class SortableFilter implements SortableInterface
{
    private ?string $sortBy = null;

    private string $sortDirection = 'ASC';

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function setSortBy(?string $sortBy): void
    {
        if ($sortBy !== null && !in_array($sortBy, $this->getSortableFields())) {
            throw new RuntimeException('Invalid sort field');
        }

        $this->sortBy = $sortBy;
    }

    public function getSortDirection(): string
    {
        return $this->sortDirection;
    }

    public function setSortDirection(string $sortDirection): void
    {
        $sortDirection = strtoupper($sortDirection);
        if (!in_array($sortDirection, ['ASC', 'DESC'], true)) {
            throw new RuntimeException('Invalid sort direction');
        }

        $this->sortDirection = $sortDirection;
    }

    public function setSort(?string $sortBy, string $sortDirection = 'ASC'): void
    {
        $this->setSortBy($sortBy);
        $this->setSortDirection($sortDirection);
    }

    /**
     * @return array<int,string>
     */
    abstract public function getSortableFields(): array;

    public function getSortColumn(): ?string
    {
        $sortBy = $this->getSortBy();
        if ($sortBy === null) {
            return null;
        }

        return $this->getSortableFields()[$sortBy] ?? null;
    }
}
