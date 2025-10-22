<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Message\SyncCampaignProcessorMessage;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Request\CreateMessageRequest;
use PhpList\RestBundle\Messaging\Request\UpdateMessageRequest;
use PhpList\RestBundle\Messaging\Service\CampaignService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API to manage campaigns.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/campaigns', name: 'campaign_')]
class CampaignController extends BaseController
{
    private CampaignService $campaignService;
    private MessageBusInterface $messageBus;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        CampaignService $campaignService,
        MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct($authentication, $validator);
        $this->campaignService = $campaignService;
        $this->messageBus = $messageBus;
    }

    #[Route('', name: 'get_list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/campaigns',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns a JSON list of all campaigns/messages.',
        summary: 'Gets a list of all campaigns.',
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(
                    type: 'string'
                ),
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
        $authUser = $this->requireAuthentication($request);

        return $this->json(
            $this->campaignService->getMessages($request, $authUser),
            Response::HTTP_OK
        );
    }

    #[Route('/{messageId}', name: 'get_one', requirements: ['messageId' => '\d+'], methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/campaigns/{messageId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns campaign/message by id.',
        summary: 'Gets a campaign by id.',
        tags: ['campaigns'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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

        return $this->json($this->campaignService->getMessage($message), Response::HTTP_OK);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/campaigns',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns created message.',
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
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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
    public function createMessage(Request $request): JsonResponse
    {
        $authUser = $this->requireAuthentication($request);

        /** @var CreateMessageRequest $createMessageRequest */
        $createMessageRequest = $this->validator->validate($request, CreateMessageRequest::class);
        $message = $this->campaignService->createMessage($createMessageRequest, $authUser);
        $this->entityManager->flush();

        return $this->json(data: $message, status: Response::HTTP_CREATED);
    }

    #[Route('/{messageId}', name: 'update', requirements: ['messageId' => '\d+'], methods: ['PUT'])]
    #[OA\Put(
        path: '/api/v2/campaigns/{messageId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Updates campaign/message by id.',
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
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
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

        /** @var UpdateMessageRequest $updateMessageRequest */
        $updateMessageRequest = $this->validator->validate($request, UpdateMessageRequest::class);
        $message = $this->campaignService->updateMessage($updateMessageRequest, $authUser, $message);
        $this->entityManager->flush();

        return $this->json(data:$message, status: Response::HTTP_OK);
    }

    #[Route('/{messageId}', name: 'delete', requirements: ['messageId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/campaigns/{messageId}',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Delete campaign/message by id.',
        summary: 'Delete campaign by id.',
        tags: ['campaigns'],
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
        $authUser = $this->requireAuthentication($request);

        $this->campaignService->deleteMessage($authUser, $message);
        $this->entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }

    #[Route('/{messageId}/send', name: 'send_campaign', requirements: ['messageId' => '\d+'], methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/campaigns/{messageId}/send',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Processes/sends campaign/message by id.',
        summary: 'Processes/sends campaign/message by id.',
        tags: ['campaigns'],
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
    public function sendMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $this->requireAuthentication($request);
        if ($message === null) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        $this->messageBus->dispatch(new SyncCampaignProcessorMessage($message->getId()));

        return $this->json($this->campaignService->getMessage($message), Response::HTTP_OK);
    }
}
