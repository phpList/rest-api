<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use PhpList\Core\Domain\Model\Subscription\Subscription;
use PhpList\Core\Domain\Repository\Subscription\SubscriberListRepository;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\Core\Domain\Repository\Subscription\SubscriptionRepository;
use PhpList\RestBundle\Exception\SubscriptionCreationException;

class SubscriptionManager
{
    private SubscriptionRepository $subscriptionRepository;
    private SubscriberRepository $subscriberRepository;
    private SubscriberListRepository $subscriberListRepository;

    public function __construct(
        SubscriptionRepository $subscriptionRepository,
        SubscriberRepository $subscriberRepository,
        SubscriberListRepository $subscriberListRepository
    ) {
        $this->subscriptionRepository = $subscriptionRepository;
        $this->subscriberRepository = $subscriberRepository;
        $this->subscriberListRepository = $subscriberListRepository;
    }

    public function createSubscription(string $email, int $listId): Subscription
    {
        $subscriber = $this->subscriberRepository->findOneBy(['email' => $email]);
        $subscriberList = $this->subscriberListRepository->find($listId);

        if (!$subscriber || !$subscriberList) {
            throw new SubscriptionCreationException('Subscriber or list does not exists.');
        }

        $existingSubscription = $this->subscriptionRepository
            ->findOneBySubscriberListAndSubscriber($subscriberList, $subscriber);

        if ($existingSubscription) {
            throw new SubscriptionCreationException('Subscriber is already subscribed to this list.');
        }
        $subscription = new Subscription();
        $subscription->setSubscriber($subscriber);
        $subscription->setSubscriberList($subscriberList);

        $this->subscriptionRepository->save($subscription);

        return $subscription;
    }
}
