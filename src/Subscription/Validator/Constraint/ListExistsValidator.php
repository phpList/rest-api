<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Validator\Constraint;

use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ListExistsValidator extends ConstraintValidator
{
    private SubscriberListRepository $subscriberListRepository;

    public function __construct(SubscriberListRepository $subscriberListRepository)
    {
        $this->subscriberListRepository = $subscriberListRepository;
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ListExists) {
            throw new UnexpectedTypeException($constraint, ListExists::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value)) {
            throw new UnexpectedValueException($value, 'string');
        }

        $existingList = $this->subscriberListRepository->find($value);

        if (!$existingList) {
            throw new NotFoundHttpException('Subscriber list does not exists.');
        }
    }
}
