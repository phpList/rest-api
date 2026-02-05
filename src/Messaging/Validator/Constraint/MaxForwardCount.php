<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class MaxForwardCount extends Constraint
{
    public string $message = 'You can forward to at most {{ limit }} recipients.';
}
