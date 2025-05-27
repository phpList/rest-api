<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\Dto\CreateSubscriberListDto;
use PhpList\RestBundle\Subscription\Request\CreateSubscriberListRequest;
use PHPUnit\Framework\TestCase;

class CreateSubscriberListRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new CreateSubscriberListRequest();
        $request->name = 'Test List';
        $request->public = true;
        $request->listPosition = 5;
        $request->description = 'Test description';

        $dto = $request->getDto();

        $this->assertInstanceOf(CreateSubscriberListDto::class, $dto);
        $this->assertEquals('Test List', $dto->name);
        $this->assertTrue($dto->isPublic);
        $this->assertEquals(5, $dto->listPosition);
        $this->assertEquals('Test description', $dto->description);
    }

    public function testGetDtoWithDefaultValues(): void
    {
        $request = new CreateSubscriberListRequest();
        $request->name = 'Test List';

        $dto = $request->getDto();

        $this->assertInstanceOf(CreateSubscriberListDto::class, $dto);
        $this->assertEquals('Test List', $dto->name);
        $this->assertFalse($dto->isPublic);
        $this->assertNull($dto->listPosition);
        $this->assertNull($dto->description);
    }
}
