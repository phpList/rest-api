<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use PhpList\Core\Domain\Messaging\Model\Template;
use PhpList\Core\Domain\Messaging\Model\TemplateImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
