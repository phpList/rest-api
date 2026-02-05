<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Messaging\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Messaging\Model\Dto\MessageForwardDto;
use PhpList\Core\Domain\Messaging\Model\Message;
use PhpList\Core\Domain\Messaging\Service\MessageForwardService;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Messaging\Request\ForwardMessageRequest;
use PhpList\RestBundle\Messaging\Serializer\ForwardingResultNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API for email forwarding
 *
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/email-forward', name: 'email_forward_')]
class EmailForwardController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageForwardService $messageForwardService,
        private readonly ForwardingResultNormalizer $forwardingResultNormalizer,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('/{messageId}', name: 'forward', requirements: ['messageId' => '\\d+'], methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/campaigns/{messageId}/forward',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
        'Queues forwarding of a campaign/message to provided recipient emails.',
        summary: 'Forward a message to recipients.',
        requestBody: new OA\RequestBody(
            description: 'Forwarding payload',
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/ForwardMessageRequest')
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
                response: 202,
                description: 'Accepted',
                content: new OA\JsonContent(ref: '#/components/schemas/ForwardResult')
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
            )
        ]
    )]
    public function forwardMessage(
        Request $request,
        #[MapEntity(mapping: ['messageId' => 'id'])] ?Message $message = null
    ): JsonResponse {
        if ($message === null) {
            throw $this->createNotFoundException('Campaign not found.');
        }

        /** @var ForwardMessageRequest $forwardRequest */
        $forwardRequest = $this->validator->validate($request, ForwardMessageRequest::class);

        $result = $this->messageForwardService->forward(
            messageForwardDto: new MessageForwardDto(
                emails: $forwardRequest->recipients,
                uid: $forwardRequest->uid,
                fromName: $forwardRequest->fromName,
                fromEmail: $forwardRequest->fromEmail,
                note: $forwardRequest->note,
            ),
            campaign: $message,
        );

        $this->entityManager->flush();

        return $this->json(
            $this->forwardingResultNormalizer->normalize($result),
            Response::HTTP_ACCEPTED
        );
    }
}
