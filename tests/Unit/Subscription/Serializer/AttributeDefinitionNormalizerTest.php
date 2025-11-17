<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Serializer;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\Dto\DynamicListAttrDto;
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
        $definition->method('getType')->willReturn(AttributeTypeEnum::Text);
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
            'options' => [],
        ], $result);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $normalizer = new AttributeDefinitionNormalizer();
        $result = $normalizer->normalize(new \stdClass());

        self::assertSame([], $result);
    }

    public function testNormalizeWithOptions(): void
    {
        $options = [
            new DynamicListAttrDto(
                id: 10,
                name: 'USA',
                listOrder: 1
            ),
            new DynamicListAttrDto(
                id: 20,
                name: 'Canada',
                listOrder: 2
            ),
        ];

        $definition = $this->createMock(SubscriberAttributeDefinition::class);
        $definition->method('getId')->willReturn(5);
        $definition->method('getName')->willReturn('Country');
        $definition->method('getType')->willReturn(AttributeTypeEnum::Select);
        $definition->method('getListOrder')->willReturn(3);
        $definition->method('getDefaultValue')->willReturn(null);
        $definition->method('isRequired')->willReturn(false);
        $definition->method('getOptions')->willReturn($options);

        $normalizer = new AttributeDefinitionNormalizer();
        $result = $normalizer->normalize($definition);

        self::assertIsArray($result);

        self::assertSame([
            'id' => 5,
            'name' => 'Country',
            'type' => 'select',
            'list_order' => 3,
            'default_value' => null,
            'required' => false,
            'options' => [
                [
                    'id' => 10,
                    'name' => 'USA',
                    'list_order' => 1,
                ], [
                    'id' => 20,
                    'name' => 'Canada',
                    'list_order' => 2,
                ],
            ],
        ], $result);
    }
}
