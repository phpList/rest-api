<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Validator\Constraint;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Repository\AdministratorRepository;
use PhpList\RestBundle\Identity\Validator\Constraint\UniqueLoginName;
use PhpList\RestBundle\Identity\Validator\Constraint\UniqueLoginNameValidator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class UniqueLoginNameValidatorTest extends TestCase
{
    public function testValidateWithUniqueLoginName(): void
    {
        $repository = $this->createMock(AdministratorRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())->method('buildViolation');

        $validator = new UniqueLoginNameValidator($repository);
        $validator->initialize($context);

        $constraint = new UniqueLoginName();
        $validator->validate('new_login', $constraint);

        $this->assertTrue(true);
    }

    public function testValidateThrowsConflictForExistingLoginName(): void
    {
        $admin = $this->createMock(Administrator::class);
        $admin->method('getId')->willReturn(2);

        $repository = $this->createMock(AdministratorRepository::class);
        $repository->method('findOneBy')->willReturn($admin);

        $context = $this->createMock(ExecutionContextInterface::class);
        $dto = new class {
            public $administratorId = 1;
        };

        $context->method('getObject')->willReturn($dto);

        $validator = new UniqueLoginNameValidator($repository);
        $validator->initialize($context);

        $this->expectException(ConflictHttpException::class);

        $constraint = new UniqueLoginName();
        $validator->validate('duplicate_login', $constraint);
    }

    public function testValidateSkipsConflictIfSameAdministrator(): void
    {
        $admin = $this->createMock(Administrator::class);
        $admin->method('getId')->willReturn(1);

        $repository = $this->createMock(AdministratorRepository::class);
        $repository->method('findOneBy')->willReturn($admin);

        $context = $this->createMock(ExecutionContextInterface::class);
        $dto = new class {
            public $administratorId = 1;
        };

        $context->method('getObject')->willReturn($dto);

        $validator = new UniqueLoginNameValidator($repository);
        $validator->initialize($context);

        $constraint = new UniqueLoginName();
        $validator->validate('same_login', $constraint);

        $this->assertTrue(true);
    }
}
