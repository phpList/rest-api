<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscriberAttributeValue;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'SubscriberAttributeValue',
    properties: [
        new OA\Property(property: 'subscriber', ref: '#/components/schemas/Subscriber'),
        new OA\Property(property: 'definition', ref: '#/components/schemas/AttributeDefinition'),
        new OA\Property(property: 'value', type: 'string', example: 'United States'),
    ],
)]
class SubscriberAttributeValueNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly AttributeDefinitionNormalizer $definitionNormalizer,
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
            'definition' => $this->definitionNormalizer->normalize($object->getAttributeDefinition()),
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
