<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Entity\Request;

use PhpList\Core\Domain\Model\Subscription\Dto\UpdateSubscriberDto;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\RestBundle\Validator\Constraint as CustomAssert;
use Symfony\Component\Validator\Constraints as Assert;

class UpdateSubscriberRequest implements RequestInterface
{
    public int $subscriberId;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[CustomAssert\UniqueEmail(entityClass: Subscriber::class)]
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

    public function getDto(): UpdateSubscriberDto
    {
        return new UpdateSubscriberDto(
            subscriberId: $this->subscriberId,
            email: $this->email,
            confirmed: $this->confirmed,
            blacklisted: $this->blacklisted,
            htmlEmail: $this->htmlEmail,
            disabled: $this->disabled,
            additionalData: $this->additionalData,
        );
    }
}
