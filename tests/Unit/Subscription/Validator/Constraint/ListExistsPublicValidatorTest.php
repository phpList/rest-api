<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Subscription\Validator\Constraint;

use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExistsPublic;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExistsPublicValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class ListExistsPublicValidatorTest extends TestCase
{
    private SubscriberListRepository&MockObject $subscriberListRepository;
    private ListExistsPublicValidator $validator;

    protected function setUp(): void
    {
        $this->subscriberListRepository = $this->createMock(SubscriberListRepository::class);
        $context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new ListExistsPublicValidator($this->subscriberListRepository);
        $this->validator->initialize($context);
    }

    public function testValidateSkipsNull(): void
    {
        $this->subscriberListRepository->expects($this->never())->method('findBy');
        $this->validator->validate(null, new ListExistsPublic());
        $this->assertTrue(true);
    }

    public function testValidateSkipsEmptyString(): void
    {
        $this->subscriberListRepository->expects($this->never())->method('findBy');
        $this->validator->validate('', new ListExistsPublic());
        $this->assertTrue(true);
    }

    public function testValidateThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(123, $this->createMock(Constraint::class));
    }

    public function testValidateThrowsNotFoundExceptionIfListDoesNotExist(): void
    {
        $this->subscriberListRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => 123, 'public' => true])
            ->willReturn([]);

        $this->expectException(NotFoundHttpException::class);
        $this->expectExceptionMessage('Subscriber list does not exists.');

        $this->validator->validate('123', new ListExistsPublic());
    }

    public function testValidatePassesIfPublicListExists(): void
    {
        $subscriberList = $this->createMock(SubscriberList::class);

        $this->subscriberListRepository
            ->expects($this->once())
            ->method('findBy')
            ->with(['id' => 123, 'public' => true])
            ->willReturn([$subscriberList]);

        $this->validator->validate('123', new ListExistsPublic());
        $this->assertTrue(true);
    }
}
