<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Model\Identity\Administrator;
use Symfony\Component\Validator\Constraints as Assert;
use PhpList\RestBundle\Validator\Constraint as CustomAssert;
use PhpList\Core\Domain\Model\Identity\Dto\CreateAdministratorDto;

class CreateAdministratorRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[CustomAssert\UniqueLoginName]
    public string $loginName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 255)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[CustomAssert\UniqueEmail(Administrator::class)]
    public string $email;

    #[Assert\NotNull]
    #[Assert\Type('bool')]
    public bool $superUser = false;

    public function getDto(): CreateAdministratorDto
    {
        return new CreateAdministratorDto(
            $this->loginName,
            $this->password,
            $this->email,
            $this->superUser
        );
    }
}
