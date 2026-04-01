<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Validator\Constraint;

use PhpList\RestBundle\Messaging\Validator\Constraint\MaxPersonalNoteSize;
use PhpList\RestBundle\Messaging\Validator\Constraint\MaxPersonalNoteSizeValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MaxPersonalNoteSizeValidatorTest extends TestCase
{
    public function testSkipsWhenValueIsNullOrEmpty(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator = new MaxPersonalNoteSizeValidator(10);
        $validator->initialize($context);

        $constraint = new MaxPersonalNoteSize();
        $validator->validate(null, $constraint);
        $validator->validate('', $constraint);

        $this->assertTrue(true);
    }

    public function testSkipsWhenMaxSizeIsNullOrNegative(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validatorNull = new MaxPersonalNoteSizeValidator(null);
        $validatorNull->initialize($context);
        $validatorNull->validate('anything', new MaxPersonalNoteSize());

        $validatorNeg = new MaxPersonalNoteSizeValidator(-1);
        $validatorNeg->initialize($context);
        $validatorNeg->validate('anything', new MaxPersonalNoteSize());

        $this->assertTrue(true);
    }

    public function testNoViolationWhenWithinOrAtLimit(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');
        // sizeLimit = 20
        $max = 10;
        $validator = new MaxPersonalNoteSizeValidator($max);
        $validator->initialize($context);

        $constraint = new MaxPersonalNoteSize();
        // exactly at limit
        $within = str_repeat('a', 20);
        $validator->validate($within, $constraint);
        // below limit
        $short = str_repeat('b', 5);
        $validator->validate($short, $constraint);

        $this->assertTrue(true);
    }

    public function testViolationWhenExceedsLimit(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('setParameter')
            ->with('{{ limit }}', '4')
            ->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('Your personal note must be at most {{ limit }} characters long.')
            ->willReturn($builder);
        // sizeLimit = 4
        $validator = new MaxPersonalNoteSizeValidator(2);
        $validator->initialize($context);

        $constraint = new MaxPersonalNoteSize();
        // length 5 > 4
        $value = str_repeat('x', 5);
        $validator->validate($value, $constraint);
    }
}
