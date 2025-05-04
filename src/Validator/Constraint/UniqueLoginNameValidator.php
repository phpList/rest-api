<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator\Constraint;

use PhpList\Core\Domain\Repository\Identity\AdministratorRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueLoginNameValidator extends ConstraintValidator
{
    private AdministratorRepository $administratorRepository;

    public function __construct(AdministratorRepository $administratorRepository)
    {
        $this->administratorRepository = $administratorRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueLoginName) {
            throw new UnexpectedTypeException($constraint, UniqueLoginName::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $existingUser = $this->administratorRepository->findOneBy(['loginName' => $value]);

        $dto = $this->context->getObject();
        $updatingId = $dto->administratorId ?? null;

        if ($existingUser && $existingUser->getId() !== $updatingId) {
            throw new ConflictHttpException('Login already exists.');
        }
    }
}
