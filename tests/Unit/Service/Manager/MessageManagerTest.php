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

    public function testUpdateMessageReturnsUpdatedMessage(): void
    {
        $messageRepository = $this->createMock(MessageRepository::class);
        $messageBuilder = $this->createMock(MessageBuilder::class);

        $manager = new MessageManager($messageRepository, $messageBuilder);

        $updateRequest = new \PhpList\RestBundle\Entity\Request\UpdateMessageRequest();
        $updateRequest->messageId = 1;
        $updateRequest->format = new MessageFormatRequest();
        $updateRequest->format->htmlFormated = false;
        $updateRequest->format->sendFormat = 'text';
        $updateRequest->format->formatOptions = ['text'];

        $updateRequest->schedule = new MessageScheduleRequest();
        $updateRequest->schedule->repeatInterval = 0;
        $updateRequest->schedule->repeatUntil = '2025-04-30T00:00:00+00:00';
        $updateRequest->schedule->requeueInterval = 0;
        $updateRequest->schedule->requeueUntil = '2025-04-20T00:00:00+00:00';
        $updateRequest->schedule->embargo = '2025-04-17T09:00:00+00:00';

        $updateRequest->content = new MessageContentRequest();
        $updateRequest->content->subject = 'Updated Subject';
        $updateRequest->content->text = 'Updated Full text';
        $updateRequest->content->textMessage = 'Updated Short text';
        $updateRequest->content->footer = 'Updated Footer';

        $updateRequest->options = new MessageOptionsRequest();
        $updateRequest->options->fromField = 'newfrom@example.com';
        $updateRequest->options->toField = 'newto@example.com';
        $updateRequest->options->replyTo = 'newreply@example.com';
        $updateRequest->options->userSelection = 'active-users';

        $updateRequest->templateId = 2;

        $authUser = $this->createMock(Administrator::class);

        $existingMessage = $this->createMock(Message::class);
        $expectedContent = $this->createMock(Message\MessageContent::class);
        $expectedMetadata = $this->createMock(Message\MessageMetadata::class);

        $expectedContent->method('getSubject')->willReturn('Updated Subject');
        $expectedMetadata->method('getStatus')->willReturn('draft');

        $existingMessage->method('getContent')->willReturn($expectedContent);
        $existingMessage->method('getMetadata')->willReturn($expectedMetadata);

        $messageBuilder->expects($this->once())
            ->method('buildFromRequest')
            ->with($updateRequest, $this->anything())
            ->willReturn($existingMessage);

        $messageRepository->expects($this->once())
            ->method('save')
            ->with($existingMessage);

        $message = $manager->updateMessage($updateRequest, $existingMessage, $authUser);

        $this->assertSame('Updated Subject', $message->getContent()->getSubject());
        $this->assertSame('draft', $message->getMetadata()->getStatus());
    }
}
