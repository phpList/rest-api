<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Common\Service\Provider;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Common\Model\Filter\FilterRequestInterface;
use PhpList\Core\Domain\Common\Repository\Interfaces\PaginatableRepositoryInterface;
use PhpList\RestBundle\Common\Dto\CursorPaginationResult;
use PhpList\RestBundle\Common\Serializer\CursorPaginationNormalizer;
use PhpList\RestBundle\Common\Service\Factory\PaginationCursorRequestFactory;
use RuntimeException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class PaginatedDataProvider
{
    public function __construct(
        private readonly CursorPaginationNormalizer $paginationNormalizer,
        private readonly PaginationCursorRequestFactory $paginationFactory,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function getPaginatedList(
        Request $request,
        NormalizerInterface $normalizer,
        string $className,
        FilterRequestInterface $filter = null
    ): array {
        $pagination = $this->paginationFactory->fromRequest($request);

        $repository = $this->entityManager->getRepository($className);

        if (!$repository instanceof PaginatableRepositoryInterface) {
            throw new RuntimeException('Repository not found');
        }

        $items = $repository->getFilteredAfterId(
            lastId: $pagination->afterId,
            limit: $pagination->limit,
            filter: $filter,
        );
        $total = $repository->count();

        $normalizedItems = array_map(
            fn($item) => $normalizer->normalize($item, 'json'),
            $items
        );

        return $this->paginationNormalizer->normalize(
            new CursorPaginationResult($normalizedItems, $pagination->limit, $total)
        );
    }
}
