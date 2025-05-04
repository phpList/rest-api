<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Serializer;

use PhpList\RestBundle\Entity\Dto\CursorPaginationResult;
use PhpList\RestBundle\Serializer\CursorPaginationNormalizer;
use PHPUnit\Framework\TestCase;

class CursorPaginationNormalizerTest extends TestCase
{
    public function testNormalizeWithItems(): void
    {
        $items = [
            ['id' => 1, 'value' => 'A'],
            ['id' => 2, 'value' => 'B'],
        ];

        $paginationResult = new CursorPaginationResult($items, limit: 2, total: 10);
        $normalizer = new CursorPaginationNormalizer();

        $result = $normalizer->normalize($paginationResult);

        $this->assertIsArray($result);
        $this->assertEquals($items, $result['items']);
        $this->assertEquals([
            'total' => 10,
            'limit' => 2,
            'has_more' => true,
            'next_cursor' => 2,
        ], $result['pagination']);
    }

    public function testNormalizeWithFewerItemsThanLimit(): void
    {
        $items = [
            ['id' => 5, 'value' => 'X'],
        ];

        $paginationResult = new CursorPaginationResult($items, limit: 5, total: 3);
        $normalizer = new CursorPaginationNormalizer();

        $result = $normalizer->normalize($paginationResult);

        $this->assertFalse($result['pagination']['has_more']);
        $this->assertEquals(5, $result['pagination']['next_cursor']);
    }

    public function testNormalizeWithEmptyItems(): void
    {
        $paginationResult = new CursorPaginationResult([], limit: 5, total: 0);
        $normalizer = new CursorPaginationNormalizer();

        $result = $normalizer->normalize($paginationResult);

        $this->assertSame([], $result['items']);
        $this->assertSame([
            'total' => 0,
            'limit' => 5,
            'has_more' => false,
            'next_cursor' => null,
        ], $result['pagination']);
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new CursorPaginationNormalizer();

        $dto = new CursorPaginationResult([], 0, 10);
        $this->assertTrue($normalizer->supportsNormalization($dto));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
