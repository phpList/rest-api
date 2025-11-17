<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\Core\Domain\Identity\Model\Dto\AdminAttributeDefinitionDto;
use PhpList\RestBundle\Identity\Request\CreateAttributeDefinitionRequest;
use PHPUnit\Framework\TestCase;

class CreateAttributeDefinitionRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new CreateAttributeDefinitionRequest();
        $request->name = 'Test Attribute';
        $request->type = 'text';
        $request->order = 5;
        $request->defaultValue = 'default';
        $request->required = true;

        $dto = $request->getDto();

        $this->assertInstanceOf(AdminAttributeDefinitionDto::class, $dto);
        $this->assertEquals('Test Attribute', $dto->name);
        $this->assertEquals('text', $dto->type);
        $this->assertEquals(5, $dto->listOrder);
        $this->assertEquals('default', $dto->defaultValue);
        $this->assertTrue($dto->required);
    }

    public function testGetDtoWithDefaultValues(): void
    {
        $request = new CreateAttributeDefinitionRequest();
        $request->name = 'Test Attribute';

        $dto = $request->getDto();

        $this->assertInstanceOf(AdminAttributeDefinitionDto::class, $dto);
        $this->assertEquals('Test Attribute', $dto->name);
        $this->assertNull($dto->type);
        $this->assertNull($dto->listOrder);
        $this->assertNull($dto->defaultValue);
        $this->assertFalse($dto->required);
    }
}
