<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Provider;

use PhpList\Core\Domain\Repository\Identity\AdministratorRepository;
use PhpList\RestBundle\Entity\Dto\CursorPaginationResult;
use PhpList\RestBundle\Serializer\AdministratorNormalizer;
use PhpList\RestBundle\Serializer\CursorPaginationNormalizer;
use PhpList\RestBundle\Service\Factory\PaginationCursorRequestFactory;
use Symfony\Component\HttpFoundation\Request;

class AdministratorProvider
{
    public function __construct(
        private readonly AdministratorRepository $administratorRepository,
        private readonly AdministratorNormalizer $normalizer,
        private readonly CursorPaginationNormalizer $paginationNormalizer,
        private readonly PaginationCursorRequestFactory $paginationFactory,
    ) {
    }

    public function getPaginatedList(Request $request): array
    {
        $pagination = $this->paginationFactory->fromRequest($request);
        $lists = $this->administratorRepository->getAfterId($pagination->afterId, $pagination->limit);
        $total = $this->administratorRepository->count();

        $normalized = array_map(fn($item) => $this->normalizer->normalize($item), $lists);

        return $this->paginationNormalizer->normalize(
            new CursorPaginationResult($normalized, $pagination->limit, $total)
        );
    }
}
