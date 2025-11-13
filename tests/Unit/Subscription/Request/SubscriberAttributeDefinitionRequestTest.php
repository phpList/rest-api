<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Request;

use PhpList\Core\Domain\Common\Model\AttributeTypeEnum;
use PhpList\Core\Domain\Subscription\Model\Dto\AttributeDefinitionDto;
use PhpList\Core\Domain\Subscription\Model\Dto\DynamicListAttrDto;
use PhpList\RestBundle\Subscription\Request\SubscriberAttributeDefinitionRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class SubscriberAttributeDefinitionRequestTest extends TestCase
{
    public function testGetDtoReturnsCorrectDto(): void
    {
        $request = new SubscriberAttributeDefinitionRequest();
        $request->name = 'Test Attribute';
        $request->type = 'textline';
        $request->order = 5;
        $request->defaultValue = 'default';
        $request->required = true;

        $dto = $request->getDto();

        $this->assertInstanceOf(AttributeDefinitionDto::class, $dto);
        $this->assertEquals('Test Attribute', $dto->name);
        $this->assertInstanceOf(AttributeTypeEnum::class, $dto->type);
        $this->assertSame(AttributeTypeEnum::TextLine, $dto->type);
        $this->assertEquals(5, $dto->listOrder);
        $this->assertEquals('default', $dto->defaultValue);
        $this->assertTrue($dto->required);
        $this->assertIsArray($dto->options);
    }

    public function testGetDtoWithDefaultValues(): void
    {
        $request = new SubscriberAttributeDefinitionRequest();
        $request->name = 'Test Attribute';

        $dto = $request->getDto();

        $this->assertInstanceOf(AttributeDefinitionDto::class, $dto);
        $this->assertEquals('Test Attribute', $dto->name);
        $this->assertNull($dto->type);
        $this->assertNull($dto->listOrder);
        $this->assertNull($dto->defaultValue);
        $this->assertFalse($dto->required);
        $this->assertIsArray($dto->options);
        $this->assertSame([], $dto->options);
    }

    public function testGetDtoWithOptions(): void
    {
        $request = new SubscriberAttributeDefinitionRequest();
        $request->name = 'With options';
        $request->type = 'select';
        $request->options = [
            new DynamicListAttrDto(null, 'Option A', 1),
            new DynamicListAttrDto(5, 'Option B', 2),
        ];

        $dto = $request->getDto();

        $this->assertInstanceOf(AttributeDefinitionDto::class, $dto);
        $this->assertSame('With options', $dto->name);
        $this->assertSame(AttributeTypeEnum::Select, $dto->type);
        $this->assertCount(2, $dto->options);
        $this->assertInstanceOf(DynamicListAttrDto::class, $dto->options[0]);
        $this->assertInstanceOf(DynamicListAttrDto::class, $dto->options[1]);
        $this->assertSame('Option A', $dto->options[0]->name);
        $this->assertSame('Option B', $dto->options[1]->name);
    }


    public function testValidationFailsWhenOptionsContainNonDto(): void
    {
        $request = new SubscriberAttributeDefinitionRequest();
        $request->name = 'Mixed options';
        $request->options = ['foo'];

        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $violations = $validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertStringStartsWith('options[', $violations->get(0)->getPropertyPath());
    }

    public function testValidationFailsOnInvalidType(): void
    {
        $request = new SubscriberAttributeDefinitionRequest();
        $request->name = 'Invalid type';
        $request->type = 'not-a-valid-type';

        $validator = Validation::createValidatorBuilder()->enableAnnotationMapping()->getValidator();
        $violations = $validator->validate($request);

        $this->assertGreaterThan(0, $violations->count());
        $this->assertSame('type', $violations->get(0)->getPropertyPath());
    }
}
