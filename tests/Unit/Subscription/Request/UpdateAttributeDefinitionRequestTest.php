<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\Dto\AttributeDefinitionDto;
use PhpList\RestBundle\Subscription\Request\UpdateAttributeDefinitionRequest;
use PHPUnit\Framework\TestCase;

class UpdateAttributeDefinitionRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new UpdateAttributeDefinitionRequest();
        $request->name = 'Test Attribute';
        $request->type = 'text';
        $request->order = 5;
        $request->defaultValue = 'default';
        $request->required = true;
        $request->tableName = 'test_table';

        $dto = $request->getDto();

        $this->assertInstanceOf(AttributeDefinitionDto::class, $dto);
        $this->assertEquals('Test Attribute', $dto->name);
        $this->assertEquals('text', $dto->type);
        $this->assertEquals(5, $dto->listOrder);
        $this->assertEquals('default', $dto->defaultValue);
        $this->assertTrue($dto->required);
        $this->assertEquals('test_table', $dto->tableName);
    }

    public function testGetDtoWithDefaultValues(): void
    {
        $request = new UpdateAttributeDefinitionRequest();
        $request->name = 'Test Attribute';

        $dto = $request->getDto();

        $this->assertInstanceOf(AttributeDefinitionDto::class, $dto);
        $this->assertEquals('Test Attribute', $dto->name);
        $this->assertNull($dto->type);
        $this->assertNull($dto->listOrder);
        $this->assertNull($dto->defaultValue);
        $this->assertFalse($dto->required);
        $this->assertNull($dto->tableName);
    }
}
