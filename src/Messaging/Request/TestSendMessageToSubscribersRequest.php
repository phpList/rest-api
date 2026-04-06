<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\EmailExists;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'TestSendMessageToSubscribersRequest',
    required: ['emails'],
    properties: [
        new OA\Property(
            property: 'emails',
            description: 'Target subscribers emails.',
            type: 'array',
            items: new OA\Items(type: 'string', format: 'email'),
            example: ['user1@example.com', 'user2@example.com']
        ),
    ],
    type: 'object'
)]
class TestSendMessageToSubscribersRequest implements RequestInterface
{
    #[Assert\NotNull]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\Type('string'),
        new Assert\Email(),
        new EmailExists(),
    ])]
    public array $emails;

    public function getDto(): array
    {
        return [
            'emails' => $this->emails,
        ];
    }
}
