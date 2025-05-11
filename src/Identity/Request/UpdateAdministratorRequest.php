<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\Dto\UpdateAdministratorDto;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Identity\Validator\Constraint\UniqueEmail;
use PhpList\RestBundle\Identity\Validator\Constraint\UniqueLoginName;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateAdministratorRequest implements RequestInterface
{
    public int $administratorId;

    #[Assert\Length(min: 3, max: 255)]
    #[UniqueLoginName]
    public ?string $loginName = null;

    #[Assert\Length(min: 6, max: 255)]
    public ?string $password = null;

    #[Assert\Email]
    #[UniqueEmail(Administrator::class)]
    public ?string $email = null;

    #[Assert\Type('bool')]
    public ?bool $superAdmin = null;

    public function getDto(): UpdateAdministratorDto
    {
        return new UpdateAdministratorDto(
            administratorId: $this->administratorId,
            loginName: $this->loginName,
            password: $this->password,
            email: $this->email,
            superAdmin: $this->superAdmin,
        );
    }
}
