<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscribePage;
use PhpList\RestBundle\Identity\Serializer\AdministratorNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'SubscribePage',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Subscribe to our newsletter'),
        new OA\Property(property: 'active', type: 'boolean', example: true),
        new OA\Property(property: 'owner', ref: '#/components/schemas/Administrator'),
        new OA\Property(
            property: 'data',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/SubscribePageData')
        ),
    ],
)]
class SubscribePageNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly AdministratorNormalizer $adminNormalizer,
        private readonly SubscribePageDataNormalizer $dataNormalizer,
    ) {
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscribePage) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'title' => $object->getTitle(),
            'active' => $object->isActive(),
            'owner' => $this->adminNormalizer->normalize($object->getOwner()),
            'data' => array_map(function ($data) {
                return $this->dataNormalizer->normalize($data);
            }, $object->getData())
        ];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscribePage;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            SubscribePage::class => true,
        ];
    }
}
