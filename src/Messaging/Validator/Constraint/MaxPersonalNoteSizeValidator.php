<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Validator\Constraint;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class MaxPersonalNoteSizeValidator extends ConstraintValidator
{
    public function __construct(
        #[Autowire('%phplist.forward_personal_note_size%')] private readonly ?int $maxSize
    ) {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof MaxPersonalNoteSize) {
            return;
        }

        if ($value === null || $value === '' || $this->maxSize === null || $this->maxSize < 0) {
            return;
        }

        if (!is_string($value)) {
            return;
        }

        $sizeLimit = $this->maxSize * 2;
        $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);

        if ($length > $sizeLimit) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ limit }}', (string) $sizeLimit)
                ->addViolation();
        }
    }
}
