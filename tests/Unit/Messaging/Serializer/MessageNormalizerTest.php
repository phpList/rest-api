<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Serializer;

use DateTime;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Model\Template;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use PhpList\RestBundle\Messaging\Serializer\TemplateImageNormalizer;
use PhpList\RestBundle\Messaging\Serializer\TemplateNormalizer;
use PHPUnit\Framework\TestCase;

class MessageNormalizerTest extends TestCase
{
    private MessageNormalizer $normalizer;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->normalizer = new MessageNormalizer(new TemplateNormalizer(new TemplateImageNormalizer()));
    }

    public function testSupportsNormalization(): void
    {
        $message = $this->createMock(Message::class);
        $this->assertTrue($this->normalizer->supportsNormalization($message));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeReturnsExpectedArray(): void
    {
        $template = $this->createConfiguredMock(Template::class, [
            'getId' => 5,
            'getTitle' => 'Test Template',
            'getContent' => '<html>Hello</html>',
            'getText' => 'Hello',
            'getListOrder' => 1,
        ]);

        $content = new Message\MessageContent('Subject', 'Text', 'TextMsg', 'Footer');
        $format = new Message\MessageFormat(true, 'html');
        $format->setFormatOptions(['text', 'html']);

        $entered = new DateTime('2025-01-01T10:00:00+00:00');
        $sent = new DateTime('2025-01-02T10:00:00+00:00');

        $metadata = new Message\MessageMetadata('draft');
        $metadata->setProcessed(true);
        $metadata->setViews(10);
        $metadata->setBounceCount(3);
        $metadata->setEntered($entered);
        $metadata->setSent($sent);

        $schedule = new Message\MessageSchedule(
            24,
            new DateTime('2025-01-10T00:00:00+00:00'),
            12,
            new DateTime('2025-01-05T00:00:00+00:00'),
            new DateTime('2025-01-01T00:00:00+00:00')
        );

        $options = new Message\MessageOptions('from@example.com', 'to@example.com', 'reply@example.com', 'group');

        $message = $this->createMock(Message::class);
        $message->method('getId')->willReturn(1);
        $message->method('getUuid')->willReturn('uuid-123');
        $message->method('getTemplate')->willReturn($template);
        $message->method('getContent')->willReturn($content);
        $message->method('getFormat')->willReturn($format);
        $message->method('getMetadata')->willReturn($metadata);
        $message->method('getSchedule')->willReturn($schedule);
        $message->method('getOptions')->willReturn($options);

        $result = $this->normalizer->normalize($message);

        $this->assertSame(1, $result['id']);
        $this->assertSame('uuid-123', $result['unique_id']);
        $this->assertSame('Test Template', $result['template']['title']);
        $this->assertSame('Subject', $result['message_content']['subject']);
        $this->assertSame(['text', 'html'], $result['message_format']['format_options']);
        $this->assertSame('draft', $result['message_metadata']['status']);
        $this->assertSame('from@example.com', $result['message_options']['from_field']);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->normalizer->normalize(new \stdClass()));
    }
}
