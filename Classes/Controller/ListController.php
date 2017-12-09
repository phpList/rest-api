<?php
declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Routing\ClassResourceInterface;
use FOS\RestBundle\View\View;
use PhpList\PhpList4\Domain\Repository\Messaging\SubscriberListRepository;
use PhpList\PhpList4\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * This controller provides REST API access to subscriber lists.
 *
 * @author Oliver Klee <oliver@phplist.com>
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
}
