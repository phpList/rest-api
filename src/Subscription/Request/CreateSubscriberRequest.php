<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use PhpList\Core\Domain\Subscription\Model\Dto\CreateSubscriberDto;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

class CreateSubscriberRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    #[UniqueEmail(Subscriber::class)]
    public string $email;

    #[Assert\Type(type: 'bool')]
    public ?bool $requestConfirmation = null;

    #[Assert\Type(type: 'bool')]
    public ?bool $htmlEmail = null;

    public function getDto(): CreateSubscriberDto
    {
        return new CreateSubscriberDto(
            email: $this->email,
            requestConfirmation: $this->requestConfirmation,
            htmlEmail: $this->htmlEmail,
        );
    }
}
