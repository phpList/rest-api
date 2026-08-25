<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Common\Model\UploadResult;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'EditorUpload',
    properties: [
        new OA\Property(property: 'uploaded', type: 'boolean', example: true),
        new OA\Property(property: 'file_name', type: 'string', example: 'test.png'),
        new OA\Property(property: 'url', type: 'string', example: 'https://ex.com/uploads/test.png'),
        new OA\Property(property: 'default', type: 'string', example: 'https://ex.com/uploads/test.png'),
        new OA\Property(property: 'mime_type', type: 'string', example: 'image/png'),
        new OA\Property(property: 'size', type: 'integer', example: 123456, nullable: true),
        new OA\Property(property: 'extension', type: 'string', example: 'png'),
    ],
    type: 'object',
    nullable: true
)]
class EditorUploadNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof UploadResult) {
            return [];
        }

        return [
            'uploaded' => true,
            'file_name' => $object->getFilename(),
            'url' => $object->getUrl(),
            'default' => $object->getUrl(),
            'mime_type' => $object->getMimeType(),
            'size' => $object->getSize(),
            'extension' => $object->getExtension(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof UploadResult;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            UploadResult::class => true,
        ];
    }
}
