<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Model\Subscription\Subscription;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscriberNormalizer implements NormalizerInterface
{
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
            'creation_date' => $object->getCreationDate()->format('Y-m-d\TH:i:sP'),
            'confirmed' => $object->isConfirmed(),
            'blacklisted' => $object->isBlacklisted(),
            'bounce_count' => $object->getBounceCount(),
            'unique_id' => $object->getUniqueId(),
            'html_email' => $object->hasHtmlEmail(),
            'disabled' => $object->isDisabled(),
            'subscribedLists' => array_map(function (Subscription $subscription) {
                return [
                    'id' => $subscription->getSubscriberList()->getId(),
                    'name' => $subscription->getSubscriberList()->getName(),
                    'description' => $subscription->getSubscriberList()->getDescription(),
                    'creation_date' => $subscription->getSubscriberList()->getCreationDate()->format('Y-m-d\TH:i:sP'),
                    'public' => $subscription->getSubscriberList()->isPublic(),
                ];
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

