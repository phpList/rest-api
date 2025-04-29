<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use Symfony\Component\Validator\Constraints as Assert;

class UpdateAdministratorRequest
{
    #[Assert\Length(min: 3, max: 255)]
    public ?string $loginName = null;

    #[Assert\Length(min: 6, max: 255)]
    public ?string $password = null;

    #[Assert\Email]
    public ?string $email = null;

    #[Assert\Type('bool')]
    public ?bool $superAdmin = null;
}
