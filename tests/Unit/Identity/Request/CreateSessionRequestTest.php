<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\RestBundle\Identity\Request\CreateSessionRequest;
use PHPUnit\Framework\TestCase;

class CreateSessionRequestTest extends TestCase
{
    public function testGetDtoReturnsSelf(): void
    {
        $request = new CreateSessionRequest();
        $request->loginName = 'testuser';
        $request->password = 'password123';

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertEquals('testuser', $dto->loginName);
        $this->assertEquals('password123', $dto->password);
    }
}
