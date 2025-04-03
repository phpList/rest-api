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
    public ?bool $request_confirmation = null;

    #[Assert\Type(type: 'bool')]
    public ?bool $html_email = null;
}
