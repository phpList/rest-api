<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'AdminAttributeDefinition',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Country'),
        new OA\Property(property: 'type', type: 'string', example: 'hidden'),
        new OA\Property(property: 'list_order', type: 'integer', example: 12),
        new OA\Property(property: 'default_value', type: 'string', example: 'United States'),
        new OA\Property(property: 'required', type: 'boolean', example: true),
    ],
    type: 'object'
)]
class AdminAttributeDefinitionNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof AdminAttributeDefinition) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'type' => $object->getType(),
            'list_order' => $object->getListOrder(),
            'default_value' => $object->getDefaultValue(),
            'required' => $object->isRequired(),
            'table_name' => $object->getTableName(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof AdminAttributeDefinition;
    }
}
