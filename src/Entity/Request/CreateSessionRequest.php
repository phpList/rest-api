<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSessionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $loginName;

    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $password;
}
