<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Provider;

use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\MessageRepository;
use PhpList\RestBundle\Service\Provider\MessageProvider;
use PHPUnit\Framework\TestCase;

class MessageProviderTest extends TestCase
{
    public function testGetMessagesByOwnerReturnsExpectedMessages(): void
    {
        $ownerId = 42;
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
        $expectedMessages = [$message1, $message2];

        $repository = $this->createMock(MessageRepository::class);
        $repository->expects($this->once())
            ->method('getByOwnerId')
            ->with($ownerId)
            ->willReturn($expectedMessages);

        $owner = $this->createMock(Administrator::class);
        $owner->expects($this->once())
            ->method('getId')
            ->willReturn($ownerId);

        $provider = new MessageProvider($repository);

        $result = $provider->getMessagesByOwner($owner);

        $this->assertSame($expectedMessages, $result);
    }
}
