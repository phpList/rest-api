<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Validator\Constraint;

use PhpList\Core\Domain\Messaging\Repository\TemplateRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueTemplateTitleValidator extends ConstraintValidator
{
    public function __construct(private readonly TemplateRepository $templateRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueTemplateTitle) {
            throw new UnexpectedTypeException($constraint, UniqueTemplateTitle::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $existingTemplate = $this->templateRepository->findOneBy(['title' => $value]);
        $dto = $this->context->getObject();
        $updatingId = $dto->templateId ?? null;

        if ($existingTemplate && (null === $updatingId || $existingTemplate->getId() !== $updatingId)) {
            throw new ConflictHttpException('Template title already exists.');
        }
    }
}
