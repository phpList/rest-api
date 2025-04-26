<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Builder;

use InvalidArgumentException;
use PhpList\RestBundle\Entity\Request\Message\MessageContentRequest;
use PhpList\RestBundle\Service\Builder\MessageContentBuilder;
use PHPUnit\Framework\TestCase;

class MessageContentBuilderTest extends TestCase
{
    private MessageContentBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MessageContentBuilder();
    }

    public function testBuildsMessageContentSuccessfully(): void
    {
        $dto = new MessageContentRequest();
        $dto->subject = 'Test Subject';
        $dto->text = 'Full text content';
        $dto->textMessage = 'Short text version';
        $dto->footer = 'Footer text';

        $messageContent = $this->builder->buildFromDto($dto);

        $this->assertSame('Test Subject', $messageContent->getSubject());
        $this->assertSame('Full text content', $messageContent->getText());
        $this->assertSame('Short text version', $messageContent->getTextMessage());
        $this->assertSame('Footer text', $messageContent->getFooter());
    }

    public function testThrowsExceptionOnInvalidDto(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidDto = new \stdClass();
        $this->builder->buildFromDto($invalidDto);
    }
}
