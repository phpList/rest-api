<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Builder;

use InvalidArgumentException;
use PhpList\RestBundle\Entity\Request\Message\MessageOptionsRequest;
use PhpList\RestBundle\Service\Builder\MessageOptionsBuilder;
use PHPUnit\Framework\TestCase;

class MessageOptionsBuilderTest extends TestCase
{
    private MessageOptionsBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MessageOptionsBuilder();
    }

    public function testBuildsMessageOptionsSuccessfully(): void
    {
        $dto = new MessageOptionsRequest();
        $dto->fromField = 'info@example.com';
        $dto->toField = 'user@example.com';
        $dto->replyTo = 'reply@example.com';
        $dto->userSelection = 'all-users';

        $messageOptions = $this->builder->buildFromDto($dto);

        $this->assertSame('info@example.com', $messageOptions->getFromField());
        $this->assertSame('user@example.com', $messageOptions->getToField());
        $this->assertSame('reply@example.com', $messageOptions->getReplyTo());
        $this->assertSame('all-users', $messageOptions->getUserSelection());
    }

    public function testThrowsExceptionOnInvalidDto(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidDto = new \stdClass();
        $this->builder->buildFromDto($invalidDto);
    }
}
