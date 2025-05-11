<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Validator\Constraint;

use PhpList\Core\Domain\Messaging\Repository\TemplateRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class TemplateExistsValidator extends ConstraintValidator
{
    private TemplateRepository $templateRepository;

    public function __construct(TemplateRepository $templateRepository)
    {
        $this->templateRepository = $templateRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof TemplateExists) {
            throw new UnexpectedTypeException($constraint, TemplateExists::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_int($value)) {
            throw new UnexpectedValueException($value, 'integer');
        }

        $existingUser = $this->templateRepository->find($value);

        if (!$existingUser) {
            throw new ConflictHttpException('Template with that id does not exists.');
        }
    }
}
