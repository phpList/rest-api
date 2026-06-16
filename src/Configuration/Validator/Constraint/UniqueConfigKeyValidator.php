<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Configuration\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Configuration\Model\Config;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueConfigKeyValidator extends ConstraintValidator
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueConfigKey) {
            throw new UnexpectedTypeException($constraint, UniqueConfigKey::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        if ($this->entityManager->find(Config::class, $value) instanceof Config) {
            throw new ConflictHttpException($constraint->message);
        }
    }
}
