<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Provider;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Model\Dto\Filter\FilterRequestInterface;
use PhpList\Core\Domain\Repository\Interfaces\PaginatableRepositoryInterface;
use PhpList\RestBundle\Entity\Dto\CursorPaginationResult;
use PhpList\RestBundle\Serializer\CursorPaginationNormalizer;
use PhpList\RestBundle\Service\Factory\PaginationCursorRequestFactory;
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

        $items = $repository->getFilteredAfterId($pagination->afterId, $pagination->limit, $filter);
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
