<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\RestBundle\Messaging\Request\Message\MessageOptionsRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class MessageOptionsRequestTest extends TestCase
{
    /**
     * @dataProvider fromFieldProvider
     */
    public function testFromFieldValidation(string $fromField, int $expectedViolations): void
    {
        $request = new MessageOptionsRequest();
        $request->fromField = $fromField;
        $request->toField = '';

        $violations = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()
            ->validate($request);

        $this->assertCount($expectedViolations, $violations);
    }

    public static function fromFieldProvider(): array
    {
        return [
            'plain email' => ['bryan@imark-pen.com', 0],
            'name and email' => ['Bryan Irby bryan@imark-pen.com', 0],
            'name and bracketed email' => ['Bryan Irby <bryan@imark-pen.com>', 0],
            'leading space' => [' My Name <my@email.com>', 0],
            'name only' => ['Bryan Irby', 1],
            'invalid email' => ['Bryan Irby bryan@', 1],
            'invalid bracketed' => ['Bryan <nope>', 1],
        ];
    }
}
