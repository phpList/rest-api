<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\RestBundle\Messaging\Request\Message\MessageFormatRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

class MessageFormatRequestTest extends TestCase
{
    /**
     * @dataProvider validValuesProvider
     */
    public function testValidValuesAreAcceptedAndSavedAsLowercase(string $value, string $expected): void
    {
        $request = new MessageFormatRequest();
        $request->sendFormat = $value;

        $this->assertCount(0, $this->validate($request));
        $this->assertSame($expected, $request->getDto()->sendFormat);
    }

    public function testUnknownValueIsRejected(): void
    {
        $request = new MessageFormatRequest();
        $request->sendFormat = 'pdf';

        $this->assertCount(1, $this->validate($request));
    }

    public static function validValuesProvider(): array
    {
        return [
            'lowercase' => ['html', 'html'],
            'uppercase' => ['TEXT', 'text'],
            'mixed case' => ['Invite', 'invite'],
        ];
    }

    private function validate(MessageFormatRequest $request): iterable
    {
        return Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator()->validate($request);
    }
}
