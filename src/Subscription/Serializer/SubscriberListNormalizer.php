<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscriberListNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscriberList) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'name' => $object->getName(),
            'created_at' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'description' => $object->getDescription(),
            'list_position' => $object->getListPosition(),
            'subject_prefix' => $object->getSubjectPrefix(),
            'public' => $object->isPublic(),
            'category' => $object->getCategory(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscriberList;
    }
}
