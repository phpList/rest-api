<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_CLASS)]
class ValidPublicSubscription extends Constraint
{
    public string $requiredAttributeMessage = 'This attribute is required.';
    public string $invalidValueMessage = 'Invalid value.';
    public string $unknownAttributeMessage = 'Unknown attribute.';
    public string $invalidEmailMessage = 'Invalid email address.';
    public bool $rejectUnknownAttributes = true;

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }

    public function validatedBy(): string
    {
        return ValidPublicSubscriptionValidator::class;
    }
}

