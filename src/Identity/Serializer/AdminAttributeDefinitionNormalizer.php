<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Serializer;

use PhpList\Core\Domain\Identity\Model\AdminAttributeDefinition;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
