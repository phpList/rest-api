<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\Core\Domain\Analytics\Service\UserMessageService;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Throwable;

#[Route('/t', name: 'tracks_')]
class MessageOpenTrackController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly UserMessageService $userMessageService,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('/open.gif', name: 'user_message_open', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/t/open.gif',
        description: '1x1 tracking pixel endpoint that records a message view.'
        . ' Requires `u` (subscriber UID) and `m` (message ID) as query parameters.',
        summary: 'Track user message open',
        tags: ['tracking'],
        parameters: [
            new OA\Parameter(
                name: 'u',
                description: 'Subscriber unique identifier (UID)',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'm',
                description: 'Message ID',
                in: 'query',
                required: true,
                schema: new OA\Schema(type: 'integer')
            ),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Transparent 1x1 GIF'),
        ]
    )]
    public function trackUserMessageView(
        Request $request,
        #[MapQueryParameter(name: 'u')] ?string $uid = null,
        #[MapQueryParameter(name: 'm')] ?int $messageId = null,
    ): Response {
        if (!$uid || !$messageId) {
            return $this->returnPixelResponse();
        }

        $metadata = [
            'HTTP_USER_AGENT' => $request->server->get('HTTP_USER_AGENT'),
            'HTTP_REFERER' => $request->server->get('HTTP_REFERER'),
            'client_ip' => $request->getClientIp(),
        ];

        try {
            $this->userMessageService->trackUserMessageView($uid, $messageId, $metadata);
            $this->entityManager->flush();
        } catch (Throwable $e) {
            $this->logger->error(
                'Failed to track user message view',
                [
                    'exception' => $e,
                    'message_id' => $messageId,
                    'subscriber_uid' => $uid,
                    'metadata' => $metadata
                ]
            );
        }

        return $this->returnPixelResponse();
    }

    private function returnPixelResponse(): Response
    {
        return new Response(
            content: base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=='),
            status: 200,
            headers: [
                'Content-Type' => 'image/gif',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]
        );
    }
}
