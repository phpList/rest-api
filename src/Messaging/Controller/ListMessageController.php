<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Service\Manager\ListMessageManager;
use PhpList\Core\Domain\Subscription\Model\SubscriberList;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Serializer\ListMessageNormalizer;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use PhpList\RestBundle\Subscription\Serializer\SubscriberListNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API to manage relations between subscriber lists and campaigns.
 */
#[Route('/list-messages', name: 'list_message_')]
class ListMessageController extends BaseController
{
    private ListMessageManager $listMessageManager;
    private ListMessageNormalizer $listMessageNormalizer;
    private SubscriberListNormalizer $subscriberListNormalizer;
    private MessageNormalizer $messageNormalizer;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        ListMessageManager $listMessageManager,
        ListMessageNormalizer $listMessageNormalizer,
        SubscriberListNormalizer $subscriberListNormalizer,
        MessageNormalizer $messageNormalizer
    ) {
        parent::__construct($authentication, $validator);
        $this->listMessageManager = $listMessageManager;
        $this->listMessageNormalizer = $listMessageNormalizer;
        $this->subscriberListNormalizer = $subscriberListNormalizer;
        $this->messageNormalizer = $messageNormalizer;
    }

    #[Route(
        '/message/{messageId}/lists',
        name: 'get_lists_by_message',
        requirements: ['messageId' => '\d+'],
        methods: ['GET']
    )]
    #[OA\Get(
        path: '/api/v2/list-messages/message/{messageId}/lists',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Returns a list of subscriber lists associated with a message.',
        tags: ['list-messages'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'Message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/SubscriberList')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Message not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function getListsByMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if ($message === null) {
            throw $this->createNotFoundException('Message not found.');
        }

        $subscriberLists = array_map(function (SubscriberList $list) {
            return $this->subscriberListNormalizer->normalize($list);
        }, $this->listMessageManager->getListByMessage($message));

        return $this->json(
            data: ['items' => $subscriberLists],
            status: Response::HTTP_OK
        );
    }

    #[Route('/list/{listId}/messages', name: 'get_messages_by_list', requirements: ['listId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/list-messages/list/{listId}/messages',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Returns a list of message IDs associated with a subscriber list.',
        tags: ['list-messages'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'listId',
                description: 'Subscriber List ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(
                            property: 'items',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Message')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Subscriber list not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function getMessagesByList(
        Request $request,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $subscriberList = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if ($subscriberList === null) {
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        $messages =  array_map(function (Message $message) {
            return $this->messageNormalizer->normalize($message);
        }, $this->listMessageManager->getMessagesByList($subscriberList));

        return $this->json(
            data:['items' => $messages],
            status: Response::HTTP_OK
        );
    }

    #[Route(
        '/message/{messageId}/list/{listId}',
        name: 'associate',
        requirements: ['messageId' => '\d+', 'listId' => '\d+'],
        methods: ['POST']
    )]
    #[OA\Post(
        path: '/api/v2/list-messages/message/{messageId}/list/{listId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Associates a message with a subscriber list.',
        tags: ['list-messages'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'Message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'listId',
                description: 'Subscriber List ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/ListMessage')
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Message or subscriber list not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function associateMessageWithList(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $subscriberList = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if ($message === null) {
            throw $this->createNotFoundException('Message not found.');
        }

        if ($subscriberList === null) {
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        $listMessage = $this->listMessageManager->associateMessageWithList($message, $subscriberList);

        return $this->json(
            data: $this->listMessageNormalizer->normalize($listMessage),
            status: Response::HTTP_CREATED
        );
    }

    #[Route(
        '/message/{messageId}/list/{listId}',
        name: 'disassociate',
        requirements: ['messageId' => '\d+', 'listId' => '\d+'],
        methods: ['DELETE']
    )]
    #[OA\Delete(
        path: '/api/v2/list-messages/message/{messageId}/list/{listId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Disassociates a message from a subscriber list.',
        tags: ['list-messages'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'Message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'listId',
                description: 'Subscriber List ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Success, no content'
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Message or subscriber list not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function disassociateMessageFromList(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $subscriberList = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if ($message === null) {
            throw $this->createNotFoundException('Message not found.');
        }

        if ($subscriberList === null) {
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        $this->listMessageManager->removeAssociation($message, $subscriberList);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(
        '/message/{messageId}/lists',
        name: 'remove_all_lists',
        requirements: ['messageId' => '\d+'],
        methods: ['DELETE']
    )]
    #[OA\Delete(
        path: '/api/v2/list-messages/message/{messageId}/lists',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Removes all list associations for a message.',
        tags: ['list-messages'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'Message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 204,
                description: 'Success, no content'
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Message not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function removeAllListAssociationsForMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if ($message === null) {
            throw $this->createNotFoundException('Message not found.');
        }

        $this->listMessageManager->removeAllListAssociationsForMessage($message);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route(
        '/message/{messageId}/list/{listId}/check',
        name: 'check_association',
        requirements: ['messageId' => '\d+', 'listId' => '\d+'],
        methods: ['GET']
    )]
    #[OA\Get(
        path: '/api/v2/list-messages/message/{messageId}/list/{listId}/check',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production.',
        summary: 'Checks if a message is associated with a subscriber list.',
        tags: ['list-messages'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'Message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
            new OA\Parameter(
                name: 'listId',
                description: 'Subscriber List ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'is_associated', type: 'boolean')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Unauthorized',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Message or subscriber list not found',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function checkAssociation(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null,
        #[MapEntity(mapping: ['listId' => 'id'])] ?SubscriberList $subscriberList = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if ($message === null) {
            throw $this->createNotFoundException('Message not found.');
        }

        if ($subscriberList === null) {
            throw $this->createNotFoundException('Subscriber list not found.');
        }

        $isAssociated = $this->listMessageManager->isMessageAssociatedWithList($message, $subscriberList);

        return $this->json(['is_associated' => $isAssociated], Response::HTTP_OK);
    }
}
