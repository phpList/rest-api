<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\Dto\CreateSubscriberDto;
use PhpList\RestBundle\Subscription\Request\CreateSubscriberRequest;
use PHPUnit\Framework\TestCase;

class CreateSubscriberRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new CreateSubscriberRequest();
        $request->email = 'subscriber@example.com';
        $request->requestConfirmation = true;
        $request->htmlEmail = false;

        $dto = $request->getDto();

        $this->assertInstanceOf(CreateSubscriberDto::class, $dto);
        $this->assertEquals('subscriber@example.com', $dto->email);
        $this->assertTrue($dto->requestConfirmation);
        $this->assertFalse($dto->htmlEmail);
    }

    public function testGetDtoWithNullValues(): void
    {
        $request = new CreateSubscriberRequest();
        $request->email = 'subscriber@example.com';

        $dto = $request->getDto();

        $this->assertInstanceOf(CreateSubscriberDto::class, $dto);
        $this->assertEquals('subscriber@example.com', $dto->email);
        $this->assertNull($dto->requestConfirmation);
        $this->assertNull($dto->htmlEmail);
    }
}
