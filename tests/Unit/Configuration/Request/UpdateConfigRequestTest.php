<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Configuration\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Configuration\Request\UpdateConfigRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class UpdateConfigRequestTest extends TestCase
{
    public function testGetDtoReturnsValueAsArray(): void
    {
        $request = new UpdateConfigRequest();
        $request->value = 'Example Organisation';

        self::assertSame(['value' => 'Example Organisation'], $request->getDto());
    }

    public function testValidationPassesForValidStringValue(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $request = new UpdateConfigRequest();
        $request->value = 'Example Organisation';

        $violations = $validator->validate($request);

        self::assertCount(0, $violations);
    }

    public function testValidationFailsForEmptyStringIsActuallyValid(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $request = new UpdateConfigRequest();
        $request->value = '';

        $violations = $validator->validate($request);

        self::assertCount(0, $violations);
    }

    public function testValidationFailsForNullString(): void
    {
        $validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();

        $request = new UpdateConfigRequest();

        $violations = $validator->validate($request);

        self::assertCount(1, $violations);
    }

    public function testImplementsRequestInterface(): void
    {
        $request = new UpdateConfigRequest();

        self::assertInstanceOf(RequestInterface::class, $request);
    }
}
