<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Service;

use PhpList\Core\Domain\Subscription\Model\Subscriber;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberManager;
use PhpList\RestBundle\Subscription\Request\CreateSubscriberRequest;
use PhpList\RestBundle\Subscription\Request\UpdateSubscriberRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscriberNormalizer;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriberService
{
    public function __construct(
        private readonly SubscriberManager $subscriberManager,
        private readonly SubscriberNormalizer $subscriberNormalizer,
        private readonly SubscriberHistoryService $subscriberHistoryService,
    ) {
    }

    public function createSubscriber(CreateSubscriberRequest $subscriberRequest): array
    {
        $subscriber = $this->subscriberManager->createSubscriber($subscriberRequest->getDto());
        return $this->subscriberNormalizer->normalize($subscriber, 'json');
    }

    public function updateSubscriber(UpdateSubscriberRequest $updateSubscriberRequest): array
    {
        $subscriber = $this->subscriberManager->updateSubscriber($updateSubscriberRequest->getDto());
        return $this->subscriberNormalizer->normalize($subscriber, 'json');
    }

    public function getSubscriber(int $subscriberId): array
    {
        $subscriber = $this->subscriberManager->getSubscriber($subscriberId);
        return $this->subscriberNormalizer->normalize($subscriber);
    }

    public function getSubscriberHistory(Request $request, ?Subscriber $subscriber): array
    {
        return $this->subscriberHistoryService->getSubscriberHistory($request, $subscriber);
    }

    public function deleteSubscriber(Subscriber $subscriber): void
    {
        $this->subscriberManager->deleteSubscriber($subscriber);
    }

    public function confirmSubscriber(string $uniqueId): ?Subscriber
    {
        if (!$uniqueId) {
            return null;
        }

        try {
            return $this->subscriberManager->markAsConfirmedByUniqueId($uniqueId);
        } catch (NotFoundHttpException) {
            return null;
        }
    }
}
