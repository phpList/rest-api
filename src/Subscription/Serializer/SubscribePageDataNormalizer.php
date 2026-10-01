<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscribePageData;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'SubscribePageData',
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'button'),
        new OA\Property(property: 'value', type: 'string', example: 'Subscribe to the selected newsletters'),
    ],
)]
class SubscribePageDataNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscribePageData) {
            return [];
        }

        if ($object->getName() === 'attributes') {
            $object->setData(trim(str_replace('+', ',', $object->getData()), ','));
        }

        return [
            'key' => $object->getName(),
            'value' => $object->getData(),
        ];
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscribePageData;
    }

    /**
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            SubscribePageData::class => true,
        ];
    }
}
