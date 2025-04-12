<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Domain\Repository\Subscription\SubscriberListRepository;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Entity\CreateSubscriberListRequest;

class SubscriberListManager
{
    private SubscriberListRepository $subscriberListRepository;
    private SubscriberRepository $subscriberRepository;

    public function __construct(
        SubscriberListRepository $subscriberListRepository,
        SubscriberRepository $subscriberRepository
    ) {
        $this->subscriberListRepository = $subscriberListRepository;
        $this->subscriberRepository = $subscriberRepository;
    }

    public function createSubscriberList(CreateSubscriberListRequest $subscriberListRequest): SubscriberList
    {
        $subscriberList = (new SubscriberList())
            ->setName($subscriberListRequest->name)
            ->setDescription($subscriberListRequest->description)
            ->setListPosition($subscriberListRequest->listPosition)
            ->setPublic($subscriberListRequest->public);

        $this->subscriberListRepository->save($subscriberList);

        return $subscriberList;
    }

    /** @return SubscriberList[] */
    public function getAll(): array
    {
        return $this->subscriberListRepository->findAll();
    }

    public function delete(SubscriberList $subscriberList): void
    {
        $this->subscriberListRepository->remove($subscriberList);
    }

    /** @return Subscriber[] */
    public function getSubscriberListMembers(SubscriberList $list): array
    {
        return $this->subscriberRepository->getSubscribersBySubscribedListId($list->getId());
    }
}
