<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\RestBundle\Subscription\Request\SubscriptionRequest;
use PHPUnit\Framework\TestCase;

class SubscriptionRequestTest extends TestCase
{
    public function testGetDtoReturnsSelf(): void
    {
        $request = new SubscriptionRequest();
        $request->emails = ['test1@example.com', 'test2@example.com'];

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertEquals(['test1@example.com', 'test2@example.com'], $dto->emails);
    }

    public function testGetDtoWithEmptyEmails(): void
    {
        $request = new SubscriptionRequest();

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertEquals([], $dto->emails);
    }
}
