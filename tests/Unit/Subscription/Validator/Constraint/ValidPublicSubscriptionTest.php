<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Validator\Constraint;

use PhpList\RestBundle\Subscription\Validator\Constraint\ValidPublicSubscription;
use PhpList\RestBundle\Subscription\Validator\Constraint\ValidPublicSubscriptionValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;

class ValidPublicSubscriptionTest extends TestCase
{
    public function testHasExpectedDefaultMessagesAndFlags(): void
    {
        $constraint = new ValidPublicSubscription();

        $this->assertSame('This attribute is required.', $constraint->requiredAttributeMessage);
        $this->assertSame('Invalid value.', $constraint->invalidValueMessage);
        $this->assertSame('Unknown attribute.', $constraint->unknownAttributeMessage);
        $this->assertSame('Invalid email address.', $constraint->invalidEmailMessage);
        $this->assertTrue($constraint->rejectUnknownAttributes);
    }

    public function testGetTargetsReturnsClassConstraint(): void
    {
        $constraint = new ValidPublicSubscription();

        $this->assertSame(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidatedByReturnsValidatorClass(): void
    {
        $constraint = new ValidPublicSubscription();

        $this->assertSame(ValidPublicSubscriptionValidator::class, $constraint->validatedBy());
    }
}
