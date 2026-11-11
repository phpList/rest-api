<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\Dto\AdminNotification;
use PhpList\Core\Domain\Identity\Service\Authentication;
use PhpList\Core\Domain\Identity\Service\Notification\AdminNotificationCollector;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Identity\Serializer\AdminNotificationNormalizer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Returns the in-app notifications to administrators after login.
 */
#[Route('/notifications', name: 'admin_notification_')]
class AdminNotificationController extends BaseController
{
    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        private readonly AdminNotificationCollector $collector,
        private readonly AdminNotificationNormalizer $normalizer,
    ) {
        parent::__construct($authentication, $validator);
    }

    #[Route('', name: 'list', methods: ['GET'])]
    #[OA\Get(
        path: '/api/v2/notifications',
        description: '🚧 **Status: Beta** – This method is under development. Avoid using in production. ' .
            'Returns the in-app notifications for the authenticated administrator.',
        summary: 'Gets admin notifications.',
        tags: ['notifications'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
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
                            items: new OA\Items(ref: '#/components/schemas/AdminNotification')
                        ),
                    ],
                    type: 'object'
                )
            ),
            new OA\Response(
                response: 403,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/UnauthorizedResponse')
            ),
        ]
    )]
    public function list(Request $request): JsonResponse
    {
        $administrator = $this->requireAuthentication($request);

        $items = array_map(
            fn (AdminNotification $notification): array => $this->normalizer->normalize($notification),
            $this->collector->collect($administrator)
        );

        return $this->json(['items' => $items], Response::HTTP_OK);
    }
}
