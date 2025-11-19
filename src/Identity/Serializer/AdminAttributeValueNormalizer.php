<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\AdminAttributeValue;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'AdminAttributeValue',
    properties: [
        new OA\Property(property: 'administrator', ref: '#/components/schemas/Administrator'),
        new OA\Property(property: 'definition', ref: '#/components/schemas/AdminAttributeDefinition'),
        new OA\Property(property: 'value', type: 'string', example: 'United States'),
    ],
    type: 'object'
)]
class AdminAttributeValueNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly AdminAttributeDefinitionNormalizer $definitionNormalizer,
        private readonly AdministratorNormalizer $adminNormalizer,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof AdminAttributeValue) {
            return [];
        }

        return [
            'administrator' => $this->adminNormalizer->normalize($object->getAdministrator()),
            'definition' => $this->definitionNormalizer->normalize($object->getAttributeDefinition()),
            'value' => $object->getValue() ?? $object->getAttributeDefinition()->getDefaultValue(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof AdminAttributeValue;
    }
}
