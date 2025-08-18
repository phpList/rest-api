<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SubscribePageDataRequest implements RequestInterface
{
    #[Assert\NotBlank]
    public string $name;

    public ?string $value = null;

    public function getDto(): SubscribePageDataRequest
    {
        return $this;
    }
}
