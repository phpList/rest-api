<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity;

use Symfony\Component\Validator\Constraints as Assert;
use PhpList\RestBundle\Validator\UniqueEmail;

class SubscriberRequest
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[UniqueEmail]
    public string $email;

    #[Assert\Type(type: 'bool')]
    public ?bool $request_confirmation = null;

    #[Assert\Type(type: 'bool')]
    public ?bool $html_email = null;
}
