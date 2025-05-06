<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Helpers;

use Doctrine\ORM\EntityRepository;
use PhpList\Core\Domain\Model\Dto\Filter\FilterRequestInterface;
use PhpList\Core\Domain\Repository\Interfaces\PaginatableRepositoryInterface;

class DummyPaginatableRepository extends EntityRepository implements PaginatableRepositoryInterface
{
    public function getFilteredAfterId(int $lastId, int $limit, ?FilterRequestInterface $filter = null): array
    {
        return [
            (object)['id' => 1, 'name' => 'Item 1'],
            (object)['id' => 2, 'name' => 'Item 2'],
        ];
    }

    public function count(array $criteria = []): int
    {
        return 10;
    }
}
