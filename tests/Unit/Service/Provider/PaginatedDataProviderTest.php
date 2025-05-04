<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Provider;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\RestBundle\Entity\Dto\CursorPaginationResult;
use PhpList\RestBundle\Entity\Request\PaginationCursorRequest;
use PhpList\RestBundle\Service\Factory\PaginationCursorRequestFactory;
use PhpList\RestBundle\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Serializer\CursorPaginationNormalizer;
use PhpList\RestBundle\Tests\Helpers\DummyPaginatableRepository;
use PhpList\RestBundle\Tests\Helpers\DummyRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use RuntimeException;

class PaginatedDataProviderTest extends TestCase
{
    public function testGetPaginatedListSuccess(): void
    {
        $request = new Request(query: [
            'after_id' => 0,
            'limit' => 2,
        ]);

        $paginationFactory = $this->createMock(PaginationCursorRequestFactory::class);
        $paginationFactory->method('fromRequest')
            ->willReturn(new PaginationCursorRequest(0, 2));

        $entityManager = $this->createMock(EntityManagerInterface::class);

        $repository = $this->createMock(DummyPaginatableRepository::class);
        $entityManager->method('getRepository')->willReturn($repository);
        $repository->expects($this->once())
            ->method('getFilteredAfterId')
            ->with(0, 2)
            ->willReturn([
                (object)['id' => 1, 'name' => 'Item 1'],
                (object)['id' => 2, 'name' => 'Item 2'],
            ]);

        $repository->expects($this->once())
            ->method('count')
            ->willReturn(10);

        $entityManager->method('getRepository')
            ->willReturn($repository);

        $normalizer = $this->createMock(NormalizerInterface::class);
        $normalizer->method('normalize')
            ->willReturnCallback(fn($item) => (array)$item);

        $paginationNormalizer = $this->createMock(CursorPaginationNormalizer::class);
        $paginationNormalizer->expects($this->once())
            ->method('normalize')
            ->with($this->isInstanceOf(CursorPaginationResult::class))
            ->willReturn(['items' => [], 'pagination' => []]);

        $provider = new PaginatedDataProvider($paginationNormalizer, $paginationFactory, $entityManager);

        $result = $provider->getPaginatedList($request, $normalizer, 'Some\\Entity\\Class');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('items', $result);
        $this->assertArrayHasKey('pagination', $result);
    }

    public function testThrowsIfRepositoryIsNotPaginatable(): void
    {
        $request = new Request();

        $paginationFactory = $this->createMock(PaginationCursorRequestFactory::class);
        $paginationFactory->method('fromRequest')
            ->willReturn(new PaginationCursorRequest(0, 10));

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $repository = $this->createMock(DummyRepository::class);
        $entityManager->method('getRepository')->willReturn($repository);

        $normalizer = $this->createMock(NormalizerInterface::class);
        $paginationNormalizer = $this->createMock(CursorPaginationNormalizer::class);

        $provider = new PaginatedDataProvider($paginationNormalizer, $paginationFactory, $entityManager);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Repository not found');

        $provider->getPaginatedList($request, $normalizer, 'NonPaginatableClass');
    }
}
