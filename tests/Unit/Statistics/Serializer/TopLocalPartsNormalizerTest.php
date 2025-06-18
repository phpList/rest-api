<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Statistics\Serializer;

use PhpList\RestBundle\Statistics\Serializer\TopLocalPartsNormalizer;
use PHPUnit\Framework\TestCase;

class TopLocalPartsNormalizerTest extends TestCase
{
    public function testNormalizeWithValidData(): void
    {
        $data = [
            'localParts' => [
                ['localPart' => 'john', 'count' => 100, 'percentage' => 40.0],
                ['localPart' => 'info', 'count' => 50, 'percentage' => 20.0],
            ],
            'total' => 250,
        ];

        $normalizer = new TopLocalPartsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('local_parts', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(250, $result['total']);
        $this->assertCount(2, $result['local_parts']);
        $this->assertEquals('john', $result['local_parts'][0]['local_part']);
        $this->assertEquals(100, $result['local_parts'][0]['count']);
        $this->assertEquals(40.0, $result['local_parts'][0]['percentage']);
        $this->assertEquals('info', $result['local_parts'][1]['local_part']);
        $this->assertEquals(50, $result['local_parts'][1]['count']);
        $this->assertEquals(20.0, $result['local_parts'][1]['percentage']);
    }

    public function testNormalizeWithMissingFields(): void
    {
        $data = [
            'localParts' => [
                ['localPart' => 'john'],
                ['count' => 50],
                ['percentage' => 20.0],
                [],
            ],
        ];

        $normalizer = new TopLocalPartsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('local_parts', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
        $this->assertCount(4, $result['local_parts']);
        $this->assertEquals('john', $result['local_parts'][0]['local_part']);
        $this->assertEquals(0, $result['local_parts'][0]['count']);
        $this->assertEquals(0.0, $result['local_parts'][0]['percentage']);
        $this->assertEquals('', $result['local_parts'][1]['local_part']);
        $this->assertEquals(50, $result['local_parts'][1]['count']);
        $this->assertEquals(0.0, $result['local_parts'][1]['percentage']);
        $this->assertEquals('', $result['local_parts'][2]['local_part']);
        $this->assertEquals(0, $result['local_parts'][2]['count']);
        $this->assertEquals(20.0, $result['local_parts'][2]['percentage']);
        $this->assertEquals('', $result['local_parts'][3]['local_part']);
        $this->assertEquals(0, $result['local_parts'][3]['count']);
        $this->assertEquals(0.0, $result['local_parts'][3]['percentage']);
    }

    public function testNormalizeWithEmptyLocalParts(): void
    {
        $data = [
            'localParts' => [],
            'total' => 0,
        ];

        $normalizer = new TopLocalPartsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('local_parts', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(0, $result['total']);
        $this->assertEmpty($result['local_parts']);
    }

    public function testNormalizeWithNoLocalParts(): void
    {
        $data = [
            'total' => 100,
        ];

        $normalizer = new TopLocalPartsNormalizer();
        $result = $normalizer->normalize($data);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('local_parts', $result);
        $this->assertArrayHasKey('total', $result);
        $this->assertEquals(100, $result['total']);
        $this->assertEmpty($result['local_parts']);
    }

    public function testNormalizeWithInvalidObject(): void
    {
        $normalizer = new TopLocalPartsNormalizer();
        $result = $normalizer->normalize('not an array');

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new TopLocalPartsNormalizer();

        $this->assertTrue($normalizer->supportsNormalization([], null, ['top_local_parts' => true]));
        $this->assertFalse($normalizer->supportsNormalization([], null, []));
        $this->assertFalse($normalizer->supportsNormalization('not an array', null, ['top_local_parts' => true]));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), null, ['top_local_parts' => true]));
    }
}
