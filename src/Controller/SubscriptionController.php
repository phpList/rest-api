<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use PhpList\Core\Domain\Model\Subscription\Subscription;
use PhpList\Core\Domain\Repository\Messaging\SubscriberListRepository;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use PhpList\Core\Domain\Repository\Subscription\SubscriptionRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * This controller provides REST API access to subscriptions.
 *
 * @author Matthieu Robin <matthieu@macolu.org>
 */
class SubscriptionController extends FOSRestController implements ClassResourceInterface
{
    use AuthenticationTrait;

    /**
     * @var SubscriberRepository
     */
    private $subscriberRepository = null;

    /**
     * @var SubscriberListRepository
     */
    private $subscriberListRepository;

    /**
     * @var SubscriptionRepository
     */
    private $subscriptionRepository;

    /**
     * @param Authentication $authentication
     * @param SubscriberRepository|null $subscriberRepository
     * @param SubscriberListRepository $subscriberListRepository
     * @param SubscriptionRepository $subscriptionRepository
     */
    public function __construct(
        Authentication $authentication,
        SubscriberRepository $subscriberRepository,
        SubscriberListRepository $subscriberListRepository,
        SubscriptionRepository $subscriptionRepository
    ) {
        $this->authentication = $authentication;
        $this->subscriberRepository = $subscriberRepository;
        $this->subscriberListRepository = $subscriberListRepository;
        $this->subscriptionRepository = $subscriptionRepository;
    }

    /**
     * Creates a new subscription.
     *
     * @param Request $request
     *
     * @return View
     *
     * @throws UnprocessableEntityHttpException
     * @throws ConflictHttpException
     */
    public function postAction(Request $request): View
    {
        $this->requireAuthentication($request);

        $this->validateSubscription($request);

        $subscriber = $this->subscriberRepository->findOneById($request->get('subscriber_id'));
        if ($subscriber === null) {
            throw new UnprocessableEntityHttpException(
                'subscriber_id not found: '.$request->get('subscriber_id'),
                null,
                1598917596
            );
        }

        $subscriberList = $this->subscriberListRepository->findOneById($request->get('subscriber_list_id'));
        if ($subscriberList === null) {
            throw new UnprocessableEntityHttpException(
                'subscriber_list_id not found: '.$request->get('subscriber_list_id'),
                null,
                1598917574
            );
        }

        $subscription = new Subscription();
        $subscription->setSubscriber($subscriber);
        $subscription->setSubscriberList($subscriberList);

        try {
            $this->subscriptionRepository->save($subscription);
        } catch (UniqueConstraintViolationException $e) {
            throw new ConflictHttpException('This resource already exists.', null, 1598918448);
        }

        return View::create()->setStatusCode(Response::HTTP_CREATED)->setData($subscription);
    }

    private function validateSubscription(Request $request)
    {
        /** @var string[] $invalidFields */
        $invalidFields = [];
        if (filter_var($request->get('subscriber_id'), FILTER_VALIDATE_INT) === false) {
            $invalidFields[] = 'subscriber_id';
        }

        if (filter_var($request->get('subscriber_list_id'), FILTER_VALIDATE_INT) === false) {
            $invalidFields[] = 'subscriber_list_id';
        }

        if (!empty($invalidFields)) {
            throw new UnprocessableEntityHttpException(
                'Some fields invalid:' . implode(', ', $invalidFields),
                null,
                1598914359
            );
        }
    }
}
