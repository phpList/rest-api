<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\Dto\UpdateSubscriberDto;
use PhpList\RestBundle\Subscription\Request\UpdateSubscriberRequest;
use PHPUnit\Framework\TestCase;

class UpdateSubscriberRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new UpdateSubscriberRequest();
        $request->email = 'subscriber@example.com';
        $request->confirmed = true;
        $request->blacklisted = false;
        $request->htmlEmail = true;
        $request->disabled = false;
        $request->additionalData = 'Some additional data';

        $dto = $request->getDto();

        $this->assertInstanceOf(UpdateSubscriberDto::class, $dto);
        $this->assertEquals('subscriber@example.com', $dto->email);
        $this->assertTrue($dto->confirmed);
        $this->assertFalse($dto->blacklisted);
        $this->assertTrue($dto->htmlEmail);
        $this->assertFalse($dto->disabled);
        $this->assertEquals('Some additional data', $dto->additionalData);
    }
}
