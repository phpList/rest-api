<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Messaging\Validator\Constraint;

use PhpList\Core\Domain\Messaging\Model\Template;
use PhpList\Core\Domain\Messaging\Repository\TemplateRepository;
use PhpList\RestBundle\Messaging\Validator\Constraint\UniqueTemplateTitle;
use PhpList\RestBundle\Messaging\Validator\Constraint\UniqueTemplateTitleValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueTemplateTitleValidatorTest extends TestCase
{
    private TemplateRepository&MockObject $templateRepository;
    private UniqueTemplateTitleValidator $validator;
    private ExecutionContextInterface&MockObject $context;

    protected function setUp(): void
    {
        $this->templateRepository = $this->createMock(TemplateRepository::class);
        $this->context = $this->createMock(ExecutionContextInterface::class);

        $this->validator = new UniqueTemplateTitleValidator($this->templateRepository);
        $this->validator->initialize($this->context);
    }

    public function testValidateSkipsNull(): void
    {
        $this->templateRepository->expects($this->never())->method('findOneBy');
        $this->validator->validate(null, new UniqueTemplateTitle());
        $this->assertTrue(true);
    }

    public function testValidateSkipsEmptyString(): void
    {
        $this->templateRepository->expects($this->never())->method('findOneBy');
        $this->validator->validate('', new UniqueTemplateTitle());
        $this->assertTrue(true);
    }

    public function testValidateThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate('title', $this->createMock(Constraint::class));
    }

    public function testValidateThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->validator->validate(123, new UniqueTemplateTitle());
    }

    public function testValidateThrowsConflictHttpExceptionIfTemplateTitleExists(): void
    {
        $existingTemplate = $this->createMock(Template::class);

        $this->templateRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'Newsletter Template'])
            ->willReturn($existingTemplate);

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Template title already exists.');

        $this->validator->validate('Newsletter Template', new UniqueTemplateTitle());
    }

    public function testValidatePassesIfTemplateTitleIsUnique(): void
    {
        $this->templateRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'Unique Template'])
            ->willReturn(null);

        $this->validator->validate('Unique Template', new UniqueTemplateTitle());
        $this->assertTrue(true);
    }

    public function testValidateSkipsConflictForSameTemplateOnUpdate(): void
    {
        $existingTemplate = $this->createConfiguredMock(Template::class, ['getId' => 10]);

        $this->templateRepository
            ->expects($this->once())
            ->method('findOneBy')
            ->with(['title' => 'Existing Title'])
            ->willReturn($existingTemplate);

        $dto = new class {
            public int $templateId = 10;
        };

        $this->context
            ->method('getObject')
            ->willReturn($dto);

        $this->validator->validate('Existing Title', new UniqueTemplateTitle());
        $this->assertTrue(true);
    }
}
