<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\SubscriberHistory;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscriberHistoryNormalizer implements NormalizerInterface
{
    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof SubscriberHistory) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'ip' => $object->getIp(),
            'created_at' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'summery' => $object->getSummary(),
            'detail' => $object->getDetail(),
            'system_info' => $object->getSystemInfo(),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof SubscriberHistory;
    }
}
