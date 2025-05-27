<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Validator\Constraint;

use Symfony\Component\Validator\Constraint;

#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD | \Attribute::IS_REPEATABLE)]
class UniqueEmail extends Constraint
{
    public string $message = 'This email is already in use.';
    public string $entityClass;

    public function __construct(string $entityClass)
    {
        parent::__construct([]);
        $this->entityClass = $entityClass;
    }

    public function validatedBy(): string
    {
        return UniqueEmailValidator::class;
    }
}
