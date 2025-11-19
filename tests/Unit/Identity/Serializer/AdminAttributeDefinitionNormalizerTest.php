<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Serializer;

use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use PhpList\RestBundle\Identity\Serializer\AdminAttributeDefinitionNormalizer;
use PHPUnit\Framework\TestCase;

class AdminAttributeDefinitionNormalizerTest extends TestCase
{
    public function testNormalizeReturnsExpectedArray(): void
    {
        $definition = $this->createMock(AdminAttributeDefinition::class);
        $definition->method('getId')->willReturn(123);
        $definition->method('getName')->willReturn('test_attribute');
        $definition->method('getType')->willReturn('text');
        $definition->method('getListOrder')->willReturn(5);
        $definition->method('getDefaultValue')->willReturn('default');
        $definition->method('isRequired')->willReturn(true);
        $definition->method('getTableName')->willReturn('test_table');

        $normalizer = new AdminAttributeDefinitionNormalizer();
        $data = $normalizer->normalize($definition);

        $this->assertIsArray($data);
        $this->assertEquals([
            'id' => 123,
            'name' => 'test_attribute',
            'type' => 'text',
            'list_order' => 5,
            'default_value' => 'default',
            'required' => true,
        ], $data);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $normalizer = new AdminAttributeDefinitionNormalizer();
        $data = $normalizer->normalize(new \stdClass());

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function testSupportsNormalization(): void
    {
        $normalizer = new AdminAttributeDefinitionNormalizer();

        $definition = $this->createMock(AdminAttributeDefinition::class);
        $this->assertTrue($normalizer->supportsNormalization($definition));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass()));
    }
}
