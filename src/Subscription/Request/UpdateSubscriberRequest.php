<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Dto\UpdateSubscriberDto;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Identity\Validator\Constraint\UniqueEmail;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'UpdateSubscriberRequest',
    required: ['email'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'string', example: 'admin@example.com'),
        new OA\Property(property: 'confirmed', type: 'boolean', example: false),
        new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
        new OA\Property(property: 'html_email', type: 'boolean', example: false),
        new OA\Property(property: 'disabled', type: 'boolean', example: false),
        new OA\Property(property: 'additional_data', type: 'string', example: 'asdf'),
    ],
    type: 'object'
)]
class UpdateSubscriberRequest implements RequestInterface
{
    public int $subscriberId;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[UniqueEmail(entityClass: Subscriber::class)]
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
