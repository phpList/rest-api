<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Message\CampaignProcessor\SyncCampaignProcessorMessage;
use PhpList\Core\Domain\Messaging\Message\CampaignProcessor\TestCampaignProcessorMessage;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Model\Message\MessageStatus;
use PhpList\Core\Domain\Messaging\Service\Manager\MessageManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Request\Message\MessageMetadataRequest;
use PhpList\RestBundle\Messaging\Request\ResendMessageToListsRequest;
use PhpList\RestBundle\Messaging\Request\TestSendMessageToSubscribersRequest;
use PhpList\RestBundle\Messaging\Serializer\MessageNormalizer;
use PhpList\RestBundle\Messaging\Service\CampaignService;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API to manage campaign actions.
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
#[Route('/campaigns', name: 'campaign_')]
class CampaignActionController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly CampaignService $campaignService,
        private readonly MessageBusInterface $messageBus,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageManager $messageManager,
        private readonly MessageNormalizer $messageNormalizer
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('/{messageId}/copy', name: 'copy_campaign', requirements: ['messageId' => '\d+'], methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/campaigns/{messageId}/copy',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Copies campaign/message by id into a draft message.',
        summary: 'Copies campaign/message by id.',
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
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
        ]
    )]
    public function copyMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $authUser = $this->requireAuthentication($request);
        if ($message === null) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        $message = $this->messageManager->copyAsDraftMessage($message, $authUser);
        $this->entityManager->flush();

        return $this->json($this->campaignService->getMessage($message), Response::HTTP_CREATED);
    }

    #[Route('/{messageId}/status', name: 'update_status', requirements: ['messageId' => '\d+'], methods: ['PATCH'])]
    #[OA\Patch(
        path: '/api/v2/campaigns/{messageId}/status',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Updates campaign/message status by id.',
        summary: 'Update campaign status by id.',
        requestBody: new OA\RequestBody(
            description: 'Update message status.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/MessageMetadataRequest')
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
    public function updateMessageStatus(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null,
    ): JsonResponse {
        $this->requireAuthentication($request);
        if ($message === null) {
            throw $this->createNotFoundException('Message not found.');
        }

        /** @var MessageMetadataRequest $messageMetadataRequest */
        $messageMetadataRequest = $this->validator->validate($request, MessageMetadataRequest::class);

        $message = $this->messageManager->updateStatus(
            $message,
            MessageStatus::from($messageMetadataRequest->status),
        );
        $this->entityManager->flush();

        return $this->json($this->messageNormalizer->normalize($message), Response::HTTP_OK);
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
            ),
            new OA\Response(
                response: 404,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorResponse')
            ),
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

    #[Route('/{messageId}/resend', name: 'resend_campaign', requirements: ['messageId' => '\d+'], methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/campaigns/{messageId}/resend',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Processes/sends campaign/message by id to specified mailing lists.',
        summary: 'Processes/sends campaign/message by id to lists.',
        requestBody: new OA\RequestBody(
            description: 'List ids to send this campaign to.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ResendMessageToListsRequest')
        ),
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
            ),
        ]
    )]
    public function resendMessageToLists(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $this->requireAuthentication($request);
        if ($message === null) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        /** @var ResendMessageToListsRequest $resendToListsRequest */
        $resendToListsRequest = $this->validator->validate($request, ResendMessageToListsRequest::class);

        $this->messageBus->dispatch(
            new SyncCampaignProcessorMessage($message->getId(), $resendToListsRequest->listIds)
        );

        return $this->json($this->campaignService->getMessage($message), Response::HTTP_OK);
    }

    #[Route(
        '/{messageId}/test-send',
        name: 'test_send_campaign',
        requirements: ['messageId' => '\d+'],
        methods: ['POST']
    )]
    #[OA\Post(
        path: '/api/v2/campaigns/{messageId}/test-send',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Processes/sends campaign/message by id to specified subscribers.',
        summary: 'Processes/sends campaign/message by id to specified subscribers.',
        requestBody: new OA\RequestBody(
            description: 'Subscribers email to send this campaign to.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ResendMessageToListsRequest')
        ),
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
            ),
        ]
    )]
    public function testSendMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        $this->requireAuthentication($request);
        if ($message === null) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        /** @var TestSendMessageToSubscribersRequest $testSendRequest */
        $testSendRequest = $this->validator->validate($request, TestSendMessageToSubscribersRequest::class);

        $this->messageBus->dispatch(new TestCampaignProcessorMessage(
            messageId: $message->getId(),
            subscriberEmails: $testSendRequest->emails
        ));

        return $this->json($this->campaignService->getMessage($message), Response::HTTP_OK);
    }
}
