<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\Core\Domain\Identity\Model\Dto\UpdateAdministratorDto;
use PhpList\RestBundle\Identity\Request\UpdateAdministratorRequest;
use PHPUnit\Framework\TestCase;

class UpdateAdministratorRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new UpdateAdministratorRequest();
        $request->administratorId = 123;
        $request->loginName = 'testuser';
        $request->password = 'password123';
        $request->email = 'test@example.com';
        $request->superAdmin = true;
        $request->privileges = [
            'subscribers' => true,
            'campaigns' => false,
            'statistics' => true,
            'settings' => false,
        ];

        $dto = $request->getDto();

        $this->assertEquals(123, $dto->administratorId);
        $this->assertEquals('testuser', $dto->loginName);
        $this->assertEquals('password123', $dto->password);
        $this->assertEquals('test@example.com', $dto->email);
        $this->assertTrue($dto->superAdmin);
        $this->assertEquals([
            'subscribers' => true,
            'campaigns' => false,
            'statistics' => true,
            'settings' => false,
        ], $dto->privileges);
    }

    public function testGetDtoWithNullValues(): void
    {
        $request = new UpdateAdministratorRequest();
        $request->administratorId = 456;

        $dto = $request->getDto();

        $this->assertEquals(456, $dto->administratorId);
        $this->assertNull($dto->loginName);
        $this->assertNull($dto->password);
        $this->assertNull($dto->email);
        $this->assertNull($dto->superAdmin);
        $this->assertEquals([], $dto->privileges);
    }
}
