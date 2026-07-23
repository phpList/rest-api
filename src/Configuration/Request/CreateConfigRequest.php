<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Configuration\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use PhpList\RestBundle\Configuration\Validator\Constraint\UniqueConfigKey;
use Symfony\Component\Validator\Constraints as Assert;
use PhpList\Core\Domain\Configuration\Model\Dto\CreateConfigDto;

#[OA\Schema(
    schema: 'ConfigRequest',
    required: ['key', 'value'],
    properties: [
        new OA\Property(property: 'key', type: 'string', maxLength: 35, example: 'organisation_name'),
        new OA\Property(property: 'value', type: 'string', example: 'Example Organisation'),
        new OA\Property(property: 'editable', type: 'boolean', example: true),
        new OA\Property(property: 'type', type: 'string', maxLength: 25, example: 'text', nullable: true),
    ],
    type: 'object'
)]
class CreateConfigRequest implements RequestInterface
{
    #[Assert\NotBlank]
    #[Assert\Type('string')]
    #[Assert\Length(max: 35)]
    #[Assert\Regex(pattern: '/^[A-Za-z0-9_.:-]+$/')]
    #[UniqueConfigKey]
    public string $key;

    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $value;

    #[Assert\Type('boolean')]
    public bool $editable = true;

    #[Assert\Type('string')]
    #[Assert\Length(max: 25)]
    public ?string $type = null;

    public function getDto(): CreateConfigDto
    {
        return new CreateConfigDto(
            key: $this->key,
            value: $this->value,
            editable: $this->editable,
            type: $this->type
        );
    }
}
