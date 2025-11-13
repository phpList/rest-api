<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use PhpList\RestBundle\Subscription\Serializer\AttributeDefinitionNormalizer;
use PHPUnit\Framework\TestCase;

class AttributeDefinitionNormalizerTest extends TestCase
{
    public function testSupportsNormalization(): void
    {
        $normalizer = new AttributeDefinitionNormalizer();

        $definition = $this->createMock(SubscriberAttributeDefinition::class);
        self::assertTrue($normalizer->supportsNormalization($definition));

        $nonSupported = new \stdClass();
        self::assertFalse($normalizer->supportsNormalization($nonSupported));
    }

    public function testNormalize(): void
    {
        $definition = $this->createMock(SubscriberAttributeDefinition::class);
        $definition->method('getId')->willReturn(1);
        $definition->method('getName')->willReturn('Country');
        $definition->method('getType')->willReturn('text');
        $definition->method('getListOrder')->willReturn(12);
        $definition->method('getDefaultValue')->willReturn('US');
        $definition->method('isRequired')->willReturn(true);

        $normalizer = new AttributeDefinitionNormalizer();
        $result = $normalizer->normalize($definition);

        self::assertIsArray($result);
        self::assertSame([
            'id' => 1,
            'name' => 'Country',
            'type' => 'text',
            'list_order' => 12,
            'default_value' => 'US',
            'required' => true,
        ], $result);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $normalizer = new AttributeDefinitionNormalizer();
        $result = $normalizer->normalize(new \stdClass());

        self::assertSame([], $result);
    }
}
