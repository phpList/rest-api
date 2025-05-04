<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Factory;

use PhpList\RestBundle\Service\Factory\PaginationCursorRequestFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class PaginationCursorRequestFactoryTest extends TestCase
{
    public function testFromRequestWithBothParams(): void
    {
        $request = new Request(query: [
            'after_id' => '10',
            'limit' => '50',
        ]);

        $factory = new PaginationCursorRequestFactory();
        $paginationRequest = $factory->fromRequest($request);

        $this->assertSame(10, $paginationRequest->afterId);
        $this->assertSame(50, $paginationRequest->limit);
    }

    public function testFromRequestWithMissingLimit(): void
    {
        $request = new Request(query: [
            'after_id' => '5',
        ]);

        $factory = new PaginationCursorRequestFactory();
        $paginationRequest = $factory->fromRequest($request);

        $this->assertSame(5, $paginationRequest->afterId);
        $this->assertSame(25, $paginationRequest->limit);
    }

    public function testFromRequestWithDefaults(): void
    {
        $request = new Request();

        $factory = new PaginationCursorRequestFactory();
        $paginationRequest = $factory->fromRequest($request);

        $this->assertSame(0, $paginationRequest->afterId);
        $this->assertSame(25, $paginationRequest->limit);
    }
}
