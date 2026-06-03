<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Validator\Constraint;

use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ListExistsPublicValidator extends ConstraintValidator
{
    public function __construct(private readonly SubscriberListRepository $subscriberListRepository)
    {
    }

    public function validate($value, Constraint $constraint): void
    {
        if (!$constraint instanceof ListExistsPublic) {
            throw new UnexpectedTypeException($constraint, ListExistsPublic::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $existingList = $this->subscriberListRepository->findBy(['id' => (int)$value, 'public' => true]);

        if (!$existingList) {
            throw new NotFoundHttpException('Subscriber list does not exists.');
        }
    }
}
