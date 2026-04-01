<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Subscription\Validator\Constraint\ListExists;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'ResendMessageToListsRequest',
    required: ['list_ids'],
    properties: [
        new OA\Property(
            property: 'list_ids',
            description: 'Target mailing list IDs.',
            type: 'array',
            items: new OA\Items(type: 'integer', minimum: 1),
            example: [1, 2, 3]
        ),
    ],
    type: 'object'
)]
class ResendMessageToListsRequest implements RequestInterface
{
    #[Assert\NotNull]
    #[Assert\Type('array')]
    #[Assert\Count(min: 1)]
    #[Assert\All([
        new Assert\Type('integer'),
        new Assert\Positive(),
        new ListExists()
    ])]
    public array $listIds;

    public function getDto(): array
    {
        return [
            'list_ids' => $this->listIds,
        ];
    }
}
