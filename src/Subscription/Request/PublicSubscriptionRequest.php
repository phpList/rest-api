<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExistsPublic;
use PhpList\RestBundle\Subscription\Validator\Constraint\ValidPublicSubscription;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'PublicSubscriptionRequest',
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'lia@example.com'
        ),
        new OA\Property(
            property: 'confirm_email',
            type: 'string',
            format: 'email',
            example: 'lia@example.com'
        ),
        new OA\Property(
            property: 'list_id',
            type: 'integer',
            example: 1
        ),
        new OA\Property(
            property: 'attributes',
            type: 'object',
            example: [
                'firstname' => 'John',
                'lastname' => 'Grigoryan',
                'country' => 'Armenia',
            ],
            additionalProperties: true
        ),
    ]
)]
#[ValidPublicSubscription]
class PublicSubscriptionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    #[ListExistsPublic]
    #[Assert\Type(type: 'integer')]
    public ?int $listId = null;

    #[Assert\NotBlank]
    #[Assert\Email]
    #[Assert\EqualTo(
        propertyPath: 'email',
        message: 'Email addresses do not match.'
    )]
    public ?string $confirmEmail = null;

    /**
     * Key/value pairs matching the subscribe page attributes.
     *
     * Example:
     * [
     *     'firstname' => 'John',
     *     'lastname' => 'Doe',
     * ]
     */
    #[Assert\Type('array')]
    public array $attributes = [];

    #[Ignore]
    private ?SubscribePage $subscribePage = null;

    public function getDto(): self
    {
        if ($this->email !== null) {
            $this->email = trim($this->email);
        }

        return $this;
    }

    public function setSubscribePage(SubscribePage $subscribePage): self
    {
        $this->subscribePage = $subscribePage;

        return $this;
    }

    public function getSubscribePage(): ?SubscribePage
    {
        return $this->subscribePage;
    }
}
