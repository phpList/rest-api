<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Validator;

use PhpList\Core\Domain\Repository\Subscription\SubscriberListRepository;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
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

        if (!is_int($value)) {
            throw new UnexpectedValueException($value, 'integer');
        }

        $list = $this->subscriberListRepository->find($value);

        if (!$list) {
            throw new ConflictHttpException('Subscriber list does not exists.');
        }
    }
}
