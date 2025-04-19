<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriberListRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    public string $name;

    #[Assert\NotBlank]
    public bool $public;

    #[Assert\NotBlank]
    public int $listPosition;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    public string $description;
}
