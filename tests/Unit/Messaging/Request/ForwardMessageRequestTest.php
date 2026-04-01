<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\RestBundle\Messaging\Request\ForwardMessageRequest;
use PHPUnit\Framework\TestCase;

class ForwardMessageRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectArray(): void
    {
        $request = new ForwardMessageRequest();

        $request->recipients = ['friend1@example.com', 'friend2@example.com'];
        $request->uid = 'fwd-123e4567-e89b-12d3-a456-426614174000';
        $request->note = 'Thought you might like this.';
        $request->fromName = 'Alice';
        $request->fromEmail = 'alice@example.com';

        $dto = $request->getDto();

        $this->assertIsArray($dto);
        $this->assertSame(['friend1@example.com', 'friend2@example.com'], $dto['recipients']);
        $this->assertSame('fwd-123e4567-e89b-12d3-a456-426614174000', $dto['uid']);
        $this->assertSame('Thought you might like this.', $dto['note']);
        $this->assertSame('Alice', $dto['fromName']);
        $this->assertSame('alice@example.com', $dto['fromEmail']);
    }

    public function testGetDtoHandlesNullables(): void
    {
        $request = new ForwardMessageRequest();

        $request->recipients = ['friend@example.com'];
        $request->uid = 'fwd-uid-1';
        $request->note = null;
        $request->fromName = 'Bob';
        $request->fromEmail = 'bob@example.com';

        $dto = $request->getDto();

        $this->assertIsArray($dto);
        $this->assertSame(['friend@example.com'], $dto['recipients']);
        $this->assertSame('fwd-uid-1', $dto['uid']);
        $this->assertNull($dto['note']);
        $this->assertSame('Bob', $dto['fromName']);
        $this->assertSame('bob@example.com', $dto['fromEmail']);
    }
}
