<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use PhpList\Core\Domain\Messaging\Model\BounceRegex;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class BounceRegexNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof BounceRegex) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'regex' => $object->getRegex(),
            'regex_hash' => $object->getRegexHash(),
            'action' => $object->getAction(),
            'list_order' => $object->getListOrder(),
            'admin_id' => $object->getAdminId(),
            'comment' => $object->getComment(),
            'status' => $object->getStatus(),
            'count' => $object->getCount(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof BounceRegex;
    }
}
