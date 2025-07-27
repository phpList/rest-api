<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ResetPasswordRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    public string $token;

    #[Assert\NotBlank]
    #[Assert\Type(type: 'string')]
    #[Assert\Length(min: 8)]
    public string $newPassword;

    public function getDto(): ResetPasswordRequest
    {
        return $this;
    }
}
