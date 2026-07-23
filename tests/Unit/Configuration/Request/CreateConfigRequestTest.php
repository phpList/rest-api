<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Configuration\Request;

use PhpList\Core\Domain\Configuration\Model\Dto\CreateConfigDto;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Configuration\Request\CreateConfigRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateConfigRequestTest extends TestCase
{
    public function testImplementsRequestInterface(): void
    {
        $request = new CreateConfigRequest();
        $request->key = 'organisation_name';
        $request->value = 'Example Organisation';

        self::assertInstanceOf(RequestInterface::class, $request);
    }

    public function testEditableDefaultsToTrue(): void
    {
        $request = new CreateConfigRequest();

        self::assertTrue($request->editable);
    }

    public function testTypeDefaultsToNull(): void
    {
        $request = new CreateConfigRequest();

        self::assertNull($request->type);
    }

    public function testGetDtoReturnsCreateConfigDtoWithExpectedValues(): void
    {
        $request = new CreateConfigRequest();
        $request->key = 'organisation_name';
        $request->value = 'Example Organisation';
        $request->editable = false;
        $request->type = 'text';

        $dto = $request->getDto();

        self::assertInstanceOf(CreateConfigDto::class, $dto);
        self::assertSame('organisation_name', $dto->key);
        self::assertSame('Example Organisation', $dto->value);
        self::assertFalse($dto->editable);
        self::assertSame('text', $dto->type);
    }

    public function testGetDtoUsesDefaultsWhenNotExplicitlySet(): void
    {
        $request = new CreateConfigRequest();
        $request->key = 'site_title';
        $request->value = 'My Site';

        $dto = $request->getDto();

        self::assertTrue($dto->editable);
        self::assertNull($dto->type);
    }

    public function testGetDtoReturnsCreateConfigDto(): void
    {
        $request = new CreateConfigRequest();

        $request->key = 'organisation_name';
        $request->value = 'Example Organisation';
        $request->editable = true;
        $request->type = 'text';

        $dto = $request->getDto();

        $this->assertInstanceOf(CreateConfigDto::class, $dto);
    }

    public function testGetDtoMapsAllProperties(): void
    {
        $request = new CreateConfigRequest();

        $request->key = 'organisation_name';
        $request->value = 'Example Organisation';
        $request->editable = false;
        $request->type = 'text';

        $dto = $request->getDto();

        $this->assertSame('organisation_name', $dto->key);
        $this->assertSame('Example Organisation', $dto->value);
        $this->assertFalse($dto->editable);
        $this->assertSame('text', $dto->type);
    }

    public function testGetDtoWithNullType(): void
    {
        $request = new CreateConfigRequest();

        $request->key = 'organisation_name';
        $request->value = 'Example Organisation';
        $request->editable = true;
        $request->type = null;

        $dto = $request->getDto();

        $this->assertNull($dto->type);
    }

    public function testKeyFailsWhenBlank(): void
    {
        $violations = $this->createValidator()->validate('', [new NotBlank()]);

        self::assertGreaterThan(0, count($violations));
    }

    public function testKeyFailsWhenExceedsMaxLength(): void
    {
        $longKey = str_repeat('a', 36);

        $request = new CreateConfigRequest();
        $request->key = $longKey;
        $request->value = 'value';

        $validator = $this->createValidator();
        $violations = $validator->validate($request->key, [
            new Length(max: 35),
        ]);

        self::assertGreaterThan(0, count($violations));
    }

    public function testKeyFailsWithInvalidCharacters(): void
    {
        $validator = $this->createValidator();

        $violations = $validator->validate('invalid key!', [
            new Regex(pattern: '/^[A-Za-z0-9_.:-]+$/'),
        ]);

        self::assertGreaterThan(0, count($violations));
    }

    public function testKeyPassesWithAllowedCharacters(): void
    {
        $validator = $this->createValidator();

        $violations = $validator->validate('organisation_name.v1:test-2', [
            new Regex(pattern: '/^[A-Za-z0-9_.:-]+$/'),
        ]);

        self::assertCount(0, $violations);
    }

    public function testValueFailsWhenNotString(): void
    {
        $validator = $this->createValidator();

        $violations = $validator->validate(123, [
            new Type('string'),
        ]);

        self::assertGreaterThan(0, count($violations));
    }

    public function testTypeFailsWhenExceedsMaxLength(): void
    {
        $validator = $this->createValidator();

        $violations = $validator->validate(str_repeat('a', 26), [
            new Length(max: 25),
        ]);

        self::assertGreaterThan(0, count($violations));
    }

    public function testTypeAllowsNull(): void
    {
        $request = new CreateConfigRequest();
        $request->key = 'organisation_name';
        $request->value = 'Example Organisation';
        $request->type = null;

        self::assertNull($request->type);
    }

    private function createValidator(): ValidatorInterface
    {
        return Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }
}
