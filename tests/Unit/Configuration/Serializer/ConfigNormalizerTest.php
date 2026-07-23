<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Configuration\Serializer;

use PhpList\Core\Domain\Configuration\Model\Config;
use PhpList\RestBundle\Configuration\Serializer\ConfigNormalizer;
use PHPUnit\Framework\TestCase;
use stdClass;

class ConfigNormalizerTest extends TestCase
{
    private ConfigNormalizer $normalizer;

    protected function setUp(): void
    {
        $this->normalizer = new ConfigNormalizer();
    }

    public function testSupportsNormalizationReturnsTrueForConfig(): void
    {
        $config = $this->createMock(Config::class);

        self::assertTrue($this->normalizer->supportsNormalization($config));
    }

    public function testSupportsNormalizationReturnsFalseForNonConfig(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(new stdClass()));
    }

    public function testSupportsNormalizationReturnsFalseForNull(): void
    {
        self::assertFalse($this->normalizer->supportsNormalization(null));
    }

    public function testNormalizeReturnsExpectedArrayForConfig(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getKey')->willReturn('organisation_name');
        $config->method('getValue')->willReturn('Example Organisation');
        $config->method('isEditable')->willReturn(true);
        $config->method('getType')->willReturn('text');

        $result = $this->normalizer->normalize($config);

        self::assertSame([
            'key' => 'organisation_name',
            'value' => 'Example Organisation',
            'editable' => true,
            'type' => 'text',
        ], $result);
    }

    public function testNormalizeHandlesNullValueAndType(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('getKey')->willReturn('some_key');
        $config->method('getValue')->willReturn(null);
        $config->method('isEditable')->willReturn(false);
        $config->method('getType')->willReturn(null);

        $result = $this->normalizer->normalize($config);

        self::assertSame([
            'key' => 'some_key',
            'value' => null,
            'editable' => false,
            'type' => null,
        ], $result);
    }

    public function testNormalizeReturnsEmptyArrayForNonConfigObject(): void
    {
        $result = $this->normalizer->normalize(new stdClass());

        self::assertSame([], $result);
    }
}
