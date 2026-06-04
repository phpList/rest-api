<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Validator\Constraint;

use PhpList\RestBundle\Subscription\Validator\Constraint\ListExistsPublic;
use PHPUnit\Framework\TestCase;

class ListExistsPublicTest extends TestCase
{
    public function testConstructorUsesDefaultValues(): void
    {
        $constraint = new ListExistsPublic();

        $this->assertSame('strict', $constraint->mode);
        $this->assertSame('Subscriber list with id "{{ value }}" does not exists.', $constraint->message);
    }

    public function testConstructorAllowsOverridingValues(): void
    {
        $constraint = new ListExistsPublic(mode: 'relaxed', message: 'Custom message.');

        $this->assertSame('relaxed', $constraint->mode);
        $this->assertSame('Custom message.', $constraint->message);
    }
}
