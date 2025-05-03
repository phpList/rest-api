<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Model\Identity\Administrator;
use Symfony\Component\Validator\Constraints as Assert;
use PhpList\RestBundle\Validator\Constraint as CustomAssert;

class UpdateAdministratorRequest
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
}
