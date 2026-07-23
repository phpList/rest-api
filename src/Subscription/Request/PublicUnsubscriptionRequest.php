<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'PublicUnsubscriptionRequest',
    required: ['email'],
    properties: [
        new OA\Property(
            property: 'email',
            type: 'string',
            format: 'email',
            example: 'lia@example.com'
        ),
    ]
)]
class PublicUnsubscriptionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Email]
    public ?string $email = null;

    public function getDto(): self
    {
        if ($this->email !== null) {
            $this->email = trim($this->email);
        }

        return $this;
    }
}
