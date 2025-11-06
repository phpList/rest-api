<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SubscribePageRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Length(min: 1, max: 255)]
    public string $title;

    #[Assert\Type(type: 'bool')]
    public ?bool $active = false;

    public function getDto(): SubscribePageRequest
    {
        return $this;
    }
}
