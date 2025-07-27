<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\RestBundle\Identity\Request\RequestPasswordResetRequest;
use PHPUnit\Framework\TestCase;

class RequestPasswordResetRequestTest extends TestCase
{
    public function testGetDtoReturnsSelf(): void
    {
        $request = new RequestPasswordResetRequest();
        $request->email = 'test@example.com';

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertEquals('test@example.com', $dto->email);
    }
}
