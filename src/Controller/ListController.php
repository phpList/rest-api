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
     * @OA\Get(
     *     path="/api/v2/lists",
     *     tags={"lists"},
     *     summary="Gets a list of all subscriber lists.",
     *     description="Returns a json list of all subscriber lists.",
     * @OA\Parameter(
     *          name="session",
     *          in="header",
     *          description="Session ID obtained from authentication",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Response(
     *        response=201,
     *        description="Success",
     * @OA\JsonContent(
     *            type="object",
     *            example={
     *                {
     *                   "name": "News",
     *                   "description": "News (and some fun stuff)",
     *                   "creation_date": "2016-06-22T15:01:17+00:00",
     *                   "list_position": 12,
     *                   "subject_prefix": "phpList",
     *                   "public": true,
     *                   "category": "news",
     *                   "id": 1
     *               },
     *               {
     *                   "name": "More news",
     *                   "description": "",
     *                   "creation_date": "2016-06-22T15:01:17+00:00",
     *                   "list_position": 12,
     *                   "subject_prefix": "",
     *                   "public": true,
     *                   "category": "",
     *                   "id": 2
     *             }
     *         }
     *      )
     *     ),
     * @OA\Response(
     *        response=403,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No valid session key was provided as basic auth password.")
     *        )
     *     )
     * )
     *
     *
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
     * @OA\Get(
     *     path="/api/v2/lists/{list}",
     *     tags={"lists"},
     *     summary="Gets a subscriber list.",
     *     description="Returns a single subscriber lists with specified ID",
     * @OA\Parameter(
     *          name="list",
     *          in="path",
     *          description="List ID",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Parameter(
     *          name="session",
     *          in="header",
     *          description="Session ID obtained from authentication",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Response(
     *        response=200,
     *        description="Success",
     * @OA\JsonContent(
     *            type="object",
     *            example={
     *                {
     *                   "name": "News",
     *                   "description": "News (and some fun stuff)",
     *                   "creation_date": "2016-06-22T15:01:17+00:00",
     *                   "list_position": 12,
     *                   "subject_prefix": "phpList",
     *                   "public": true,
     *                   "category": "news",
     *                   "id": 1
     *               }
     *       }
     *      )
     *     ),
     * @OA\Response(
     *        response=403,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No valid session key was provided as basic auth password.")
     *        )
     *     ),
     * @OA\Response(
     *        response=404,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="There is no list with that ID.")
     *        )
     *     )
     * )
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
     *
     * @OA\Delete(
     *     path="/api/v2/lists/{list}",
     *     tags={"lists"},
     *     summary="Deletes a list.",
     *     description="Deletes a single subscriber list passed as",
     * @OA\Parameter(
     *          name="session",
     *          in="header",
     *          description="Session ID",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Response(
     *        response=200,
     *        description="Success"
     *     ),
     * @OA\Response(
     *        response=403,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(
     *               property="message",
     *               type="string",
     *               example="No valid session key was provided as basic auth password or You do not have access to this session."
     *           )
     *        )
     *     ),
     * @OA\Response(
     *        response=404,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="There is no session with that ID.")
     *        )
     *     )
     * )
     *
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
     *
     * @OA\Get(
     *     path="/api/v2/lists/{list}/members",
     *     tags={"lists"},
     *     summary="Gets a list of all subscribers (members) of a subscriber list.",
     *     description="Returns a json list of all subscriber lists.",
     * @OA\Parameter(
     *          name="session",
     *          in="header",
     *          description="Session ID obtained from authentication",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Parameter(
     *          name="list",
     *          in="path",
     *          description="List ID",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Response(
     *        response=200,
     *        description="Success",
     * @OA\JsonContent(
     *            type="object",
     *            example={
     *                {
     *                    "creation_date": "2016-07-22T15:01:17+00:00",
     *                    "email": "oliver@example.com",
     *                    "confirmed": true,
     *                    "blacklisted": true,
     *                    "bounce_count": 17,
     *                    "unique_id": "95feb7fe7e06e6c11ca8d0c48cb46e89",
     *                    "html_email": true,
     *                    "disabled": true,
     *                    "id": 1,
     *              },
     *              {
     *                    "creation_date": "2017-07-22T15:12:17+00:00",
     *                    "email": "sam@example.com",
     *                    "confirmed": true,
     *                    "blacklisted": false,
     *                    "bounce_count": 1,
     *                    "unique_id": "95feb7fe7e06e6c11ca8d0c48cb4616d",
     *                    "html_email": false,
     *                    "disabled": false,
     *                    "id": 2,
     *            }
     *         }
     *      )
     *     ),
     * @OA\Response(
     *        response=403,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No valid session key was provided as basic auth password.")
     *        )
     *     )
     * )
     *
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
     * @OA\Get(
     *     path="/api/v2/lists/{list}/count",
     *     tags={"lists"},
     *     summary="Gets the total number of subscribers of a list",
     *     description="Returns a count of all subscribers in a given list.",
     * @OA\Parameter(
     *          name="session",
     *          in="header",
     *          description="Session ID obtained from authentication",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Parameter(
     *          name="list",
     *          in="path",
     *          description="List ID",
     *          required=true,
     * @OA\Schema(
     *             type="string"
     *         )
     *      ),
     * @OA\Response(
     *        response=200,
     *        description="Success"
     *     ),
     * @OA\Response(
     *        response=403,
     *        description="Failure",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="No valid session key was provided as basic auth password.")
     *        )
     *     )
     * )
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
