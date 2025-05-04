<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Tests\Unit\Service\Manager;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\Core\Security\HashGenerator;
use PhpList\RestBundle\Entity\Request\CreateAdministratorRequest;
use PhpList\RestBundle\Entity\Request\UpdateAdministratorRequest;
use PhpList\RestBundle\Service\Manager\AdministratorManager;
use PHPUnit\Framework\TestCase;

class AdministratorManagerTest extends TestCase
{
    public function testCreateAdministrator(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $hashGenerator = $this->createMock(HashGenerator::class);

        $dto = new CreateAdministratorRequest();
        $dto->loginName = 'admin';
        $dto->email = 'admin@example.com';
        $dto->superUser = true;
        $dto->password = 'securepass';

        $hashGenerator->expects($this->once())
            ->method('createPasswordHash')
            ->with('securepass')
            ->willReturn('hashed_pass');

        $entityManager->expects($this->once())->method('persist');
        $entityManager->expects($this->once())->method('flush');

        $manager = new AdministratorManager($entityManager, $hashGenerator);
        $admin = $manager->createAdministrator($dto);

        $this->assertInstanceOf(Administrator::class, $admin);
        $this->assertEquals('admin', $admin->getLoginName());
        $this->assertEquals('admin@example.com', $admin->getEmail());
        $this->assertEquals(true, $admin->isSuperUser());
        $this->assertEquals('hashed_pass', $admin->getPasswordHash());
    }

    public function testUpdateAdministrator(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $hashGenerator = $this->createMock(HashGenerator::class);

        $admin = new Administrator();
        $admin->setLoginName('old');
        $admin->setEmail('old@example.com');
        $admin->setSuperUser(false);
        $admin->setPasswordHash('old_hash');

        $dto = new UpdateAdministratorRequest();
        $dto->loginName = 'new';
        $dto->email = 'new@example.com';
        $dto->superAdmin = true;
        $dto->password = 'newpass';

        $hashGenerator->expects($this->once())
            ->method('createPasswordHash')
            ->with('newpass')
            ->willReturn('new_hash');

        $entityManager->expects($this->once())->method('flush');

        $manager = new AdministratorManager($entityManager, $hashGenerator);
        $manager->updateAdministrator($admin, $dto);

        $this->assertEquals('new', $admin->getLoginName());
        $this->assertEquals('new@example.com', $admin->getEmail());
        $this->assertTrue($admin->isSuperUser());
        $this->assertEquals('new_hash', $admin->getPasswordHash());
    }

    public function testDeleteAdministrator(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $hashGenerator = $this->createMock(HashGenerator::class);

        $admin = $this->createMock(Administrator::class);

        $entityManager->expects($this->once())->method('remove')->with($admin);
        $entityManager->expects($this->once())->method('flush');

        $manager = new AdministratorManager($entityManager, $hashGenerator);
        $manager->deleteAdministrator($admin);

        $this->assertTrue(true);
    }
}
