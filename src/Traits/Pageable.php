<?php

declare(strict_types=1);

namespace Pantono\Database\Traits;

trait Pageable
{
    private int $page = 1;

    private int $perPage = 50;

    private int $totalResults = 0;

    public function getPage(): int
    {
        if ($this->getTotalResults() === 0) {
            $this->setPage(1);
        } else {
            $paginatedResults = ($this->getTotalResults() / $this->getPerPage());
            if ($this->page > $paginatedResults) {
                $this->setPage((int)ceil($paginatedResults));
            }
        }

        return $this->page;
    }

    public function setPage(int $page): void
    {
        $this->page = $page;
    }

    public function getPerPage(): int
    {
        return $this->perPage;
    }

    public function setPerPage(int $perPage): void
    {
        $this->perPage = $perPage;
    }

    public function getTotalResults(): int
    {
        return $this->totalResults;
    }

    public function setTotalResults(int $totalResults): void
    {
        $this->totalResults = $totalResults;
    }
}
