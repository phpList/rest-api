<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Entity\SubscriberRequest;

class SubscriberManager
{
    private SubscriberRepository $subscriberRepository;

    public function __construct(SubscriberRepository $subscriberRepository)
    {
        $this->subscriberRepository = $subscriberRepository;
    }

    public function createSubscriber(SubscriberRequest $subscriberRequest): Subscriber
    {
        $subscriber = new Subscriber();
        $subscriber->setEmail($subscriberRequest->email);
        $confirmed = (bool)$subscriberRequest->request_confirmation;
        $subscriber->setConfirmed(!$confirmed);
        $subscriber->setBlacklisted(false);
        $subscriber->setHtmlEmail((bool)$subscriberRequest->html_email);
        $subscriber->setDisabled(false);

        $this->subscriberRepository->save($subscriber);

        return $subscriber;
    }
}
