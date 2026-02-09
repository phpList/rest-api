<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Serializer;

use PhpList\Core\Domain\Messaging\Model\Dto\ForwardingRecipientResult;
use PhpList\Core\Domain\Messaging\Model\Dto\ForwardingResult;
use PhpList\RestBundle\Messaging\Serializer\ForwardingResultNormalizer;
use PHPUnit\Framework\TestCase;

class ForwardingResultNormalizerTest extends TestCase
{
    private ForwardingResultNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ForwardingResultNormalizer();
    }

    public function testSupportsNormalizationReturnsTrueForForwardingResult(): void
    {
        $result = new ForwardingResult(
            totalRequested: 0,
            totalSent: 0,
            totalFailed: 0,
            totalAlreadySent: 0,
            recipients: [],
        );

        $this->assertTrue($this->normalizer->supportsNormalization($result));
    }

    public function testSupportsNormalizationReturnsFalseForOtherObjects(): void
    {
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeMapsAllTopLevelCounts(): void
    {
        $result = new ForwardingResult(
            totalRequested: 5,
            totalSent: 3,
            totalFailed: 1,
            totalAlreadySent: 1,
            recipients: [],
        );

        $data = $this->normalizer->normalize($result);

        $this->assertIsArray($data);
        $this->assertSame(5, $data['total_requested']);
        $this->assertSame(3, $data['total_sent']);
        $this->assertSame(1, $data['total_failed']);
        $this->assertSame(1, $data['total_already_sent']);
        $this->assertSame([], $data['recipients']);
    }

    public function testNormalizeMapsRecipientsWithNullableReason(): void
    {
        $r1 = new ForwardingRecipientResult('a@example.com', 'sent', null);
        $r2 = new ForwardingRecipientResult('b@example.com', 'failed', 'precache_failed');

        $result = new ForwardingResult(
            totalRequested: 2,
            totalSent: 1,
            totalFailed: 1,
            totalAlreadySent: 0,
            recipients: [$r1, $r2],
        );

        $data = $this->normalizer->normalize($result);

        $this->assertCount(2, $data['recipients']);
        $this->assertSame([
            'email' => 'a@example.com',
            'status' => 'sent',
            'reason' => null,
        ], $data['recipients'][0]);
        $this->assertSame([
            'email' => 'b@example.com',
            'status' => 'failed',
            'reason' => 'precache_failed',
        ], $data['recipients'][1]);
    }

    public function testNormalizeReturnsEmptyArrayForNonForwardingResult(): void
    {
        $data = $this->normalizer->normalize(new \stdClass());
        $this->assertSame([], $data);
    }
}
