<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Integration\Validator;

use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Validator\UniqueEmail;
use PhpList\RestBundle\Validator\UniqueEmailValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueEmailValidatorTest extends TestCase
{
    private SubscriberRepository|MockObject $repo;
    private UniqueEmailValidator $validator;

    protected function setUp(): void
    {
        $this->repo = $this->createMock(SubscriberRepository::class);
        $this->validator = new UniqueEmailValidator($this->repo);
    }

    public function testThrowsUnexpectedTypeExceptionWhenConstraintIsWrong(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('anything', $this->createMock(Constraint::class));
    }

    public function testSkipsValidationForNullOrEmpty(): void
    {
        $this->repo->expects(self::never())->method('findOneBy');

        $this->validator->validate(null, new UniqueEmail());
        $this->validator->validate('', new UniqueEmail());

        $this->addToAssertionCount(1);
    }

    public function testThrowsUnexpectedValueExceptionForNonString(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new UniqueEmail());
    }

    public function testThrowsConflictHttpExceptionWhenEmailAlreadyExists(): void
    {
        $email = 'foo@bar.com';

        $this->repo
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn((object)['email' => $email]);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Email already exists.');

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testAllowsUniqueEmailWhenNoExistingSubscriber(): void
    {
        $this->repo
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $this->validator->validate('new@example.com', new UniqueEmail());

        $this->addToAssertionCount(1);
    }
}
