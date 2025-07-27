<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\RestBundle\Identity\Request\ValidateTokenRequest;
use PHPUnit\Framework\TestCase;

class ValidateTokenRequestTest extends TestCase
{
    public function testGetDtoReturnsSelf(): void
    {
        $request = new ValidateTokenRequest();
        $request->token = 'test-token-123';

        $dto = $request->getDto();

        $this->assertSame($request, $dto);
        $this->assertEquals('test-token-123', $dto->token);
    }
}
