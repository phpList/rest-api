<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Request;

use PhpList\RestBundle\Messaging\Request\Message\MessageContentRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class MessageContentRequestTest extends TestCase
{
    public function testValidateNoClickTrackLinksWithCleanTextDoesNotAddViolation(): void
    {
        $request = new MessageContentRequest();
        $request->text = 'Hello, this is a normal message body.';

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $request->validateNoClickTrackLinks($context);
    }

    public function testValidateNoClickTrackLinksWithLtPhpPatternAddsViolation(): void
    {
        $request = new MessageContentRequest();
        $request->text = 'See this link: https://example.com/lt.php?id=abcdefghijklmnop';

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('atPath')->with('text')->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($builder);

        $request->validateNoClickTrackLinks($context);
    }

    public function testValidateNoClickTrackLinksWithLinkMapPatternAddsViolation(): void
    {
        $request = new MessageContentRequest();
        $request->text = 'Mapped link: https://example.com/lt/abcdefghijklmnopqrstuv';

        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())->method('atPath')->with('text')->willReturnSelf();
        $builder->expects($this->once())->method('addViolation');

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->willReturn($builder);

        $request->validateNoClickTrackLinks($context);
    }
}
