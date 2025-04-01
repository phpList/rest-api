<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueEmailValidator extends ConstraintValidator
{
    private SubscriberRepository $subscriberRepository;

    public function __construct(SubscriberRepository $subscriberRepository)
    {
        $this->subscriberRepository = $subscriberRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint UniqueEmail */

        if (null === $value || '' === $value) {
            return;
        }

        if ($this->subscriberRepository->findOneByEmail($value)) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}

