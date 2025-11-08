<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class ValidateTokenRequest implements RequestInterface
{
    #[Assert\NotBlank(normalizer: 'trim')]
    #[Assert\Type(type: 'string')]
    public string $token;

    public function getDto(): ValidateTokenRequest
    {
        return $this;
    }
}
