<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\Dto\CreateAdministratorDto;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Identity\Validator\Constraint\UniqueEmail;
use PhpList\RestBundle\Identity\Validator\Constraint\UniqueLoginName;
use Symfony\Component\Validator\Constraints as Assert;

class CreateAdministratorRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    #[UniqueLoginName]
    public string $loginName;

    #[Assert\NotBlank]
    #[Assert\Length(min: 6, max: 255)]
    public string $password;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[UniqueEmail(Administrator::class)]
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
