<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Configuration\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Configuration\Model\Config;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'Config',
    properties: [
        new OA\Property(property: 'key', type: 'string', example: 'organisation_name'),
        new OA\Property(property: 'value', type: 'string', example: 'Example Organisation', nullable: true),
        new OA\Property(property: 'editable', type: 'boolean', example: true),
        new OA\Property(property: 'type', type: 'string', example: 'text', nullable: true),
    ],
    type: 'object'
)]
class ConfigNormalizer implements NormalizerInterface
{
    /**
     * Normalizes a configuration item.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Config) {
            return [];
        }

        return [
            'key' => $object->getKey(),
            'value' => $object->getValue(),
            'editable' => $object->isEditable(),
            'type' => $object->getType(),
        ];
    }

    /**
     * Checks whether the value can be normalized.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Config;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            Config::class => true,
        ];
    }
}
