<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'SubscriberList',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 2),
        new OA\Property(property: 'name', type: 'string', example: 'Newsletter'),
        new OA\Property(property: 'description', type: 'string', example: 'Monthly updates'),
        new OA\Property(
            property: 'created_at',
            type: 'string',
            format: 'date-time',
            example: '2022-12-01T10:00:00Z'
        ),
        new OA\Property(property: 'list_position', type: 'integer', example: 1),
        new OA\Property(property: 'subject_prefix', type: 'string', example: 'Newsletter: '),
        new OA\Property(property: 'public', type: 'boolean', example: true),
        new OA\Property(property: 'category', type: 'string', example: 'News'),
    ],
    type: 'object'
)]
class SubscriberListNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscriberList) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'created_at' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'description' => $object->getDescription(),
            'list_position' => $object->getListPosition(),
            'subject_prefix' => $object->getSubjectPrefix(),
            'public' => $object->isPublic(),
            'category' => $object->getCategory(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscriberList;
    }
}
