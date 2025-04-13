<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\RestBundle\Validator as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriptionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[CustomAssert\EmailExists]
    public string $email;

    #[Assert\NotBlank]
    #[Assert\NotNull]
    #[CustomAssert\ListExists]
    public int $listId;
}
