<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Configuration\Validator\Constraint;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Configuration\Model\Config;
use PhpList\RestBundle\Configuration\Validator\Constraint\UniqueConfigKey;
use PhpList\RestBundle\Configuration\Validator\Constraint\UniqueConfigKeyValidator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

class UniqueConfigKeyValidatorTest extends TestCase
{
    private EntityManagerInterface&MockObject $entityManager;
    private UniqueConfigKeyValidator $validator;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = new UniqueConfigKeyValidator($this->entityManager);
    }

    public function testValidateSkipsNull(): void
    {
        $this->entityManager->expects(self::never())->method('find');

        $this->validator->validate(null, new UniqueConfigKey());
    }

    public function testValidateSkipsEmptyString(): void
    {
        $this->entityManager->expects(self::never())->method('find');

        $this->validator->validate('', new UniqueConfigKey());
    }

    public function testValidateThrowsUnexpectedTypeException(): void
    {
        $this->expectException(UnexpectedTypeException::class);

        $this->validator->validate('organisation_name', $this->createMock(Constraint::class));
    }

    public function testValidateThrowsUnexpectedValueException(): void
    {
        $this->expectException(UnexpectedValueException::class);

        $this->validator->validate(123, new UniqueConfigKey());
    }

    public function testValidateThrowsConflictHttpExceptionIfConfigKeyExists(): void
    {
        $this->entityManager
            ->expects(self::once())
            ->method('find')
            ->with(Config::class, 'organisation_name')
            ->willReturn(new Config());

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('Configuration item already exists.');

        $this->validator->validate('organisation_name', new UniqueConfigKey());
    }

    public function testValidatePassesIfConfigKeyIsUnique(): void
    {
        $this->entityManager
            ->expects(self::once())
            ->method('find')
            ->with(Config::class, 'new_config_key')
            ->willReturn(null);

        $this->validator->validate('new_config_key', new UniqueConfigKey());
    }
}
