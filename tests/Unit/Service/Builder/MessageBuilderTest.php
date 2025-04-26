<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Builder;

use InvalidArgumentException;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\RestBundle\Entity\Dto\MessageContext;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageContentRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageFormatRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageMetadataRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageOptionsRequest;
use PhpList\RestBundle\Entity\Request\Message\MessageScheduleRequest;
use PhpList\RestBundle\Entity\Request\RequestInterface;
use PhpList\RestBundle\Service\Builder\MessageBuilder;
use PhpList\RestBundle\Service\Builder\MessageContentBuilder;
use PhpList\RestBundle\Service\Builder\MessageFormatBuilder;
use PhpList\RestBundle\Service\Builder\MessageOptionsBuilder;
use PhpList\RestBundle\Service\Builder\MessageScheduleBuilder;
use PHPUnit\Framework\TestCase;

class MessageBuilderTest extends TestCase
{
    private MessageFormatBuilder $formatBuilder;
    private MessageScheduleBuilder $scheduleBuilder;
    private MessageContentBuilder $contentBuilder;
    private MessageOptionsBuilder $optionsBuilder;
    private MessageBuilder $builder;

    protected function setUp(): void
    {
        $templateRepository = $this->createMock(TemplateRepository::class);
        $this->formatBuilder = $this->createMock(MessageFormatBuilder::class);
        $this->scheduleBuilder = $this->createMock(MessageScheduleBuilder::class);
        $this->contentBuilder = $this->createMock(MessageContentBuilder::class);
        $this->optionsBuilder = $this->createMock(MessageOptionsBuilder::class);

        $this->builder = new MessageBuilder(
            $templateRepository,
            $this->formatBuilder,
            $this->scheduleBuilder,
            $this->contentBuilder,
            $this->optionsBuilder
        );
    }

    private function createRequest(): CreateMessageRequest
    {
        $request = new CreateMessageRequest();
        $request->format = new MessageFormatRequest();
        $request->schedule = new MessageScheduleRequest();
        $request->content = new MessageContentRequest();
        $request->metadata = new MessageMetadataRequest();
        $request->metadata->status = 'draft';
        $request->options = new MessageOptionsRequest();
        $request->templateId = 0;

        return $request;
    }

    private function mockBuildFromDtoCalls(CreateMessageRequest $request): void
    {
        $this->formatBuilder->expects($this->once())
            ->method('buildFromDto')
            ->with($request->format)
            ->willReturn($this->createMock(Message\MessageFormat::class));

        $this->scheduleBuilder->expects($this->once())
            ->method('buildFromDto')
            ->with($request->schedule)
            ->willReturn($this->createMock(Message\MessageSchedule::class));

        $this->contentBuilder->expects($this->once())
            ->method('buildFromDto')
            ->with($request->content)
            ->willReturn($this->createMock(Message\MessageContent::class));

        $this->optionsBuilder->expects($this->once())
            ->method('buildFromDto')
            ->with($request->options)
            ->willReturn($this->createMock(Message\MessageOptions::class));
    }

    public function testBuildsNewMessage(): void
    {
        $request = $this->createRequest();
        $admin = $this->createMock(Administrator::class);
        $context = new MessageContext($admin);

        $this->mockBuildFromDtoCalls($request);

        $this->builder->buildFromRequest($request, $context);
    }

    public function testThrowsExceptionOnInvalidRequest(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder->buildFromRequest(
            $this->createMock(RequestInterface::class),
            new MessageContext($this->createMock(Administrator::class))
        );
    }

    public function testThrowsExceptionOnInvalidContext(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->builder->buildFromRequest(new CreateMessageRequest(), new \stdClass());
    }

    public function testUpdatesExistingMessage(): void
    {
        $request = $this->createRequest();
        $admin = $this->createMock(Administrator::class);
        $existingMessage = $this->createMock(Message::class);
        $context = new MessageContext($admin, $existingMessage);

        $this->mockBuildFromDtoCalls($request);

        $existingMessage
            ->expects($this->once())
            ->method('setFormat')
            ->with($this->isInstanceOf(Message\MessageFormat::class));
        $existingMessage
            ->expects($this->once())
            ->method('setSchedule')
            ->with($this->isInstanceOf(Message\MessageSchedule::class));
        $existingMessage
            ->expects($this->once())
            ->method('setContent')
            ->with($this->isInstanceOf(Message\MessageContent::class));
        $existingMessage
            ->expects($this->once())
            ->method('setOptions')
            ->with($this->isInstanceOf(Message\MessageOptions::class));
        $existingMessage->expects($this->once())->method('setTemplate')->with(null);

        $result = $this->builder->buildFromRequest($request, $context);

        $this->assertSame($existingMessage, $result);
    }
}
