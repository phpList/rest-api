<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Serializer;

use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use PhpList\Core\Domain\Identity\Model\AdminAttributeValue;
use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\RestBundle\Identity\Serializer\AdminAttributeDefinitionNormalizer;
use PhpList\RestBundle\Identity\Serializer\AdminAttributeValueNormalizer;
use PhpList\RestBundle\Identity\Serializer\AdministratorNormalizer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class AdminAttributeValueNormalizerTest extends TestCase
{
    private AdminAttributeValueNormalizer $normalizer;
    /**
     * @var AdministratorNormalizer&MockObject
     * @method array normalize($object, string $format = null, array $context = [])
     */
    private AdministratorNormalizer $adminNormalizer;

    /**
     * @var AdminAttributeDefinitionNormalizer&MockObject
     * @method array normalize($object, string $format = null, array $context = [])
     */
    private AdminAttributeDefinitionNormalizer $definitionNormalizer;

    protected function setUp(): void
    {
        $this->adminNormalizer = $this->createMock(AdministratorNormalizer::class);
        $this->definitionNormalizer = $this->createMock(AdminAttributeDefinitionNormalizer::class);
        $this->normalizer = new AdminAttributeValueNormalizer(
            $this->definitionNormalizer,
            $this->adminNormalizer
        );
    }

    public function testNormalizeReturnsExpectedArray(): void
    {
        $admin = $this->createMock(Administrator::class);
        $definition = $this->createMock(AdminAttributeDefinition::class);
        $definition->method('getDefaultValue')->willReturn('default_value');

        $attributeValue = $this->createMock(AdminAttributeValue::class);
        $attributeValue->method('getAdministrator')->willReturn($admin);
        $attributeValue->method('getAttributeDefinition')->willReturn($definition);
        $attributeValue->method('getValue')->willReturn('test_value');

        $this->adminNormalizer->method('normalize')->willReturn(['id' => 1, 'login_name' => 'admin']);
        $this->definitionNormalizer->method('normalize')->willReturn(['id' => 2, 'name' => 'test_attribute']);

        $data = $this->normalizer->normalize($attributeValue);

        $this->assertIsArray($data);
        $this->assertEquals([
            'administrator' => ['id' => 1, 'login_name' => 'admin'],
            'definition' => ['id' => 2, 'name' => 'test_attribute'],
            'value' => 'test_value',
        ], $data);
    }

    public function testNormalizeUsesDefaultValueWhenValueIsNull(): void
    {
        $admin = $this->createMock(Administrator::class);
        $definition = $this->createMock(AdminAttributeDefinition::class);
        $definition->method('getDefaultValue')->willReturn('default_value');

        $attributeValue = $this->createMock(AdminAttributeValue::class);
        $attributeValue->method('getAdministrator')->willReturn($admin);
        $attributeValue->method('getAttributeDefinition')->willReturn($definition);
        $attributeValue->method('getValue')->willReturn(null);

        $this->adminNormalizer->method('normalize')->willReturn(['id' => 1, 'login_name' => 'admin']);
        $this->definitionNormalizer->method('normalize')->willReturn(['id' => 2, 'name' => 'test_attribute']);

        $data = $this->normalizer->normalize($attributeValue);

        $this->assertIsArray($data);
        $this->assertEquals([
            'administrator' => ['id' => 1, 'login_name' => 'admin'],
            'definition' => ['id' => 2, 'name' => 'test_attribute'],
            'value' => 'default_value',
        ], $data);
    }

    public function testNormalizeWithInvalidObjectReturnsEmptyArray(): void
    {
        $data = $this->normalizer->normalize(new \stdClass());

        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }

    public function testSupportsNormalization(): void
    {
        $attributeValue = $this->createMock(AdminAttributeValue::class);
        $this->assertTrue($this->normalizer->supportsNormalization($attributeValue));
        $this->assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
    }
}
