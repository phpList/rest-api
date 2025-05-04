<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Model\Identity\Administrator;
use PhpList\RestBundle\Entity\Request\CreateAdministratorRequest;
use PhpList\RestBundle\Entity\Request\UpdateAdministratorRequest;
use PhpList\Core\Security\HashGenerator;

class AdministratorManager
{
    private EntityManagerInterface $entityManager;
    private HashGenerator $hashGenerator;

    public function __construct(EntityManagerInterface $entityManager, HashGenerator $hashGenerator)
    {
        $this->entityManager = $entityManager;
        $this->hashGenerator = $hashGenerator;
    }

    public function createAdministrator(CreateAdministratorRequest $dto): Administrator
    {
        $administrator = new Administrator();
        $administrator->setLoginName($dto->loginName);
        $administrator->setEmail($dto->email);
        $administrator->setSuperUser($dto->superUser);
        $hashedPassword = $this->hashGenerator->createPasswordHash($dto->password);
        $administrator->setPasswordHash($hashedPassword);

        $this->entityManager->persist($administrator);
        $this->entityManager->flush();

        return $administrator;
    }

    public function updateAdministrator(Administrator $administrator, UpdateAdministratorRequest $dto): void
    {
        if ($dto->loginName !== null) {
            $administrator->setLoginName($dto->loginName);
        }
        if ($dto->email !== null) {
            $administrator->setEmail($dto->email);
        }
        if ($dto->superAdmin !== null) {
            $administrator->setSuperUser($dto->superAdmin);
        }
        if ($dto->password !== null) {
            $hashedPassword = $this->hashGenerator->createPasswordHash($dto->password);
            $administrator->setPasswordHash($hashedPassword);
        }

        $this->entityManager->flush();
    }

    public function deleteAdministrator(Administrator $administrator): void
    {
        $this->entityManager->remove($administrator);
        $this->entityManager->flush();
    }
}
