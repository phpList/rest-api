<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Validator\Constraint;

use PhpList\Core\Domain\Identity\Repository\AdministratorRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueEmailValidator extends ConstraintValidator
{
    public function __construct(private readonly AdministratorRepository $repository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueEmail) {
            throw new UnexpectedTypeException($constraint, UniqueEmail::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $existingUser = $this->repository->findOneBy(['email' => $value]);

        $dto = $this->context->getObject();
        $updatingId = $dto->administratorId ?? null;

        if ($existingUser && $existingUser->getId() !== $updatingId) {
            throw new ConflictHttpException('Email already exists.');
        }
    }
}
