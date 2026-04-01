<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Validator\Constraint;

use PhpList\RestBundle\Messaging\Validator\Constraint\MaxForwardCount;
use PhpList\RestBundle\Messaging\Validator\Constraint\MaxForwardCountValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MaxForwardCountValidatorTest extends TestCase
{
    public function testSkipsWhenValueIsNotArray(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator = new MaxForwardCountValidator(5);
        $validator->initialize($context);

        $constraint = new MaxForwardCount();
        $validator->validate('not-an-array', $constraint);

        $this->assertTrue(true);
    }

    public function testTriggersViolationWhenUniqueCountExceedsLimit(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('setParameter')
            ->with('{{ limit }}', '1')
            ->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('You can forward to at most {{ limit }} recipients.')
            ->willReturn($builder);

        $validator = new MaxForwardCountValidator(1);
        $validator->initialize($context);

        $constraint = new MaxForwardCount();
        $emails = [
            '  A@Example.com  ',
            // duplicate after trim+lower
            'a@example.com',
            'b@example.com',
            // ignored empty
            '',
            // ignored non-string
            null,
            // ignored non-string
            123,
        ];

        $validator->validate($emails, $constraint);
    }

    public function testNoViolationWhenWithinLimit(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator = new MaxForwardCountValidator(3);
        $validator->initialize($context);

        $constraint = new MaxForwardCount();
        $emails = ['a@example.com', 'b@example.com', 'a@example.com'];

        $validator->validate($emails, $constraint);
        $this->assertTrue(true);
    }
}
