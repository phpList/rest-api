<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\Subscription;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscriptionNormalizer implements NormalizerInterface
{
    private SubscriberNormalizer $subscriberNormalizer;
    private SubscriberListNormalizer $subscriberListNormalizer;

    public function __construct(
        SubscriberNormalizer $subscriberNormalizer,
        SubscriberListNormalizer $subscriberListNormalizer
    ) {
        $this->subscriberNormalizer = $subscriberNormalizer;
        $this->subscriberListNormalizer = $subscriberListNormalizer;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Subscription) {
            return [];
        }

        return [
            'subscriber' => $this->subscriberNormalizer->normalize($object->getSubscriber()),
            'subscriber_list' => $this->subscriberListNormalizer->normalize($object->getSubscriberList()),
            'subscription_date' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Subscription;
    }
}
