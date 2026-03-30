<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'SubscriptionRequest',
    required: ['emails'],
    properties: [
        new OA\Property(
            property: 'emails',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'email'),
            example: ['test1@example.com', 'test2@example.com']
        ),
        new OA\Property(
            property: 'autoConfirm',
            description: 'Whether to automatically confirm subscriptions',
            type: 'boolean',
            example: true
        ),
    ]
)]
class SubscriptionRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type('array')]
    #[Assert\All([
        new Assert\NotBlank(),
        new Assert\Email(),
    ])]
    public array $emails = [];

    public bool $autoConfirm = false;

    public function getDto(): SubscriptionRequest
    {
        return $this;
    }
}
