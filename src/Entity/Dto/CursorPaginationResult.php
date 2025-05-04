<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Dto;

class CursorPaginationResult
{
    public function __construct(
        public readonly array $items,
        public readonly int $limit,
        public readonly int $total,
    ) {
    }
}
