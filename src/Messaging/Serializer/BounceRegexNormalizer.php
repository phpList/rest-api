<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\BounceRegex;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'BounceRegex',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'regex', type: 'string', example: '/mailbox is full/i'),
        new OA\Property(property: 'regex_hash', type: 'string', example: 'd41d8cd98f00b204e9800998ecf8427e'),
        new OA\Property(property: 'action', type: 'string', example: 'delete', nullable: true),
        new OA\Property(property: 'list_order', type: 'integer', example: 0, nullable: true),
        new OA\Property(property: 'admin_id', type: 'integer', example: 1, nullable: true),
        new OA\Property(property: 'comment', type: 'string', example: 'Auto-generated rule', nullable: true),
        new OA\Property(property: 'status', type: 'string', example: 'active', nullable: true),
        new OA\Property(property: 'count', type: 'integer', example: 5, nullable: true),
    ],
    type: 'object'
)]
class BounceRegexNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof BounceRegex) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'regex' => $object->getRegex(),
            'regex_hash' => $object->getRegexHash(),
            'action' => $object->getAction(),
            'list_order' => $object->getListOrder(),
            'admin_id' => $object->getAdminId(),
            'comment' => $object->getComment(),
            'status' => $object->getStatus(),
            'count' => $object->getCount(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof BounceRegex;
    }
}
