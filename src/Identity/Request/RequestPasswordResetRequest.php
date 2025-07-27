<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class RequestPasswordResetRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\Type(type: 'string')]
    public string $email;

    public function getDto(): RequestPasswordResetRequest
    {
        return $this;
    }
}
