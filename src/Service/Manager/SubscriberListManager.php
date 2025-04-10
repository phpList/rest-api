<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Model\Subscription\SubscriberList;
use PhpList\Core\Domain\Repository\Subscription\SubscriberListRepository;
use PhpList\RestBundle\Entity\CreateSubscriberListRequest;

class SubscriberListManager
{
    private SubscriberListRepository $subscriberListRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        SubscriberListRepository $subscriberListRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->subscriberListRepository = $subscriberListRepository;
        $this->entityManager = $entityManager;
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
}
