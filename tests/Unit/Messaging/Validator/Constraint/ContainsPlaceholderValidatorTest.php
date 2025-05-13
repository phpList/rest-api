<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Validator\Constraint;

use PhpList\RestBundle\Messaging\Validator\Constraint\ContainsPlaceholder;
use PhpList\RestBundle\Messaging\Validator\Constraint\ContainsPlaceholderValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ContainsPlaceholderValidatorTest extends TestCase
{
    public function testValidateWithValidPlaceholder(): void
    {
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator = new ContainsPlaceholderValidator();
        $validator->initialize($context);

        $constraint = new ContainsPlaceholder(['placeholder' => '[CONTENT]']);
        $validator->validate('<html>[CONTENT]</html>', $constraint);

        $this->assertTrue(true);
    }

    public function testValidateWithMissingPlaceholder(): void
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('setParameter')->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with('The content must include the "{{ placeholder }}" placeholder.')
            ->willReturn($builder);

        $validator = new ContainsPlaceholderValidator();
        $validator->initialize($context);

        $constraint = new ContainsPlaceholder([
            'placeholder' => '[CONTENT]',
            'message' => 'The content must include the "{{ placeholder }}" placeholder.'
        ]);

        $validator->validate('<html>no placeholder here</html>', $constraint);
    }
}
