<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\Filter\SubscriberFilter;
use PhpList\RestBundle\Subscription\Request\SubscribersFilterRequest;
use PHPUnit\Framework\TestCase;

class SubscribersFilterRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDtoWithAllValues(): void
    {
        $request = new SubscribersFilterRequest();
        $request->isConfirmed = 'true';
        $request->isBlacklisted = 'false';
        $request->sortBy = 'email';
        $request->sortDirection = 'desc';
        $request->findColumn = 'email';
        $request->findValue = 'test@example.com';

        $dto = $request->getDto();

        $this->assertInstanceOf(SubscriberFilter::class, $dto);
        $this->assertTrue($dto->getIsConfirmed());
        $this->assertFalse($dto->getIsBlacklisted());
        $this->assertEquals('email', $dto->getSortBy());
        $this->assertEquals('desc', $dto->getSortDirection());
        $this->assertEquals('email', $dto->getFindColumn());
        $this->assertEquals('test@example.com', $dto->getFindValue());
    }

    public function testGetDtoWithBooleanValues(): void
    {
        $request = new SubscribersFilterRequest();
        $request->isConfirmed = true;
        $request->isBlacklisted = false;

        $dto = $request->getDto();

        $this->assertTrue($dto->getIsConfirmed());
        $this->assertFalse($dto->getIsBlacklisted());
    }

    public function testGetDtoWithNumericStringValues(): void
    {
        $request = new SubscribersFilterRequest();
        $request->isConfirmed = '1';
        $request->isBlacklisted = '0';

        $dto = $request->getDto();

        $this->assertTrue($dto->getIsConfirmed());
        $this->assertFalse($dto->getIsBlacklisted());
    }

    public function testGetDtoReturnsCorrectDtoWithNullValues(): void
    {
        $request = new SubscribersFilterRequest();

        $dto = $request->getDto();

        $this->assertInstanceOf(SubscriberFilter::class, $dto);
        $this->assertNull($dto->getIsConfirmed());
        $this->assertNull($dto->getIsBlacklisted());
        $this->assertNull($dto->getSortBy());
        $this->assertNull($dto->getSortDirection());
        $this->assertNull($dto->getFindColumn());
        $this->assertNull($dto->getFindValue());
    }

    public function testGetDtoNullsFindColumnAndValueWhenOnlyValueProvided(): void
    {
        $request = new SubscribersFilterRequest();
        $request->findColumn = null;
        $request->findValue = 'test@example.com';

        $dto = $request->getDto();

        $this->assertInstanceOf(SubscriberFilter::class, $dto);
        $this->assertNull($dto->getFindColumn());
        $this->assertNull($dto->getFindValue());
    }
}
