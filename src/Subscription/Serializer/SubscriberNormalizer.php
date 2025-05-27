<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Serializer;

use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Model\Subscription;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscriberNormalizer implements NormalizerInterface
{
    public function __construct(private readonly SubscriberListNormalizer $subscriberListNormalizer)
    {
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Subscriber) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'email' => $object->getEmail(),
            'created_at' => $object->getCreatedAt()->format('Y-m-d\TH:i:sP'),
            'confirmed' => $object->isConfirmed(),
            'blacklisted' => $object->isBlacklisted(),
            'bounce_count' => $object->getBounceCount(),
            'unique_id' => $object->getUniqueId(),
            'html_email' => $object->hasHtmlEmail(),
            'disabled' => $object->isDisabled(),
            'subscribed_lists' => array_map(function (Subscription $subscription) {
                return $this->subscriberListNormalizer->normalize($subscription->getSubscriberList());
            }, $object->getSubscriptions()->toArray()),
        ];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Subscriber;
    }
}
