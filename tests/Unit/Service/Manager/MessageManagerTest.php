<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Manager;

use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\MessageRepository;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageContentRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageFormatRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageMetadataRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageOptionsRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageScheduleRequest;
use PhpList\RestBundle\Service\Builder\MessageBuilder;
use PhpList\RestBundle\Service\Manager\MessageManager;
use PHPUnit\Framework\TestCase;

class MessageManagerTest extends TestCase
{
    public function testCreateMessageReturnsPersistedMessage(): void
    {
        $messageRepository = $this->createMock(MessageRepository::class);
        $messageBuilder = $this->createMock(MessageBuilder::class);

        $manager = new MessageManager($messageRepository, $messageBuilder);

        $format = new MessageFormatRequest();
        $format->htmlFormated = true;
        $format->sendFormat = 'html';
        $format->formatOptions = ['html'];

        $schedule = new MessageScheduleRequest();
        $schedule->repeatInterval = 60 * 24;
        $schedule->repeatUntil = '2025-04-30T00:00:00+00:00';
        $schedule->requeueInterval = 60 * 12;
        $schedule->requeueUntil = '2025-04-20T00:00:00+00:00';
        $schedule->embargo = '2025-04-17T09:00:00+00:00';

        $metadata = new MessageMetadataRequest();
        $metadata->status = 'draft';

        $content = new MessageContentRequest();
        $content->subject = 'Subject';
        $content->text = 'Full text';
        $content->textMessage = 'Short text';
        $content->footer = 'Footer';

        $options = new MessageOptionsRequest();
        $options->fromField = 'from@example.com';
        $options->toField = 'to@example.com';
        $options->replyTo = 'reply@example.com';
        $options->userSelection = 'all-users';

        $request = new CreateMessageRequest();
        $request->format = $format;
        $request->schedule = $schedule;
        $request->metadata = $metadata;
        $request->content = $content;
        $request->options = $options;
        $request->templateId = 0;

        $authUser = $this->createMock(Administrator::class);

        $expectedMessage = $this->createMock(Message::class);
        $expectedContent = $this->createMock(Message\MessageContent::class);
        $expectedMetadata = $this->createMock(Message\MessageMetadata::class);

        $expectedContent->method('getSubject')->willReturn('Subject');
        $expectedMetadata->method('getStatus')->willReturn('draft');

        $expectedMessage->method('getContent')->willReturn($expectedContent);
        $expectedMessage->method('getMetadata')->willReturn($expectedMetadata);

        $messageBuilder->expects($this->once())
            ->method('buildFromRequest')
            ->with($request, $this->anything())
            ->willReturn($expectedMessage);

        $messageRepository->expects($this->once())
            ->method('save')
            ->with($expectedMessage);

        $message = $manager->createMessage($request, $authUser);

        $this->assertSame('Subject', $message->getContent()->getSubject());
        $this->assertSame('draft', $message->getMetadata()->getStatus());
    }
}
