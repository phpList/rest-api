<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\TemplateImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'TemplateImage',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 12),
        new OA\Property(property: 'template_id', type: 'integer', example: 1),
        new OA\Property(property: 'mimetype', type: 'string', example: 'image/png', nullable: true),
        new OA\Property(property: 'filename', type: 'string', example: 'header.png', nullable: true),
        new OA\Property(
            property: 'data',
            description: 'Base64-encoded image data',
            type: 'string',
            format: 'byte',
            example: 'iVBORw0KGgoAAAANSUhEUgAAA...',
            nullable: true
        ),
        new OA\Property(property: 'width', type: 'integer', example: 600, nullable: true),
        new OA\Property(property: 'height', type: 'integer', example: 200, nullable: true),
    ],
    type: 'object',
    nullable: true
)]
class TemplateImageNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof TemplateImage) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'template_id' => $object->getTemplate()?->getId(),
            'mimetype' => $object->getMimeType(),
            'filename' => $object->getFilename(),
            'data' => base64_encode($object->getData() ?? ''),
            'width' => $object->getWidth(),
            'height' => $object->getHeight(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof TemplateImage;
    }
}
