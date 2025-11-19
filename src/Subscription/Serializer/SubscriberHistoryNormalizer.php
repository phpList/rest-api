<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscriberHistory;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'SubscriberHistory',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'ip', type: 'string', example: '127.0.0.1'),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2022-12-01T10:00:00Z'
        ),
        new OA\Property(property: 'summary', type: 'string', example: 'Added by admin'),
        new OA\Property(property: 'detail', type: 'string', example: 'Added with add-email on test'),
        new OA\Property(property: 'system_info', type: 'string', example: 'HTTP_USER_AGENT = Mozilla/5.0'),
    ],
    type: 'object'
)]
class SubscriberHistoryNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscriberHistory) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'ip' => $object->getIp(),
            'created_at' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'summary' => $object->getSummary(),
            'detail' => $object->getDetail(),
            'system_info' => $object->getSystemInfo(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscriberHistory;
    }
}
