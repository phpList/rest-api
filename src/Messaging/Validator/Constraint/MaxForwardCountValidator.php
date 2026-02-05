<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Validator\Constraint;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxForwardCountValidator extends ConstraintValidator
{
    public function __construct(
        #[Autowire('%phplist.forward_email_count%')] private readonly string $maxForward
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxForwardCount) {
            return;
        }

        if (!is_array($value)) {
            return;
        }

        $normalized = [];
        foreach ($value as $item) {
            if (!is_string($item)) {
                continue;
            }
            $email = strtolower(trim($item));
            if ($email === '') {
                continue;
            }
            $normalized[$email] = true;
        }

        $uniqueCount = count($normalized);

        if ($uniqueCount > $this->maxForward) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ limit }}', $this->maxForward)
                ->addViolation();
        }
    }
}
