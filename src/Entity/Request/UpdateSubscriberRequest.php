<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\RestBundle\Validator\Constraint as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateSubscriberRequest implements RequestInterface
{
    public int $subscriberId;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[CustomAssert\UniqueEmail]
    public string $email;

    #[Assert\Type(type: 'bool')]
    public bool $confirmed;

    #[Assert\Type(type: 'bool')]
    public bool $blacklisted;

    #[Assert\Type(type: 'bool')]
    public bool $htmlEmail;

    #[Assert\Type(type: 'bool')]
    public bool $disabled;

    #[Assert\Type(type: 'string')]
    public string $additionalData;
}
