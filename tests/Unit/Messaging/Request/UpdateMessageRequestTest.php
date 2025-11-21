<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageContentDto;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageFormatDto;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageMetadataDto;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageOptionsDto;
use PhpList\Core\Domain\Messaging\Model\Dto\Message\MessageScheduleDto;
use PhpList\Core\Domain\Messaging\Model\Dto\UpdateMessageDto;
use PhpList\RestBundle\Messaging\Request\Message\MessageContentRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageFormatRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageMetadataRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageOptionsRequest;
use PhpList\RestBundle\Messaging\Request\Message\MessageScheduleRequest;
use PhpList\RestBundle\Messaging\Request\UpdateMessageRequest;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class UpdateMessageRequestTest extends TestCase
{
    private MessageContentDto&MockObject $contentDto;
    private MessageFormatDto&MockObject $formatDto;
    private MessageMetadataDto&MockObject $metadataDto;
    private MessageOptionsDto&MockObject $optionsDto;
    private MessageScheduleDto&MockObject $scheduleDto;

    private UpdateMessageRequest $request;

    protected function setUp(): void
    {
        $this->contentDto = $this->createMock(MessageContentDto::class);
        $this->formatDto = $this->createMock(MessageFormatDto::class);
        $this->metadataDto = $this->createMock(MessageMetadataDto::class);
        $this->optionsDto = $this->createMock(MessageOptionsDto::class);
        $this->scheduleDto = $this->createMock(MessageScheduleDto::class);

        $contentRequest = $this->createMock(MessageContentRequest::class);
        $contentRequest->method('getDto')->willReturn($this->contentDto);

        $formatRequest = $this->createMock(MessageFormatRequest::class);
        $formatRequest->method('getDto')->willReturn($this->formatDto);

        $metadataRequest = $this->createMock(MessageMetadataRequest::class);
        $metadataRequest->method('getDto')->willReturn($this->metadataDto);

        $optionsRequest = $this->createMock(MessageOptionsRequest::class);
        $optionsRequest->method('getDto')->willReturn($this->optionsDto);

        $scheduleRequest = $this->createMock(MessageScheduleRequest::class);
        $scheduleRequest->method('getDto')->willReturn($this->scheduleDto);

        $this->request = new UpdateMessageRequest();
        $this->request->content = $contentRequest;
        $this->request->format = $formatRequest;
        $this->request->metadata = $metadataRequest;
        $this->request->options = $optionsRequest;
        $this->request->schedule = $scheduleRequest;
    }

    public function testGetDtoReturnsCorrectDto(): void
    {
        $this->request->templateId = 456;

        $dto = $this->request->getDto();

        $this->assertInstanceOf(UpdateMessageDto::class, $dto);
        $this->assertSame($this->contentDto, $dto->content);
        $this->assertSame($this->formatDto, $dto->format);
        $this->assertSame($this->metadataDto, $dto->metadata);
        $this->assertSame($this->optionsDto, $dto->options);
        $this->assertSame($this->scheduleDto, $dto->schedule);
        $this->assertEquals(456, $dto->templateId);
    }

    public function testGetDtoWithNullTemplateId(): void
    {
        $this->request->templateId = null;

        $dto = $this->request->getDto();

        $this->assertInstanceOf(UpdateMessageDto::class, $dto);
        $this->assertSame($this->contentDto, $dto->content);
        $this->assertSame($this->formatDto, $dto->format);
        $this->assertSame($this->metadataDto, $dto->metadata);
        $this->assertSame($this->optionsDto, $dto->options);
        $this->assertSame($this->scheduleDto, $dto->schedule);
        $this->assertNull($dto->templateId);
    }
}
