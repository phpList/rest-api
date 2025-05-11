<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Identity\Model\Administrator;
use PhpList\Core\Domain\Identity\Model\Dto\UpdateAdministratorDto;
use Symfony\Component\Validator\Constraints as Assert;
use PhpList\RestBundle\Validator\Constraint as CustomAssert;

class UpdateAdministratorRequest implements RequestInterface
{
    public int $administratorId;

    #[Assert\Length(min: 3, max: 255)]
    #[CustomAssert\UniqueLoginName]
    public ?string $loginName = null;

    #[Assert\Length(min: 6, max: 255)]
    public ?string $password = null;

    #[Assert\Email]
    #[CustomAssert\UniqueEmail(Administrator::class)]
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
