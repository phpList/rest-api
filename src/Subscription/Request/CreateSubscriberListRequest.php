<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Request;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Dto\CreateSubscriberListDto;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'CreateSubscriberListRequest',
    required: ['name'],
    properties: [
        new OA\Property(property: 'name', type: 'string', format: 'string', example: 'News'),
        new OA\Property(property: 'description', type: 'string', example: 'News (and some fun stuff)'),
        new OA\Property(property: 'list_position', type: 'number', example: 12),
        new OA\Property(property: 'public', type: 'boolean', example: true),
    ],
    type: 'object'
)]
class CreateSubscriberListRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\NotNull]
    public string $name;

    public bool $public = false;

    public ?int $listPosition = null;

    public ?string $description = null;

    public function getDto(): CreateSubscriberListDto
    {
        return new CreateSubscriberListDto(
            name: $this->name,
            isPublic: $this->public,
            listPosition: $this->listPosition,
            description: $this->description,
        );
    }
}
