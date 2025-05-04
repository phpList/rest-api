<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Factory;

use PhpList\RestBundle\Entity\Request\PaginationCursorRequest;
use Symfony\Component\HttpFoundation\Request;

class PaginationCursorRequestFactory
{
    public function fromRequest(Request $request): PaginationCursorRequest
    {
        return new PaginationCursorRequest(
            $request->query->getInt('after_id'),
            $request->query->getInt('limit', 25)
        );
    }
}
