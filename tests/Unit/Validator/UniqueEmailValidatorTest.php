<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Validator;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Validator\UniqueEmail;
use PhpList\RestBundle\Validator\UniqueEmailValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueEmailValidatorTest extends TestCase
{
    private SubscriberRepository|MockObject $repository;
    private UniqueEmailValidator $validator;
    private ExecutionContextInterface|MockObject $context;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SubscriberRepository::class);
        $this->validator = new UniqueEmailValidator($this->repository);
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);
    }

    public function testThrowsUnexpectedTypeExceptionWhenConstraintIsWrong(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('anything', $this->createMock(Constraint::class));
    }

    public function testSkipsValidationForNullOrEmpty(): void
    {
        $this->repository->expects(self::never())->method('findOneBy');

        $this->validator->validate(null, new UniqueEmail());
        $this->validator->validate('', new UniqueEmail());

        $this->addToAssertionCount(1);
    }

    public function testThrowsUnexpectedValueExceptionForNonString(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new UniqueEmail());
    }

    public function testThrowsConflictHttpExceptionWhenEmailAlreadyExistsWithDifferentId(): void
    {
        $email = 'foo@bar.com';

        $existingUser = $this->createConfiguredMock(Subscriber::class, [
            'getId' => 99
        ]);

        $this->repository
            ->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($existingUser);

        $dto = new class {
            public int $subscriberId = 100;
        };

        $this->context
            ->method('getObject')
            ->willReturn($dto);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Email already exists.');

        $this->validator->validate($email, new UniqueEmail());
    }

    public function testAllowsSameEmailForSameSubscriberId(): void
    {
        $email = 'foo@bar.com';

        $existingUser = $this->createConfiguredMock(Subscriber::class, [
            'getId' => 100
        ]);

        $this->repository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn($existingUser);

        $dto = new class {
            public int $subscriberId = 100;
        };

        $this->context
            ->method('getObject')
            ->willReturn($dto);

        $this->validator->validate($email, new UniqueEmail());

        $this->addToAssertionCount(1);
    }

    public function testAllowsUniqueEmailWhenNoExistingSubscriber(): void
    {
        $this->repository
            ->expects(self::once())
            ->method('findOneBy')
            ->willReturn(null);

        $dto = new class {
            public int $subscriberId = 200;
        };

        $this->context
            ->method('getObject')
            ->willReturn($dto);

        $this->validator->validate('new@example.com', new UniqueEmail());

        $this->addToAssertionCount(1);
    }
}
