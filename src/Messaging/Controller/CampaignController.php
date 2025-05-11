<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Filter\MessageFilter;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Service\MessageManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Service\Provider\PaginatedDataProvider;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Request\CreateMessageRequest;
use PhpList\RestBundle\Messaging\Request\UpdateMessageRequest;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API to manage campaigns.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/campaigns')]
class CampaignController extends BaseController
{
    private MessageNormalizer $normalizer;
    private MessageManager $messageManager;
    private PaginatedDataProvider $paginatedProvider;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        MessageNormalizer $normalizer,
        MessageManager $messageManager,
        PaginatedDataProvider $paginatedProvider,
    ) {
        parent::__construct($authentication, $validator);
        $this->normalizer = $normalizer;
        $this->messageManager = $messageManager;
        $this->paginatedProvider = $paginatedProvider;
    }

    #[Route('', name: 'get_campaigns', methods: ['GET'])]
    #[OA\Get(
        path: '/campaigns',
        description: 'Returns a JSON list of all campaigns/messages.',
        summary: 'Gets a list of all campaigns.',
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
            new OA\Parameter(
                name: 'after_id',
                description: 'Last id (starting from 0)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 1, minimum: 1)
            ),
            new OA\Parameter(
                name: 'limit',
                description: 'Number of results per page',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'integer', default: 25, maximum: 100, minimum: 1)
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
                        new OA\Property(property: 'pagination', ref: '#/components/schemas/CursorPagination')
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getMessages(Request $request): JsonResponse
    {
        $authUer = $this->requireAuthentication($request);

        $filter = (new MessageFilter())->setOwner($authUer);

        return $this->json(
            $this->paginatedProvider->getPaginatedList($request, $this->normalizer, Message::class, $filter),
            Response::HTTP_OK
        );
    }

    #[Route('/{messageId}', name: 'get_campaign', methods: ['GET'])]
    #[OA\Get(
        path: '/campaigns/{messageId}',
        description: 'Returns campaign/message by id.',
        summary: 'Gets a campaign by id.',
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Message')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            )
        ]
    )]
    public function getMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$message) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        return $this->json($this->normalizer->normalize($message), Response::HTTP_OK);
    }

    #[Route('', name: 'create_message', methods: ['POST'])]
    #[OA\Post(
        path: '/campaigns',
        description: 'Returns created message.',
        summary: 'Create a message for campaign.',
        requestBody: new OA\RequestBody(
            description: 'Create a new message.',
            required: true,
            content: new OA\JsonContent(
                required: ['content', 'format', 'metadata', 'schedule', 'options'],
                properties: [
                    new OA\Property(property: 'template_id', type: 'integer', example: 1),
                    new OA\Property(property: 'content', ref: '#/components/schemas/MessageContentRequest'),
                    new OA\Property(property: 'format', ref: '#/components/schemas/MessageFormatRequest'),
                    new OA\Property(property: 'metadata', ref: '#/components/schemas/MessageMetadataRequest'),
                    new OA\Property(property: 'schedule', ref: '#/components/schemas/MessageScheduleRequest'),
                    new OA\Property(property: 'options', ref: '#/components/schemas/MessageOptionsRequest'),
                ],
                type: 'object'
            )
        ),
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            )
        ],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Message')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function createMessage(Request $request, MessageNormalizer $normalizer): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);

        /** @var CreateMessageRequest $createMessageRequest */
        $createMessageRequest = $this->validator->validate($request, CreateMessageRequest::class);
        $data = $this->messageManager->createMessage($createMessageRequest->getDto(), $authUser);

        return $this->json($normalizer->normalize($data), Response::HTTP_CREATED);
    }

    #[Route('/{messageId}', name: 'update_campaign', methods: ['PUT'])]
    #[OA\Put(
        path: '/campaigns/{messageId}',
        description: 'Updates campaign/message by id.',
        summary: 'Update campaign by id.',
        requestBody: new OA\RequestBody(
            description: 'Update message.',
            required: true,
            content: new OA\JsonContent(
                required: ['content', 'format', 'schedule', 'options'],
                properties: [
                    new OA\Property(property: 'template_id', type: 'integer', example: 1),
                    new OA\Property(property: 'content', ref: '#/components/schemas/MessageContentRequest'),
                    new OA\Property(property: 'format', ref: '#/components/schemas/MessageFormatRequest'),
                    new OA\Property(property: 'schedule', ref: '#/components/schemas/MessageScheduleRequest'),
                    new OA\Property(property: 'options', ref: '#/components/schemas/MessageOptionsRequest'),
                ],
                type: 'object'
            )
        ),
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Message')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 422,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorResponse')
            ),
        ]
    )]
    public function updateMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null,
    ): JsonResponse {
        $authUser = $this->requireAuthentication($request);

        if (!$message) {
            throw $this->createNotFoundException('Campaign not found.');
        }
        /** @var UpdateMessageRequest $updateMessageRequest */
        $updateMessageRequest = $this->validator->validate($request, UpdateMessageRequest::class);
        $data = $this->messageManager->updateMessage($updateMessageRequest->getDto(), $message, $authUser);

        return $this->json($this->normalizer->normalize($data), Response::HTTP_OK);
    }

    #[Route('/{messageId}', name: 'delete_campaign', methods: ['DELETE'])]
    #[OA\Delete(
        path: '/campaigns/{messageId}',
        description: 'Delete campaign/message by id.',
        summary: 'Delete campaign by id.',
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'session',
                description: 'Session ID obtained from authentication',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                )
            ),
            new OA\Parameter(
                name: 'messageId',
                description: 'message ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Message')
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            )
        ]
    )]
    public function deleteMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $this->requireAuthentication($request);

        if (!$message) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        $this->messageManager->delete($message);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
