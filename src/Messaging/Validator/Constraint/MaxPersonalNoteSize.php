<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MaxPersonalNoteSize extends Constraint
{
    public string $message = 'Your personal note must be at most {{ limit }} characters long.';
}
