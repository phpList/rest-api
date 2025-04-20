<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Validator;

use PhpList\Core\Domain\Model\Messaging\Template;
use PhpList\Core\Domain\Repository\Messaging\TemplateRepository;
use PhpList\RestBundle\Validator\TemplateExists;
use PhpList\RestBundle\Validator\TemplateExistsValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class TemplateExistsValidatorTest extends TestCase
{
    private TemplateRepository $templateRepository;
    private TemplateExistsValidator $validator;

    protected function setUp(): void
    {
        $this->templateRepository = $this->createMock(TemplateRepository::class);
        $context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new TemplateExistsValidator($this->templateRepository);
        $this->validator->initialize($context);
    }

    public function testValidateSkipsNull(): void
    {
        $this->templateRepository->expects($this->never())->method('find');
        $this->validator->validate(null, new TemplateExists());
        $this->assertTrue(true);
    }

    public function testValidateSkipsEmptyString(): void
    {
        $this->templateRepository->expects($this->never())->method('find');
        $this->validator->validate('', new TemplateExists());
        $this->assertTrue(true);
    }

    public function testValidateThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate(1, $this->createMock(Constraint::class));
    }

    public function testValidateThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate('not-an-int', new TemplateExists());
    }

    public function testValidateThrowsConflictHttpExceptionIfTemplateDoesNotExist(): void
    {
        $this->templateRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template with that id does not exists.');

        $this->validator->validate(999, new TemplateExists());
    }

    public function testValidatePassesIfTemplateExists(): void
    {
        $template = $this->createMock(Template::class);

        $this->templateRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($template);

        $this->validator->validate(1, new TemplateExists());
        $this->assertTrue(true);
    }
}
