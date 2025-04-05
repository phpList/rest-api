<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity;

use PhpList\RestBundle\Validator as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriberRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[CustomAssert\UniqueEmail]
    public string $email;

    #[Assert\Type(type: 'bool')]
    public ?bool $requestConfirmation = null;

    #[Assert\Type(type: 'bool')]
    public ?bool $htmlEmail = null;
}
