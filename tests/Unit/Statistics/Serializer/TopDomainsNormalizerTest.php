<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Statistics\Serializer;

use PhpList\RestBundle\Statistics\Serializer\TopDomainsNormalizer;
use PHPUnit\Framework\TestCase;

class TopDomainsNormalizerTest extends TestCase
{
    public function testNormalizeWithValidData(): void
    {
        $data = [
            'domains' => [
                ['domain' => 'example.com', 'subscribers' => 100],
                ['domain' => 'test.org', 'subscribers' => 50],
            ],
            'total' => 150,
        ];

        $normalizer = new TopDomainsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(150, $result['total']);
        $this->assertCount(2, $result['domains']);
        $this->assertEquals('example.com', $result['domains'][0]['domain']);
        $this->assertEquals(100, $result['domains'][0]['subscribers']);
        $this->assertEquals('test.org', $result['domains'][1]['domain']);
        $this->assertEquals(50, $result['domains'][1]['subscribers']);
    }

    public function testNormalizeWithMissingFields(): void
    {
        $data = [
            'domains' => [
                ['domain' => 'example.com'],
                ['subscribers' => 50],
                [],
            ],
        ];

        $normalizer = new TopDomainsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
        $this->assertCount(3, $result['domains']);
        $this->assertEquals('example.com', $result['domains'][0]['domain']);
        $this->assertEquals(0, $result['domains'][0]['subscribers']);
        $this->assertEquals('', $result['domains'][1]['domain']);
        $this->assertEquals(50, $result['domains'][1]['subscribers']);
        $this->assertEquals('', $result['domains'][2]['domain']);
        $this->assertEquals(0, $result['domains'][2]['subscribers']);
    }

    public function testNormalizeWithEmptyDomains(): void
    {
        $data = [
            'domains' => [],
            'total' => 0,
        ];

        $normalizer = new TopDomainsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['domains']);
    }

    public function testNormalizeWithNoDomains(): void
    {
        $data = [
            'total' => 100,
        ];

        $normalizer = new TopDomainsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('domains', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(100, $result['total']);
        $this->assertEmpty($result['domains']);
    }

    public function testNormalizeWithInvalidObject(): void
    {
        $normalizer = new TopDomainsNormalizer();
        $result = $normalizer->normalize('not an array');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new TopDomainsNormalizer();

        $this->assertTrue($normalizer->supportsNormalization([], null, ['top_domains' => true]));
        $this->assertFalse($normalizer->supportsNormalization([], null, []));
        $this->assertFalse($normalizer->supportsNormalization('not an array', null, ['top_domains' => true]));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), null, ['top_domains' => true]));
    }
}
