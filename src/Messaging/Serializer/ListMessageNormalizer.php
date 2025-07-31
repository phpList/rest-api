<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Serializer;

use PhpList\Core\Domain\Messaging\Model\ListMessage;
use PhpList\RestBundle\Subscription\Serializer\SubscriberListNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ListMessageNormalizer implements NormalizerInterface
{
    public function __construct(
        private readonly MessageNormalizer $messageNormalizer,
        private readonly SubscriberListNormalizer $subscriberListNormalizer,
    ) {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof ListMessage) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'message' => $this->messageNormalizer->normalize($object->getMessage()),
            'subscriber_list' => $this->subscriberListNormalizer->normalize($object->getList()),
            'created_at' => $object->getEntered()->format('Y-m-d\TH:i:sP'),
            'updated_at' => $object->getUpdatedAt()->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof ListMessage;
    }
}
