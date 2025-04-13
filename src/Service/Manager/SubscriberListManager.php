<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Domain\Repository\Subscription\SubscriberListRepository;
use PhpList\RestBundle\Entity\Request\CreateSubscriberListRequest;

class SubscriberListManager
{
    private SubscriberListRepository $subscriberListRepository;

    public function __construct(
        SubscriberListRepository $subscriberListRepository,
    ) {
        $this->subscriberListRepository = $subscriberListRepository;
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
}
