<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\Subscription;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'Subscriber',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', example: 'subscriber@example.com'),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2023-01-01T12:00:00Z',
        ),
        new OA\Property(property: 'confirmed', type: 'boolean', example: true),
        new OA\Property(property: 'blacklisted', type: 'boolean', example: false),
        new OA\Property(property: 'bounce_count', type: 'integer', example: 0),
        new OA\Property(property: 'unique_id', type: 'string', example: '69f4e92cf50eafca9627f35704f030f4'),
        new OA\Property(property: 'html_email', type: 'boolean', example: true),
        new OA\Property(property: 'disabled', type: 'boolean', example: false),
        new OA\Property(
            property: 'subscribed_lists',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SubscriberList')
        ),
    ],
    type: 'object'
)]
class SubscriberNormalizer implements NormalizerInterface
{
    public function __construct(private readonly SubscriberListNormalizer $subscriberListNormalizer)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Subscriber) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'email' => $object->getEmail(),
            'created_at' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'confirmed' => $object->isConfirmed(),
            'blacklisted' => $object->isBlacklisted(),
            'bounce_count' => $object->getBounceCount(),
            'unique_id' => $object->getUniqueId(),
            'html_email' => $object->hasHtmlEmail(),
            'disabled' => $object->isDisabled(),
            'subscribed_lists' => array_map(function (Subscription $subscription) {
                return $this->subscriberListNormalizer->normalize($subscription->getSubscriberList());
            }, $object->getSubscriptions()->toArray()),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Subscriber;
    }
}
