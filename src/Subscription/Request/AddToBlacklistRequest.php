<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class AddToBlacklistRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public string $email;

    #[Assert\Type(type: 'string')]
    public ?string $reason = null;

    public function getDto(): self
    {
        return $this;
    }
}
