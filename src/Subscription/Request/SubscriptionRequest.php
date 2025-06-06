<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\EmailExists;
use Symfony\Component\Validator\Constraints as Assert;

class SubscriptionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Email(),
        new EmailExists()
    ])]
    public array $emails = [];

    public function getDto(): SubscriptionRequest
    {
        return $this;
    }
}
