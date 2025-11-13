<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeDefinition;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'type' => $object->getType() ? $object->getType()->value : null,
            'list_order' => $object->getListOrder(),
            'default_value' => $object->getDefaultValue(),
            'required' => $object->isRequired(),
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
