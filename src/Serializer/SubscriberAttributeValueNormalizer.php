<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use PhpList\Core\Domain\Model\Subscription\SubscriberAttributeValue;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscriberAttributeValueNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly AttributeDefinitionNormalizer $attributeDefinitionNormalizer,
        private readonly SubscriberNormalizer $subscriberNormalizer,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscriberAttributeValue) {
            return [];
        }

        return [
            'subscriber' => $this->subscriberNormalizer->normalize($object->getSubscriber()),
            'definition' => $this->attributeDefinitionNormalizer->normalize($object->getAttributeDefinition()),
            'value' => $object->getValue(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscriberAttributeValue;
    }
}
