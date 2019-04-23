<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use PhpList\Core\Domain\Model\Messaging\SubscriberList;
use PhpList\Core\Domain\Repository\Messaging\SubscriberListRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\Request;

/**
 * This controller provides REST API access to subscriber lists.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Xheni Myrtaj <xheni@phplist.com>
 */
class ListController extends FOSRestController implements ClassResourceInterface
{
    use AuthenticationTrait;

    /**
     * @var SubscriberListRepository
     */
    private $subscriberListRepository = null;

    /**
     * @param Authentication $authentication
     * @param SubscriberListRepository $repository
     */
    public function __construct(Authentication $authentication, SubscriberListRepository $repository)
    {
        $this->authentication = $authentication;
        $this->subscriberListRepository = $repository;
    }

    /**
     * Gets a list of all subscriber lists.
     *
     * @param Request $request
     *
     * @return View
     */
    public function cgetAction(Request $request): View
    {
        $this->requireAuthentication($request);

        return View::create()->setData($this->subscriberListRepository->findAll());
    }

    /**
     * Gets a subscriber list.
     *
     * @param Request $request
     * @param SubscriberList $list
     *
     * @return View
     */
    public function getAction(Request $request, SubscriberList $list): View
    {
        $this->requireAuthentication($request);

        return View::create()->setData($list);
    }

    /**
     * Deletes a subscriber list.
     *
     * @param Request $request
     * @param SubscriberList $list
     *
     * @return View
     */
    public function deleteAction(Request $request, SubscriberList $list): View
    {
        $this->requireAuthentication($request);

        $this->subscriberListRepository->remove($list);

        return View::create();
    }

    /**
     * Gets a list of all subscribers (members) of a subscriber list.
     *
     * @param Request $request
     * @param SubscriberList $list
     *
     * @return View
     */
    public function getMembersAction(Request $request, SubscriberList $list): View
    {
        $this->requireAuthentication($request);

        return View::create()->setData($list->getSubscribers());
    }

    /**
     * Gets the total number of subscribers of a list.
     *
     * @param Request $request
     * @param SubscriberList $list
     *
     * @return View
     */
    public function getSubscribersCountAction(Request $request, SubscriberList $list): View
    {
        $this->requireAuthentication($request);

        return View::create()->setData(count($list->getSubscribers()));
    }
}
