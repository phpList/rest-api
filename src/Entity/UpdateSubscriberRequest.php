<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity;

use PhpList\RestBundle\Validator as CustomAssert;
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

    #[Assert\Type(type: 'number')]
    public ?int $rssFrequency = null; // todo check what is this

    #[Assert\Type(type: 'bool')]
    public bool $disabled;

    #[Assert\Type(type: 'string')]
    public string $additionalData;

    #[Assert\Type(type: 'string')]
    public ?string $woonplaats = null;  // todo check what is this

    #[Assert\Type(type: 'string')]
    public ?string $foreignKey = null;  // todo check what is this

    #[Assert\Type(type: 'string')]
    public ?string $country = null;  // todo check what is this
}
