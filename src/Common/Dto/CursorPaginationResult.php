<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\Dto;

class CursorPaginationResult
{
    public function __construct(
        private readonly array $items,
        private readonly int $limit,
        private readonly int $total,
    ) {
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getTotal(): int
    {
        return $this->total;
    }
}
