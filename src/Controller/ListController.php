<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use PhpList\Core\Domain\Model\Messaging\SubscriberList;
use PhpList\Core\Domain\Repository\Subscription\SubscriberRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use PhpList\Core\Domain\Repository\Messaging\SubscriberListRepository;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This controller provides REST API access to subscriber lists.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Xheni Myrtaj <xheni@phplist.com>
 */
class ListController extends AbstractController
{
    use AuthenticationTrait;

    private SubscriberListRepository $subscriberListRepository;
    private SubscriberRepository $subscriberRepository;
    private SerializerInterface $serializer;

    /**
     * @param Authentication $authentication
     * @param SubscriberListRepository $repository
     * @param SubscriberRepository $subscriberRepository
     * @param SerializerInterface $serializer
     */
    public function __construct(
        Authentication $authentication,
        SubscriberListRepository $repository,
        SubscriberRepository $subscriberRepository,
        SerializerInterface $serializer
    ) {
        $this->authentication = $authentication;
        $this->subscriberListRepository = $repository;
        $this->subscriberRepository = $subscriberRepository;
        $this->serializer = $serializer;
    }

    #[Route('/lists', name: 'get_lists', methods: ['GET'])]
    public function getLists(Request $request): JsonResponse
    {
        $this->requireAuthentication($request);
        $data = $this->subscriberListRepository->findAll();
        $json = $this->serializer->serialize($data, 'json', [
            AbstractNormalizer::GROUPS => 'SubscriberList',
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/lists/{id}', name: 'get_list', methods: ['GET'])]
    public function getList(Request $request, SubscriberList $list): JsonResponse
    {
        $this->requireAuthentication($request);
        $json = $this->serializer->serialize($list, 'json', [
            AbstractNormalizer::GROUPS => 'SubscriberList',
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/lists/{id}', name: 'delete_list', methods: ['DELETE'])]
    public function deleteList(Request $request, SubscriberList $list): JsonResponse
    {
        $this->requireAuthentication($request);

        $this->subscriberListRepository->remove($list);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT, [], false);
    }

    #[Route('/lists/{id}/members', name: 'get_subscriber_from_list', methods: ['GET'])]
    public function getListMembers(Request $request, SubscriberList $list): JsonResponse
    {
        $this->requireAuthentication($request);

        $subscribers = $this->subscriberRepository->getSubscribersBySubscribedListId($list->getId());

        $json = $this->serializer->serialize($subscribers, 'json', [
            AbstractNormalizer::GROUPS => 'SubscriberListMembers',
        ]);

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }

    #[Route('/lists/{id}/subscribers/count', name: 'get_subscribers_count_from_list', methods: ['GET'])]
    public function getSubscribersCount(Request $request, SubscriberList $list): JsonResponse
    {
        $this->requireAuthentication($request);
        $json = $this->serializer->serialize(count($list->getSubscribers()), 'json');

        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
