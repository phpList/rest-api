<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Provider;

use PhpList\RestBundle\Entity\Dto\CursorPaginationResult;
use PhpList\RestBundle\Entity\Request\PaginationCursorRequest;
use PhpList\RestBundle\Serializer\CursorPaginationNormalizer;
use PhpList\RestBundle\Serializer\SubscriberListNormalizer;
use PhpList\RestBundle\Service\Manager\SubscriberListManager;

class SubscriberListProvider
{
    public function __construct(
        private readonly SubscriberListManager $subscriberListManager,
        private readonly SubscriberListNormalizer $normalizer,
        private readonly CursorPaginationNormalizer $paginationNormalizer
    ) {
    }

    public function getPaginatedList(PaginationCursorRequest $pagination): array
    {
        $lists = $this->subscriberListManager->getPaginated($pagination);
        $total = $this->subscriberListManager->getTotalCount();

        $normalized = array_map(fn($item) => $this->normalizer->normalize($item), $lists);

        return $this->paginationNormalizer->normalize(
            new CursorPaginationResult($normalized, $pagination->limit, $total)
        );
    }
}
