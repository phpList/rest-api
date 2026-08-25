<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use PhpList\Core\Domain\Common\Model\Dto\DirectoryEntryDto;

#[OA\Schema(
    schema: 'FileListingEntry',
    properties: [
        new OA\Property(property: 'name', type: 'string', example: 'image.png'),
        new OA\Property(property: 'url', type: 'string', example: 'https://ex.com/api/v2/editor-uploads/image.png'),
        new OA\Property(property: 'size', type: 'integer', example: 12345),
        new OA\Property(property: 'type', type: 'string', example: 'file'),
        new OA\Property(property: 'modified', type: 'integer', example: 1689255600),
    ],
    type: 'object',
    nullable: true
)]
class FileListingNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof DirectoryEntryDto) {
            return [];
        }

        return [
            'name' => $object->name,
            'url' => $object->type === 'directory' ? null : $this->urlGenerator->generate(
                'editor_uploads_get_file',
                ['filename' => $object->name],
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'size' => $object->size,
            'type' => $object->type,
            'modified' => $object->modified,
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof DirectoryEntryDto;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getSupportedTypes(?string $format): array
    {
        return [
            DirectoryEntryDto::class => true,
        ];
    }
}
