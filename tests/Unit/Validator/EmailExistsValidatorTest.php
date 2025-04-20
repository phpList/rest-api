<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Validator;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Validator\EmailExists;
use PhpList\RestBundle\Validator\EmailExistsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class EmailExistsValidatorTest extends TestCase
{
    private SubscriberRepository $subscriberRepository;
    private EmailExistsValidator $validator;

    protected function setUp(): void
    {
        $this->subscriberRepository = $this->createMock(SubscriberRepository::class);
        $context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new EmailExistsValidator($this->subscriberRepository);
        $this->validator->initialize($context);
    }

    public function testValidateSkipsNull(): void
    {
        $this->subscriberRepository->expects($this->never())->method('findOneBy');
        $this->validator->validate(null, new EmailExists());
        $this->assertTrue(true); // to mark test as passed
    }

    public function testValidateSkipsEmptyString(): void
    {
        $this->subscriberRepository->expects($this->never())->method('findOneBy');
        $this->validator->validate('', new EmailExists());
        $this->assertTrue(true);
    }

    public function testValidateThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('test@example.com', $this->createMock(Constraint::class));
    }

    public function testValidateThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new EmailExists());
    }

    public function testValidateThrowsNotFoundExceptionIfEmailDoesNotExist(): void
    {
        $this->subscriberRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'missing@example.com'])
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Subscriber with email does not exists.');

        $this->validator->validate('missing@example.com', new EmailExists());
    }

    public function testValidatePassesIfEmailExists(): void
    {
        $subscriber = $this->createMock(Subscriber::class);

        $this->subscriberRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['email' => 'found@example.com'])
            ->willReturn($subscriber);

        $this->validator->validate('found@example.com', new EmailExists());
        $this->assertTrue(true);
    }
}
