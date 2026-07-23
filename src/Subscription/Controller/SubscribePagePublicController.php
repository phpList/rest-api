<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Subscription\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Subscription\Model\SubscribePageData;
use PhpList\Core\Domain\Subscription\Repository\SubscriberListRepository;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscribePageManager;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriberAttributeManager;
use PhpList\Core\Domain\Subscription\Service\Manager\SubscriptionManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Subscription\Request\PublicSubscriptionRequest;
use PhpList\RestBundle\Subscription\Request\PublicUnsubscriptionRequest;
use PhpList\RestBundle\Subscription\Serializer\SubscribePagePublicNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/public/subscribe-pages', name: 'subscribe_pages_')]
class SubscribePagePublicController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly SubscribePageManager $subscribePageManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly SubscriptionManager $subscriptionManager,
        private readonly SubscriberAttributeManager $subscriberAttributeManager,
        private readonly SubscriberListRepository $subscriberListRepository,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('/{pageId}', name: 'get_public', requirements: ['pageId' => '\\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/public/subscribe-pages/{pageId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Get public subscribe page (placeholders replaced with actual values)',
        tags: ['subscribe-pages'],
        parameters: [
            new OA\Parameter(
                name: 'pageId',
                description: 'Subscribe page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/SubscribePagePublic'),
            ),
            new OA\Response(
                response: 404,
                description: 'Not Found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function getPublicPage(Request $request, SubscribePagePublicNormalizer $normalizer): JsonResponse
    {
        $page = $this->subscribePageManager->findPublicPage(id: (int) $request->get('pageId'));

        if (!$page || $page->isActive() === false) {
            throw $this->createNotFoundException('Subscribe page not found');
        }

        return $this->json($normalizer->normalize($page), Response::HTTP_OK);
    }

    #[Route('/{pageId}', name: 'subscribe', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/public/subscribe-pages/{pageId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.' .
        'Subscribe subscriber to a list from subscribe page.',
        summary: 'Create subscription',
        requestBody: new OA\RequestBody(
            description: '',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PublicSubscriptionRequest')
        ),
        tags: ['subscribe-pages'],
        parameters: [
            new OA\Parameter(
                name: 'pageId',
                description: 'Subscribe page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Success'
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function subscribe(Request $request, int $pageId): JsonResponse
    {
        $page = $this->subscribePageManager->findPublicPage(id: $pageId);
        if (!$page) {
            throw $this->createNotFoundException('Subscribe page not found.');
        }

        /** @var PublicSubscriptionRequest $subscriptionRequest */
        $subscriptionRequest = $this->validator->validate(
            request: $request,
            dtoClass: PublicSubscriptionRequest::class,
            beforeValidation: static function (PublicSubscriptionRequest $dto) use ($page): void {
                $dto->setSubscribePage($page);
            }
        );

        $list = $this->subscriberListRepository->findById($subscriptionRequest->listId);
        if ($list === null) {
            throw $this->createNotFoundException('Subscriber list does not exists.');
        }
        $subscriptions = $this->subscriptionManager->createSubscriptions(
            subscriberList: $list,
            emails: [$subscriptionRequest->email],
            autoConfirm: false,
        );
        $this->entityManager->flush();

        if ($subscriptionRequest->attributes !== []) {
            $this->subscriberAttributeManager->processAttributes(
                subscriber: $subscriptions[0]->getSubscriber(),
                attributeData: $subscriptionRequest->attributes
            );
        }
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{pageId}', name: 'unsubscribe', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/public/subscribe-pages/{pageId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.' .
        'Unsubscribe subscriber from lists of subscribe page.',
        summary: 'Delete subscription',
        requestBody: new OA\RequestBody(
            description: '',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/PublicUnsubscriptionRequest')
        ),
        tags: ['subscribe-pages'],
        parameters: [
            new OA\Parameter(
                name: 'pageId',
                description: 'Subscribe page ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Success'
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function unsubscribe(Request $request, int $pageId): JsonResponse
    {
        $page = $this->subscribePageManager->findPage(id: $pageId);
        if (!$page) {
            throw $this->createNotFoundException('Subscribe page not found.');
        }

        /** @var SubscribePageData|null $listsField */
        $listsField = array_find(
            $page->getData(),
            fn (SubscribePageData $data) => $data->getName() === 'lists'
        );

        if ($listsField === null) {
            return $this->json(null, Response::HTTP_NO_CONTENT);
        }

        $listsIds = explode(',', $listsField->getData() ?? '');
        if ($listsIds == []) {
            return $this->json(null, Response::HTTP_NO_CONTENT);
        }

        /** @var PublicUnsubscriptionRequest $unsubscribeRequest */
        $unsubscribeRequest = $this->validator->validate(
            request: $request,
            dtoClass: PublicUnsubscriptionRequest::class
        );

        $lists = $this->subscriberListRepository->findBy(['id' => $listsIds]);
        foreach ($lists as $list) {
            $this->subscriptionManager->deleteSubscriptions(
                subscriberList: $list,
                emails: [$unsubscribeRequest->email]
            );
        }
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
