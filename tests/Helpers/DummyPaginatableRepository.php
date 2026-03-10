<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Helpers;

use Doctrine\ORM\EntityRepository;
use PhpList\Core\Domain\Common\Model\Filter\FilterRequestInterface;
use PhpList\Core\Domain\Common\Model\PaginatedResult;
use PhpList\Core\Domain\Common\Repository\Interfaces\PaginatableRepositoryInterface;

class DummyPaginatableRepository extends EntityRepository implements PaginatableRepositoryInterface
{
    public function getFilteredAfterId(int $lastId, int $limit, ?FilterRequestInterface $filter = null): PaginatedResult
    {
        return new PaginatedResult(
            [
                new DummyDomainModel(1, 'Item 1'),
                new DummyDomainModel(2, 'Item 2'),
            ],
            2,
            10,
            2,
        );
    }
}
