<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Serializer;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class SubscriberNormalizer implements NormalizerInterface
{
    public function normalize($object, string $format = null, array $context = []): array
    {
        if (!$object instanceof Subscriber) {
            return [];
        }

        return [
            'id' => $object->getId(),
            'email' => $object->getEmail(),
            'subscribedLists' => array_map(function ($subscription) {

                return [
                    'id' => $subscription->getSubscriberList()->getId(),
                    'name' => $subscription->getSubscriberList()->getName(),
                ];
            }, $object->getSubscriptions()->toArray()),
        ];
    }

    public function supportsNormalization($data, string $format = null): bool
    {
        return $data instanceof Subscriber;
    }
}

