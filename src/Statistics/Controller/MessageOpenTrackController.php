<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Statistics\Controller;

use OpenApi\Attributes as OA;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\Core\Domain\Analytics\Service\UserMessageService;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/t', name: 'tracks_')]
class MessageOpenTrackController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly UserMessageService $userMessageService,
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
            new OA\Response(response: 400, description: 'Bad request'),
            new OA\Response(response: 500, description: 'Server error'),
        ]
    )]
    public function trackUserMessageView(
        Request $request,
        #[MapQueryParameter(name: 'u')] string $uid,
        #[MapQueryParameter(name: 'm')] int $messageId,
    ): Response {
        $metadata = [
            'HTTP_USER_AGENT' => $request->server->get('HTTP_USER_AGENT'),
            'HTTP_REFERER' => $request->server->get('HTTP_REFERER'),
            'client_ip' => $request->getClientIp(),
        ];

        $this->userMessageService->trackUserMessageView($uid, $messageId, $metadata);

        $gifData = base64_decode('R0lGODlhAQABAPAAAAAAAAAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');

        return new Response(
            content: $gifData,
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
