<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\RestBundle\Identity\Request\ResetPasswordRequest;
use PHPUnit\Framework\TestCase;

class ResetPasswordRequestTest extends TestCase
{
    public function testGetDtoReturnsSelf(): void
    {
        $request = new ResetPasswordRequest();
        $request->token = 'test-token-123';
        $request->newPassword = 'newSecurePassword123';

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertEquals('test-token-123', $dto->token);
        $this->assertEquals('newSecurePassword123', $dto->newPassword);
    }
}
