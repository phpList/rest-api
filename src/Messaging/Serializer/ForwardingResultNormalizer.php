<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\ForwardingRecipientResult;
use PhpList\Core\Domain\Messaging\Model\Dto\ForwardingResult;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'ForwardRecipientResult',
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'friend@example.com'),
        new OA\Property(property: 'status', type: 'string', example: 'sent'),
        new OA\Property(property: 'reason', type: 'string', example: 'precache_failed', nullable: true),
    ],
    type: 'object'
)]
#[OA\Schema(
    schema: 'ForwardResult',
    properties: [
        new OA\Property(property: 'total_requested', type: 'integer', example: 3),
        new OA\Property(property: 'total_sent', type: 'integer', example: 2),
        new OA\Property(property: 'total_failed', type: 'integer', example: 1),
        new OA\Property(property: 'total_already_sent', type: 'integer', example: 0),
        new OA\Property(
            property: 'recipients',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/ForwardRecipientResult')
        ),
    ],
    type: 'object'
)]
class ForwardingResultNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof ForwardingResult) {
            return [];
        }

        $recipients = array_map(static function (ForwardingRecipientResult $recipient): array {
            return [
                'email' => $recipient->email,
                'status' => $recipient->status,
                'reason' => $recipient->reason,
            ];
        }, $object->recipients);

        return [
            'total_requested' => $object->totalRequested,
            'total_sent' => $object->totalSent,
            'total_failed' => $object->totalFailed,
            'total_already_sent' => $object->totalAlreadySent,
            'recipients' => $recipients,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof ForwardingResult;
    }
}
