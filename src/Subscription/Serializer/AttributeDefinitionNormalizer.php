<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'AttributeDefinition',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'name', type: 'string', example: 'Country'),
        new OA\Property(property: 'type', type: 'string', example: 'checkbox'),
        new OA\Property(property: 'list_order', type: 'integer', example: 12),
        new OA\Property(property: 'default_value', type: 'string', example: 'United States'),
        new OA\Property(property: 'required', type: 'boolean', example: true),
        new OA\Property(property: 'table_name', type: 'string', example: 'list_attributes'),
        new OA\Property(
            property: 'options',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/DynamicListAttrOption'),
            nullable: true,
        ),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'DynamicListAttrOption',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1, nullable: false),
        new OA\Property(property: 'name', type: 'string', example: 'United States'),
        new OA\Property(property: 'list_order', type: 'integer', example: 1, nullable: false),
    ],
    type: 'object',
)]
class AttributeDefinitionNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscriberAttributeDefinition) {
            return [];
        }

        $options = $object->getOptions();
        if (!empty($options)) {
            $options = array_map(function ($option) {
                return [
                    'id' => $option->id,
                    'name' => $option->name,
                    'list_order' => $option->listOrder,
                ];
            }, $options);
        }

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'type' => $object->getType() ? $object->getType()->value : null,
            'list_order' => $object->getListOrder(),
            'default_value' => $object->getDefaultValue(),
            'required' => $object->isRequired(),
            'options' => $options,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscriberAttributeDefinition;
    }
}
