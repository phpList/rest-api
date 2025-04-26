<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;
use PhpList\RestBundle\Entity\Request\UpdateMessageRequest;
use PhpList\RestBundle\Serializer\MessageNormalizer;
use PhpList\RestBundle\Service\Manager\MessageManager;
use PhpList\RestBundle\Service\Provider\MessageProvider;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * This controller provides REST API to manage campaigns.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/campaigns')]
class CampaignController extends AbstractController
{
    use AuthenticationTrait;

    private MessageProvider $messageProvider;
    private RequestValidator $validator;
    private MessageNormalizer $normalizer;
    private MessageManager $messageManager;

    public function __construct(
        Authentication $authentication,
        MessageProvider $messageProvider,
        RequestValidator $validator,
        MessageNormalizer $normalizer,
        MessageManager $messageManager
    ) {
        $this->authentication = $authentication;
        $this->messageProvider = $messageProvider;
        $this->validator = $validator;
        $this->normalizer = $normalizer;
        $this->messageManager = $messageManager;
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
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Message')
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
        $data = $this->messageProvider->getMessagesByOwner($authUer);

        $normalized = array_map(function ($item) {
            return $this->normalizer->normalize($item);
        }, $data);

        return new JsonResponse($normalized, Response::HTTP_OK);
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
        #[MapEntity(mapping: ['messageId' => 'id'])] Message $message
    ): JsonResponse {
        $this->requireAuthentication($request);

        return new JsonResponse($this->normalizer->normalize($message), Response::HTTP_OK);
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
        $data = $this->messageManager->createMessage($createMessageRequest, $authUser);

        return new JsonResponse($normalizer->normalize($data), Response::HTTP_CREATED);
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
        #[MapEntity(mapping: ['messageId' => 'id'])] Message $message,
        SerializerInterface $serializer,
    ): JsonResponse {
        $authUser = $this->requireAuthentication($request);

        /** @return UpdateMessageRequest $updateMessageRequest */
        $updateMessageRequest = $serializer->deserialize($request->getContent(), UpdateMessageRequest::class, 'json');
        $updateMessageRequest->messageId = $message->getId();
        $this->validator->validateDto($updateMessageRequest);

        return new JsonResponse(
            $this->normalizer->normalize(
                $this->messageManager->updateMessage($updateMessageRequest, $message, $authUser)
            ),
            Response::HTTP_OK
        );
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
        #[MapEntity(mapping: ['messageId' => 'id'])] Message $message
    ): JsonResponse {
        $this->requireAuthentication($request);

        $this->messageManager->delete($message);

        return new JsonResponse(null, Response::HTTP_NO_CONTENT);
    }
}
