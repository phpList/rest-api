<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Identity\Request;

use PhpList\Core\Domain\Identity\Model\Dto\AdminAttributeDefinitionDto;
use PhpList\RestBundle\Identity\Request\UpdateAttributeDefinitionRequest;
use PHPUnit\Framework\TestCase;

class UpdateAttributeDefinitionRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new UpdateAttributeDefinitionRequest();
        $request->name = 'Updated Attribute';
        $request->type = 'checkbox';
        $request->order = 10;
        $request->defaultValue = 'updated_default';
        $request->required = true;

        $dto = $request->getDto();

        $this->assertInstanceOf(AdminAttributeDefinitionDto::class, $dto);
        $this->assertEquals('Updated Attribute', $dto->name);
        $this->assertEquals('checkbox', $dto->type);
        $this->assertEquals(10, $dto->listOrder);
        $this->assertEquals('updated_default', $dto->defaultValue);
        $this->assertTrue($dto->required);
    }

    public function testGetDtoWithDefaultValues(): void
    {
        $request = new UpdateAttributeDefinitionRequest();
        $request->name = 'Updated Attribute';

        $dto = $request->getDto();

        $this->assertInstanceOf(AdminAttributeDefinitionDto::class, $dto);
        $this->assertEquals('Updated Attribute', $dto->name);
        $this->assertNull($dto->type);
        $this->assertNull($dto->listOrder);
        $this->assertNull($dto->defaultValue);
        $this->assertFalse($dto->required);
    }
}
