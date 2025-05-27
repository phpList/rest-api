<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Repository\SubscriberRepository;
use PhpList\RestBundle\Subscription\Validator\Constraint\UniqueEmail;
use PhpList\RestBundle\Subscription\Validator\Constraint\UniqueEmailValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueEmailValidatorTest extends TestCase
{
    private EntityManagerInterface|MockObject $entityManager;
    private UniqueEmailValidator $validator;
    private ExecutionContextInterface|MockObject $context;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = new UniqueEmailValidator($this->entityManager);
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
        $this->entityManager->expects(self::never())->method('getRepository');

        $this->validator->validate(null, new UniqueEmail(Subscriber::class));
        $this->validator->validate('', new UniqueEmail(Subscriber::class));

        $this->addToAssertionCount(1);
    }

    public function testThrowsUnexpectedValueExceptionForNonString(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new UniqueEmail(Subscriber::class));
    }

    public function testThrowsConflictHttpExceptionWhenEmailAlreadyExistsWithDifferentId(): void
    {
        $email = 'foo@bar.com';

        $existingUser = $this->createConfiguredMock(Subscriber::class, [
            'getId' => 99
        ]);

        $repo = $this->createMock(SubscriberRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($existingUser);

        $this->entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Subscriber::class)
            ->willReturn($repo);

        $dto = new class {
            public int $subscriberId = 100;
        };

        $this->context
            ->method('getObject')
            ->willReturn($dto);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Email already exists.');

        $this->validator->validate($email, new UniqueEmail(Subscriber::class));
    }

    public function testAllowsSameEmailForSameSubscriberId(): void
    {
        $email = 'foo@bar.com';

        $existingUser = $this->createConfiguredMock(Subscriber::class, [
            'getId' => 100
        ]);

        $repo = $this->createMock(SubscriberRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => $email])
            ->willReturn($existingUser);

        $this->entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Subscriber::class)
            ->willReturn($repo);

        $dto = new class {
            public int $subscriberId = 100;
        };

        $this->context
            ->method('getObject')
            ->willReturn($dto);

        $this->validator->validate($email, new UniqueEmail(Subscriber::class));

        $this->addToAssertionCount(1);
    }

    public function testAllowsUniqueEmailWhenNoExistingSubscriber(): void
    {
        $repo = $this->createMock(SubscriberRepository::class);
        $repo->expects(self::once())
            ->method('findOneBy')
            ->with(['email' => 'new@example.com'])
            ->willReturn(null);

        $this->entityManager
            ->expects(self::once())
            ->method('getRepository')
            ->with(Subscriber::class)
            ->willReturn($repo);

        $dto = new class {
            public int $subscriberId = 200;
        };

        $this->context
            ->method('getObject')
            ->willReturn($dto);

        $this->validator->validate('new@example.com', new UniqueEmail(Subscriber::class));

        $this->addToAssertionCount(1);
    }
}
