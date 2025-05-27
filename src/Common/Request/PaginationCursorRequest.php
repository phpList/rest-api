<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\Request;

use Symfony\Component\HttpFoundation\Request;

class PaginationCursorRequest
{
    public ?int $afterId;
    public int $limit;

    public function __construct(?int $afterId = null, int $limit = 25)
    {
        $this->afterId = $afterId;
        $this->limit = min(100, max(1, $limit));
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->query->get('after_id') ? (int)$request->query->get('after_id') : 0,
            $request->query->getInt('limit', 25)
        );
    }
}
