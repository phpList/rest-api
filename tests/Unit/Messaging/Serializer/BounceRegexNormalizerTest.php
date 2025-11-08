<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Serializer;

use PhpList\Core\Domain\Messaging\Model\BounceRegex;
use PhpList\RestBundle\Messaging\Serializer\BounceRegexNormalizer;
use PHPUnit\Framework\TestCase;

class BounceRegexNormalizerTest extends TestCase
{
    private BounceRegexNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new BounceRegexNormalizer();
    }

    public function testSupportsNormalization(): void
    {
        $regex = new BounceRegex();
        $this->assertTrue($this->normalizer->supportsNormalization($regex));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }

    public function testNormalizeReturnsExpectedArray(): void
    {
        $regexPattern = '/mailbox is full/i';
        $hash = md5($regexPattern);

        $entity = new BounceRegex(
            regex: $regexPattern,
            regexHash: $hash,
            action: 'delete',
            listOrder: 2,
            adminId: 42,
            comment: 'Auto-generated rule',
            status: 'active',
            count: 7
        );

        $result = $this->normalizer->normalize($entity);

        $this->assertSame($regexPattern, $result['regex']);
        $this->assertSame($hash, $result['regex_hash']);
        $this->assertSame('delete', $result['action']);
        $this->assertSame(2, $result['list_order']);
        $this->assertSame(42, $result['admin_id']);
        $this->assertSame('Auto-generated rule', $result['comment']);
        $this->assertSame('active', $result['status']);
        $this->assertSame(7, $result['count']);
        $this->assertArrayHasKey('id', $result);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->normalizer->normalize(new \stdClass()));
    }
}
