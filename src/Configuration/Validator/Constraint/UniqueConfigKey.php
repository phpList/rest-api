<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Configuration\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class UniqueConfigKey extends Constraint
{
    public string $message = 'Configuration item already exists.';
}
