<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Template;
use PhpList\Core\Domain\Messaging\Model\TemplateImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[OA\Schema(
    schema: 'Template',
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Newsletter'),
        new OA\Property(property: 'content', type: 'string', example: 'Hello World!', nullable: true),
        new OA\Property(property: 'text', type: 'string', nullable: true),
        new OA\Property(property: 'order', type: 'integer', nullable: true),
        new OA\Property(
            property: 'images',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/TemplateImage'),
            nullable: true
        ),
    ],
    type: 'object',
    nullable: true
)]
class TemplateNormalizer implements NormalizerInterface
{
    public function __construct(private readonly TemplateImageNormalizer $templateImageNormalizer)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Template) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'title' => $object->getTitle(),
            'content' => $object->getContent(),
            'text' => $object->getText(),
            'order' => $object->getListOrder(),
            'images' => $object->getImages()->toArray() ? array_map(function (TemplateImage $image) {
                return $this->templateImageNormalizer->normalize($image);
            }, $object->getImages()->toArray()) : null
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Template;
    }
}
