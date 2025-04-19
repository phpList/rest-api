<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Model\Messaging\Message;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Entity\Request\CreateMessageRequest;
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
                properties: [
                    new OA\Property(property: 'template_id', type: 'integer', example: 1),
                    new OA\Property(
                        property: 'message_content',
                        properties: [
                            new OA\Property(property: 'subject', type: 'string', example: 'Campaign Subject'),
                            new OA\Property(property: 'text', type: 'string', example: 'Full text content'),
                            new OA\Property(property: 'text_message', type: 'string', example: 'Short text message'),
                            new OA\Property(property: 'footer', type: 'string', example: 'Unsubscribe link here'),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'message_format',
                        properties: [
                            new OA\Property(property: 'html_formated', type: 'boolean', example: true),
                            new OA\Property(
                                property: 'send_format',
                                type: 'string',
                                enum: ['html', 'text', 'invite'],
                                example: 'html'
                            ),
                            new OA\Property(
                                property: 'format_options',
                                type: 'array',
                                items: new OA\Items(type: 'string', enum: ['text', 'html', 'pdf']),
                                example: ['html']
                            ),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'message_metadata',
                        properties: [
                            new OA\Property(property: 'status', type: 'string', example: 'draft'),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'message_schedule',
                        properties: [
                            new OA\Property(
                                property: 'embargo',
                                type: 'string',
                                format: 'date-time',
                                example: '2025-04-17 09:00:00'
                            ),
                            new OA\Property(property: 'repeat_interval', type: 'string', example: '24 hours'),
                            new OA\Property(
                                property: 'repeat_until',
                                type: 'string',
                                format: 'date-time',
                                example: '2025-04-30T00:00:00+04:00'
                            ),
                            new OA\Property(property: 'requeue_interval', type: 'string', example: '12 hours'),
                            new OA\Property(
                                property: 'requeue_until',
                                type: 'string',
                                format: 'date-time',
                                example: '2025-04-20T00:00:00+04:00'
                            ),
                        ],
                        type: 'object'
                    ),
                    new OA\Property(
                        property: 'message_options',
                        properties: [
                            new OA\Property(property: 'from_field', type: 'string', example: 'info@example.com'),
                            new OA\Property(property: 'to_field', type: 'string', example: 'subscriber@example.com'),
                            new OA\Property(property: 'reply_to', type: 'string', example: 'reply@example.com'),
                            new OA\Property(property: 'user_selection', type: 'string', example: 'all-active-users'),
                        ],
                        type: 'object'
                    ),
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
}
