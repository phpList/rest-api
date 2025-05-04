<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriberListRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    public string $name;

    public bool $public = false;

    public ?int $listPosition = null;

    public ?string $description = null;
}
