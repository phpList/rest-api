<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Subscription;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'Subscription',
    properties: [
        new OA\Property(property: 'subscriber', ref: '#/components/schemas/SubscriberOnly'),
        new OA\Property(property: 'subscriber_list', ref: '#/components/schemas/SubscriberList'),
        new OA\Property(
            property: 'subscription_date',
            type: 'string',
            format: 'date-time',
            example: '2023-01-01T12:00:00Z',
        ),
    ],
    type: 'object'
)]
class SubscriptionNormalizer implements NormalizerInterface
{
    private SubscriberOnlyNormalizer $subscriberNormalizer;
    private SubscriberListNormalizer $subscriberListNormalizer;

    public function __construct(
        SubscriberOnlyNormalizer $subscriberNormalizer,
        SubscriberListNormalizer $subscriberListNormalizer
    ) {
        $this->subscriberNormalizer = $subscriberNormalizer;
        $this->subscriberListNormalizer = $subscriberListNormalizer;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Subscription) {
            return [];
        }

        return [
            'subscriber' => $this->subscriberNormalizer->normalize($object->getSubscriber()),
            'subscriber_list' => $this->subscriberListNormalizer->normalize($object->getSubscriberList()),
            'subscription_date' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Subscription;
    }
}
