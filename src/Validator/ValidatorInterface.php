<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use PhpList\RestBundle\Entity\Dto\ValidationContext;

interface ValidatorInterface
{
    public function validate(mixed $value, ValidationContext $context = null): void;
}
