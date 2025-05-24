<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Validator\Constraint;

use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExists;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExistsValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class ListExistsValidatorTest extends TestCase
{
    private SubscriberListRepository&MockObject $subscriberListRepository;
    private ListExistsValidator $validator;

    protected function setUp(): void
    {
        $this->subscriberListRepository = $this->createMock(SubscriberListRepository::class);
        $context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ListExistsValidator($this->subscriberListRepository);
        $this->validator->initialize($context);
    }

    public function testValidateSkipsNull(): void
    {
        $this->subscriberListRepository->expects($this->never())->method('find');
        $this->validator->validate(null, new ListExists());
        $this->assertTrue(true);
    }

    public function testValidateSkipsEmptyString(): void
    {
        $this->subscriberListRepository->expects($this->never())->method('find');
        $this->validator->validate('', new ListExists());
        $this->assertTrue(true);
    }

    public function testValidateThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('123', $this->createMock(Constraint::class));
    }

    public function testValidateThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new ListExists());
    }

    public function testValidateThrowsNotFoundExceptionIfListDoesNotExist(): void
    {
        $this->subscriberListRepository
            ->expects($this->once())
            ->method('find')
            ->with('123')
            ->willReturn(null);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Subscriber list does not exists.');

        $this->validator->validate('123', new ListExists());
    }

    public function testValidatePassesIfListExists(): void
    {
        $subscriberList = $this->createMock(SubscriberList::class);

        $this->subscriberListRepository
            ->expects($this->once())
            ->method('find')
            ->with('123')
            ->willReturn($subscriberList);

        $this->validator->validate('123', new ListExists());
        $this->assertTrue(true);
    }
}
