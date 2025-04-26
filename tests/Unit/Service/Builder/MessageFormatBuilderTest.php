<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Builder;

use InvalidArgumentException;
use PhpList\RestBundle\Entity\Request\Message\MessageFormatRequest;
use PhpList\RestBundle\Service\Builder\MessageFormatBuilder;
use PHPUnit\Framework\TestCase;

class MessageFormatBuilderTest extends TestCase
{
    private MessageFormatBuilder $builder;

    protected function setUp(): void
    {
        $this->builder = new MessageFormatBuilder();
    }

    public function testBuildsMessageFormatSuccessfully(): void
    {
        $dto = new MessageFormatRequest();
        $dto->htmlFormated = true;
        $dto->sendFormat = 'html';
        $dto->formatOptions = ['html', 'text'];

        $messageFormat = $this->builder->buildFromDto($dto);

        $this->assertSame(true, $messageFormat->isHtmlFormatted());
        $this->assertSame('html', $messageFormat->getSendFormat());
        $this->assertEqualsCanonicalizing(['html', 'text'], $messageFormat->getFormatOptions());
    }

    public function testThrowsExceptionOnInvalidDto(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $invalidDto = new \stdClass();
        $this->builder->buildFromDto($invalidDto);
    }
}
