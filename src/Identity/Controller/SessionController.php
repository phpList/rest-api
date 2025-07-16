<?php

declare(strict_types=1);

namespace PhpList\RestBundle\Identity\Controller;

use OpenApi\Attributes as OA;
use PhpList\Core\Domain\Identity\Model\AdministratorToken;
use PhpList\Core\Domain\Identity\Service\SessionManager;
use PhpList\Core\Security\Authentication;
use PhpList\RestBundle\Common\Controller\BaseController;
use PhpList\RestBundle\Common\Validator\RequestValidator;
use PhpList\RestBundle\Identity\Request\CreateSessionRequest;
use PhpList\RestBundle\Identity\Serializer\AdministratorTokenNormalizer;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * This controller provides methods to create and destroy REST API sessions.
 *
 * @author Oliver Klee <oliver@phplist.com>
 * @author Tatevik Grigoryan <tatevik@phplist.com>
 */
#[Route('/sessions', name: 'session_')]
class SessionController extends BaseController
{
    private SessionManager $sessionManager;

    public function __construct(
        Authentication $authentication,
        RequestValidator $validator,
        SessionManager $sessionManager,
    ) {
        parent::__construct($authentication, $validator);

        $this->sessionManager = $sessionManager;
    }

    #[Route('', name: 'create', methods: ['POST'])]
    #[OA\Post(
        path: '/api/v2/sessions',
        description: '✅ **Status: Stable** – This method is stable and safe for production use. ' .
            'Given valid login data, this will generate a login token that will be valid for 1 hour.',
        summary: 'Log in or create new session.',
        requestBody: new OA\RequestBody(
            description: 'Pass session credentials',
            required: true,
            content: new OA\JsonContent(
                required: ['login_name', 'password'],
                properties: [
                    new OA\Property(property: 'login_name', type: 'string', format: 'string', example: 'admin'),
                    new OA\Property(property: 'password', type: 'string', format: 'password', example: 'eetIc/Gropvoc1')
                ]
            )
        ),
        tags: ['sessions'],
        responses: [
            new OA\Response(
                response: 201,
                description: 'Success',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'id', type: 'integer', example: 1234),
                        new OA\Property(property: 'key', type: 'string', example: '2cfe100561473c6cdd99c9e2f26fa974'),
                        new OA\Property(property: 'expiry', type: 'string', example: '2017-07-20T18:22:48+00:00')
                    ]
                )
            ),
            new OA\Response(
                response: 400,
                description: 'Failure',
                content: new OA\JsonContent(ref: '#/components/schemas/BadRequestResponse')
            ),
            new OA\Response(
                response: 401,
                description: 'Failure',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'message', type: 'string', example: 'Not authorized.')
                    ]
                )
            )
        ]
    )]
    public function createSession(
        Request $request,
        AdministratorTokenNormalizer $normalizer
    ): JsonResponse {
        /** @var CreateSessionRequest $createSessionRequest */
        $createSessionRequest = $this->validator->validate($request, CreateSessionRequest::class);
        $token = $this->sessionManager->createSession(
            loginName:$createSessionRequest->loginName,
            password: $createSessionRequest->password
        );

        $json = $normalizer->normalize($token, 'json');

        return $this->json($json, Response::HTTP_CREATED);
    }

    /**
     * Deletes a session.
     *
     * This action may only be called for sessions that are owned by the authenticated administrator.
     *
     * @throws AccessDeniedHttpException
     */
    #[Route('/{sessionId}', name: 'delete', requirements: ['sessionId' => '\d+'], methods: ['DELETE'])]
    #[OA\Delete(
        path: '/api/v2/sessions/{sessionId}',
        description: '✅ **Status: Stable** – This method is stable and safe for production use. ' .
            'Delete the session passed as a parameter.',
        summary: 'Delete a session.',
        tags: ['sessions'],
        parameters: [
            new OA\Parameter(
                name: 'php-auth-pw',
                description: 'Session key obtained from login',
                in: 'header',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'sessionId',
                description: 'Session id (not key as for authentication) obtained from login',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success'
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
    public function deleteSession(
        Request $request,
        #[MapEntity(mapping: ['sessionId' => 'id'])] ?AdministratorToken $token = null
    ): JsonResponse {
        $administrator = $this->requireAuthentication($request);

        if (!$token) {
            throw $this->createNotFoundException('Token not found.');
        }
        if ($token->getAdministrator() !== $administrator) {
            throw new AccessDeniedHttpException('You do not have access to this session.', null, 1519831644);
        }

        $this->sessionManager->deleteSession($token);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
