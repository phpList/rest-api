<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use PhpList\Core\Domain\Model\Messaging\TemplateImage;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

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
