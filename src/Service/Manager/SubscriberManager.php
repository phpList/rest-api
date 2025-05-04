<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Service\Manager;

use Doctrine\ORM\EntityManagerInterface;
use PhpList\Core\Domain\Model\Subscription\Subscriber;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\RestBundle\Entity\Request\CreateSubscriberRequest;
use PhpList\RestBundle\Entity\Request\UpdateSubscriberRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class SubscriberManager
{
    private SubscriberRepository $subscriberRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(SubscriberRepository $subscriberRepository, EntityManagerInterface $entityManager)
    {
        $this->subscriberRepository = $subscriberRepository;
        $this->entityManager = $entityManager;
    }

    public function createSubscriber(CreateSubscriberRequest $subscriberRequest): Subscriber
    {
        $subscriber = new Subscriber();
        $subscriber->setEmail($subscriberRequest->email);
        $confirmed = (bool)$subscriberRequest->requestConfirmation;
        $subscriber->setConfirmed(!$confirmed);
        $subscriber->setBlacklisted(false);
        $subscriber->setHtmlEmail((bool)$subscriberRequest->htmlEmail);
        $subscriber->setDisabled(false);

        $this->subscriberRepository->save($subscriber);

        return $subscriber;
    }

    public function getSubscriber(int $subscriberId): Subscriber
    {
        $subscriber = $this->subscriberRepository->findSubscriberWithSubscriptions($subscriberId);

        if (!$subscriber) {
            throw new NotFoundHttpException('Subscriber not found');
        }

        return $subscriber;
    }

    public function updateSubscriber(UpdateSubscriberRequest $subscriberRequest): Subscriber
    {
        /** @var Subscriber $subscriber */
        $subscriber = $this->subscriberRepository->find($subscriberRequest->subscriberId);

        $subscriber->setEmail($subscriberRequest->email);
        $subscriber->setConfirmed($subscriberRequest->confirmed);
        $subscriber->setBlacklisted($subscriberRequest->blacklisted);
        $subscriber->setHtmlEmail($subscriberRequest->htmlEmail);
        $subscriber->setDisabled($subscriberRequest->disabled);
        $subscriber->setExtraData($subscriberRequest->additionalData);

        $this->entityManager->flush();

        return $subscriber;
    }

    public function deleteSubscriber(Subscriber $subscriber): void
    {
        $this->subscriberRepository->remove($subscriber);
    }
}
