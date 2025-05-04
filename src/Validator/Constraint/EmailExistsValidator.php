<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator\Constraint;

use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class EmailExistsValidator extends ConstraintValidator
{
    private SubscriberRepository $subscriberRepository;

    public function __construct(SubscriberRepository $subscriberRepository)
    {
        $this->subscriberRepository = $subscriberRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof EmailExists) {
            throw new UnexpectedTypeException($constraint, EmailExists::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $existingUser = $this->subscriberRepository->findOneBy(['email' => $value]);

        if (!$existingUser) {
            throw new NotFoundHttpException('Subscriber with email does not exists.');
        }
    }
}
