<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Serializer;

use DateTime;
use PhpList\Core\Domain\Model\Identity\AdministratorToken;
use PhpList\RestBundle\Serializer\AdministratorTokenNormalizer;
use PHPUnit\Framework\TestCase;

class AdministratorTokenNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new AdministratorTokenNormalizer();
        $token = $this->createMock(AdministratorToken::class);

        $this->assertTrue($normalizer->supportsNormalization($token));
        $this->assertFalse($normalizer->supportsNormalization(AdministratorToken::class));
    }

    public function testNormalize(): void
    {
        $expiry = new DateTime('2025-01-01T12:00:00+00:00');

        $token = $this->createMock(AdministratorToken::class);
        $token->method('getId')->willReturn(42);
        $token->method('getKey')->willReturn('abcdef123456');
        $token->method('getExpiry')->willReturn($expiry);

        $normalizer = new AdministratorTokenNormalizer();

        $expected = [
            'id' => 42,
            'key' => 'abcdef123456',
            'expiry_date' => '2025-01-01T12:00:00+00:00'
        ];

        $this->assertSame($expected, $normalizer->normalize($token));
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $normalizer = new AdministratorTokenNormalizer();
        $this->assertSame([], $normalizer->normalize(AdministratorToken::class));
    }
}
