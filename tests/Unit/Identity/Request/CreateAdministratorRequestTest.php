<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\RestBundle\Identity\Request\CreateAdministratorRequest;
use PHPUnit\Framework\TestCase;

class CreateAdministratorRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new CreateAdministratorRequest();
        $request->loginName = 'testuser';
        $request->password = 'password123';
        $request->email = 'test@example.com';
        $request->superUser = true;

        $dto = $request->getDto();

        $this->assertEquals('testuser', $dto->loginName);
        $this->assertEquals('password123', $dto->password);
        $this->assertEquals('test@example.com', $dto->email);
        $this->assertTrue($dto->isSuperUser);
    }

    public function testGetDtoWithDefaultSuperUserValue(): void
    {
        $request = new CreateAdministratorRequest();
        $request->loginName = 'testuser';
        $request->password = 'password123';
        $request->email = 'test@example.com';

        $dto = $request->getDto();

        $this->assertEquals('testuser', $dto->loginName);
        $this->assertEquals('password123', $dto->password);
        $this->assertEquals('test@example.com', $dto->email);
        $this->assertFalse($dto->isSuperUser);
    }
}
