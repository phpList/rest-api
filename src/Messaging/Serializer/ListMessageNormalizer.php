<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\ListMessage;
use PhpList\RestBundle\Subscription\Serializer\SubscriberListNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'ListMessage',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'message', ref: '#/components/schemas/Message'),
        new OA\Property(property: 'subscriber_list', ref: '#/components/schemas/SubscriberList'),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2022-12-01T10:00:00Z'
        ),
        new OA\Property(
            property: 'updated_at',
            type: 'string',
            format: 'date-time',
            example: '2022-12-01T10:00:00Z'
        ),
    ],
)]
class ListMessageNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly MessageNormalizer $messageNormalizer,
        private readonly SubscriberListNormalizer $subscriberListNormalizer,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof ListMessage) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'message' => $this->messageNormalizer->normalize($object->getMessage()),
            'subscriber_list' => $this->subscriberListNormalizer->normalize($object->getList()),
            'created_at' => $object->getEntered()->format('Y-m-d\TH:i:sP'),
            'updated_at' => $object->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof ListMessage;
    }
}
