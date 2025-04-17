<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Controller\Traits\AuthenticationTrait;
use PhpList\RestBundle\Serializer\MessageNormalizer;
use PhpList\RestBundle\Service\Provider\MessageProvider;
use PhpList\RestBundle\Validator\RequestValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides REST API manage campaigns.
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

    public function __construct(
        Authentication $authentication,
        MessageProvider $messageProvider,
        RequestValidator $validator,
        MessageNormalizer $normalizer
    ) {
        $this->authentication = $authentication;
        $this->messageProvider = $messageProvider;
        $this->validator = $validator;
        $this->normalizer = $normalizer;
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
}
