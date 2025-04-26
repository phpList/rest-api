<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class ContainsPlaceholderValidator extends ConstraintValidator
{
    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ContainsPlaceholder) {
            throw new InvalidArgumentException(sprintf('%s expects %s.', __CLASS__, ContainsPlaceholder::class));
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!str_contains($value, $constraint->placeholder)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ placeholder }}', $constraint->placeholder)
                ->addViolation();
        }
    }
}
