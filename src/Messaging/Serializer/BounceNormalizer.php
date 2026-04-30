<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use DateTimeInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\BounceView;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'BounceView',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'status', type: 'string', example: 'not processed', nullable: true),
        new OA\Property(property: 'date', type: 'string', example: '2023-01-01T12:00:00Z', nullable: true),
        new OA\Property(property: 'message_id', type: 'integer', example: 123),
        new OA\Property(property: 'message_subject', type: 'string', example: 'Newsletter', nullable: true),
        new OA\Property(property: 'subscriber_id', type: 'integer', example: 0, nullable: true),
        new OA\Property(property: 'subscriber_email', type: 'string', example: 'user@example.com', nullable: true),
        new OA\Property(property: 'comment', type: 'string', example: 'Auto-generated rule', nullable: true),
    ],
    type: 'object'
)]
class BounceNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof BounceView) {
            return [];
        }
        return [
            'id' => $object->id,
            'status' => $object->status,
            'date' => $object->date?->format(DateTimeInterface::ATOM),
            'message_id' => $object->messageId,
            'message_subject' => $object->messageSubject,
            'subscriber_id' => $object->subscriberId,
            'subscriber_email' => $object->subscriberEmail,
            'comment' => $object->comment,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof BounceView;
    }
}
