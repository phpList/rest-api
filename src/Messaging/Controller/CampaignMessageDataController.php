<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Service\Authentication;
use PhpList\Core\Domain\Identity\Service\PermissionChecker;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Service\Manager\MessageDataManager;
use PhpList\Core\Domain\Messaging\Service\MessageDataLoader;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Dto\CampaignMessageData;
use PhpList\RestBundle\Messaging\Request\UpdateMessageDataRequest;
use PhpList\RestBundle\Messaging\Serializer\MessageDataNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API to set the campaign settings that are kept in the message data.
 */
#[Route('/campaigns', name: 'campaign_message_data_')]
class CampaignMessageDataController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly MessageDataManager $messageDataManager,
        private readonly MessageDataLoader $messageDataLoader,
        private readonly PermissionChecker $permissionChecker,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageDataNormalizer $messageDataNormalizer,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route(
        '/{messageId}/message-data',
        name: 'update',
        requirements: ['messageId' => '\d+'],
        methods: ['PATCH']
    )]
    #[OA\Patch(
        path: '/api/v2/campaigns/{messageId}/message-data',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Sets campaign settings that are not part of the campaign itself: analytics tracking, notification ' .
        'addresses and excluded lists. Only the fields that are sent are changed.',
        summary: 'Set campaign message data.',
        requestBody: new OA\RequestBody(
            description: 'The settings to change.',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/UpdateMessageDataRequest')
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
                description: 'Success, returns all the settings of the campaign',
                content: new OA\JsonContent(ref: '#/components/schemas/MessageData')
            ),
            new OA\Response(
                response: 401,
                description: 'Not authenticated',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
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
    public function updateMessageData(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null,
    ): JsonResponse {
        $authUser = $this->requireAuthentication($request);
        if ($message === null) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        if (!$this->permissionChecker->canEdit($authUser, $message)) {
            throw $this->createAccessDeniedException('You are not allowed to update campaigns.');
        }

        /** @var UpdateMessageDataRequest $messageDataRequest */
        $messageDataRequest = $this->validator->validate($request, UpdateMessageDataRequest::class);

        $this->messageDataManager->storeMessageData($message, $messageDataRequest->getDto());
        $this->entityManager->flush();

        $messageData = new CampaignMessageData(($this->messageDataLoader)($message));

        return $this->json($this->messageDataNormalizer->normalize($messageData), Response::HTTP_OK);
    }
}
