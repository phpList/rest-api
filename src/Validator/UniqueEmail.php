<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::IS_REPEATABLE)]
class UniqueEmail extends Constraint
{
    public string $message = 'The email "{{ value }}" is already in use.';
}
