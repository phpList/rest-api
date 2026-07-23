<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Configuration\Request;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Request\RequestInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[OA\Schema(
    schema: 'ConfigUpdateRequest',
    required: ['value'],
    properties: [
        new OA\Property(property: 'value', type: 'string', example: 'Example Organisation'),
    ],
    type: 'object'
)]
class UpdateConfigRequest implements RequestInterface
{
    #[Assert\NotNull]
    #[Assert\Type('string')]
    public string $value;

    public function getDto(): array
    {
        return [
            'value' => $this->value,
        ];
    }
}
