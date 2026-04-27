<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Serializer;

use DateTimeImmutable;
use PhpList\Core\Domain\Messaging\Model\Dto\BounceView;
use PhpList\RestBundle\Messaging\Serializer\BounceNormalizer;
use PHPUnit\Framework\TestCase;

class BounceNormalizerTest extends TestCase
{
    private BounceNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BounceNormalizer();
    }

    public function testSupportsNormalization(): void
    {
        $bounce = new BounceView(
            id: 1,
            status: 'processed',
            comment: 'Handled',
            date: new DateTimeImmutable('2026-01-01T12:00:00+00:00'),
            messageId: 10,
            messageSubject: 'Subject',
            subscriberId: 20,
            subscriberEmail: 'user@example.com'
        );

        $this->assertTrue($this->normalizer->supportsNormalization($bounce));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeReturnsExpectedArray(): void
    {
        $bounce = new BounceView(
            id: 10,
            status: 'not processed',
            comment: 'Auto-generated rule',
            date: new DateTimeImmutable('2026-03-10T14:20:00+00:00'),
            messageId: 123,
            messageSubject: 'Newsletter',
            subscriberId: 456,
            subscriberEmail: 'subscriber@example.com'
        );

        $result = $this->normalizer->normalize($bounce);

        $this->assertSame(10, $result['id']);
        $this->assertSame('not processed', $result['status']);
        $this->assertSame('Auto-generated rule', $result['comment']);
        $this->assertSame('2026-03-10T14:20:00+00:00', $result['date']);
        $this->assertSame(123, $result['message_id']);
        $this->assertSame('Newsletter', $result['message_subject']);
        $this->assertSame(456, $result['subscriber_id']);
        $this->assertSame('subscriber@example.com', $result['subscriber_email']);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->normalizer->normalize(new \stdClass()));
    }
}
