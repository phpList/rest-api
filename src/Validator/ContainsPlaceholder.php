<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class ContainsPlaceholder extends Constraint
{
    public string $placeholder = '[CONTENT]';
    public string $message = 'The content must include at least one "{{ placeholder }}" placeholder.';
}
