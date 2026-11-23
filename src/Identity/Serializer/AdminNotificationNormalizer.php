<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Serializer;

use InvalidArgumentException;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\Dto\AdminNotification;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'AdminNotification',
    properties: [
        new OA\Property(property: 'code', type: 'string', example: 'campaigns_in_queue'),
        new OA\Property(property: 'level', type: 'string', enum: ['info', 'warning', 'error']),
        new OA\Property(property: 'message', type: 'string'),
        new OA\Property(property: 'context', type: 'object'),
    ],
    type: 'object'
)]
class AdminNotificationNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     * @throws InvalidArgumentException
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof AdminNotification) {
            throw new InvalidArgumentException('Expected an AdminNotification object.');
        }

        return [
            'code' => $object->code,
            'level' => $object->level,
            'message' => $object->message,
            // an object, so that an empty context is encoded as {} and not []
            'context' => (object) $object->context,
        ];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof AdminNotification;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            AdminNotification::class => true,
        ];
    }
}
